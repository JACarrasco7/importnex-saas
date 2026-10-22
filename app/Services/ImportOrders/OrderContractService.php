<?php

namespace App\Services\ImportOrders;

use App\Enums\ContractStatus;
use App\Enums\ImportOrderState;
use App\Enums\OrderEventType;
use App\Events\OrderContractIssued;
use App\Events\OrderContractRejected;
use App\Events\OrderContractSigned;
use App\Events\OrderContractVoided;
use App\Models\Car;
use App\Models\ContractAcceptance;
use App\Models\ImportOrder;
use App\Models\PortalOtp;
use App\Models\User;
use App\Support\ContractDocument;
use App\Support\SpanishId;
use Illuminate\Support\Facades\DB;

/**
 * Contrato del encargo: emitir (anulando el anterior), registrar que se ha
 * visto, firmar con código de verificación, rechazar, anular si cambian las
 * cifras y caducar.
 */
class OrderContractService
{
    public function __construct(
        private ImportOrderService $orders,
        private OrderPaymentService $payments,
        private PortalOtpService $otps,
        private OrderTimeline $timeline,
    ) {}

    /** @throws OrderFlowException */
    public function issue(ImportOrder $order, User $user): ContractAcceptance
    {
        $issuable = [ImportOrderState::Draft, ImportOrderState::AwaitingSignature, ImportOrderState::ContractRejected];
        if (! in_array($order->state, $issuable, true)) {
            throw new OrderFlowException('El contrato solo se puede emitir antes de que esté firmado.');
        }

        $client = $order->client;
        if (! $client?->email) {
            throw new OrderFlowException('Falta el email del cliente: lo necesitamos para enviarle el contrato y el código de firma. Edítalo en la ficha del cliente.', 'email');
        }
        if (! $order->car) {
            throw new OrderFlowException('El encargo no tiene coche asignado.');
        }

        $contract = DB::transaction(function () use ($order, $user, $client) {
            $this->voidOpen($order, 'Sustituido por una versión nueva');

            $car = $order->car;
            $snapshot = [
                'cliente_nombre' => $client->name ?: '—',
                'cliente_email' => $client->email,
                'cliente_dni' => $client->dni ?: '—',
                'vehiculo_marca' => (string) $car->brand,
                'vehiculo_modelo' => (string) ($car->version ?: $car->model),
                'vehiculo_anio' => (string) ($car->year ?: ''),
                'vehiculo_vin' => (string) ($car->vin ?: '—'),
                'precio_compra' => number_format((float) $car->purchase_price, 0, ',', '.'),
                'precio_total' => number_format((float) $car->calculateTotalCost(), 0, ',', '.'),
                'honorarios' => number_format((float) $order->fee_amount, 0, ',', '.'),
                'fecha_firma' => now()->format('d/m/Y H:i'),
                'contrato_id' => $order->reference.'-'.strtoupper(substr(bin2hex(random_bytes(3)), 0, 6)),
                '_clausulas' => config('contracts.clausulas'),
                '_prestador' => config('contracts.prestador'),
            ];

            $contract = ContractAcceptance::create([
                'organization_id' => $order->organization_id,
                'car_id' => $car->id,
                'import_order_id' => $order->id,
                'public_token' => ContractAcceptance::generateToken(),
                'contract_version' => (string) config('contracts.version'),
                'contract_hash' => '',
                'snapshot' => $snapshot,
                'client_email' => $client->email,
                'client_name' => $client->name,
                'client_dni' => $client->dni,
                'locale' => app()->getLocale(),
                'status' => ContractStatus::Sent,
                'expires_at' => now()->addDays((int) config('import_orders.contract_valid_days', 7)),
            ]);

            $this->timeline->record($order, OrderEventType::ContractIssued, 'Contrato enviado para firma', [
                'user' => $user,
                'visible' => true,
                'meta' => ['contract_id' => $contract->id, 'version' => $contract->contract_version],
            ]);

            if ($order->state !== ImportOrderState::AwaitingSignature) {
                $this->orders->transition($order, ImportOrderState::AwaitingSignature, ['user' => $user]);
            }

            return $contract;
        });

        OrderContractIssued::dispatch($order->fresh(), $contract);

        return $contract;
    }

