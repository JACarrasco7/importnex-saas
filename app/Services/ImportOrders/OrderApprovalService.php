<?php

namespace App\Services\ImportOrders;

use App\Enums\ApprovalKind;
use App\Enums\ApprovalStatus;
use App\Enums\ImportOrderState;
use App\Enums\OrderEventType;
use App\Enums\PaymentKind;
use App\Enums\UnitDecision;
use App\Events\OrderApprovalRequested;
use App\Events\OrderApprovalResolved;
use App\Models\ImportOrder;
use App\Models\OrderApproval;
use App\Models\PortalOtp;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;

/**
 * Aprobaciones en 1 clic: costes extra y decisión del cliente tras la
 * inspección (seguir, buscar otro coche o cancelar). Nunca se aprueba por
 * silencio: si vence el plazo queda «sin respuesta» y decide el equipo.
 */
class OrderApprovalService
{
    public function __construct(
        private ImportOrderService $orders,
        private OrderPaymentService $payments,
        private OrderMediaService $media,
        private PortalOtpService $otps,
        private OrderTimeline $timeline,
    ) {}

    /**
     * @param  array{kind: string, title: string, description?: ?string, amount?: float|string|null, due_hours?: ?int}  $data
     * @param  list<UploadedFile>  $photos
     *
     * @throws OrderFlowException
     */
    public function request(ImportOrder $order, array $data, User $user, array $photos = []): OrderApproval
    {
        $kind = ApprovalKind::from($data['kind']);

        if ($order->state->isTerminal()) {
            throw new OrderFlowException('El encargo está cerrado.');
        }
        if ($kind === ApprovalKind::UnitDecision && $order->state !== ImportOrderState::Inspecting) {
            throw new OrderFlowException('La decisión sobre el coche se pide durante la inspección.');
        }
        if ($kind === ApprovalKind::ExtraCost && (float) ($data['amount'] ?? 0) <= 0) {
            throw new OrderFlowException('Indica el importe del coste adicional.', 'amount');
        }
        if ($order->approvals()->where('status', ApprovalStatus::Pending->value)->where('kind', $kind->value)->exists()) {
            throw new OrderFlowException('Ya hay una petición de este tipo esperando respuesta del cliente.');
        }

        $approval = DB::transaction(function () use ($order, $data, $user, $photos, $kind) {
            $approval = $order->approvals()->create([
                'organization_id' => $order->organization_id,
                'created_by' => $user->id,
                'kind' => $kind,
                'title' => $data['title'],
                'description' => $data['description'] ?? null,
                'amount' => $kind === ApprovalKind::ExtraCost ? $data['amount'] : ($data['amount'] ?? null),
                'status' => ApprovalStatus::Pending,
                'due_at' => now()->addHours((int) ($data['due_hours'] ?? config('import_orders.approval_due_hours', 48))),
            ]);

            foreach ($photos as $photo) {
                $this->media->store($order, $photo, [
                    'collection' => 'inspection',
                    'visibility' => 'client',
                    'approval_id' => $approval->id,
                ], $user);
            }

            $this->timeline->record($order, OrderEventType::ApprovalRequested, 'Necesitamos tu respuesta: '.$approval->title, [
                'user' => $user,
                'visible' => true,
                'meta' => ['approval_id' => $approval->id, 'amount' => $approval->amount],
            ]);

            if ($kind === ApprovalKind::UnitDecision) {
                $this->orders->transition($order, ImportOrderState::AwaitingClientDecision, ['user' => $user]);
            }

            return $approval;
        });

        OrderApprovalRequested::dispatch($order->fresh(), $approval);

        return $approval;
    }

    /** @throws OrderFlowException */
    public function sendCode(OrderApproval $approval, ?string $ip): PortalOtp
    {
        $this->assertPending($approval);

        return $this->otps->send($approval->importOrder, PortalOtp::PURPOSE_APPROVE, $approval, $ip);
    }

