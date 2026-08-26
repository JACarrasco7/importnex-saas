<?php

namespace App\Http\Controllers;

use App\Models\CarPublicLink;
use App\Support\Esqueleto;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

/**
 * Dossier público del coche (sin auth).
 * URL: /c/{token}
 *
 * Muestra ficha técnica + folleto del coche en una sola página web bonita
 * (no PDF) pensada para que el dealer la comparta por WhatsApp. El link
 * puede revocarse en cualquier momento desde Cars/Show.
 */
class PublicCarController extends Controller
{
    public function show(Request $request, string $token)
    {
        $link = CarPublicLink::where('token', $token)->first();

        if (! $link || ! $link->isActive()) {
            return response()->view('public.car-unavailable');
        }

        $car = $link->car()->with(['photos', 'client'])->first();
        if (! $car) {
            return response()->view('public.car-unavailable');
        }

        // Registramos la vista (sin bloquear si falla).
        try {
            $link->recordView();
        } catch (\Throwable $e) {
            // No interrumpimos la carga por un fallo de tracking.
        }

        $contenido = $this->leerContenido($car, 'ficha-publicitaria.txt');

        $esqueleto = $contenido ? Esqueleto::desde($contenido) : null;

        return view('public.car-dossier', [
            'car' => $car,
            'esqueleto' => $esqueleto,
            'logoBase64' => $this->logo(),
            'fotos' => $this->fotos($car),
            'clienteNombre' => $car->client?->name,
        ]);
    }

    private function leerContenido($car, string $filename): ?string
    {
        $path = "cars/{$car->id}/contenido/{$filename}";
        if (! Storage::disk('local')->exists($path)) {
            return null;
        }

        return Storage::disk('local')->get($path);
    }

    private function logo(): ?string
    {
        $path = public_path('images/jj-import/logo-horizontal-blanco.png');
        if (! file_exists($path)) {
            return null;
        }

        return 'data:image/png;base64,'.base64_encode(file_get_contents($path));
    }

    /** @return array<int, string> */
    private function fotos($car): array
    {
        $out = [];
        foreach ($car->photos()->orderBy('sort_order')->get() as $foto) {
            $abs = str_starts_with($foto->url, '/storage/')
                ? public_path($foto->url)
                : storage_path('app/public/'.ltrim($foto->url, '/'));

            if (file_exists($abs)) {
                $mime = mime_content_type($abs) ?: 'image/jpeg';
                $out[] = 'data:'.$mime.';base64,'.base64_encode(file_get_contents($abs));
            }
        }

        return $out;
    }
}
