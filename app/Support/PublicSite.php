<?php

namespace App\Support;

use App\Models\Car;
use App\Models\Organization;
use Illuminate\Database\QueryException;

/**
 * Web pública de coches de una empresa.
 *
 * Cada organización con la web activada (`is_public`) tiene la suya en
 * `/web/{slug}`: su catálogo, sus fichas, su comparador y su formulario de
 * solicitud, con su marca y su contacto. La organización que opera la
 * plataforma conserva además `/marketplace` (sus enlaces y su SEO de siempre),
 * y su `/web/{slug}` redirige ahí para no tener dos URLs del mismo catálogo.
 */
final class PublicSite
{
    /** Slugs que chocarían con rutas o confundirían al cliente. */
    public const RESERVED_SLUGS = [
        'admin', 'api', 'app', 'login', 'register', 'marketplace', 'mercado', 'web',
        'legal', 'pricing', 'precios', 'request', 'contrato', 'tracking', 'mi-encargo',
        'stripe', 'dashboard', 'organization', 'settings', 'ajustes', 'soporte', 'support',
    ];

    private function __construct(
        public readonly ?Organization $organization,
        public readonly bool $isPlatform,
    ) {}

    /** Web de la organización que opera la plataforma (la de `/marketplace`). */
    public static function platform(): self
    {
        return new self(self::platformOrganization(), true);
    }

    /** Web de una empresa por su slug, o null si no existe o no está disponible. */
    public static function forSlug(string $slug): ?self
    {
        $organization = Organization::query()->where('slug', $slug)->first();

        return $organization && self::isAvailable($organization)
            ? self::forOrganization($organization)
            : null;
    }

    public static function forOrganization(Organization $organization): self
    {
        return new self($organization, self::platformOrganization()?->is($organization) ?? false);
    }

    /**
     * La web se ve si la empresa la ha activado y su cuenta está al día. La
     * organización operadora no paga suscripción.
     */
    public static function isAvailable(Organization $organization): bool
    {
        if (! $organization->is_public) {
            return false;
        }

        return $organization->isOwner()
            || ! config('subscription.enforce')
            || $organization->hasActiveSubscription();
    }

    /** URL de la web de una empresa, o null si no la tiene activa. */
    public static function urlFor(?Organization $organization): ?string
    {
        return $organization && self::isAvailable($organization)
            ? self::forOrganization($organization)->homeUrl()
            : null;
    }

    public function organizationId(): ?int
    {
        return $this->organization?->id;
    }

    public function brand(): Brand
    {
        return $this->organization ? $this->organization->brand() : Brand::platform();
    }

    public function homeUrl(): string
    {
        return $this->isPlatform
            ? route('marketplace.index')
            : route('site.index', ['site' => $this->organization->slug]);
    }

    public function carUrl(Car|int $car): string
    {
        $id = $car instanceof Car ? $car->getKey() : $car;

        return $this->isPlatform
            ? route('marketplace.show', ['car' => $id])
            : route('site.show', ['site' => $this->organization->slug, 'car' => $id]);
    }

    public function compareUrl(): string
    {
        return $this->isPlatform
            ? route('marketplace.compare')
            : route('site.compare', ['site' => $this->organization->slug]);
    }

    /**
     * Formulario público de solicitud de la empresa («Solicita tu vehículo»).
     *
     * @param  array<string, mixed>  $query
     */
    public function requestUrl(array $query = []): ?string
    {
        if (! $this->organization?->is_public) {
            return null;
        }

        return route('public.car-request.index', ['slug' => $this->organization->slug, ...$query]);
    }

    public function legalUrl(): string
    {
        return $this->organization
            ? route('public.legal', ['org' => $this->organization->slug])
            : route('public.legal');
    }

    /**
     * El pie «¿Eres importador? Conoce la plataforma» se oculta en los planes
     * con marca propia y en la web de la propia plataforma no hace falta
     * quitarlo.
     */
    public function showsPlatformCredit(): bool
    {
        if (! $this->organization || $this->organization->isOwner()) {
            return true;
        }

        return ! $this->organization->hasFeature('custom_branding');
    }

    /**
     * Contexto para el layout público (prop `marketplace`).
     *
     * @return array{orgName: string, orgSlug: ?string, isPlatform: bool, homeUrl: string, carUrlBase: string, compareUrl: string, requestUrl: ?string, legalUrl: string, logoUrl: ?string, platformCredit: bool, brochureUrl: ?string}
     */
    public function context(): array
    {
        $brand = $this->brand();
        $home = $this->homeUrl();

        return [
            'orgName' => $brand->name(),
            'orgSlug' => $this->organization?->slug,
            'isPlatform' => $this->isPlatform,
            'homeUrl' => $home,
            'carUrlBase' => $this->isPlatform ? $home : $home.'/coche',
            'compareUrl' => $this->compareUrl(),
            'requestUrl' => $this->requestUrl(),
            'legalUrl' => $this->legalUrl(),
            'logoUrl' => $brand->toPublicArray()['logo_url'] ?? null,
            'platformCredit' => $this->showsPlatformCredit(),
            // El folleto PDF estático es de la organización operadora.
            'brochureUrl' => $this->organization?->isOwner() && file_exists(public_path('jj-import-folleto.pdf'))
                ? '/jj-import-folleto.pdf'
                : null,
        ];
    }

    private static function platformOrganization(): ?Organization
    {
        return once(function (): ?Organization {
            try {
                return Organization::query()->where('is_public', true)->where('is_owner', true)->orderBy('id')->first()
                    ?? Organization::query()->where('is_public', true)->orderBy('id')->first();
            } catch (QueryException) {
                return null;
            }
        });
    }
}
