<?php

namespace App\Services\ImportOrders;

use App\Enums\ImportOrderState;
use App\Enums\OrderEventType;
use App\Enums\PaymentKind;
use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use App\Events\OrderPaymentRecorded;
use App\Models\ImportOrder;
use App\Models\OrderApproval;
use App\Models\OrderPayment;
use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * Pagos del cliente. Se registran a mano (transferencia, efectivo o tarjeta
 * mediante un enlace de pago de Stripe pegado en el pago).
 *
 * Al marcar pagada la señal con el encargo en «Pendiente de señal», el
 * encargo pasa solo a «Inspección».
 */
class OrderPaymentService
{
    public function __construct(
        private ImportOrderService $orders,
        private OrderTimeline $timeline,
    ) {}

    /** Crea señal (50 %) y resto (50 %) de honorarios tras la firma. Idempotente. */
    public function scheduleFees(ImportOrder $order): void
    {
        $fee = (float) $order->fee_amount;
        if ($fee <= 0) {
            return;
        }

        $exists = $order->payments()
            ->whereIn('kind', [PaymentKind::FeeDeposit->value, PaymentKind::FeeFinal->value])
            ->where('status', '!=', PaymentStatus::Cancelled->value)
            ->exists();
        if ($exists) {
            return;
        }

        $deposit = $order->depositAmount();

        foreach ([
            [PaymentKind::FeeDeposit, $deposit, 'Señal: 50 % de los honorarios', now()->addDays(3)],
            [PaymentKind::FeeFinal, round($fee - $deposit, 2), 'Resto de honorarios: 50 % a la entrega', null],
        ] as [$kind, $amount, $concept, $due]) {
            $order->payments()->create([
                'organization_id' => $order->organization_id,
                'kind' => $kind,
                'concept' => $concept,
                'amount' => $amount,
                'status' => PaymentStatus::Pending,
                'due_date' => $due,
            ]);
        }

        $this->timeline->record($order, OrderEventType::PaymentScheduled, 'Pagos de honorarios programados', [
            'visible' => true,
            'body' => sprintf('Señal de %s € a abonar ahora y %s € a la entrega.', number_format($deposit, 2, ',', '.'), number_format($fee - $deposit, 2, ',', '.')),
        ]);
    }

    /**
     * @param  array{kind?: string, concept: string, amount: float|string, due_date?: ?string, stripe_payment_link?: ?string, notes?: ?string}  $data
     */
    public function create(ImportOrder $order, array $data, User $user, ?OrderApproval $approval = null): OrderPayment
    {
        $payment = $order->payments()->create([
            'organization_id' => $order->organization_id,
            'order_approval_id' => $approval?->id,
            'kind' => $data['kind'] ?? PaymentKind::Other->value,
            'concept' => $data['concept'],
            'amount' => $data['amount'],
            'status' => PaymentStatus::Pending,
            'due_date' => $data['due_date'] ?? null,
            'stripe_payment_link' => $data['stripe_payment_link'] ?? null,
            'notes' => $data['notes'] ?? null,
        ]);

        $this->timeline->record($order, OrderEventType::PaymentScheduled, 'Nuevo pago pendiente: '.$payment->concept, [
            'user' => $user,
            'visible' => true,
            'meta' => ['payment_id' => $payment->id, 'amount' => (float) $payment->amount],
        ]);

        return $payment;
    }

    /**
     * Edita un pago pendiente (importe, concepto, vencimiento, enlace Stripe).
     *
     * @param  array{concept?: string, amount?: float|string, due_date?: ?string, stripe_payment_link?: ?string, notes?: ?string}  $data
     *
     * @throws OrderFlowException
     */
    public function update(OrderPayment $payment, array $data): OrderPayment
    {
        if ($payment->status !== PaymentStatus::Pending) {
            throw new OrderFlowException('Solo se pueden editar pagos pendientes.');
        }

        $payment->fill(array_intersect_key($data, array_flip(['concept', 'amount', 'due_date', 'stripe_payment_link', 'notes'])))->save();

        return $payment;
    }

    /** @throws OrderFlowException */
    public function markPaid(OrderPayment $payment, PaymentMethod $method, User $user, ?string $reference = null, ?string $paidAt = null): OrderPayment
    {
        if ($payment->status === PaymentStatus::Paid) {
            throw new OrderFlowException('Este pago ya está marcado como pagado.');
        }
        if ($payment->status === PaymentStatus::Cancelled) {
            throw new OrderFlowException('Este pago está anulado.');
        }

        $order = $payment->importOrder;

        DB::transaction(function () use ($payment, $method, $user, $reference, $paidAt, $order) {
            $payment->forceFill([
                'status' => PaymentStatus::Paid,
                'method' => $method,
                'reference' => $reference,
                'paid_at' => $paidAt ? Carbon::parse($paidAt) : now(),
                'recorded_by' => $user->id,
            ])->save();

            $this->timeline->record($order, OrderEventType::PaymentRecorded, 'Pago recibido: '.$payment->concept, [
                'user' => $user,
                'visible' => true,
                'body' => number_format((float) $payment->amount, 2, ',', '.').' € · '.$method->label(),
                'meta' => ['payment_id' => $payment->id],
            ]);

            if ($payment->kind === PaymentKind::FeeDeposit) {
                $this->completeMilestone($order, 'deposit_paid');
            }

            if ($payment->kind === PaymentKind::FeeDeposit && $order->state === ImportOrderState::AwaitingDeposit) {
                $this->orders->transition($order, ImportOrderState::Inspecting, ['user' => $user, 'reason' => 'Señal recibida']);
            }
        });

        OrderPaymentRecorded::dispatch($order->fresh(), $payment->fresh());

        return $payment;
    }

    /** Marca el hito del checklist del coche (lo ve el cliente en «Detalle del proceso»). */
    private function completeMilestone(ImportOrder $order, string $key): void
    {
        $order->car?->checklists()->withoutGlobalScopes()
            ->where('kind', 'milestone')
            ->where('item_key', $key)
            ->where('completed', false)
            ->update(['completed' => true, 'completed_at' => now()]);
    }

    /** Deshace un "pagado" marcado por error. */
    public function markPending(OrderPayment $payment, User $user): OrderPayment
    {
        $payment->forceFill(['status' => PaymentStatus::Pending, 'method' => null, 'paid_at' => null, 'recorded_by' => null])->save();

        $this->timeline->record($payment->importOrder, OrderEventType::Note, 'Pago marcado de nuevo como pendiente: '.$payment->concept, [
            'user' => $user,
        ]);

        return $payment;
    }

    public function cancel(OrderPayment $payment, User $user): OrderPayment
    {
        $payment->forceFill(['status' => PaymentStatus::Cancelled])->save();

        $this->timeline->record($payment->importOrder, OrderEventType::Note, 'Pago anulado: '.$payment->concept, [
            'user' => $user,
        ]);

        return $payment;
    }
}
