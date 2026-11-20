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
}
