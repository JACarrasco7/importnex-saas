<?php

namespace App\Console\Commands;

use App\Enums\ContractStatus;
use App\Models\ContractAcceptance;
use App\Services\ImportOrders\OrderApprovalService;
use App\Services\ImportOrders\OrderContractService;
use App\Services\ImportOrders\OrderNotifier;
use Illuminate\Console\Command;

/**
 * Tareas periódicas de los encargos (programado cada hora en routes/console.php):
 *   1. Caducar contratos no firmados fuera de plazo (y avisar al equipo).
 *   2. Recordar al cliente los contratos pendientes de firma.
 *   3. Marcar como «sin respuesta» las aprobaciones vencidas.
 */
class OrdersMaintenance extends Command
{
    protected $signature = 'orders:maintenance';

    protected $description = 'Caduca contratos, envía recordatorios de firma y vence aprobaciones sin respuesta';

    public function handle(OrderContractService $contracts, OrderApprovalService $approvals, OrderNotifier $notifier): int
    {
        $expired = $contracts->expireOverdue();
        $reminded = $this->sendSignatureReminders($notifier);
        $expiredApprovals = $approvals->expireOverdue();

        $this->info("Contratos caducados: {$expired} · Recordatorios enviados: {$reminded} · Aprobaciones vencidas: {$expiredApprovals}");

        return self::SUCCESS;
    }

    private function sendSignatureReminders(OrderNotifier $notifier): int
    {
        $firstAfter = now()->subHours((int) config('import_orders.contract_first_reminder_hours', 24));
        $every = now()->subHours((int) config('import_orders.contract_reminder_every_hours', 48));

        $pending = ContractAcceptance::query()
            ->whereNotNull('import_order_id')
            ->whereIn('status', [ContractStatus::Sent->value, ContractStatus::Viewed->value])
            ->where('created_at', '<=', $firstAfter)
            ->where('expires_at', '>', now())
            ->where('reminders_sent', '<', (int) config('import_orders.contract_max_reminders', 2))
            ->where(fn ($q) => $q->whereNull('last_reminder_at')->orWhere('last_reminder_at', '<=', $every))
            ->with('importOrder.client', 'importOrder.car')
            ->get();

        $sent = 0;
        foreach ($pending as $contract) {
            $order = $contract->importOrder;
            if (! $order) {
                continue;
            }

            $params = [
                'reference' => $order->reference,
                'vehicle' => $order->vehicleLabel(),
                'date' => $contract->expires_at?->format('d/m/Y'),
            ];

            $ok = $notifier->toClient(
                $order,
                __('orders.mail.contract_reminder_subject'),
                __('orders.mail.contract_reminder_heading'),
                [__('orders.mail.contract_reminder_line1', $params), __('orders.mail.contract_reminder_line2')],
                __('orders.mail.contract_issued_button'),
            );

            $contract->forceFill([
                'reminders_sent' => $contract->reminders_sent + 1,
                'last_reminder_at' => now(),
            ])->save();

            $sent += $ok ? 1 : 0;
        }

        return $sent;
    }
}
