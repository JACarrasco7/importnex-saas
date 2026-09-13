<?php

namespace App\Http\Controllers;

use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Guías internas navegables desde el panel — sección "Guía" del menú lateral.
 *
 * El sidebar ya traía esta sección preparada pero **comentada** con la nota
 * "la ruta `guide.index` aún no existe y Ziggy lanza errores". Este controlador
 * crea esa ruta.
 *
 * Se listan con un **whitelist**: la ruta traduce a un fichero concreto de este
 * mapa, nunca se lee una ruta que venga de la URL (nada de path traversal).
 * Para publicar una guía nueva: se añade aquí.
 */
class GuideController extends Controller
{
    /**
     * slug de la URL => ruta del markdown, relativa a la raíz del proyecto.
     *
     * @var array<string, string>
     */
    private const GUIAS = [
        'guia-de-uso' => 'docs/guias/00-guia-de-uso.md',
        'inicio-rapido' => 'docs/claude-desktop/GUIA_INICIO_RAPIDO.md',
        'primeros-pasos' => 'docs/guias/01-primeros-pasos.md',
        'flujo-a-unidad' => 'docs/guias/02-flujo-a-unidad.md',
        'flujo-b-modelo' => 'docs/guias/03-flujo-b-modelo.md',
        'flujo-c-mercado' => 'docs/guias/04-flujo-c-mercado.md',
        'flujo-d-descubrimiento' => 'docs/guias/05-flujo-d-descubrimiento.md',
        'informes' => 'docs/guias/06-informes.md',
        'cierre-venta' => 'docs/guias/07-cierre-venta.md',
        'solucion-problemas' => 'docs/guias/08-solucion-problemas.md',
    ];

    /** Abre la primera guía del índice. */
    public function index(): Response
    {
        return $this->render((string) array_key_first(self::GUIAS));
    }

    /** Abre una guía concreta. Slug desconocido => 404. */
    public function show(string $slug): Response
    {
        abort_unless(array_key_exists($slug, self::GUIAS), 404);

        return $this->render($slug);
    }

    private function render(string $slug): Response
    {
        $indice = [];
        foreach (self::GUIAS as $clave => $ruta) {
            $indice[] = [
                'slug' => $clave,
                'titulo' => $this->titulo($ruta, $clave),
            ];
        }

        return Inertia::render('Guide/Index', [
            'guias' => $indice,
            'actual' => [
                'slug' => $slug,
                'titulo' => $this->titulo(self::GUIAS[$slug], $slug),
                'html' => $this->markdown(self::GUIAS[$slug]),
            ],
        ]);
    }

    /** Título = primer `# ` del documento; si no lo hay, el slug humanizado. */
    private function titulo(string $ruta, string $slug): string
    {
        $contenido = $this->leer($ruta);

        if (preg_match('/^#\s+(.+)$/mu', $contenido, $coincidencias) === 1) {
            return trim($coincidencias[1]);
        }

        return Str::headline($slug);
    }

    /** Markdown de confianza (está en el repo) → HTML. Sin HTML crudo ni enlaces inseguros. */
    private function markdown(string $ruta): string
    {
        return (string) Str::markdown($this->leer($ruta), [
            'html_input' => 'strip',
            'allow_unsafe_links' => false,
        ]);
    }

    private function leer(string $ruta): string
    {
        $absoluta = base_path($ruta);

        if (! is_file($absoluta)) {
            return "# Guía no encontrada\n\nEl fichero `{$ruta}` no está en el despliegue.";
        }

        return (string) file_get_contents($absoluta);
    }
}
