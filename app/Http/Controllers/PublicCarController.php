<?php

namespace App\Http\Controllers;

use App\Models\CarPublicLink;
use App\Support\Esqueleto;
use App\Support\FiltroPublico;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

/**
 * Dossier público del coche (sin auth).
 * URL: /c/{token}
 *
 * Muestra el informe completo del coche (ficha técnica, veredicto, por qué
 * este coche, comparativa de mercado) en una sola página web (no PDF)
 * pensada para que el equipo la comparta por WhatsApp con el cliente. El
 * link puede revocarse en cualquier momento desde Cars/Show.
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

        $fotos = $this->fotos($car, $token);

        return view('public.car-dossier', [
            'car' => $car,
            'esqueleto' => $esqueleto,
            'ficha' => $this->fichaCliente($car),
            'logoBase64' => $this->logo(),
            'fotos' => $fotos,
            'fotoPortada' => $fotos[0] ?? null,
            'clienteNombre' => $car->client?->name,
        ]);
    }

    /**
     * Ficha del cliente v2 en JSON (`contenido/json/ficha-cliente.json`).
     *
     * Es lo que genera la skill con `esqueleto_a_json.py`; si el coche todavía
     * no lo trae, la vista sigue funcionando con el esqueleto antiguo.
     *
     * @return array<string, mixed>|null
     */
    private function fichaCliente($car): ?array
    {
        // El ingestor aplana los nombres al guardar, así que puede estar en
        // contenido/json/ (tal cual viene del ZIP) o suelto en contenido/.
        $candidatos = [
            "cars/{$car->id}/contenido/json/ficha-cliente.json",
            "cars/{$car->id}/contenido/ficha-cliente.json",
        ];

        $path = null;
        foreach ($candidatos as $candidato) {
            if (Storage::disk('local')->exists($candidato)) {
                $path = $candidato;
                break;
            }
        }

        if (! $path) {
            return null;
        }

        $datos = json_decode((string) Storage::disk('local')->get($path), true);
        if (! is_array($datos) || ! isset($datos['ficha']) || ! is_array($datos['ficha'])) {
            return null;
        }

        return $this->limpiarFicha($datos['ficha']);
    }

    /**
     * Aplica el filtro público a todo lo que venga del ZIP (A22b).
     *
     * @param  array<string, mixed>  $ficha
     * @return array<string, mixed>
     */
    private function limpiarFicha(array $ficha): array
    {
        foreach ($ficha as $clave => $valor) {
            if (is_string($valor)) {
                $ficha[$clave] = FiltroPublico::texto($valor);
            } elseif (is_array($valor)) {
                $ficha[$clave] = array_values(array_filter($valor, function ($item) {
                    if (is_string($item)) {
                        return FiltroPublico::permitida($item);
                    }

                    if (is_array($item)) {
                        return FiltroPublico::permitida(implode(' ', array_filter($item, 'is_string')));
                    }

                    return true;
                }));
            }
        }

        return array_filter($ficha, fn ($v) => $v !== null && $v !== []);
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

    /**
     * Sirve una foto del coche por su posición (1..N).
     *
     * Va por ruta y no por /storage/... a propósito: si el symlink public/storage
     * no existe en el servidor, el enlace del cliente se quedaría sin fotos y sin
     * previsualización en WhatsApp.
     */
    public function foto(string $token, int $indice)
    {
        $link = CarPublicLink::where('token', $token)->first();
        if (! $link || ! $link->isActive()) {
            abort(404);
        }

        $foto = $link->car?->photos()->orderBy('sort_order')->skip(max(0, $indice - 1))->first();
        if (! $foto) {
            abort(404);
        }

        $ruta = ltrim((string) $foto->url, '/');
        $ruta = str_starts_with($ruta, 'storage/') ? substr($ruta, 8) : $ruta;

        if (! Storage::disk('public')->exists($ruta)) {
            abort(404);
        }

        return response()->file(Storage::disk('public')->path($ruta), [
            'Cache-Control' => 'public, max-age=86400',
        ]);
    }

    /**
     * URLs absolutas de las fotos.
     *
     * Antes se incrustaban en base64: con 30 fotos la página pesaba decenas de
     * megas y `og:image` quedaba en un `data:` URI que WhatsApp no puede leer
     * para la previsualización del enlace.
     *
     * @return array<int, string>
     */
    private function fotos($car, string $token): array
    {
        $out = [];
        $posicion = 0;
        foreach ($car->photos()->orderBy('sort_order')->get() as $foto) {
            $posicion++;
            $ruta = (string) $foto->url;
            if ($ruta === '') {
                continue;
            }

            $out[] = str_starts_with($ruta, 'http')
                ? $ruta
                : route('public.car.photo', ['token' => $token, 'indice' => $posicion]);
        }

        return $out;
    }
}
