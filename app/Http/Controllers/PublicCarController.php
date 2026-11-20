<?php

namespace App\Http\Controllers;

use App\Models\CarPublicLink;
use App\Support\CostBreakdown;
use App\Support\Esqueleto;
use App\Support\FichaPublica;
use App\Support\FiltroPublico;
use App\Support\PrecioClienteCalculator;
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
            // Tambien contamos en el contador per-car del marketplace para
            // que el admin vea el mismo numero en /cars/{id} sea cual sea
            // la URL de entrada (/marketplace/{id} o /c/{token}). Excluimos
            // bots igual que PublicMarketplaceController.
            if (! preg_match('/bot|crawl|spider|slurp|curl|wget|python-requests|headless/i', (string) $request->userAgent())) {
                $car->incrementQuietly('marketplace_views');
            }
        } catch (\Throwable $e) {
            // No interrumpimos la carga por un fallo de tracking.
        }

        $esqueleto = FichaPublica::esqueleto($car);
        $ficha = FichaPublica::fichaCliente($car);

        // A3 auditoría 09-sep-2026 (fix 10-sep-2026): con el FiltroPublico
        // endurecido (A1+A2), ya no ocultamos el dossier cuando la
        // recomendación interna es "No importar". El operador ha generado el
        // enlace a propósito y la ficha del cliente usa exclusivamente bloques
        // escritos para él. Si quiere ocultarlo, revoca el enlace desde el panel.

        $fotos = $this->fotos($car, $token);

        // Mismo total que la ficha del marketplace (revisión 20-nov-2026): si
        // el coche tiene costes en BD manda CostBreakdown; si no, el desglose
        // del ZIP (C3, coches antiguos).
        $costBreakdown = CostBreakdown::forCar($car, (float) $car->calculateIEDMT());
        $precioCliente = $costBreakdown['has_data']
            ? PrecioClienteCalculator::desdeDesglose($car, $costBreakdown)
            : PrecioClienteCalculator::desde($car, $esqueleto, $ficha);
        $mostrarMercado = $costBreakdown['has_data']
            ? CostBreakdown::marketComparisonFavoursClient($car, (float) $costBreakdown['total'])
            : true;

        return view('public.car-dossier', [
            'car' => $car,
            'esqueleto' => $esqueleto,
            'ficha' => $ficha,
            'argumentosPublicos' => $this->argumentosPublicos($ficha, $esqueleto),
            'precioCliente' => $precioCliente,
            'mostrarMercado' => $mostrarMercado,
            'equipamiento' => $this->equipamiento($car, $ficha, $esqueleto),
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
     * Equipamiento del dossier: la lista más completa de las que haya.
     *
     * Revisión 20-nov-2026: el dossier del Mazda enseñaba 8 extras (los de
     * la ficha del ZIP) y la ficha del marketplace 25 (los del coche en BD).
     * Mismo coche, dos fichas: se enseña la lista más larga.
     *
     * @param  array<string, mixed>|null  $ficha
     * @return array<int, string>
     */
    private function equipamiento($car, ?array $ficha, ?Esqueleto $esqueleto): array
    {
        $limpia = fn ($lista) => array_values(array_filter(
            is_array($lista) ? $lista : [],
            fn ($item) => is_string($item) && trim($item) !== ''
        ));

        $candidatas = [
            $limpia($ficha['equipamiento'] ?? []),
            $limpia($esqueleto?->lista('EQUIPAMIENTO') ?? []),
            $limpia($car->equipment ?? []),
        ];
        usort($candidatas, fn (array $a, array $b) => count($b) <=> count($a));

        return FiltroPublico::lista($candidatas[0]);
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
