<?php

namespace App\Services\ImportOrders;

use App\Enums\ApprovalStatus;
use App\Enums\ClientPhase;
use App\Enums\ContractStatus;
use App\Enums\ImportOrderState;
use App\Enums\PaymentStatus;
use App\Models\CarPhoto;
use App\Models\ContractAcceptance;
use App\Models\ImportOrder;
use App\Models\OrderApproval;
use App\Models\OrderMedia;
use App\Models\OrderMessage;
use App\Models\User;
use App\Support\CarChecklistDefinitions;
use App\Support\ContractDocument;
use App\Support\WhatsAppLink;
use Illuminate\Support\Collection;

/**
 * Construye las props del portal del cliente con una LISTA BLANCA explícita:
 * nunca salen precio de compra interno, IEDMT, márgenes, comparables, notas
 * internas ni fotos/mensajes internos.
 */
class PortalPresenter
{
    /** @return array<string, mixed> */
    public function present(ImportOrder $order, string $token): array
    {
        $order->loadMissing(['client', 'car', 'assignee']);
        $state = $order->state;
        $contract = $this->portalContract($order);

        return [
            'token' => $token,
            'order' => [
                'reference' => $order->reference,
                'state' => $state->value,
                'state_label' => $state->clientLabel(),
                'is_tracking_visible' => $state->isTrackingVisible(),
                'is_closed' => $state->isTerminal(),
                'is_cancelled' => $state === ImportOrderState::Cancelled,
                'expected_delivery_date' => $order->expected_delivery_date?->toDateString(),
                'cancellation_requested_at' => $order->cancellation_requested_at?->toIso8601String(),
                'can_request_cancellation' => $state->isBeforePurchase() && ! $state->isTerminal() && ! $order->cancellation_requested_at,
                'phases' => $this->phases($state),
            ],
            'client' => [
                'first_name' => explode(' ', trim((string) $order->client?->name))[0] ?? '',
                'masked_email' => PortalOtpService::maskEmail($order->client?->email),
            ],
            'vehicle' => $this->vehicle($order, $token),
            'contract' => $contract ? $this->contract($order, $contract, $token) : null,
            'payments' => $this->payments($order),
            'bank' => [
                ...$order->organization->brand()->bank(),
                'concept' => 'Encargo '.$order->reference,
            ],
            'orgBrand' => $order->organization->brand()->toPublicArray(),
            'approvals' => $this->approvals($order, $token),
            'milestones' => $state->isTrackingVisible() ? $this->milestones($order) : [],
            'inspection' => $state->isTrackingVisible() ? $this->inspection($order) : null,
            'gallery' => $this->gallery($order, $token),
            'feed' => $this->feed($order),
            'contact' => $this->contact($order),
        ];
    }

    /** @return array{title: string, description: string, url: string, image: ?string} */
    public function ogMeta(ImportOrder $order, string $token): array
    {
        $vehicle = $order->car ? trim("{$order->car->brand} {$order->car->model}") : 'tu coche';
        $cover = $this->carPhotos($order)->first();

        return [
            'title' => "Tu {$vehicle} · ".$order->organization->brand()->name(),
            'description' => $order->state->clientLabel().' · Encargo '.$order->reference,
            'url' => route('portal.show', $token),
            'image' => $cover ? route('portal.car-photo', ['token' => $token, 'photo' => $cover->id]) : null,
        ];
    }

    /** Contrato a mostrar: el abierto, si no el firmado, si no el último rechazado/caducado. */
    public function portalContract(ImportOrder $order): ?ContractAcceptance
    {
        $contracts = $order->contracts()->whereNot('status', ContractStatus::Voided->value)->latest('id')->get();

        return $contracts->first(fn (ContractAcceptance $c) => $c->isSignable())
            ?? $contracts->first(fn (ContractAcceptance $c) => $c->status === ContractStatus::Signed)
            ?? $contracts->first();
    }

    /** @return list<array{key: string, label: string, description: string, status: string}> */
    private function phases(ImportOrderState $state): array
    {
        $current = $state->clientPhase()->position();
        $delivered = $state === ImportOrderState::Delivered;

        return array_map(fn (ClientPhase $phase) => [
            'key' => $phase->value,
            'label' => $phase->label(),
            'description' => $phase->description(),
            'status' => match (true) {
                $delivered || $phase->position() < $current => 'done',
                $phase->position() === $current => 'current',
                default => 'upcoming',
            },
        ], ClientPhase::cases());
    }

