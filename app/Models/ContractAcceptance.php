<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

/**
 * Aceptación electrónica de un contrato de prestación de servicios.
 *
 * - `contract_hash` es SHA256 del texto íntegro del contrato en el momento
 *   de la firma. Cualquier modificación posterior del texto NO afecta a
 *   contratos ya firmados (inmutabilidad legal).
 * - `snapshot` guarda los valores de los placeholders del contrato (cliente,
 *   vehículo, honorarios, etc.) en el momento de la firma.
 * - `public_token` se usa para la URL pública del contrato; se regenera
 *   si el cliente lo revoca o se rota por seguridad.
 */
class ContractAcceptance extends Model
{
    protected $fillable = [
        'organization_id',
        'car_id',
        'public_token',
        'contract_version',
        'contract_hash',
        'snapshot',
        'client_email',
        'client_name',
        'client_dni',
        'accepted_at',
        'accepted_ip',
        'user_agent',
        'locale',
    ];

    protected function casts(): array
    {
        return [
            'snapshot' => 'array',
            'accepted_at' => 'datetime',
        ];
    }

    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }

    public function car(): BelongsTo
    {
        return $this->belongsTo(Car::class);
    }

    public function getPublicUrlAttribute(): string
    {
        $base = rtrim(config('app.url', ''), '/');

        return $base.'/contrato/'.$this->public_token;
    }

    /** Genera un token URL-safe único para un nuevo contrato. */
    public static function generateToken(): string
    {
        do {
            $token = Str::random(48);
        } while (self::where('public_token', $token)->exists());

        return $token;
    }

    /**
     * Devuelve el texto del contrato ya renderizado con los valores del snapshot,
     * que es exactamente lo que el cliente vio y firmó.
     */
    public function getContractText(): string
    {
        return self::renderContractText($this->snapshot, $this->contract_version);
    }

    /**
     * Renderiza el texto del contrato sustituyendo placeholders.
     *
     * @param  array<string, string|int|float>  $values
     */
    public static function renderContractText(array $values, ?string $version = null): string
    {
        $version = $version ?: (string) config('contracts.version');

        // Las cláusulas y el prestador quedan CONGELADOS en el snapshot en el
        // momento de crear el contrato. Esto garantiza que un contrato pendiente
        // no cambie de texto aunque se edite config/contracts.php después.
        $clausulas = (array) ($values['_clausulas'] ?? config('contracts.clausulas'));
        $prestador = (array) ($values['_prestador'] ?? config('contracts.prestador'));

        $defaults = array_merge([
            'cliente_nombre' => '—',
            'cliente_email' => '—',
            'cliente_dni' => '—',
            'vehiculo_marca' => '—',
            'vehiculo_modelo' => '—',
            'vehiculo_anio' => '—',
            'vehiculo_vin' => '—',
            'precio_total' => '—',
            'honorarios' => '—',
            'fecha_firma' => now()->format('d/m/Y H:i'),
            'contrato_id' => '—',
            'email' => $prestador['email'] ?? '—',
        ], $values);

        $body = [];
        $body[] = '# CONTRATO DE PRESTACIÓN DE SERVICIOS DE IMPORTACIÓN DE VEHÍCULOS';
        $body[] = '';
        $body[] = '**Versión:** '.$version;
        $body[] = '**Identificador:** '.$defaults['contrato_id'];
        $body[] = '**Fecha:** '.$defaults['fecha_firma'];
        $body[] = '';
        $body[] = '**PRESTADOR:** '.($prestador['razon_social'] ?? 'JJ Import Motors').' · CIF '.$prestador['cif'].' · '.$prestador['direccion'];
        $body[] = '**CLIENTE:** '.$defaults['cliente_nombre'].' · DNI/NIE '.$defaults['cliente_dni'].' · '.$defaults['cliente_email'];
        $body[] = '**VEHÍCULO:** '.$defaults['vehiculo_marca'].' '.$defaults['vehiculo_modelo'].' ('.$defaults['vehiculo_anio'].')';
        $body[] = '**HONORARIOS:** '.$defaults['honorarios'].' €';
        $body[] = '';
        foreach ($clausulas as $idx => $cl) {
            $body[] = '---';
            $body[] = '## '.$idx.'. '.$cl['titulo'];
            $body[] = '';
            $body[] = self::interpolate($cl['cuerpo'], $defaults);
        }

        return implode("\n", $body);
    }

    /**
     * @param  array<string, string|int|float>  $values
     */
    public static function interpolate(string $text, array $values): string
    {
        foreach ($values as $key => $value) {
            // Saltar claves internas (_clausulas, _prestador) que son arrays.
            if (str_starts_with($key, '_')) {
                continue;
            }
            $text = str_replace(':'.$key, (string) $value, $text);
        }

        return $text;
    }

    /** SHA256 del texto del contrato (lo que se firma, sin timestamps). */
    public static function hashContract(string $text): string
    {
        return hash('sha256', $text);
    }
}
