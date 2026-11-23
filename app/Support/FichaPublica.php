<?php

namespace App\Support;

use App\Models\Car;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;

/**
 * Contenido escrito PARA el cliente que trae el ZIP de la skill
 * (ficha-publicitaria.txt y contenido/json/ficha-cliente.json).
 *
 * Lo usan las dos fichas publicas del coche: el dossier (/c/{token}) y la
 * ficha del marketplace (/marketplace/{id} y /p/{token}). Antes solo lo leia
 * el dossier, y la ficha del marketplace se quedaba sin la valoracion de la
 * unidad, que es justo lo que mas vende (revision 20-nov-2026).
 *
 * Todo lo que sale de aqui pasa por FiltroPublico (A22b).
 */
class FichaPublica
{
    public static function esqueleto(Car $car): ?Esqueleto
    {
        $path = "cars/{$car->id}/contenido/ficha-publicitaria.txt";
        if (! Storage::disk('local')->exists($path)) {
            return null;
        }

        return Esqueleto::desde((string) Storage::disk('local')->get($path));
    }

    /**
     * Ficha del cliente v2 en JSON (`contenido/json/ficha-cliente.json`), ya
     * filtrada. Cacheada 5 min por coche (auditoria 09-sep-2026 H8).
     *
     * @return array<string, mixed>|null
     */
    public static function fichaCliente(Car $car): ?array
    {
        return Cache::remember(
            "public.ficha-cliente.{$car->id}",
            now()->addMinutes(5),
            fn () => self::leerFichaCliente($car)
        );
    }

    /**
     * Valoracion de la unidad escrita para el cliente ([VALORACION] y
     * [POR_QUE] del ZIP), frase a frase por el filtro publico. Las notas
     * internas del panel (`valuation`, `recommendation`) no entran aqui.
     *
     * @return array<int, string>
     */
    public static function valoracion(?Esqueleto $esqueleto): array
    {
        if (! $esqueleto) {
            return [];
        }

        return array_values(array_filter([
            FiltroPublico::frases($esqueleto->uno('VALORACION')),
            FiltroPublico::frases($esqueleto->uno('POR_QUE')),
        ]));
    }

    /**
     * Que esta comprobado y que se comprueba antes de la reserva.
     *
     * @param  array<string, mixed>|null  $ficha
     * @return array{verificado: array<int, string>, pendiente: array<int, string>}
     */
    public static function comprobaciones(?array $ficha): array
    {
        $soloTextos = fn ($lista) => FiltroPublico::lista(array_values(array_filter(
            is_array($lista) ? $lista : [],
            fn ($item) => is_string($item) && trim($item) !== ''
        )));

        return [
            'verificado' => $soloTextos($ficha['verificado'] ?? []),
            'pendiente' => $soloTextos($ficha['pendiente_comprobar'] ?? []),
        ];
    }

    /**
     * @return array<string, mixed>|null
     */
    private static function leerFichaCliente(Car $car): ?array
    {
        // El ingestor aplana los nombres al guardar, asi que puede estar en
        // contenido/json/ (tal cual viene del ZIP) o suelto en contenido/.
        $candidatos = [
            "cars/{$car->id}/contenido/json/ficha-cliente.json",
            "cars/{$car->id}/contenido/ficha-cliente.json",
        ];

        foreach ($candidatos as $candidato) {
            if (! Storage::disk('local')->exists($candidato)) {
                continue;
            }

            $datos = json_decode((string) Storage::disk('local')->get($candidato), true);
            if (! is_array($datos) || ! isset($datos['ficha']) || ! is_array($datos['ficha'])) {
                return null;
            }

            return self::limpiarFicha($datos['ficha']);
        }

        return null;
    }

    /**
     * Aplica el filtro publico a todo lo que venga del ZIP (A22b).
     *
     * @param  array<string, mixed>  $ficha
     * @return array<string, mixed>
     */
    private static function limpiarFicha(array $ficha): array
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

