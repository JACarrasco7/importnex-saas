<?php

namespace App\Services\ImportOrders;

use DomainException;

/**
 * Regla de negocio incumplida en el flujo del encargo. El mensaje es apto
 * para mostrarse tal cual al usuario (equipo o cliente).
 */
class OrderFlowException extends DomainException
{
    public function __construct(string $message, public readonly string $field = 'general')
    {
        parent::__construct($message);
    }
}