    public function markViewed(ImportOrder $order, ContractAcceptance $contract): void
    {
        if ($contract->status !== ContractStatus::Sent) {
            return;
        }

        $contract->forceFill(['status' => ContractStatus::Viewed, 'viewed_at' => now()])->save();
        $this->timeline->record($order, OrderEventType::ContractViewed, 'El cliente ha abierto el contrato', ['actor' => 'client']);
    }

    /** @throws OrderFlowException */
    public function sendSignatureCode(ImportOrder $order, ContractAcceptance $contract, ?string $ip): PortalOtp
    {
        $this->assertSignable($order, $contract);

        return $this->otps->send($order, PortalOtp::PURPOSE_SIGN_CONTRACT, $contract, $ip);
    }

    /**
     * Firma el contrato tras validar el código.
     *
     * @param  array{name: string, dni: string, code: string}  $data
     *
     * @throws OrderFlowException
     */
    public function sign(ImportOrder $order, ContractAcceptance $contract, array $data, ?string $ip, ?string $userAgent): ContractAcceptance
    {
        $this->assertSignable($order, $contract);
        $otp = $this->otps->verify($order, PortalOtp::PURPOSE_SIGN_CONTRACT, $contract, $data['code']);

        $signed = DB::transaction(function () use ($order, $contract, $data, $ip, $userAgent, $otp) {
            /** @var ContractAcceptance $locked */
            $locked = ContractAcceptance::query()->lockForUpdate()->findOrFail($contract->id);
            if ($locked->accepted_at) {
                throw new OrderFlowException('Este contrato ya está firmado.');
            }

            $snapshot = $locked->snapshot ?? [];
            $name = trim($data['name']);
            $dni = SpanishId::normalize($data['dni']);

            $corrections = [];
            foreach (['cliente_nombre' => $name, 'cliente_dni' => $dni] as $key => $value) {
                $original = $snapshot[$key] ?? null;
                if ($original && $original !== '—' && $original !== $value) {
                    $corrections[$key] = ['original' => $original, 'firmado' => $value];
                }
                $snapshot[$key] = $value;
            }
            $snapshot['fecha_firma'] = now()->format('d/m/Y H:i');

            $locked->snapshot = $snapshot;
            $locked->client_name = $name;
            $locked->client_dni = $dni;
            $locked->contract_hash = ContractAcceptance::hashContract($locked->getContractText());
            $locked->accepted_at = now();
            $locked->accepted_ip = $ip;
            $locked->user_agent = mb_substr((string) $userAgent, 0, 191);
            $locked->status = ContractStatus::Signed;
            $locked->otp_verified_at = $otp->consumed_at;
            $locked->otp_sent_to = $otp->sent_to;
            $locked->signer_corrections = $corrections ?: null;
            $locked->save();

            $client = $order->client;
            if ($client && (! $client->dni || $client->dni !== $dni)) {
                $client->forceFill(['dni' => $dni])->save();
            }

            $this->timeline->record($order, OrderEventType::ContractSigned, 'Contrato firmado', [
                'actor' => 'client',
                'visible' => true,
                'ip' => $ip,
                'meta' => ['contract_id' => $locked->id, 'hash' => $locked->contract_hash, 'corrections' => $corrections],
            ]);

            $this->payments->scheduleFees($order);
            $this->orders->transition($order, ImportOrderState::AwaitingDeposit, ['actor' => 'client', 'ip' => $ip]);

            if ($order->fresh()->isDepositSettled()) {
                $this->orders->transition($order, ImportOrderState::Inspecting, ['reason' => 'Sin honorarios: no hay señal que cobrar']);
            }

            return $locked;
        });

        ContractDocument::archive($signed);
        OrderContractSigned::dispatch($order->fresh(), $signed->fresh());

        return $signed;
    }

