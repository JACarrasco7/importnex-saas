<?php

namespace App\Enums;

/**
 * Estado de un encargo de importación (ImportOrder).
 *
 * Es la ÚNICA fuente de verdad del avance del proceso. Las transiciones
 * permitidas viven en allowedTransitions(); cualquier cambio de estado pasa por
 * ImportOrderService::transition(), que valida la transición y deja rastro en
 * order_events.
 */
enum ImportOrderState: string
{
    case Draft = 'draft';
    case AwaitingSignature = 'awaiting_signature';
    case ContractRejected = 'contract_rejected';
    case AwaitingDeposit = 'awaiting_deposit';
    case Inspecting = 'inspecting';
    case AwaitingClientDecision = 'awaiting_client_decision';
    case SearchingReplacement = 'searching_replacement';
    case Purchasing = 'purchasing';
    case InTransit = 'in_transit';
    case Paperwork = 'paperwork';
    case ReadyForDelivery = 'ready_for_delivery';
    case Delivered = 'delivered';
    case Cancelled = 'cancelled';

    /**
     * Estados a los que se puede pasar desde este.
     *
     * @return list<self>
     */
    public function allowedTransitions(): array
    {
        return match ($this) {
            self::Draft => [self::AwaitingSignature, self::Cancelled],
            self::AwaitingSignature => [self::AwaitingDeposit, self::ContractRejected, self::Cancelled],
            self::ContractRejected => [self::AwaitingSignature, self::Cancelled],
            self::AwaitingDeposit => [self::Inspecting, self::Cancelled],
            self::Inspecting => [self::AwaitingClientDecision, self::Purchasing, self::SearchingReplacement, self::Cancelled],
            self::AwaitingClientDecision => [self::Purchasing, self::SearchingReplacement, self::Cancelled],
            self::SearchingReplacement => [self::Inspecting, self::Cancelled],
            self::Purchasing => [self::InTransit, self::SearchingReplacement],
            self::InTransit => [self::Paperwork],
            self::Paperwork => [self::ReadyForDelivery],
            self::ReadyForDelivery => [self::Delivered],
            self::Delivered, self::Cancelled => [],
        };
    }

    public function canTransitionTo(self $target): bool
    {
        return in_array($target, $this->allowedTransitions(), true);
    }

    /**
     * Transiciones que el equipo puede lanzar a mano desde el panel. El resto
     * las provoca el propio flujo (firmar contrato, registrar señal, resolver
     * una aprobación, sustituir vehículo).
     *
     * @return list<self>
     */
    public function manualTransitions(): array
    {
        $automatic = [self::AwaitingSignature, self::AwaitingDeposit, self::ContractRejected, self::AwaitingClientDecision];

        return array_values(array_filter(
            $this->allowedTransitions(),
            fn (self $state): bool => ! in_array($state, $automatic, true),
        ));
    }

    public function isTerminal(): bool
    {
        return in_array($this, [self::Delivered, self::Cancelled], true);
    }

    /** El cliente aún puede cancelar sin que se haya pagado nada al vendedor. */
    public function isBeforePurchase(): bool
    {
        return in_array($this, [
            self::Draft, self::AwaitingSignature, self::ContractRejected, self::AwaitingDeposit,
            self::Inspecting, self::AwaitingClientDecision, self::SearchingReplacement,
        ], true);
    }

    /** El seguimiento solo se muestra al cliente una vez firmado el contrato. */
    public function isTrackingVisible(): bool
    {
        return ! in_array($this, [self::Draft, self::AwaitingSignature, self::ContractRejected], true);
    }

    public function label(): string
    {
        return match ($this) {
            self::Draft => 'Borrador',
            self::AwaitingSignature => 'Pendiente de firma',
            self::ContractRejected => 'Contrato rechazado',
            self::AwaitingDeposit => 'Pendiente de señal',
            self::Inspecting => 'Inspección',
            self::AwaitingClientDecision => 'Decisión del cliente',
            self::SearchingReplacement => 'Buscando sustituto',
            self::Purchasing => 'Compra al vendedor',
            self::InTransit => 'En transporte',
            self::Paperwork => 'Trámites',
            self::ReadyForDelivery => 'Listo para entrega',
            self::Delivered => 'Entregado',
            self::Cancelled => 'Cancelado',
        };
    }

    /** Frase corta y amable para el cliente final (portal y emails). */
    public function clientLabel(): string
    {
        return match ($this) {
            self::Draft => 'Preparando tu encargo',
            self::AwaitingSignature => 'Pendiente de tu firma',
            self::ContractRejected => 'Revisando el contrato contigo',
            self::AwaitingDeposit => 'Pendiente de la señal',
            self::Inspecting => 'Inspeccionando el coche',
            self::AwaitingClientDecision => 'Necesitamos tu decisión',
            self::SearchingReplacement => 'Buscando otra unidad para ti',
            self::Purchasing => 'Cerrando la compra con el vendedor',
            self::InTransit => 'Tu coche está en camino',
            self::Paperwork => 'Haciendo los trámites en España',
            self::ReadyForDelivery => 'Listo para entregarte',
            self::Delivered => 'Entregado',
            self::Cancelled => 'Encargo cancelado',
        };
    }

    public function clientPhase(): ClientPhase
    {
        return match ($this) {
            self::Draft, self::AwaitingSignature, self::ContractRejected, self::AwaitingDeposit => ClientPhase::Signature,
            self::Inspecting, self::AwaitingClientDecision, self::SearchingReplacement => ClientPhase::Inspection,
            self::Purchasing => ClientPhase::Purchase,
            self::InTransit => ClientPhase::Transport,
            self::Paperwork => ClientPhase::Paperwork,
            self::ReadyForDelivery, self::Delivered, self::Cancelled => ClientPhase::Delivery,
        };
    }

    /**
     * Estado equivalente del coche (cars.status) para que kanban, mapa y
     * listados existentes sigan coherentes. null = no tocar.
     */
    public function carStatus(): ?string
    {
        return match ($this) {
            self::Draft, self::AwaitingSignature, self::ContractRejected, self::AwaitingDeposit,
            self::Inspecting, self::AwaitingClientDecision => 'Reserved',
            self::Purchasing => 'Purchased',
            self::InTransit => 'In_transit',
            self::Paperwork, self::ReadyForDelivery => 'Processing',
            self::Delivered => 'Delivered',
            self::SearchingReplacement, self::Cancelled => null,
        };
    }

    /** Variante de color para los badges del panel. */
    public function badgeVariant(): string
    {
        return match ($this) {
            self::Delivered => 'success',
            self::Cancelled, self::ContractRejected => 'danger',
            self::AwaitingSignature, self::AwaitingDeposit, self::AwaitingClientDecision => 'warning',
            self::SearchingReplacement => 'warning',
            default => 'blue',
        };
    }

    /** @return list<self> */
    public static function active(): array
    {
        return array_values(array_filter(self::cases(), fn (self $state): bool => ! $state->isTerminal()));
    }

    /** @return array<string, string> */
    public static function options(): array
    {
        $options = [];
        foreach (self::cases() as $state) {
            $options[$state->value] = $state->label();
        }

        return $options;
    }
}
