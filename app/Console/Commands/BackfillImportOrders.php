<?php

namespace App\Console\Commands;

use App\Enums\ImportOrderState;
use App\Enums\OrderEventType;
use App\Models\Car;
use App\Models\ContractAcceptance;
use App\Models\User;
use App\Services\ImportOrders\ImportOrderService;
use App\Services\ImportOrders\OrderPaymentService;
use App\Services\ImportOrders\OrderTimeline;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

/**
 * Crea un encargo por cada coche que ya tiene cliente asignado y no tiene
 * encargo, mapeando el estado desde cars.status y el contrato existente.
 *
 * Uso:
 *   php artisan orders:backfill            # dry-run (solo muestra)
 *   php artisan orders:backfill --apply    # crea los encargos
 *
 * Haz backup de la BD antes de --apply en producción.
 */
class BackfillImportOrders extends Command
{
    protected $signature = 'orders:backfill {--apply : Crear los encargos (sin esto es solo simulación)}';

    protected $description = 'Crea encargos para los coches con cliente asignado que aún no tienen (dry-run por defecto)';

    public function handle(ImportOrderService $orders, OrderPaymentService $payments, OrderTimeline $timeline): int
    {
        $cars = Car::withoutGlobalScope('organization')
            ->whereNotNull('client_id')
            ->whereDoesntHave('importOrders')
            ->orderBy('id')
            ->get();

        if ($cars->isEmpty()) {
            $this->info('No hay coches con cliente sin encargo.');

            return self::SUCCESS;
        }

        $rows = [];
        foreach ($cars as $car) {
            $contract = ContractAcceptance::query()->where('car_id', $car->id)->latest('id')->first();
            $rows[] = [$car->id, trim("{$car->brand} {$car->model}"), $car->status, $this->mapState($car, $contract)->label()];
        }
        $this->table(['Coche', 'Vehículo', 'Estado coche', 'Estado encargo'], $rows);

        if (! $this->option('apply')) {
            $this->warn('Simulación: no se ha creado nada. Repite con --apply para crear los encargos.');

            return self::SUCCESS;
        }

        $created = 0;
        foreach ($cars as $car) {
            $owner = User::query()->where('organization_id', $car->organization_id)->where('role', 'owner')->orderBy('id')->first()
                ?? User::query()->where('organization_id', $car->organization_id)->orderBy('id')->first();
            if (! $owner) {
                $this->warn("Coche #{$car->id}: su organización no tiene usuarios, se omite.");

                continue;
            }

            DB::transaction(function () use ($car, $owner, $orders, $payments, $timeline, &$created) {
                $contract = ContractAcceptance::query()->where('car_id', $car->id)->latest('id')->first();
                $originalStatus = $car->status;
                $order = $orders->createForCar($car, $owner);
                // createForCar() marca el coche como Reserved: restauramos el
                // estado real (el backfill no debe mover coches de columna).
                Car::withoutGlobalScope('organization')->whereKey($car->id)->update(['status' => $originalStatus]);
                $state = $this->mapState($car, $contract);

                if ($contract) {
                    $contract->forceFill(['import_order_id' => $order->id])->save();
                }

                $order->forceFill(['state' => $state, 'state_changed_at' => now()])->save();
                if ($contract?->accepted_at) {
                    $payments->scheduleFees($order);
                }

                $timeline->record($order, OrderEventType::Note, 'Encargo creado automáticamente desde datos existentes', [
                    'meta' => ['car_status' => $car->status, 'mapped_state' => $state->value],
                ]);
                $created++;
            });
        }

        $this->info("Encargos creados: {$created}");

        return self::SUCCESS;
    }

    private function mapState(Car $car, ?ContractAcceptance $contract): ImportOrderState
    {
        $byCar = match ($car->status) {
            'Purchased' => ImportOrderState::Purchasing,
            'In_transit' => ImportOrderState::InTransit,
            'Processing', 'Pending review', 'Verifying' => ImportOrderState::Paperwork,
            'Delivered' => ImportOrderState::Delivered,
            default => null,
        };
        if ($byCar) {
            return $byCar;
        }

        if ($contract?->accepted_at) {
            return ImportOrderState::AwaitingDeposit;
        }
        if ($contract && ($contract->status === null || $contract->status->isOpen()) && ! $contract->accepted_at) {
            return ImportOrderState::AwaitingSignature;
        }

        return ImportOrderState::Draft;
    }
}
