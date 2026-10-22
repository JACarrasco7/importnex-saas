<?php

namespace App\Services\ImportOrders;

use App\Enums\ApprovalKind;
use App\Enums\ClientPhase;
use App\Enums\ImportOrderState;
use App\Enums\PaymentMethod;
use App\Enums\UnitDecision;
use App\Http\Controllers\ImportOrders\ImportOrderController;
use App\Models\Car;
use App\Models\ContractAcceptance;
use App\Models\ImportOrder;
use App\Models\OrderApproval;
use App\Models\OrderEvent;
use App\Models\OrderMedia;
use App\Models\OrderMessage;
use App\Models\OrderPayment;
use App\Support\CarChecklistDefinitions;
use App\Support\WhatsAppLink;

/** Props del panel interno de encargos (bandeja y ficha). */
class OrderPanelPresenter
{
    /** @return array<string, mixed> */
    public function listItem(ImportOrder $order): array
    {
        return [
            'id' => $order->id,
            'reference' => $order->reference,
            'state' => $order->state->value,
            'state_label' => $order->state->label(),
            'state_variant' => $order->state->badgeVariant(),
            'client_name' => $order->client?->name,
            'vehicle' => $order->vehicleLabel(),
            'fee_amount' => (float) $order->fee_amount,
            'updated_at' => $order->updated_at?->toIso8601String(),
            'state_changed_at' => $order->state_changed_at?->toIso8601String(),
            'unread_messages' => (int) ($order->unread_messages_count ?? 0),
            'pending_approvals' => (int) ($order->pending_approvals_count ?? 0),
            'pending_payments' => (int) ($order->pending_payments_count ?? 0),
            'cancellation_requested' => (bool) $order->cancellation_requested_at,
        ];
    }

    /** @return array<string, mixed> */
    public function present(ImportOrder $order): array
    {
        $order->load(['client', 'car', 'carRequest', 'assignee:id,name']);
        $contracts = $order->contracts()->latest('id')->get();
        $portalUrl = $order->portalUrl();
        $client = $order->client;

        return [
            'order' => [
                'id' => $order->id,
                'reference' => $order->reference,
                'state' => $order->state->value,
                'state_label' => $order->state->label(),
                'state_variant' => $order->state->badgeVariant(),
                'client_state_label' => $order->state->clientLabel(),
                'state_changed_at' => $order->state_changed_at?->toIso8601String(),
                'fee_amount' => (float) $order->fee_amount,
                'deposit_amount' => $order->depositAmount(),
                'fee_locked' => ! in_array($order->state, [ImportOrderState::Draft, ImportOrderState::ContractRejected], true),
                'expected_delivery_date' => $order->expected_delivery_date?->toDateString(),
                'assigned_to' => $order->assigned_to,
                'internal_notes' => $order->internal_notes,
                'cancellation_requested_at' => $order->cancellation_requested_at?->toIso8601String(),
                'cancellation_request_reason' => $order->cancellation_request_reason,
                'cancel_reason' => $order->cancel_reason,
                'created_at' => $order->created_at?->toIso8601String(),
                'is_terminal' => $order->state->isTerminal(),
                'portal' => [
                    'url' => $portalUrl,
                    'views' => (int) $order->portal_views,
                    'last_seen_at' => $order->portal_last_seen_at?->toIso8601String(),
                    'rotated_at' => $order->portal_token_rotated_at?->toIso8601String(),
                ],
            ],
            'phases' => $this->phases($order->state),
            'transitions' => array_map(fn (ImportOrderState $state) => [
                'value' => $state->value,
                'label' => $state->label(),
                'needs_reason' => $state === ImportOrderState::Cancelled,
                'is_danger' => $state === ImportOrderState::Cancelled,
                'can_force' => $order->state === ImportOrderState::AwaitingDeposit && $state === ImportOrderState::Inspecting && ! $order->isDepositSettled(),
            ], $order->state->manualTransitions()),
            'next_action' => $this->nextAction($order, $contracts->first()),
            'client' => $client ? [
                'id' => $client->id,
                'name' => $client->name,
                'email' => $client->email,
                'phone' => $client->phone,
                'masked_dni' => $client->maskedDni(),
                'whatsapp_number' => $client->whatsappNumber(),
            ] : null,
            'car' => $order->car ? [
                'id' => $order->car->id,
                'label' => $order->vehicleLabel(),
                'year' => $order->car->year,
                'status' => $order->car->status,
                'purchase_price' => $order->car->purchase_price !== null ? (float) $order->car->purchase_price : null,
                'url' => route('cars.show', $order->car->id),
            ] : null,
            'car_request' => $order->carRequest ? [
                'id' => $order->carRequest->id,
                'url' => route('car-requests.show', $order->carRequest->id),
            ] : null,
            'contracts' => $contracts->map(fn (ContractAcceptance $c) => $this->contract($c))->all(),
            'payments' => $order->payments()->with('recorder:id,name')->orderBy('id')->get()->map(fn (OrderPayment $p) => $this->payment($p))->all(),
            'approvals' => $order->approvals()->with('media')->latest('id')->get()->map(fn (OrderApproval $a) => $this->approval($order, $a))->all(),
            'messages' => $order->messages()->with('user:id,name')->orderBy('id')->get()->map(fn (OrderMessage $m) => [
                'id' => $m->id,
                'author_type' => $m->author_type,
                'author_name' => $m->author_type === OrderMessage::AUTHOR_CLIENT ? $client?->name : ($m->user?->name ?? 'Equipo'),
                'body' => $m->body,
                'internal' => $m->visibility === OrderMessage::VISIBILITY_INTERNAL,
                'read_by_client_at' => $m->read_by_client_at?->toIso8601String(),
                'created_at' => $m->created_at?->toIso8601String(),
            ])->all(),
            'media' => $order->media()->whereNull('order_approval_id')->latest('id')->get()->map(fn (OrderMedia $m) => $this->media($order, $m))->all(),
            'events' => $order->events()->with('actor:id,name')->latest('id')->limit(100)->get()->map(fn (OrderEvent $e) => [
                'id' => $e->id,
                'type' => $e->type->value,
                'title' => $e->title,
                'body' => $e->body,
                'actor' => $e->actor_type === 'client' ? ($client?->name ?? 'Cliente') : ($e->actor?->name ?? 'Sistema'),
                'actor_type' => $e->actor_type,
                'visible_to_client' => $e->visible_to_client,
                'created_at' => $e->created_at?->toIso8601String(),
            ])->all(),
            'milestones' => $this->milestones($order),
            'replacement_cars' => $order->state === ImportOrderState::SearchingReplacement ? $this->replacementCars($order) : [],
            'team' => ImportOrderController::teamMembers((int) $order->organization_id),
            'whatsapp' => $this->whatsappLinks($order, $portalUrl),
            'warnings' => $this->warnings($order),
            'options' => [
                'payment_methods' => array_map(fn (PaymentMethod $m) => ['value' => $m->value, 'label' => $m->label()], PaymentMethod::cases()),
                'approval_kinds' => array_map(fn (ApprovalKind $k) => ['value' => $k->value, 'label' => $k->label()], ApprovalKind::cases()),
                'unit_decisions' => array_map(fn (UnitDecision $d) => ['value' => $d->value, 'label' => $d->label()], UnitDecision::cases()),
                'media_collections' => OrderMedia::COLLECTIONS,
                'otp_amount_threshold' => (float) config('import_orders.otp_amount_threshold', 300),
            ],
        ];
    }

