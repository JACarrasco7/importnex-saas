<?php

namespace App\Services\ImportOrders;

use App\Enums\ImportOrderState;
use App\Enums\OrderEventType;
use App\Models\ImportOrder;
use App\Models\OrderEvent;
use App\Models\User;

/** Escribe en la bitácora del encargo (order_events). */
class OrderTimeline
{
    /**
     * @param  array{body?: ?string, visible?: bool, actor?: string, user?: ?User, from?: ?ImportOrderState, to?: ?ImportOrderState, meta?: array<string, mixed>, ip?: ?string}  $options
     */
    public function record(ImportOrder $order, OrderEventType $type, string $title, array $options = []): OrderEvent
    {
        $user = $options['user'] ?? null;

        return OrderEvent::create([
            'organization_id' => $order->organization_id,
            'import_order_id' => $order->id,
            'type' => $type,
            'title' => mb_substr($title, 0, 255),
            'body' => $options['body'] ?? null,
            'visible_to_client' => $options['visible'] ?? false,
            'actor_type' => $options['actor'] ?? ($user ? 'user' : 'system'),
            'actor_user_id' => $user?->id,
            'from_state' => ($options['from'] ?? null)?->value,
            'to_state' => ($options['to'] ?? null)?->value,
            'meta' => $options['meta'] ?? null,
            'ip' => $options['ip'] ?? null,
        ]);
    }
}
