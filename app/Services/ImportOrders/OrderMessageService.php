<?php

namespace App\Services\ImportOrders;

use App\Events\OrderMessagePosted;
use App\Models\ImportOrder;
use App\Models\OrderMessage;
use App\Models\User;

/** Hilo de mensajes cliente ↔ equipo y notas internas del encargo. */
class OrderMessageService
{
    public function postFromClient(ImportOrder $order, string $body, ?string $ip): OrderMessage
    {
        $message = $order->messages()->create([
            'organization_id' => $order->organization_id,
            'author_type' => OrderMessage::AUTHOR_CLIENT,
            'body' => trim($body),
            'visibility' => OrderMessage::VISIBILITY_CLIENT,
            'read_by_client_at' => now(),
            'ip' => $ip,
        ]);

        OrderMessagePosted::dispatch($order, $message);

        return $message;
    }

    public function postFromTeam(ImportOrder $order, string $body, User $user, bool $internal = false): OrderMessage
    {
        $message = $order->messages()->create([
            'organization_id' => $order->organization_id,
            'author_type' => OrderMessage::AUTHOR_TEAM,
            'user_id' => $user->id,
            'body' => trim($body),
            'visibility' => $internal ? OrderMessage::VISIBILITY_INTERNAL : OrderMessage::VISIBILITY_CLIENT,
            'read_by_team_at' => now(),
        ]);

        if (! $internal) {
            OrderMessagePosted::dispatch($order, $message);
        }

        return $message;
    }

    public function markReadByTeam(ImportOrder $order): void
    {
        $order->messages()->whereNull('read_by_team_at')->update(['read_by_team_at' => now()]);
    }

    public function markReadByClient(ImportOrder $order): void
    {
        $order->messages()
            ->where('visibility', OrderMessage::VISIBILITY_CLIENT)
            ->whereNull('read_by_client_at')
            ->update(['read_by_client_at' => now()]);
    }
}
