<?php

namespace App\Events;

use App\Models\ImportOrder;
use App\Models\OrderMessage;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/** Evento del flujo de encargos (ver ImportOrderNotificationSubscriber). */
class OrderMessagePosted
{
    use Dispatchable;
    use SerializesModels;

    public function __construct(
        public readonly ImportOrder $order,
        public readonly OrderMessage $message,
    ) {}
}
