<?php

namespace App\Events;

use App\Models\ImportOrder;
use App\Models\OrderPayment;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/** Evento del flujo de encargos (ver ImportOrderNotificationSubscriber). */
class OrderPaymentRecorded
{
    use Dispatchable;
    use SerializesModels;

    public function __construct(
        public readonly ImportOrder $order,
        public readonly OrderPayment $payment,
    ) {}
}