    /**
     * Respuesta del cliente desde el portal.
     *
     * @param  array{approved?: bool, decision?: ?string, comment?: ?string, code?: ?string}  $data
     *
     * @throws OrderFlowException
     */
    public function resolve(OrderApproval $approval, array $data, ?string $ip): OrderApproval
    {
        $this->assertPending($approval);
        $order = $approval->importOrder;

        $decision = null;
        if ($approval->kind === ApprovalKind::UnitDecision) {
            $decision = UnitDecision::tryFrom((string) ($data['decision'] ?? ''));
            if (! $decision) {
                throw new OrderFlowException('Elige una opción.', 'decision');
            }
            if ($decision === UnitDecision::Cancel && blank($data['comment'] ?? null)) {
                throw new OrderFlowException('Cuéntanos brevemente por qué quieres cancelar.', 'comment');
            }
        }

        $approved = $approval->kind === ApprovalKind::UnitDecision ? true : (bool) ($data['approved'] ?? false);

        $otp = null;
        if ($approved && $approval->requiresOtp()) {
            $otp = $this->otps->verify($order, PortalOtp::PURPOSE_APPROVE, $approval, $data['code'] ?? null);
        }

        DB::transaction(function () use ($approval, $order, $decision, $approved, $data, $ip, $otp) {
            $approval->forceFill([
                'status' => $approved ? ApprovalStatus::Approved : ApprovalStatus::Rejected,
                'decision' => $decision,
                'client_comment' => $data['comment'] ?? null,
                'resolved_at' => now(),
                'resolved_ip' => $ip,
                'otp_verified_at' => $otp?->consumed_at,
            ])->save();

            $title = match (true) {
                $decision !== null => 'Has decidido: '.$decision->label(),
                $approved => 'Has aprobado: '.$approval->title,
                default => 'Has rechazado: '.$approval->title,
            };

            $this->timeline->record($order, OrderEventType::ApprovalResolved, $title, [
                'actor' => 'client',
                'visible' => true,
                'body' => $data['comment'] ?? null,
                'ip' => $ip,
                'meta' => ['approval_id' => $approval->id],
            ]);

            if ($approval->kind === ApprovalKind::ExtraCost && $approved && (float) $approval->amount > 0) {
                $order->payments()->create([
                    'organization_id' => $order->organization_id,
                    'order_approval_id' => $approval->id,
                    'kind' => PaymentKind::ExtraCost,
                    'concept' => $approval->title,
                    'amount' => $approval->amount,
                    'status' => 'pending',
                ]);
            }

            if ($decision !== null && $order->state === ImportOrderState::AwaitingClientDecision) {
                $this->orders->transition($order, $decision->targetState(), [
                    'actor' => 'client',
                    'ip' => $ip,
                    'reason' => $decision === UnitDecision::Cancel ? ($data['comment'] ?? 'Cancelado por el cliente') : $decision->label(),
                ]);
            }
        });

        OrderApprovalResolved::dispatch($order->fresh(), $approval->fresh());

        return $approval;
    }

    /** El equipo retira una petición (p. ej. se resolvió por teléfono). */
    public function withdraw(OrderApproval $approval, User $user): void
    {
        $this->assertPending($approval);
        $order = $approval->importOrder;

        DB::transaction(function () use ($approval, $user, $order) {
            $approval->forceFill(['status' => ApprovalStatus::Withdrawn, 'resolved_at' => now()])->save();

            $this->timeline->record($order, OrderEventType::ApprovalResolved, 'Petición retirada: '.$approval->title, [
                'user' => $user,
                'visible' => true,
            ]);

            if ($approval->kind === ApprovalKind::UnitDecision && $order->state === ImportOrderState::AwaitingClientDecision) {
                $this->orders->transition($order, ImportOrderState::Inspecting, ['user' => $user, 'reason' => 'Petición retirada']);
            }
        });
    }

    /** Pasa a «sin respuesta» las peticiones vencidas. Devuelve cuántas. */
    public function expireOverdue(): int
    {
        $overdue = OrderApproval::query()
            ->where('status', ApprovalStatus::Pending->value)
            ->whereNotNull('due_at')
            ->where('due_at', '<', now())
            ->get();

        foreach ($overdue as $approval) {
            $approval->forceFill(['status' => ApprovalStatus::Expired, 'resolved_at' => now()])->save();
            $this->timeline->record($approval->importOrder, OrderEventType::ApprovalResolved, 'Petición sin respuesta: '.$approval->title, []);
            OrderApprovalResolved::dispatch($approval->importOrder, $approval->fresh());
        }

        return $overdue->count();
    }

    /** @throws OrderFlowException */
    private function assertPending(OrderApproval $approval): void
    {
        if (! $approval->isPending()) {
            throw new OrderFlowException('Esta petición ya está respondida o ha caducado.');
        }
        if ($approval->due_at && $approval->due_at->isPast()) {
            throw new OrderFlowException('El plazo para responder ha terminado. Escríbenos y lo vemos.');
        }
    }
}