    /** @return list<array{key: string, label: string, status: string}> */
    private function phases(ImportOrderState $state): array
    {
        $current = $state->clientPhase()->position();

        return array_map(fn ($phase) => [
            'key' => $phase->value,
            'label' => $phase->label(),
            'status' => $state === ImportOrderState::Delivered || $phase->position() < $current ? 'done' : ($phase->position() === $current ? 'current' : 'upcoming'),
        ], ClientPhase::cases());
    }

    /**
     * La única «siguiente acción» recomendada al equipo para este encargo.
     *
     * @return array{key: string, label: string, hint: string}|null
     */
    private function nextAction(ImportOrder $order, ?ContractAcceptance $latestContract): ?array
    {
        if ($order->cancellation_requested_at && ! $order->state->isTerminal()) {
            return ['key' => 'cancellation', 'label' => 'El cliente pide cancelar', 'hint' => 'Llámale y decide: cancela el encargo o sigue adelante.'];
        }

        return match ($order->state) {
            ImportOrderState::Draft => ['key' => 'issue_contract', 'label' => 'Enviar contrato', 'hint' => 'Revisa honorarios y datos del cliente, y envía el contrato para firma.'],
            ImportOrderState::ContractRejected => ['key' => 'issue_contract', 'label' => 'Resolver dudas y reemitir', 'hint' => 'El cliente tiene dudas sobre el contrato. Habla con él y envía una versión nueva.'],
            ImportOrderState::AwaitingSignature => $latestContract && $latestContract->isSignable()
                ? ['key' => 'remind_signature', 'label' => 'Esperando firma', 'hint' => 'Puedes recordárselo por WhatsApp. El sistema enviará recordatorios por email.']
                : ['key' => 'issue_contract', 'label' => 'Reemitir contrato', 'hint' => 'El contrato está anulado o caducado: envía uno nuevo.'],
            ImportOrderState::AwaitingDeposit => ['key' => 'record_deposit', 'label' => 'Registrar la señal', 'hint' => 'Cuando recibas la transferencia, márcala como pagada: el encargo pasará solo a Inspección.'],
            ImportOrderState::Inspecting => ['key' => 'inspection', 'label' => 'Inspeccionar y decidir', 'hint' => 'Sube las fotos de la inspección. Si todo está bien, pasa a Compra; si hay defectos, pide decisión al cliente.'],
            ImportOrderState::AwaitingClientDecision => ['key' => 'wait_decision', 'label' => 'Esperando decisión del cliente', 'hint' => 'Recuérdaselo por WhatsApp si tarda.'],
            ImportOrderState::SearchingReplacement => ['key' => 'replace', 'label' => 'Asignar otro coche', 'hint' => 'Elige la nueva unidad: el encargo vuelve a Inspección sin repetir la firma.'],
            ImportOrderState::Purchasing => ['key' => 'purchase', 'label' => 'Cerrar la compra', 'hint' => 'Cuando el coche esté pagado y recogido, pásalo a «En transporte».'],
            ImportOrderState::InTransit => ['key' => 'transit', 'label' => 'Seguir el transporte', 'hint' => 'Sube fotos de la carga y la llegada. Al llegar, pasa a «Trámites».'],
            ImportOrderState::Paperwork => ['key' => 'paperwork', 'label' => 'Completar trámites', 'hint' => 'Marca COC, ITV, IEDMT y matriculación en la checklist de hitos.'],
            ImportOrderState::ReadyForDelivery => ['key' => 'deliver', 'label' => 'Entregar y cobrar el resto', 'hint' => 'Registra el pago final y marca el encargo como entregado.'],
            ImportOrderState::Delivered, ImportOrderState::Cancelled => null,
        };
    }

