<?php

namespace App\Events;

use App\Models\ContractAcceptance;
use App\Models\ImportOrder;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/** Evento del flujo de encargos (ver ImportOrderNotificationSubscriber). */
class OrderContractIssued
{
    use Dispatchable;
    use SerializesModels;

    public function __construct(
        public readonly ImportOrder $order,
        public readonly ContractAcceptance $contract,
    ) {}
}