    /** @throws OrderFlowException */
    public function reject(ImportOrder $order, ContractAcceptance $contract, string $reason, ?string $ip): void
    {
        $this->assertSignable($order, $contract);

        DB::transaction(function () use ($order, $contract, $reason, $ip) {
            $contract->forceFill([
                'status' => ContractStatus::Rejected,
                'rejected_at' => now(),
                'rejection_reason' => $reason,
            ])->save();

            $this->timeline->record($order, OrderEventType::ContractRejected, 'Has comentado dudas sobre el contrato', [
                'actor' => 'client',
                'visible' => true,
                'body' => $reason,
                'ip' => $ip,
            ]);

            $this->orders->transition($order, ImportOrderState::ContractRejected, ['actor' => 'client', 'reason' => $reason, 'ip' => $ip]);
        });

        OrderContractRejected::dispatch($order->fresh(), $contract->fresh());
    }

    /** Anula el contrato abierto del encargo (manual desde el panel). */
    public function void(ImportOrder $order, string $reason, ?User $user = null): int
    {
        $voided = $this->voidOpen($order, $reason, $user);

        foreach ($voided as $contract) {
            OrderContractVoided::dispatch($order, $contract, $reason);
        }

        return count($voided);
    }

    /**
     * Si cambian precio u honorarios de un coche con contrato pendiente, el
     * contrato deja de reflejar las cifras: se anula y se avisa al equipo.
     */
    public function handleCarFiguresChanged(Car $car): void
    {
        $orders = ImportOrder::query()
            ->where('car_id', $car->id)
            ->whereIn('state', [ImportOrderState::Draft->value, ImportOrderState::AwaitingSignature->value, ImportOrderState::ContractRejected->value])
            ->get();

        foreach ($orders as $order) {
            if ($car->wasChanged('professional_fees') && $car->professional_fees !== null) {
                $order->forceFill(['fee_amount' => (float) $car->professional_fees])->save();
            }
            $this->void($order, 'Han cambiado el precio o los honorarios del coche: reemite el contrato.');
        }
    }

    /** Marca como caducados los contratos abiertos con plazo vencido. */
    public function expireOverdue(): int
    {
        $expired = ContractAcceptance::query()
            ->whereIn('status', [ContractStatus::Sent->value, ContractStatus::Viewed->value])
            ->whereNotNull('expires_at')
            ->where('expires_at', '<', now())
            ->get();

        foreach ($expired as $contract) {
            $contract->forceFill(['status' => ContractStatus::Expired])->save();
            if ($contract->importOrder) {
                $this->timeline->record($contract->importOrder, OrderEventType::ContractExpired, 'El contrato ha caducado sin firmar', []);
                OrderContractVoided::dispatch($contract->importOrder, $contract, 'Caducado sin firmar');
            }
        }

        return $expired->count();
    }

    /** @return list<ContractAcceptance> */
    private function voidOpen(ImportOrder $order, string $reason, ?User $user = null): array
    {
        $open = $order->contracts()
            ->whereIn('status', [ContractStatus::Sent->value, ContractStatus::Viewed->value])
            ->get();

        foreach ($open as $contract) {
            $contract->forceFill([
                'status' => ContractStatus::Voided,
                'voided_at' => now(),
                'void_reason' => mb_substr($reason, 0, 255),
            ])->save();

            $this->timeline->record($order, OrderEventType::ContractVoided, 'Contrato anulado', [
                'user' => $user,
                'body' => $reason,
                'meta' => ['contract_id' => $contract->id],
            ]);
        }

        return $open->all();
    }

    /** @throws OrderFlowException */
    private function assertSignable(ImportOrder $order, ContractAcceptance $contract): void
    {
        if ($contract->import_order_id !== $order->id) {
            throw new OrderFlowException('Contrato no encontrado.');
        }
        if ($contract->accepted_at) {
            throw new OrderFlowException('Este contrato ya está firmado.');
        }
        if (! $contract->isSignable()) {
            throw new OrderFlowException('Este contrato ya no está vigente. Te enviaremos una versión actualizada.');
        }
    }
}