    /** @return array<string, mixed> */
    private function contract(ContractAcceptance $contract): array
    {
        $status = $contract->effectiveStatus();

        return [
            'id' => $contract->id,
            'status' => $status->value,
            'status_label' => $status->label(),
            'version' => $contract->contract_version,
            'created_at' => $contract->created_at?->toIso8601String(),
            'expires_at' => $contract->expires_at?->toIso8601String(),
            'viewed_at' => $contract->viewed_at?->toIso8601String(),
            'signed_at' => $contract->accepted_at?->toIso8601String(),
            'signed_ip' => $contract->accepted_ip,
            'hash' => $contract->accepted_at ? $contract->contract_hash : null,
            'otp_sent_to' => $contract->otp_sent_to,
            'voided_at' => $contract->voided_at?->toIso8601String(),
            'void_reason' => $contract->void_reason,
            'rejected_at' => $contract->rejected_at?->toIso8601String(),
            'rejection_reason' => $contract->rejection_reason,
            'reminders_sent' => (int) $contract->reminders_sent,
            'signer_corrections' => $contract->signer_corrections,
            'download_url' => $contract->accepted_at ? route('import-orders.contracts.download', [$contract->import_order_id, $contract->id]) : null,
            'is_signable' => $contract->isSignable(),
        ];
    }

    /** @return array<string, mixed> */
    private function payment(OrderPayment $payment): array
    {
        return [
            'id' => $payment->id,
            'kind' => $payment->kind->value,
            'kind_label' => $payment->kind->label(),
            'concept' => $payment->concept,
            'amount' => (float) $payment->amount,
            'status' => $payment->status->value,
            'status_label' => $payment->status->label(),
            'method' => $payment->method?->value,
            'method_label' => $payment->method?->label(),
            'reference' => $payment->reference,
            'stripe_payment_link' => $payment->stripe_payment_link,
            'due_date' => $payment->due_date?->toDateString(),
            'paid_at' => $payment->paid_at?->toIso8601String(),
            'recorded_by' => $payment->recorder?->name,
            'notes' => $payment->notes,
        ];
    }

    /** @return array<string, mixed> */
    private function approval(ImportOrder $order, OrderApproval $approval): array
    {
        return [
            'id' => $approval->id,
            'kind' => $approval->kind->value,
            'kind_label' => $approval->kind->label(),
            'title' => $approval->title,
            'description' => $approval->description,
            'amount' => $approval->amount !== null ? (float) $approval->amount : null,
            'status' => $approval->status->value,
            'status_label' => $approval->status->label(),
            'decision_label' => $approval->decision?->label(),
            'client_comment' => $approval->client_comment,
            'due_at' => $approval->due_at?->toIso8601String(),
            'resolved_at' => $approval->resolved_at?->toIso8601String(),
            'otp_verified' => (bool) $approval->otp_verified_at,
            'is_pending' => $approval->isPending(),
            'media' => $approval->media->map(fn (OrderMedia $m) => $this->media($order, $m))->all(),
        ];
    }