    /** @return array<string, mixed>|null */
    private function vehicle(ImportOrder $order, string $token): ?array
    {
        $car = $order->car;
        if (! $car) {
            return null;
        }

        return [
            'brand' => $car->brand,
            'model' => $car->model,
            'version' => $car->version,
            'year' => $car->year,
            'mileage' => $car->mileage,
            'fuel' => $car->fuel,
            'transmission' => $car->transmission,
            'color' => $car->color,
            'origin' => $car->pais_origen,
            'photos' => $this->carPhotos($order)->map(fn (CarPhoto $photo) => [
                'id' => $photo->id,
                'url' => route('portal.car-photo', ['token' => $token, 'photo' => $photo->id]),
            ])->values()->all(),
        ];
    }

    /** @return Collection<int, CarPhoto> */
    private function carPhotos(ImportOrder $order)
    {
        if (! $order->car_id) {
            return collect();
        }

        return CarPhoto::withoutGlobalScopes()
            ->where('car_id', $order->car_id)
            ->whereIn('photo_type', ['exterior', 'interior', 'engine'])
            ->orderBy('sort_order')
            ->limit(8)
            ->get();
    }

    /** @return array<string, mixed> */
    private function contract(ImportOrder $order, ContractAcceptance $contract, string $token): array
    {
        $status = $contract->effectiveStatus();
        $snapshot = $contract->snapshot ?? [];
        $clauses = (array) ($snapshot['_clausulas'] ?? config('contracts.clausulas'));
        $fee = (float) $order->fee_amount;

        return [
            'id' => $contract->id,
            'status' => $status->value,
            'status_label' => $status->label(),
            'is_signable' => $contract->isSignable(),
            'expires_at' => $contract->expires_at?->toIso8601String(),
            'signed_at' => $contract->accepted_at?->toIso8601String(),
            'hash' => $contract->accepted_at ? $contract->contract_hash : null,
            'version' => $contract->contract_version,
            'html' => ContractDocument::toHtml($contract->getContractText()),
            'clauses_count' => count($clauses),
            'pdf_url' => $contract->accepted_at ? route('portal.contract.pdf', ['token' => $token]) : null,
            'prefill_name' => $contract->client_name ?: ($order->client?->name ?? ''),
            'has_dni_on_file' => (bool) $order->client?->dni,
            'masked_dni' => $order->client?->maskedDni(),
            'summary' => [
                'vehicle' => trim(($snapshot['vehiculo_marca'] ?? '').' '.($snapshot['vehiculo_modelo'] ?? '').' ('.($snapshot['vehiculo_anio'] ?? '').')'),
                'fee' => $fee,
                'deposit' => $order->depositAmount(),
                'purchase_price' => $snapshot['precio_compra'] ?? null,
            ],
            'legal_notice' => $contract->accepted_at ? config('contracts.ui.legal_notice') : config('contracts.ui.legal_notice_pending'),
        ];
    }

    /** @return list<array<string, mixed>> */
    private function payments(ImportOrder $order): array
    {
        return $order->payments()
            ->where('status', '!=', PaymentStatus::Cancelled->value)
            ->orderBy('id')
            ->get()
            ->map(fn ($payment) => [
                'id' => $payment->id,
                'concept' => $payment->concept,
                'amount' => (float) $payment->amount,
                'status' => $payment->status->value,
                'status_label' => $payment->status->label(),
                'due_date' => $payment->due_date?->toDateString(),
                'paid_at' => $payment->paid_at?->toIso8601String(),
                'method_label' => $payment->method?->label(),
                'stripe_payment_link' => $payment->status === PaymentStatus::Pending ? $payment->stripe_payment_link : null,
            ])->all();
    }

    /** @return list<array<string, mixed>> */
    private function approvals(ImportOrder $order, string $token): array
    {
        return $order->approvals()
            ->with('media')
            ->whereNot('status', ApprovalStatus::Withdrawn->value)
            ->latest('id')
            ->limit(10)
            ->get()
            ->map(fn (OrderApproval $approval) => [
                'id' => $approval->id,
                'kind' => $approval->kind->value,
                'title' => $approval->title,
                'description' => $approval->description,
                'amount' => $approval->amount !== null ? (float) $approval->amount : null,
                'status' => $approval->status->value,
                'status_label' => $approval->status->label(),
                'decision_label' => $approval->decision?->label(),
                'client_comment' => $approval->client_comment,
                'is_pending' => $approval->isPending() && ! ($approval->due_at?->isPast() ?? false),
                'due_at' => $approval->due_at?->toIso8601String(),
                'requires_otp' => $approval->requiresOtp(),
                'media' => $approval->media->map(fn (OrderMedia $media) => $this->mediaItem($media, $token))->all(),
            ])->all();
    }

