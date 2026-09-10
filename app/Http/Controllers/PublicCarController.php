<?php

namespace App\Http\Controllers;

use App\Models\CarPublicLink;
use App\Support\Esqueleto;
use App\Support\FiltroPublico;
use App\Support\PrecioClienteCalculator;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;

/**
 * Dossier público del coche (sin auth).
 * URL: /c/{token}
 *
 * Muestra el informe completo del coche (ficha técnica, veredicto, por qué
 * este coche, comparativa de mercado) en una sola página web (no PDF)
 * pensada para que el equipo la comparta por WhatsApp con el cliente. El
 * link puede revocarse en cualquier momento desde Cars/Show.
 *
 * Reglas duras de la ficha pública (auditoría 09-sep-2026):
 *  - A1+A2: los argumentos visibles vienen SIEMPRE de `ficha-cliente.json`
 *    (escrito para el cliente). Nunca del bloque interno A_FAVOR del
 *    esqueleto técnico.
 *  - A3: si el veredicto interno desaconseja la unidad (no empieza por
 *    "comprar") y no hay ficha-cliente.json, devolvemos car-unavailable.
 *  - C3: el cliente ve "precio del anuncio + gastos de compra" SIN
 *    desglose de margen, con aviso explícito de que el precio final se
 *    confirma antes de cerrar.
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
        // Caché trivial por car_id: fichaCliente() hace 2 exists() + 1 get()
        // sobre Storage::local; lo cacheamos 5 min para evitar syscalls repetidos
        // en visitas consecutivas al mismo /c/{token} (auditoría 09-sep-2026 H8).
        $ficha = Cache::remember(
            "public.ficha-cliente.{$car->id}",
            now()->addMinutes(5),
            fn () => $this->fichaCliente($car)
        );

        // A3: si la recomendación interna desaconseja la unidad y no hay
        // ficha-cliente.json que la justifique ante el cliente, devolvemos
        // car-unavailable. La ficha armada con datos internos está prohibida.
        if (! $this->esRecomendableParaCliente($car, $esqueleto, $ficha)) {
            return response()->view('public.car-unavailable');
        }

        $fotos = $this->fotos($car, $token);

        // C3: precio origen (del anuncio) + gastos de compra estimados.
        $precioCliente = PrecioClienteCalculator::desde($car, $esqueleto, $ficha);

        return view('public.car-dossier', [
            'car' => $car,
            'esqueleto' => $esqueleto,
            'ficha' => $ficha,
            'argumentosPublicos' => $this->argumentosPublicos($ficha, $esqueleto),
            'precioCliente' => $precioCliente,
            'logoBase64' => $this->logo(),
            'fotos' => $fotos,
            'fotoPortada' => $fotos[0] ?? null,
            'clienteNombre' => $car->client?->name,
        ]);
    }

    /**
     * ¿La unidad tiene una ficha comercial presentable al cliente?
     *
     * - Si la recomendación interna empieza por "comprar" → sí.
     * - Si existe ficha-cliente.json (v2) → sí (la skill ya decidió).
     * - En otro caso → no. Mostramos car-unavailable, no una ficha
     *   comercial armada con datos internos (A3 auditoría 09-sep-2026).
     */
    private function esRecomendableParaCliente($car, ?Esqueleto $esqueleto, ?array $ficha): bool
    {
        // 1) ficha-cliente.json presente → SIEMPRE se muestra. La skill ya
        //    decidió que este coche merece ficha comercial.
        if ($ficha !== null) {
            return true;
        }

        // 2) Recomendación interna en BD: si empieza por "comprar", OK.
        $reco = strtolower(trim((string) ($car->recommendation ?? '')));
        if (str_starts_with($reco, 'comprar')) {
            return true;
        }

        // 3) Veredicto del esqueleto: bloque actual [VEREDICTO].
        $veredicto = strtolower(trim((string) ($esqueleto?->uno('VEREDICTO') ?? '')));
        if (str_starts_with($veredicto, 'comprar')) {
            return true;
        }

        // 4) ZIP legacy (auditoría 09-sep-2026, fix 10-sep): el bloque
        //    [RECOMENDACION] estaba en informe-interno.txt, no en
        //    ficha-publicitaria. Si empieza por "comprar", lo aceptamos
        //    también — son coches antiguos cuyo ZIP no se puede reimportar.
        $recoLegacy = strtolower(trim((string) ($esqueleto?->uno('RECOMENDACION') ?? '')));
        if (str_starts_with($recoLegacy, 'comprar')) {
            return true;
        }

        // 5) Coche SIN recommendation en BD y SIN bloque en el esqueleto
        //    (ZIP muy antiguo o dañado) → no mostramos ficha comercial. El
        //    operador debe reimportar el ZIP o actualizar la recomendación
        //    manualmente.
        return false;
    }

    /**
     * Argumentos de venta que verá el cliente (A1 + A2 auditoría 09-sep-2026).
     *
     * Orden de precedencia:
     *   1) ficha-cliente.json → argumentos  (escrito para el cliente)
     *   2) publicidad.argumentos (legacy de la skill, escrito para el cliente)
     *   3) nada — NUNCA cae al bloque A_FAVOR del esqueleto técnico, porque
     *      ese bloque trae análisis interno (hueco, vendibilidad, vendedor
     *      de origen) que no debe filtrarse.
     *
     * @return array<int, string>
     */
    private function argumentosPublicos(?array $ficha, ?Esqueleto $esqueleto): array
    {
        $candidatos = [];

        if (is_array($ficha)) {
            $argFicha = $ficha['argumentos'] ?? [];
            if (is_array($argFicha)) {
                $candidatos = array_merge($candidatos, $argFicha);
            }
        }

        if (empty($candidatos) && $esqueleto) {
            foreach ($esqueleto->todos('PUBLICIDAD_ARGUMENTO') as $arg) {
                $candidatos[] = $arg;
            }
        }

        // Red de seguridad final: A22b.
        return FiltroPublico::lista(array_values(array_filter(
            $candidatos,
            fn ($v) => is_string($v) && trim($v) !== ''
        )));
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
