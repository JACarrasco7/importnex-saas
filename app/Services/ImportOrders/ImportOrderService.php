<?php

namespace App\Services\ImportOrders;

use App\Enums\ApprovalStatus;
use App\Enums\ContractStatus;
use App\Enums\ImportOrderState;
use App\Enums\OrderEventType;
use App\Enums\PaymentStatus;
use App\Events\ImportOrderStateChanged;
use App\Events\OrderCancellationRequested;
use App\Models\Car;
use App\Models\CarRequest;
use App\Models\ImportOrder;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Ciclo de vida del encargo: creación, transiciones de estado (con guardas),
 * sustitución de vehículo, cancelación y enlace del portal.
 *
 * Toda transición pasa por transition(): valida, guarda, sincroniza el estado
 * del coche, escribe la bitácora y lanza ImportOrderStateChanged.
 */
class ImportOrderService
{
    public function __construct(private OrderTimeline $timeline) {}

    /**
     * Crea el encargo de un coche con cliente asignado.
     *
     * @throws OrderFlowException
     */
    public function createForCar(Car $car, User $user, ?CarRequest $carRequest = null): ImportOrder
    {
        if (! $car->client_id) {
            throw new OrderFlowException('Asigna primero un cliente al coche para crear el encargo.');
        }

        $existing = $car->activeImportOrder();
        if ($existing) {
            throw new OrderFlowException("Este coche ya tiene un encargo activo ({$existing->reference}).");
        }

        if ($carRequest && $carRequest->organization_id !== $car->organization_id) {
            throw new OrderFlowException('La solicitud pertenece a otra organización.');
        }

        return DB::transaction(function () use ($car, $user, $carRequest) {
            $fee = (float) ($car->professional_fees ?: $car->defaultProfessionalFee());

            $order = new ImportOrder([
                'organization_id' => $car->organization_id,
                'client_id' => $car->client_id,
                'car_id' => $car->id,
                'car_request_id' => $carRequest?->id ?? $this->latestRequestId($car),
                'assigned_to' => $user->id,
                'reference' => 'TMP-'.Str::uuid(),
                'state' => ImportOrderState::Draft,
                'state_changed_at' => now(),
                'fee_amount' => $fee,
                'expected_delivery_date' => $car->expected_delivery_date,
            ]);
            $order->save();

            $order->forceFill(['reference' => sprintf('%s-%s-%04d', config('import_orders.reference_prefix'), now()->format('Y'), $order->id)]);
            $this->assignNewPortalToken($order);
            $order->save();

            $this->syncCar($order);

            $this->timeline->record($order, OrderEventType::Created, 'Encargo creado', [
                'user' => $user,
                'to' => ImportOrderState::Draft,
                'meta' => ['car_id' => $car->id, 'fee_amount' => $fee],
            ]);

            return $order;
        });
    }

    /**
     * Cambia el estado del encargo validando la transición y sus guardas.
     *
     * @param  array{reason?: ?string, user?: ?User, actor?: string, ip?: ?string, force?: bool}  $context
     *
     * @throws OrderFlowException
     */
    public function transition(ImportOrder $order, ImportOrderState $target, array $context = []): ImportOrder
    {
        return DB::transaction(function () use ($order, $target, $context) {
            /** @var ImportOrder $locked */
            $locked = ImportOrder::query()->lockForUpdate()->findOrFail($order->id);
            $from = $locked->state;

            if (! $from->canTransitionTo($target)) {
                throw new OrderFlowException("No se puede pasar de «{$from->label()}» a «{$target->label()}».", 'state');
            }

            $this->guard($locked, $target, $context);

            $locked->state = $target;
            $locked->state_changed_at = now();

            if ($target === ImportOrderState::Delivered) {
                $locked->delivered_at = now();
            }
            if ($target === ImportOrderState::Cancelled) {
                $locked->cancelled_at = now();
                $locked->cancel_reason = $context['reason'] ?? null;
            }
            $locked->save();

            if ($target === ImportOrderState::Cancelled) {
                $this->closeOpenItems($locked);
            }

            $this->syncCar($locked);

            $this->timeline->record($locked, OrderEventType::StateChanged, $target->label(), [
                'body' => $context['reason'] ?? null,
                'visible' => $from->clientPhase() !== $target->clientPhase() || $target->isTerminal()
                    || in_array($target, [ImportOrderState::AwaitingClientDecision, ImportOrderState::SearchingReplacement], true),
                'user' => $context['user'] ?? null,
                'actor' => $context['actor'] ?? null,
                'from' => $from,
                'to' => $target,
                'ip' => $context['ip'] ?? null,
            ]);

            $order->setRawAttributes($locked->getAttributes(), true);

            DB::afterCommit(fn () => ImportOrderStateChanged::dispatch($locked, $from, $target, $context['reason'] ?? null));

            return $locked;
        });
    }

