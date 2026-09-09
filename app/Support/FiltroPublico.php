<?php

namespace App\Support;

/**
 * Filtro de contenido público (A22b / A27 / A31).
 *
 * El enlace /c/{token} se reenvía por WhatsApp: es una página PÚBLICA. Todo lo
 * que no publicaríamos en un anuncio de portal tampoco puede salir aquí, ni
 * aunque venga del ZIP de la IA o de datos antiguos guardados en la base.
 *
 * Este filtro es la última barrera antes del render: descarta las líneas que
 * contienen análisis interno (hueco de importación, vendibilidad, competencia,
 * datos del vendedor de origen, veredicto interno) y el lenguaje de vendedor
 * o de garantía que el modelo de negocio prohíbe.
 *
 * @see .ai/rules/business-model.md
 */
class FiltroPublico
{
    /** Análisis interno: nunca sale al cliente. */
    private const PATRONES_INTERNOS = [
        '/vendibilidad/iu',
        '/\b\d{1,3}\s*\/\s*100\b/u',
        '/\bscore\b/iu',
        '/hueco\s+(de|del|%)/iu',
        '/antes\s+de\s+(costes|gastos)/iu',
        '/ahorro\s+estimado/iu',
        '/\bmargen\b/iu',
        '/precio\s+de\s+origen/iu',
        '/coste\s+puesto/iu',
        // Banda/cuartil/cifras de precio alemán: son análisis interno de la IA
        // (dónde está el coche en la banda del mercado de origen). El cliente
        // no necesita saberlo: ve el rango español y su estimación final.
        '/banda\s+(alemana?|alemán|de\s+origen|de\s+compra)/iu',
        '/cuartil\s+(bajo|alto|superior|inferior)/iu',
        '/\b\d{1,3}([.,]\d{3})*\s*(€|eur)/iu',
        // Cualquier referencia al mercado de origen con adjetivos internos.
        '/mercado\s+(alemana?|alemán|de\s+origen|de\s+compra)/iu',
        // "mercado X muy estrecho" tolerante a palabras intermedias
        // (español, europeo, de segunda mano, etc.).
        '/mercado\s+\w[\w\s]{0,40}\s+(muy\s+)?estrecho/iu',
        '/mercado\s+\w[\w\s]{0,40}\s+(muy\s+)?escaso/iu',
        '/escasez(\s+de|\s+en|\s+alta)/iu',
        '/alta\s+escasez/iu',
        '/\bunidades\s+(disponibles|en\s+(venta|toda|el\s+mercado))/iu',
        '/en\s+toda\s+Espa(ñ|n)a/iu',
        '/vendedor\s+(profesional|alem)/iu',
        '/\d[,.]\d\s*★|★/u',
        '/operaciones\s+de\s+exportaci(ó|o)n/iu',
        '/\bcomparables?\b/iu',
        '/mobile\.de|autoscout|kleinanzeigen/iu',
        '/honorarios?\D{0,15}\d/iu',
    ];

    /** Lenguaje de vendedor o de garantía: prohibido (A31). */
    private const PATRONES_VENDEDOR = [
        '/(?<!no\s)\bvendemos\b/iu',
        '/\bse\s+vende\b/iu',
        '/\ben\s+venta\b/iu',
        '/nuestro\s+coche|nuestros\s+coches|nuestro\s+stock/iu',
        '/(nuestro|nuestra|en nuestro)\s+concesionario/iu',
        '/(?<!no )\bsomos\s+(un\s+|una\s+)?concesionario/iu',
        '/(?<!no\s)\bgarantizamos\b/iu',
        '/con\s+garant(í|i)a/iu',
        '/garant(í|i)a\s+de\s+\d+\s*(meses|años|anos)/iu',
    ];

    /** Superlativos sin prueba (A29). */
    private const PATRONES_SUPERLATIVOS = [
        '/\bchollo\b|\bganga\b|irrepetible|oportunidad\s+(única|unica)/iu',
        '/impecable|inmaculado|\bjoya\b/iu',
        '/excelente\s+compra|buena\s+compra/iu',
    ];

    /**
     * ¿Esta línea puede publicarse al cliente?
     */
    public static function permitida(?string $texto): bool
    {
        $texto = trim((string) $texto);
        if ($texto === '') {
            return false;
        }

        foreach (self::todosLosPatrones() as $patron) {
            if (preg_match($patron, $texto) === 1) {
                return false;
            }
        }

        return true;
    }

    /**
     * Filtra una lista dejando solo lo publicable.
     *
     * @param  array<int, string>  $items
     * @return array<int, string>
     */
    public static function lista(array $items): array
    {
        return array_values(array_filter($items, fn ($item) => self::permitida(is_string($item) ? $item : null)));
    }

    /**
     * Devuelve el texto solo si es publicable; si no, null.
     */
    public static function texto(?string $texto): ?string
    {
        return self::permitida($texto) ? trim((string) $texto) : null;
    }

    /**
     * Motivos por los que un texto no es publicable (para diagnóstico y tests).
     *
     * @return array<int, string>
     */
    public static function motivos(?string $texto): array
    {
        $texto = trim((string) $texto);
        $motivos = [];

        foreach (self::PATRONES_INTERNOS as $patron) {
            if (preg_match($patron, $texto) === 1) {
                $motivos[] = 'dato interno';
                break;
            }
        }
        foreach (self::PATRONES_VENDEDOR as $patron) {
            if (preg_match($patron, $texto) === 1) {
                $motivos[] = 'lenguaje de vendedor o garantía';
                break;
            }
        }
        foreach (self::PATRONES_SUPERLATIVOS as $patron) {
            if (preg_match($patron, $texto) === 1) {
                $motivos[] = 'superlativo sin prueba';
                break;
            }
        }

        return $motivos;
    }

    /**
     * @return array<int, string>
     */
    private static function todosLosPatrones(): array
    {
        return array_merge(self::PATRONES_INTERNOS, self::PATRONES_VENDEDOR, self::PATRONES_SUPERLATIVOS);
    }
}