    /**
     * Argumentos de la ficha del cliente (A1 + A2 auditoria 09-sep-2026):
     * primero `ficha-cliente.json` -> argumentos y, si no hay, los
     * [PUBLICIDAD_ARGUMENTO] del esqueleto. Nunca el bloque A_FAVOR del
     * esqueleto tecnico, que trae analisis interno.
     *
     * @return array<int, string>
     */
    public static function argumentos(Car $car): array
    {
        $ficha = self::fichaCliente($car);
        $candidatos = is_array($ficha) && is_array($ficha['argumentos'] ?? null) ? $ficha['argumentos'] : [];

        if ($candidatos === []) {
            $candidatos = self::esqueleto($car)?->todos('PUBLICIDAD_ARGUMENTO') ?? [];
        }

        return FiltroPublico::lista(array_values(array_filter(
            $candidatos,
            fn ($v) => is_string($v) && trim($v) !== ''
        )));
    }

    /**
     * Puntos a favor que ven las DOS fichas (marketplace y dossier).
     *
     * Primero las virtudes de la unidad que trae el ZIP (`balance.a_favor`,
     * guardadas en `cars.pros`: ITV, mantenimiento, piezas cambiadas,
     * propietarios...), ordenadas por peso; despues los argumentos de la
     * ficha del cliente que no repitan ninguna. Todo pasa por FiltroPublico.
     *
     * Revision 23-nov-2026: el dossier solo enseñaba los 4 argumentos de
     * marketing y la ficha del marketplace solo los pros, asi que el cliente
     * no veia en una de las dos lo bueno que dice el anuncio.
     *
     * @param  array<int, string>  $argumentos
     * @return array<int, string>
     */
    public static function puntosAFavor(Car $car, array $argumentos = []): array
    {
        $orden = ['high' => 0, 'medium' => 1, 'low' => 2];
        $pros = [];
        foreach (is_array($car->pros) ? $car->pros : [] as $i => $pro) {
            $texto = is_array($pro) ? (string) ($pro['text'] ?? '') : (string) $pro;
            $peso = is_array($pro) ? (string) ($pro['weight'] ?? 'medium') : 'medium';
            if (trim($texto) !== '') {
                $pros[] = ['texto' => trim($texto), 'orden' => $orden[$peso] ?? 1, 'i' => $i];
            }
        }
        usort($pros, fn ($a, $b) => [$a['orden'], $a['i']] <=> [$b['orden'], $b['i']]);

        $puntos = [];
        $vistos = [];
        foreach (array_column($pros, 'texto') as $texto) {
            $palabras = self::palabrasClave($texto);
            if (in_array($palabras, $vistos, true)) {
                continue;
            }
            $vistos[] = $palabras;
            $puntos[] = $texto;
        }

        // Los argumentos de marketing solo entran si cuentan algo nuevo.
        foreach ($argumentos as $texto) {
            if (! is_string($texto) || trim($texto) === '') {
                continue;
            }
            $palabras = self::palabrasClave($texto);
            if ($palabras === [] || self::repetido($palabras, $vistos)) {
                continue;
            }
            $vistos[] = $palabras;
            $puntos[] = trim($texto);
        }

        return FiltroPublico::lista($puntos);
    }

    /**
     * Palabras con significado (mas de 3 letras) del punto, en minusculas.
     *
     * @return array<int, string>
     */
    private static function palabrasClave(string $texto): array
    {
        $texto = mb_strtolower(str_replace('**', '', $texto));
        $palabras = preg_split('/[^\p{L}\p{N}]+/u', $texto, -1, PREG_SPLIT_NO_EMPTY) ?: [];

        return array_values(array_unique(array_filter($palabras, fn ($p) => mb_strlen($p) > 3)));
    }

    /**
     * Un argumento se considera repetido si al menos la mitad de sus
     * palabras clave ya estan en un punto incluido: el argumento de marketing
     * «Cadena ya cambiada: con facturas de Mazda» repite la virtud del
     * anuncio «Cadena de distribucion ya cambiada, con facturas de Mazda».
     *
     * @param  array<int, string>  $palabras
     * @param  array<int, array<int, string>>  $vistos
     */
    private static function repetido(array $palabras, array $vistos): bool
    {
        foreach ($vistos as $otras) {
            $comunes = count(array_intersect($palabras, $otras));
            if ($comunes / max(1, count($palabras)) >= 0.5) {
                return true;
            }
        }

        return false;
    }
}