    /** @return list<array<string, mixed>> */
    private function milestones(ImportOrder $order): array
    {
        if (! $order->car) {
            return [];
        }
        $rows = $order->car->checklists()->milestones()->get()->keyBy('item_key');

        return collect(CarChecklistDefinitions::milestones())->map(fn (array $def) => [
            'key' => $def['key'],
            'name' => $def['name'],
            'completed' => (bool) ($rows->get($def['key'])?->completed),
            'completed_at' => $rows->get($def['key'])?->completed_at?->toDateString(),
        ])->values()->all();
    }

    /** @return array{percent: int, completed: int, total: int}|null */
    private function inspection(ImportOrder $order): ?array
    {
        if (! $order->car) {
            return null;
        }
        $rows = $order->car->checklists()->inspections()->get();
        $total = $rows->count();
        if ($total === 0) {
            return null;
        }
        $completed = $rows->where('completed', true)->count();

        return ['percent' => (int) round($completed / $total * 100), 'completed' => $completed, 'total' => $total];
    }

    /** @return list<array<string, mixed>> */
    private function gallery(ImportOrder $order, string $token): array
    {
        return $order->media()
            ->where('visibility', OrderMedia::VISIBILITY_CLIENT)
            ->whereNull('order_approval_id')
            ->latest('id')
            ->limit(60)
            ->get()
            ->map(fn (OrderMedia $media) => $this->mediaItem($media, $token))
            ->all();
    }

    /** @return array<string, mixed> */
    private function mediaItem(OrderMedia $media, string $token): array
    {
        return [
            'id' => $media->id,
            'url' => route('portal.media', ['token' => $token, 'media' => $media->id]),
            'caption' => $media->caption,
            'name' => $media->original_name,
            'is_image' => $media->isImage(),
            'collection' => $media->collection,
            'created_at' => $media->created_at?->toIso8601String(),
        ];
    }

    /** @return list<array<string, mixed>> */
    private function feed(ImportOrder $order): array
    {
        $events = $order->events()
            ->where('visible_to_client', true)
            ->latest('id')
            ->limit(40)
            ->get()
            ->map(fn ($event) => [
                'key' => 'e'.$event->id,
                'kind' => 'event',
                'type' => $event->type->value,
                'title' => $event->title,
                'body' => $event->body,
                'author' => $event->actor_type === 'client' ? 'client' : 'team',
                'at' => $event->created_at?->toIso8601String(),
            ]);

        $messages = $order->messages()
            ->where('visibility', OrderMessage::VISIBILITY_CLIENT)
            ->with('user:id,name')
            ->latest('id')
            ->limit(60)
            ->get()
            ->map(fn (OrderMessage $message) => [
                'key' => 'm'.$message->id,
                'kind' => 'message',
                'type' => 'message',
                'title' => null,
                'body' => $message->body,
                'author' => $message->author_type === OrderMessage::AUTHOR_CLIENT ? 'client' : 'team',
                'author_name' => $message->author_type === OrderMessage::AUTHOR_TEAM ? ($message->user?->name ?? $order->organization->brand()->name()) : null,
                'at' => $message->created_at?->toIso8601String(),
            ]);

        return $events->concat($messages)
            ->sortByDesc('at')
            ->values()
            ->take(80)
            ->all();
    }

    /** @return array<string, mixed> */
    private function contact(ImportOrder $order): array
    {
        $manager = $order->assignee ?? User::query()
            ->where('organization_id', $order->organization_id)
            ->where('role', 'owner')
            ->orderBy('id')
            ->first();

        $brand = $order->organization->brand();
        $phone = (string) (config('import_orders.contact_phone') ?: $brand->get('whatsapp'));
        $clientName = explode(' ', trim((string) $order->client?->name))[0] ?? '';
        $text = trim("Hola, soy {$clientName}. Te escribo por mi encargo {$order->reference} ({$order->vehicleLabel()}).");

        return [
            'manager_name' => $manager?->name,
            'company_name' => $brand->name(),
            'phone' => $phone,
            'email' => config('import_orders.contact_email') ?: $brand->get('email'),
            'schedule' => config('import_orders.contact_schedule'),
            'whatsapp_url' => WhatsAppLink::to($phone, $text),
        ];
    }
}
