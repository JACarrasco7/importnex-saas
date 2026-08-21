<?php

namespace App\Services;

use App\Models\Car;
use App\Models\ContractAcceptance;

/**
 * Crea el contrato de un coche con todos los datos del cliente + vehículo
 * ya rellenados. La aceptación posterior (click del cliente) la gestiona
 * PublicContractController@accept.
 */
class ContractService
{
    /**
     * Crea (o reusa) la fila de ContractAcceptance para un coche,
     * pre-rellenando los placeholders con los valores actuales.
     */
    public function ensureForCar(Car $car): ContractAcceptance
    {
        $existing = $car->contractAcceptances()->latest()->first();
        if ($existing && ! $existing->accepted_at) {
            return $existing; // sigue pendiente de firma
        }
        if ($existing && $existing->accepted_at) {
            // Ya firmado: crear uno nuevo (el viejo queda en histórico).
            return $this->create($car);
        }

        return $this->create($car);
    }

    private function create(Car $car): ContractAcceptance
    {
        $client = $car->client;
        $clientName = $client ? trim((string) ($client->name ?? '')) : null;

        // El modelo Client no tiene campos email/DNI; se rellenan al firmar.
        $clientDni = null;
        $clientEmail = null;

        // Honorarios reales del servicio (professional_fees del coche), no un
        // default fijo que pueda no coincidir con lo facturado.
        $honorarios = (float) ($car->professional_fees
            ?: config('contracts.honorarios_default', 1500));

        $snapshot = [
            'cliente_nombre' => $clientName ?: '—',
            'cliente_email' => $clientEmail ?: '—',
            'cliente_dni' => $clientDni ?: '—',
            'vehiculo_marca' => (string) $car->brand,
            'vehiculo_modelo' => (string) ($car->version ?: $car->model),
            'vehiculo_anio' => (string) ($car->year ?: ''),
            'vehiculo_vin' => (string) ($car->vin ?: '—'),
            // Precio total real para el cliente (compra + gastos + honorarios).
            'precio_total' => number_format($car->calculateTotalCost(), 0, ',', '.'),
            'honorarios' => number_format($honorarios, 0, ',', '.'),
            'fecha_firma' => now()->format('d/m/Y H:i'),
            'contrato_id' => 'JJIM-'.$car->id.'-'.strtoupper(substr(md5(uniqid('', true)), 0, 6)),
            // Congelar cláusulas + prestador en el snapshot: un contrato pendiente
            // nunca cambia de texto aunque se edite config/contracts.php después.
            '_clausulas' => config('contracts.clausulas'),
            '_prestador' => config('contracts.prestador'),
        ];

        return ContractAcceptance::create([
            'organization_id' => $car->organization_id,
            'car_id' => $car->id,
            'public_token' => ContractAcceptance::generateToken(),
            'contract_version' => (string) config('contracts.version'),
            'contract_hash' => '', // se calcula al aceptar
            'snapshot' => $snapshot,
            'client_email' => (string) ($clientEmail ?: 'desconocido@example.com'),
            'client_name' => $clientName,
            'client_dni' => $clientDni,
            'accepted_at' => null,
            'accepted_ip' => null,
            'user_agent' => null,
            'locale' => app()->getLocale(),
        ]);
    }
}
