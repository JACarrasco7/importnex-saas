<?php

namespace App\Support;

/**
 * Normaliza las listas que devuelve la IA desde sus campos JSON.
 *
 * La IA puede guardar el mismo concepto ("pros", "cons", "tips", "red_flags",
 * "A_FAVOR", "EN_CONTRA", etc.) en dos formas:
 *  - vieja: array de strings  -> ["Pro 1", "Pro 2"]
 *  - nueva: array de objetos  -> [["text" => "Pro 1", "weight" => "high"], ...]
 *
 * Los blades llaman a `IaList::normalizar($campo)` para no romperse cuando
 * llega el formato nuevo. Devuelve SIEMPRE `array<int, string>` (lista
 * limpia de strings, sin nulos).
 */
class IaList
{
    /**
     * @param  mixed  $raw  Lo que venga del modelo (array|null|string|objeto suelto)
     * @return array<int, string>
     */
    public static function normalizar(mixed $raw): array
    {
        if ($raw === null) {
            return [];
        }

        // Si la IA guardó un string suelto (raro pero posible), tratarlo como
        // lista de un solo elemento.
        if (is_string($raw)) {
            $txt = trim($raw);

            return $txt === '' ? [] : [$txt];
        }

        if (! is_array($raw)) {
            return [];
        }

        $out = [];
        foreach ($raw as $item) {
            if ($item === null) {
                continue;
            }

            // string suelto (formato viejo)
            if (is_string($item)) {
                $txt = trim($item);
                if ($txt !== '') {
                    $out[] = $txt;
                }

                continue;
            }

            // objeto con campo text/texto/descripcion (formato nuevo)
            if (is_array($item)) {
                $texto = $item['text']
                    ?? $item['texto']
                    ?? $item['descripcion']
                    ?? $item['description']
                    ?? null;

                if (is_string($texto)) {
                    $txt = trim($texto);
                    if ($txt !== '') {
                        $out[] = $txt;
                    }
                }

                continue;
            }

            // Cualquier otro tipo (número, booleano) se ignora silenciosamente.
        }

        return $out;
    }
}
