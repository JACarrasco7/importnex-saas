<?php

namespace App\Support;

class Esqueleto
{
    public array $nombrados = [];   // ['TITULO' => ['Opel Astra...'], 'SPEC' => [...], ...]
    public array $orden = [];       // [['nombre' => 'TITULO', 'texto' => '...'], ...]

    public static function desde(string $contenido): self
    {
        $e = new self();

        foreach (preg_split('/\R/', $contenido) as $linea) {
            if (str_starts_with(ltrim($linea), '#')) {
                continue;                                   // comentario
            }

            if (preg_match('/^\[([A-Z0-9_]+)\]\s?(.*)$/', $linea, $m)) {
                $e->orden[] = ['nombre' => $m[1], 'texto' => trim($m[2])];
                continue;
            }

            if ($e->orden && trim($linea) !== '') {          // continuación multilínea
                $i = count($e->orden) - 1;
                $e->orden[$i]['texto'] = trim($e->orden[$i]['texto'] . "\n" . rtrim($linea));
            }
        }

        foreach ($e->orden as $bloque) {                     // índice por nombre
            $e->nombrados[$bloque['nombre']][] = $bloque['texto'];
        }

        return $e;
    }

    /** Primer valor de un bloque, o null. */
    public function uno(string $nombre): ?string
    {
        return $this->nombrados[$nombre][0] ?? null;
    }

    /** Todos los valores de un bloque repetido. */
    public function todos(string $nombre): array
    {
        return $this->nombrados[$nombre] ?? [];
    }

    /** Bloque de campos separados por ' | ', ya troceado. */
    public function filas(string $nombre): array
    {
        return array_map(
            fn ($t) => array_map('trim', explode('|', $t)),
            $this->todos($nombre)
        );
    }

    /** Agrupa bloques repetidos: cada [ASPECTO] abre un grupo con lo que le sigue. */
    public function grupos(string $cabecera): array
    {
        $grupos = [];
        $actual = null;

        foreach ($this->orden as $bloque) {
            if ($bloque['nombre'] === $cabecera) {
                if ($actual) {
                    $grupos[] = $actual;
                }
                $actual = [$cabecera => $bloque['texto']];
            } elseif ($actual !== null && $bloque['nombre'] !== 'H2') {
                $actual[$bloque['nombre']] = $bloque['texto'];
            } elseif ($bloque['nombre'] === 'H2' && $actual) {
                $grupos[] = $actual;
                $actual = null;
            }
        }

        if ($actual) {
            $grupos[] = $actual;
        }

        return $grupos;
    }

    /** **negrita** → <strong>, escapando el resto. */
    public static function negrita(?string $texto): string
    {
        return preg_replace('/\*\*(.+?)\*\*/s', '<strong>$1</strong>', e($texto ?? ''));
    }
}
