<?php

namespace App\Events;

use App\Enums\ImportOrderState;
use App\Models\ImportOrder;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/** Evento del flujo de encargos (ver ImportOrderNotificationSubscriber). */
class ImportOrderStateChanged
{
    use Dispatchable;
    use SerializesModels;

    public function __construct(
        public readonly ImportOrder $order,
        public readonly ImportOrderState $from,
        public readonly ImportOrderState $to,
        public readonly ?string $reason = null,
    ) {}
}
