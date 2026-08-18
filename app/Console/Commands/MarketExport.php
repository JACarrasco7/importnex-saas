<?php

namespace App\Console\Commands;

use App\Models\MarketModel;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

/**
 * Exporta el mapa de mercado desde la BD a datos_mercado.json (ruta pactada).
 * Cierra el bucle skill↔SaaS: las correcciones humanas de /mercado/admin
 * (veredicto_fuente=humano) se devuelven a la skill estudio-mercado para que
 * NO las sobrescriba en la próxima pasada.
 *
 * Uso:
 *   php artisan market:export
 *   php artisan market:export --file=path/to/datos_mercado.json
 */
class MarketExport extends Command
{
    protected $signature = 'market:export
                            {--file= : Ruta destino (default: Desktop/JJImportMotors/datos_mercado.json)}
                            {--org= : Organización a exportar (default: todas)}';

    protected $description = 'Exporta market_models a datos_mercado.json (bucle skill↔SaaS).';

    public function handle(): int
    {
        $models = MarketModel::query()
            ->with('history')
            ->when($this->option('org'), fn ($q) => $q->whereHas('organization', fn ($o) => $o->where('name', $this->option('org'))))
            ->get();

        if ($models->isEmpty()) {
            $this->warn('No hay modelos de mercado para exportar.');

            return self::SUCCESS;
        }

        $categorias = [];
        $marcas = [];
        foreach ($models as $m) {
            $categorias[$m->categoria][] = $this->toRow($m);
            $marca = explode('-', $m->slug)[0] ?? '?';
            $marcas[$marca]['modelos'][] = $m->slug;
        }
        foreach ($marcas as $marca => $v) {
            $marcas[$marca]['total'] = count($v['modelos']);
        }

        $payload = [
            'schema_version' => '1.2',
            'generado' => now()->toDateString(),
            'tipo_estudio' => 'export_saas',
            'refrescar_antes_de' => [
                'showstoppers' => now()->addWeeks(2)->toDateString(),
                'alta_rotacion' => now()->addWeeks(3)->toDateString(),
                'gemas_economicas' => now()->addWeeks(4)->toDateString(),
            ],
            'marcas' => $marcas,
            'ruta_canonica' => $this->resolveFile(),
            'fuentes' => ['origen' => 'laravel'],
            'categorias' => $categorias,
        ];

        $file = $this->resolveFile();
        File::ensureDirectoryExists(dirname($file));
        File::put($file, json_encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));

        $this->info("Exportados {$models->count()} modelos a {$file}");

        return self::SUCCESS;
    }

    private function resolveFile(): string
    {
        if ($file = $this->option('file')) {
            return $file;
        }

        return getenv('USERPROFILE').'\\Desktop\\JJImportMotors\\datos_mercado.json';
    }

    private function toRow(MarketModel $m): array
    {
        return [
            'slug' => $m->slug,
            'alias' => $m->alias,
            'modelo' => $m->modelo,
            'version' => $m->version,
            'categoria' => $m->categoria,
            'segmento' => $m->segmento,
            'rango_precio' => $m->rango_precio,
            'tipo_cliente' => $m->tipo_cliente,
            'tipos_cliente_secundarios' => $m->tipos_cliente_secundarios,
            'categorias_secundarias' => $m->categorias_secundarias,
            'oferta_de' => $m->oferta_de,
            'oferta_es' => $m->oferta_es,
            'mediana_de' => $m->mediana_de !== null ? (int) $m->mediana_de : null,
            'mediana_es' => $m->mediana_es !== null ? (int) $m->mediana_es : null,
            'precio_desde_de' => $m->precio_desde_de !== null ? (int) $m->precio_desde_de : null,
            'precio_desde_es' => $m->precio_desde_es !== null ? (int) $m->precio_desde_es : null,
            'sello_precio_de' => $m->sello_precio_de,
            'sello_precio_es' => $m->sello_precio_es,
            'hueco_pct' => $m->hueco_pct !== null ? (float) $m->hueco_pct : null,
            'hueco_neto_pct' => $m->hueco_neto_pct !== null ? (float) $m->hueco_neto_pct : null,
            'coste_importacion_estimado' => $m->coste_importacion_estimado,
            'iedmt_estimado' => $m->iedmt_estimado,
            'rotacion_dias_de' => $m->rotacion_dias_de,
            'rotacion_dias_es' => $m->rotacion_dias_es,
            'rotacion_fuente' => $m->rotacion_fuente,
            'demanda_trends' => $m->demanda_trends,
            'transferencias_mes_dgt' => $m->transferencias_mes_dgt,
            'matriculaciones_kba' => $m->matriculaciones_kba,
            'veredicto' => $m->veredicto,
            'veredicto_fuente' => $m->veredicto_fuente,
            'mejor_mercado' => $m->mejor_mercado,
            'fuente_medicion' => $m->fuente_medicion,
            'confianza_precio' => $m->confianza_precio,
            'oportunidad' => (bool) $m->oportunidad,
            'publicar_en_catalogo' => (bool) $m->publicar_en_catalogo,
            'foto_url' => $m->foto_url,
            'vendibilidad' => $m->vendibilidad,
            'pendiente_fase2' => (bool) $m->pendiente_fase2,
            'historial' => $m->history->sortByDesc('medido_el')->take(5)->values()->map(fn ($h) => [
                'medido_el' => $h->medido_el?->toDateString(),
                'mediana_de' => (int) $h->mediana_de,
                'mediana_es' => (int) $h->mediana_es,
                'hueco_pct' => (float) $h->hueco_pct,
            ])->all(),
            'nota' => $m->nota,
            'tasacion_pro' => $m->tasacion_pro,
            'refrescar_antes_de_categoria' => $m->refrescar_antes_de?->toDateString(),
        ];
    }
}
