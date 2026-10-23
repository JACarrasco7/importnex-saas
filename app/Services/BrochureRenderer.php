<?php

namespace App\Services;

use App\Models\Organization;
use App\Support\Brand;
use App\Support\PublicSite;
use Illuminate\Support\Facades\Storage;
use RuntimeException;
use SimpleSoftwareIO\QrCode\Facades\QrCode;
use Spatie\Browsershot\Browsershot;

/**
 * Folleto institucional en PDF de cada empresa, con su marca, su contacto, sus
 * honorarios y un QR a su formulario de encargo. Se genera una vez y se guarda
 * en el disco privado; cambia solo si cambian los datos de la empresa.
 */
class BrochureRenderer
{
    public function html(Organization $organization): string
    {
        $brand = Brand::for($organization);
        $site = PublicSite::forOrganization($organization);
        $qrUrl = $site->requestUrl() ?? $site->homeUrl();
        $qrSvg = QrCode::format('svg')
            ->size(280)->margin(1)->errorCorrection('H')
            ->backgroundColor(255, 255, 255)->color(6, 16, 31)
            ->generate($qrUrl);

        return view('jj-import.folleto', [
            'brand' => $brand,
            'logo_base64' => $brand->logoDataUri(onDark: true),
            'qr_svg' => $qrSvg,
            'qr_url' => $qrUrl,
            'precio_honorarios' => $brand->feeLabel(),
            'telefono_1' => $brand->get('phone'),
            'telefono_2' => $brand->get('phone_2'),
            'email' => $brand->get('email'),
        ])->render();
    }

    /** Ruta del PDF en el disco privado; lo genera si no existe o si los datos han cambiado. */
    public function path(Organization $organization): string
    {
        $path = "brochures/{$organization->id}-{$this->fingerprint($organization)}.pdf";
        $disk = Storage::disk('local');

        if (! $disk->exists($path)) {
            $this->forget($organization);
            $disk->makeDirectory('brochures');
            $this->makePdf($this->html($organization), $disk->path($path));
            if (! $disk->exists($path)) {
                throw new RuntimeException('No se pudo generar el folleto PDF.');
            }
        }

        return $disk->path($path);
    }

    /** Borra las versiones anteriores del folleto de la empresa. */
    public function forget(Organization $organization): void
    {
        $disk = Storage::disk('local');
        foreach ($disk->files('brochures') as $file) {
            if (str_starts_with(basename($file), $organization->id.'-')) {
                $disk->delete($file);
            }
        }
    }

    protected function makePdf(string $html, string $target): void
    {
        Browsershot::html($html)
            ->noSandbox()
            ->format('A4')
            ->margins(0, 0, 0, 0)
            ->showBackground()
            ->waitUntilNetworkIdle()
            ->deviceScaleFactor(2)
            ->savePdf($target);
    }

    private function fingerprint(Organization $organization): string
    {
        $brand = Brand::for($organization);

        return substr(sha1(json_encode([$brand->toArray(), $organization->slug, $organization->name, $organization->logo, 'v1'])), 0, 12);
    }
}
