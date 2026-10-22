<?php

namespace App\Services\ImportOrders;

use App\Enums\OrderEventType;
use App\Mail\OrderUpdateMail;
use App\Models\ImportOrder;
use App\Models\User;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

/**
 * Envío de avisos del encargo por email al cliente y al equipo.
 *
 * Síncrono con try/catch por envío (ver .ai/rules/events.md: sin worker de
 * colas documentado en Forge, un ShouldQueue dejaría los avisos atascados).
 * Cada envío al cliente queda en la bitácora del encargo.
 */
class OrderNotifier
{
    public function __construct(private OrderTimeline $timeline) {}

    /**
     * @param  list<string>  $lines
     * @param  list<array{path: string, name: string, mime: string}>  $files
     */
    public function toClient(ImportOrder $order, string $subject, string $heading, array $lines, ?string $actionLabel = null, ?string $actionUrl = null, array $files = []): bool
    {
        $client = $order->client;
        if (! $client?->email) {
            return false;
        }

        try {
            Mail::to($client->email)->send(new OrderUpdateMail(
                subjectLine: $subject,
                heading: $heading,
                lines: $lines,
                actionLabel: $actionLabel ?? __('orders.mail.button_portal'),
                actionUrl: $actionUrl ?? $order->portalUrl(),
                greeting: __('orders.mail.greeting', ['name' => $this->firstName($client->name)]),
                forClient: true,
                files: $files,
                brand: $order->organization->brand()->toPublicArray(),
            ));

            $this->timeline->record($order, OrderEventType::NotificationSent, 'Email al cliente: '.$subject, [
                'meta' => ['channel' => 'email', 'to' => $client->email],
            ]);

            return true;
        } catch (\Throwable $e) {
            Log::warning('orders.notify.client_failed', [
                'order_id' => $order->id,
                'subject' => $subject,
                'message' => $e->getMessage(),
            ]);

            return false;
        }
    }

    /** @param  list<string>  $lines */
    public function toTeam(ImportOrder $order, string $subject, array $lines): bool
    {
        $recipients = $this->teamRecipients($order);
        if ($recipients === []) {
            return false;
        }

        try {
            Mail::to($recipients)->send(new OrderUpdateMail(
                subjectLine: $subject,
                heading: $order->reference.' · '.$order->vehicleLabel(),
                lines: $lines,
                actionLabel: __('orders.mail.team_button'),
                actionUrl: route('import-orders.show', $order->id),
                forClient: false,
                brand: $order->organization->brand()->toPublicArray(),
            ));

            return true;
        } catch (\Throwable $e) {
            Log::warning('orders.notify.team_failed', [
                'order_id' => $order->id,
                'subject' => $subject,
                'message' => $e->getMessage(),
            ]);

            return false;
        }
    }

    /** @return list<string> */
    public function teamRecipients(ImportOrder $order): array
    {
        $configured = (string) config('import_orders.team_email');
        if ($configured !== '') {
            return array_values(array_filter(array_map('trim', explode(',', $configured))));
        }

        $emails = [];
        if ($order->assigned_to) {
            $emails[] = User::query()->whereKey($order->assigned_to)->value('email');
        }
        $owners = User::query()
            ->where('organization_id', $order->organization_id)
            ->where('role', 'owner')
            ->pluck('email')
            ->all();

        return array_values(array_unique(array_filter([...$emails, ...$owners])));
    }

    private function firstName(?string $name): string
    {
        $name = trim((string) $name);

        return $name === '' ? '' : explode(' ', $name)[0];
    }
}