    /** @return array<string, mixed> */
    private function media(ImportOrder $order, OrderMedia $media): array
    {
        return [
            'id' => $media->id,
            'url' => route('import-orders.media.show', [$order->id, $media->id]),
            'name' => $media->original_name,
            'caption' => $media->caption,
            'collection' => $media->collection,
            'visibility' => $media->visibility,
            'is_image' => $media->isImage(),
            'size' => (int) $media->size,
            'created_at' => $media->created_at?->toIso8601String(),
        ];
    }

    /** @return list<array<string, mixed>> */
    private function milestones(ImportOrder $order): array
    {
        if (! $order->car) {
            return [];
        }
        $rows = $order->car->checklists()->withoutGlobalScopes()->milestones()->get()->keyBy('item_key');

        return collect(CarChecklistDefinitions::milestones())->map(function (array $def) use ($rows, $order) {
            $row = $rows->get($def['key']);

            return [
                'key' => $def['key'],
                'name' => $def['name'],
                'completed' => (bool) $row?->completed,
                'completed_at' => $row?->completed_at?->toDateString(),
                'toggle_url' => $row ? route('cars.checklists.toggle', [$order->car_id, $row->id]) : null,
            ];
        })->values()->all();
    }

    /** @return list<array{id: int, label: string}> */
    private function replacementCars(ImportOrder $order): array
    {
        return Car::withoutGlobalScope('organization')
            ->where('organization_id', $order->organization_id)
            ->where('id', '!=', $order->car_id)
            ->where(fn ($q) => $q->whereNull('client_id')->orWhere('client_id', $order->client_id))
            ->whereNotIn('status', ['Delivered', 'Discarded'])
            ->whereDoesntHave('importOrders', fn ($q) => $q->active())
            ->orderBy('purchase_price')
            ->limit(100)
            ->get(['id', 'brand', 'model', 'version', 'year', 'purchase_price'])
            ->map(fn (Car $car) => [
                'id' => $car->id,
                'label' => trim("#{$car->id} · {$car->brand} {$car->model} {$car->version} ({$car->year})").($car->purchase_price ? ' · '.number_format((float) $car->purchase_price, 0, ',', '.').' €' : ''),
            ])->all();
    }

    /** @return array<string, string|null> */
    private function whatsappLinks(ImportOrder $order, ?string $portalUrl): array
    {
        $phone = $order->client?->phone;
        $first = explode(' ', trim((string) $order->client?->name))[0] ?? '';
        $vehicle = $order->vehicleLabel();

        return [
            'has_phone' => (bool) $order->client?->whatsappNumber(),
            'portal' => $portalUrl ? WhatsAppLink::to($phone, "Hola {$first}, te paso el enlace privado para seguir tu encargo {$order->reference} ({$vehicle}): {$portalUrl}") : null,
            'contract' => $portalUrl ? WhatsAppLink::to($phone, "Hola {$first}, ya tienes el contrato de servicio de tu {$vehicle} listo para firmar desde el móvil (2 minutos): {$portalUrl}") : null,
            'reminder' => $portalUrl ? WhatsAppLink::to($phone, "Hola {$first}, te recuerdo que tienes pendiente un paso en tu encargo {$order->reference}. Lo tienes aquí: {$portalUrl}") : null,
            'payment' => $portalUrl ? WhatsAppLink::to($phone, "Hola {$first}, en este enlace tienes los datos para abonar la señal de tu encargo {$order->reference}: {$portalUrl}") : null,
        ];
    }

    /** @return list<array{key: string, message: string}> */
    private function warnings(ImportOrder $order): array
    {
        $warnings = [];
        if (! $order->client?->email) {
            $warnings[] = ['key' => 'client_email', 'message' => 'El cliente no tiene email: no podrá recibir el contrato ni el código de firma. Añádelo en su ficha.'];
        }
        if (! $order->client?->phone) {
            $warnings[] = ['key' => 'client_phone', 'message' => 'El cliente no tiene teléfono: los botones de WhatsApp abrirán el selector de chat.'];
        }
        $brand = $order->organization->brand();
        if ($missing = $brand->missingForContracts()) {
            $warnings[] = ['key' => 'company', 'message' => 'Faltan datos de tu empresa para el contrato ('.implode(', ', $missing).'). Complétalos en Ajustes → Datos de empresa.'];
        }
        if (! $brand->get('iban')) {
            $warnings[] = ['key' => 'iban', 'message' => 'No hay IBAN: el cliente no verá los datos de transferencia. Añádelo en Ajustes → Datos de empresa.'];
        }

        return $warnings;
    }
}
