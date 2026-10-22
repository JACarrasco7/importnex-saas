<?php

namespace App\Support;

use App\Models\Organization;
use Illuminate\Database\QueryException;

/**
 * Datos de marca y empresa de una organización (SaaS multi-empresa).
 *
 * Cada organización rellena su perfil en Ajustes → «Datos de empresa».
 * Lo que falte se completa con config/company.php (valores por .env de la
 * plataforma). Todo lo que ve un cliente final (contrato, portal, emails,
 * fichas públicas, PDF) debe salir de aquí, nunca escrito en el código.
 */
final class Brand
{
    /** Campos editables del perfil de empresa. */
    public const FIELDS = [
        'legal_name', 'tax_id', 'address', 'city', 'phone', 'phone_2', 'whatsapp',
        'email', 'website', 'iban', 'iban_holder', 'iban_bank', 'fee_label', 'tagline',
    ];

    /** @param  array<string, string|null>  $data */
    private function __construct(private array $data) {}

    public static function for(?Organization $organization): self
    {
        $profile = (array) ($organization?->company ?? []);
        $fallback = (array) config('company', []);

        $name = $organization?->name ?: ($fallback['nombre'] ?? config('app.name'));

        $data = [
            'name' => $name,
            'legal_name' => $profile['legal_name'] ?? null,
            'tax_id' => $profile['tax_id'] ?? null,
            'address' => $profile['address'] ?? null,
            'city' => $profile['city'] ?? null,
            'phone' => $profile['phone'] ?? ($organization ? null : ($fallback['telefono_1'] ?? null)),
            'phone_2' => $profile['phone_2'] ?? ($organization ? null : ($fallback['telefono_2'] ?? null)),
            'whatsapp' => $profile['whatsapp'] ?? null,
            'email' => $profile['email'] ?? ($organization ? null : ($fallback['email'] ?? null)),
            'website' => $profile['website'] ?? ($organization ? null : ($fallback['web'] ?? null)),
            'iban' => $profile['iban'] ?? null,
            'iban_holder' => $profile['iban_holder'] ?? null,
            'iban_bank' => $profile['iban_bank'] ?? null,
            'fee_label' => $profile['fee_label'] ?? ($fallback['precio_honorarios'] ?? null),
            'tagline' => $profile['tagline'] ?? null,
            'logo_url' => self::logoUrl($organization),
        ];

        $data = array_map(fn ($v) => is_string($v) ? (trim($v) === '' ? null : trim($v)) : $v, $data);
        $data['legal_name'] ??= $data['name'];
        $data['iban_holder'] ??= $data['legal_name'];
        $data['whatsapp'] ??= $data['phone'];

        return new self($data);
    }

    /** Nombre del producto (APP_NAME); nunca «Laravel», que es el valor por defecto. */
    public static function productName(): string
    {
        $name = (string) config('app.name');

        return $name === '' || $name === 'Laravel' ? 'Importnex' : $name;
    }

    /** Organización «de la casa»: la que opera la web pública del dominio. */
    public static function platform(): self
    {
        return once(function (): self {
            try {
                return self::for(
                    Organization::query()->where('is_owner', true)->orderBy('id')->first()
                        ?? Organization::query()->where('is_public', true)->orderBy('id')->first()
                );
            } catch (QueryException) {
                // Sin base de datos (instalación a medias, vistas renderizadas en
                // tests unitarios): se usan los valores de config/company.php.
                return self::for(null);
            }
        });
    }

    public function get(string $key, ?string $default = null): ?string
    {
        return $this->data[$key] ?? $default;
    }

    public function name(): string
    {
        return (string) $this->data['name'];
    }

    /** Enlace tel: del teléfono indicado (phone|phone_2), o null si no hay. */
    public function telHref(string $key = 'phone'): ?string
    {
        $digits = self::digits($this->data[$key] ?? null);

        return $digits ? 'tel:+'.$digits : null;
    }

    /** Enlace de WhatsApp con texto opcional, o null si la empresa no tiene número. */
    public function whatsappUrl(?string $text = null): ?string
    {
        $digits = self::digits($this->data['whatsapp'] ?? null);
        if (! $digits) {
            return null;
        }

        return 'https://wa.me/'.$digits.($text ? '?text='.rawurlencode($text) : '');
    }

    /** Dígitos con prefijo internacional (9 dígitos → España). */
    private static function digits(?string $phone): ?string
    {
        $digits = preg_replace('/\D/', '', (string) $phone);
        if ($digits === '') {
            return null;
        }

        return strlen($digits) === 9 ? '34'.$digits : $digits;
    }

    /** "Nombre · Ciudad" para pies de página. */
    public function footerLine(): string
    {
        return implode(' · ', array_filter([$this->data['name'], $this->data['city']]));
    }

    public function isComplete(): bool
    {
        return filled($this->data['tax_id']) && filled($this->data['address'])
            && filled($this->data['email']) && filled($this->data['phone']);
    }

    /** @return list<string> */
    public function missingForContracts(): array
    {
        $labels = ['tax_id' => 'CIF/NIF', 'address' => 'dirección', 'email' => 'email', 'phone' => 'teléfono'];

        return array_values(array_map(fn ($k) => $labels[$k], array_filter(array_keys($labels), fn ($k) => blank($this->data[$k]))));
    }

    /** Bloque «prestador» que se congela en el snapshot del contrato. */
    public function prestador(): array
    {
        return [
            'razon_social' => $this->data['legal_name'],
            'nombre' => $this->data['name'],
            'cif' => $this->data['tax_id'] ?: '— pendiente —',
            'direccion' => $this->data['address'] ?: '— pendiente —',
            'ciudad' => $this->data['city'] ?: '—',
            'email' => $this->data['email'] ?: '—',
            'telefono' => $this->data['phone'] ?: '—',
        ];
    }

    /** @return array{iban: ?string, holder: ?string, bank: ?string} */
    public function bank(): array
    {
        return ['iban' => $this->data['iban'], 'holder' => $this->data['iban_holder'], 'bank' => $this->data['iban_bank']];
    }

    /** @return array<string, string|null> */
    public function toArray(): array
    {
        return $this->data;
    }

    /** Datos públicos (sin IBAN) para compartir con el frontend. */
    public function toPublicArray(): array
    {
        return array_diff_key($this->data, array_flip(['iban', 'iban_holder', 'iban_bank']));
    }

    /**
     * Logo como data URI para PDF/HTML autocontenido. Con $onDark se prefiere
     * la variante clara «-blanco» si existe junto al logo original.
     */
    public function logoDataUri(bool $onDark = false): ?string
    {
        $url = $this->data['logo_url'];
        if (! $url) {
            return null;
        }

        $relative = ltrim((string) parse_url($url, PHP_URL_PATH), '/');
        $candidates = [];
        if ($onDark) {
            $dir = dirname($relative);
            $candidates[] = public_path($dir.'/logo-horizontal-blanco.png');
        }
        $candidates[] = public_path($relative);
        $candidates[] = storage_path('app/public/'.preg_replace('#^storage/#', '', $relative));

        foreach ($candidates as $path) {
            if (is_file($path)) {
                $mime = mime_content_type($path) ?: 'image/png';

                return 'data:'.$mime.';base64,'.base64_encode((string) file_get_contents($path));
            }
        }

        return null;
    }

    private static function logoUrl(?Organization $organization): ?string
    {
        $logo = $organization?->logo;
        if (! $logo) {
            return null;
        }

        return str_starts_with($logo, 'http') || str_starts_with($logo, '/') ? $logo : asset('storage/'.$logo);
    }
}