    /**
     * Cambia el coche del encargo (inspección negativa o coche vendido).
     *
     * @throws OrderFlowException
     */
    public function replaceVehicle(ImportOrder $order, Car $newCar, User $user): ImportOrder
    {
        if ($order->state !== ImportOrderState::SearchingReplacement) {
            throw new OrderFlowException('Solo se puede cambiar el coche cuando el encargo está en «Buscando sustituto».');
        }
        if ($newCar->organization_id !== $order->organization_id) {
            throw new OrderFlowException('El coche pertenece a otra organización.', 'car_id');
        }
        if ($newCar->id === $order->car_id) {
            throw new OrderFlowException('Elige un coche distinto al actual.', 'car_id');
        }
        if ($newCar->client_id && $newCar->client_id !== $order->client_id) {
            throw new OrderFlowException('Ese coche ya está asignado a otro cliente.', 'car_id');
        }
        if ($newCar->activeImportOrder()) {
            throw new OrderFlowException('Ese coche ya tiene un encargo activo.', 'car_id');
        }

        DB::transaction(function () use ($order, $newCar, $user) {
            $oldCar = $order->car;
            if ($oldCar && $oldCar->client_id === $order->client_id) {
                $oldCar->forceFill(['client_id' => null])->save();
            }

            $newCar->forceFill(['client_id' => $order->client_id])->save();
            $order->forceFill(['car_id' => $newCar->id])->save();
            $order->unsetRelation('car');

            $this->timeline->record($order, OrderEventType::VehicleReplaced, 'Nuevo coche asignado al encargo', [
                'user' => $user,
                'visible' => true,
                'body' => trim("{$newCar->brand} {$newCar->model} {$newCar->version}"),
                'meta' => ['old_car_id' => $oldCar?->id, 'new_car_id' => $newCar->id],
            ]);
        });

        return $this->transition($order, ImportOrderState::Inspecting, ['user' => $user, 'reason' => 'Nueva unidad asignada']);
    }

    /** El cliente pide cancelar desde el portal: avisa al equipo, no cancela solo. */
    public function requestCancellation(ImportOrder $order, string $reason, ?string $ip): void
    {
        if ($order->state->isTerminal()) {
            throw new OrderFlowException('Este encargo ya está cerrado.');
        }
        if ($order->cancellation_requested_at) {
            throw new OrderFlowException('Ya nos has enviado una solicitud de cancelación; te contactaremos.');
        }

        $order->forceFill([
            'cancellation_requested_at' => now(),
            'cancellation_request_reason' => $reason,
        ])->save();

        $this->timeline->record($order, OrderEventType::CancellationRequested, 'Has solicitado cancelar el encargo', [
            'actor' => 'client',
            'visible' => true,
            'body' => $reason,
            'ip' => $ip,
        ]);

        OrderCancellationRequested::dispatch($order, $reason);
    }

    public function rotatePortalToken(ImportOrder $order, User $user): string
    {
        $token = $this->assignNewPortalToken($order);
        $order->portal_token_rotated_at = now();
        $order->save();

        $this->timeline->record($order, OrderEventType::PortalLinkRotated, 'Enlace del portal renovado (el anterior deja de funcionar)', [
            'user' => $user,
        ]);

        return $token;
    }

    public function assignNewPortalToken(ImportOrder $order): string
    {
        do {
            $token = Str::random(48);
            $hash = hash('sha256', $token);
        } while (ImportOrder::withTrashed()->where('portal_token_hash', $hash)->exists());

        $order->portal_token = $token;
        $order->portal_token_hash = $hash;

        return $token;
    }

    /**
     * @param  array{reason?: ?string, force?: bool}  $context
     *
     * @throws OrderFlowException
     */
    private function guard(ImportOrder $order, ImportOrderState $target, array $context): void
    {
        if ($target === ImportOrderState::AwaitingDeposit && ! $order->signedContract()) {
            throw new OrderFlowException('El contrato tiene que estar firmado.');
        }

        if ($order->state === ImportOrderState::AwaitingDeposit && $target === ImportOrderState::Inspecting
            && ! $order->isDepositSettled() && empty($context['force'])) {
            throw new OrderFlowException('Falta registrar el pago de la señal. Márcala como pagada en «Pagos» (o fuerza el paso indicando el motivo).');
        }

        if ($target === ImportOrderState::Cancelled && blank($context['reason'] ?? null)) {
            throw new OrderFlowException('Indica el motivo de la cancelación.', 'reason');
        }

        if (! empty($context['force']) && blank($context['reason'] ?? null)) {
            throw new OrderFlowException('Indica el motivo para forzar el cambio de estado.', 'reason');
        }
    }

    /** Al cancelar: anula pagos pendientes, retira aprobaciones y anula contratos abiertos. */
    private function closeOpenItems(ImportOrder $order): void
    {
        $order->payments()->where('status', PaymentStatus::Pending->value)
            ->update(['status' => PaymentStatus::Cancelled->value]);

        $order->approvals()->where('status', ApprovalStatus::Pending->value)
            ->update(['status' => ApprovalStatus::Withdrawn->value, 'resolved_at' => now()]);

        $order->contracts()->whereIn('status', [ContractStatus::Sent->value, ContractStatus::Viewed->value])
            ->update(['status' => ContractStatus::Voided->value, 'voided_at' => now(), 'void_reason' => 'Encargo cancelado']);

        $car = $order->car;
        if ($car && $car->client_id === $order->client_id) {
            $car->forceFill(['client_id' => null])->save();
        }
    }

    /** Mantiene cars.status / cars.client_id coherentes para kanban y listados. */
    private function syncCar(ImportOrder $order): void
    {
        $car = $order->car;
        if (! $car) {
            return;
        }

        $status = $order->state->carStatus();
        $changes = [];
        if ($status && $car->status !== $status) {
            $changes['status'] = $status;
        }
        if (! $order->state->isTerminal() && $order->state !== ImportOrderState::SearchingReplacement && $car->client_id !== $order->client_id) {
            $changes['client_id'] = $order->client_id;
        }
        if ($order->expected_delivery_date && ! $car->expected_delivery_date) {
            $changes['expected_delivery_date'] = $order->expected_delivery_date;
        }

        if ($changes) {
            $car->forceFill($changes)->save();
        }
    }

    private function latestRequestId(Car $car): ?int
    {
        return CarRequest::query()
            ->where('organization_id', $car->organization_id)
            ->where('client_id', $car->client_id)
            ->latest('id')
            ->value('id');
    }
}
