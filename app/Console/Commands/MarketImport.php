<?php

namespace App\Console\Commands;

use App\Models\MarketModel;
use App\Models\MarketModelHistory;
use App\Models\Organization;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

/**
 * Importa el mapa de mercado (datos_mercado.json) generado por la skill estudio-mercado.
 * Upsert por slug en market_models (mapa vigente del SaaS).
 *
 * Uso:
 *   php artisan market:import --file=path/to/datos_mercado.json
 *   php artisan market:import --file=... --org="JJ Import Motors"
 *   php artisan market:import --file=... --dry-run
 */
class MarketImport extends Command
{
    protected $signature = 'market:import
                            {--file= : Ruta al datos_mercado.json (obligatorio)}
                            {--org= : Nombre de la organización destino}
                            {--dry-run : Validar sin guardar}';

    protected $description = 'Importa el mapa de mercado (datos_mercado.json) a market_models.';

    public function handle(): int
    {
        $file = $this->option('file');
        if (! $file || ! File::exists($file)) {
            $this->error('Falta --file= con la ruta al datos_mercado.json');

            return self::FAILURE;
        }

        $org = $this->resolveOrg();
        if (! $org) {
            $this->error('No organization found. Create one first or pass --org=');

            return self::FAILURE;
        }

        try {
            $payload = json_decode(File::get($file), true, flags: JSON_THROW_ON_ERROR);
        } catch (\Throwable $e) {
            $this->error('JSON inválido: '.$e->getMessage());

            return self::FAILURE;
        }

        $rows = $this->flatten($payload);
        if (empty($rows)) {
            $this->warn('El JSON no contiene modelos en la estructura esperada (categorias.*).');

            return self::FAILURE;
        }

        $created = 0;
        $updated = 0;
        $skipped = 0;
        $schemaVersion = (string) ($payload['schema_version'] ?? '1.1');
        $bar = $this->output->createProgressBar(count($rows));
        $bar->start();

        foreach ($rows as $row) {
            $bar->advance();
            $slug = $row['slug'] ?? null;

            // Validación: slug obligatorio + hueco_pct numérico si viene
            if (! $slug) {
                $skipped++;
                $this->error("\n[SKIP] entrada sin slug: ".($row['modelo'] ?? '?'));

                continue;
            }
            if (array_key_exists('hueco_pct', $row) && ! is_numeric($row['hueco_pct'])) {
                $skipped++;
                $this->error("\n[SKIP] {$slug}: hueco_pct no numérico");

                continue;
            }
            if (! in_array($row['categoria'] ?? null, MarketModel::CATEGORIAS, true)) {
                $skipped++;
                $this->error("\n[SKIP] {$slug}: categoria inválida");

                continue;
            }
            if (($row['segmento'] ?? null) && ! in_array($row['segmento'], MarketModel::SEGMENTOS, true)) {
                $skipped++;
                $this->error("\n[SKIP] {$slug}: segmento inválido");

                continue;
            }
            if (($row['tipo_cliente'] ?? null) && ! in_array($row['tipo_cliente'], MarketModel::TIPOS_CLIENTE, true)) {
                $skipped++;
                $this->error("\n[SKIP] {$slug}: tipo_cliente inválido");

                continue;
            }
            if (($row['veredicto'] ?? null) && ! in_array($row['veredicto'], MarketModel::VEREDICTOS, true)) {
                $skipped++;
                $this->error("\n[SKIP] {$slug}: veredicto inválido");

                continue;
            }
            if (($row['mejor_mercado'] ?? null) && ! in_array($row['mejor_mercado'], MarketModel::MEJORES_MERCADOS, true)) {
                $skipped++;
                $this->error("\n[SKIP] {$slug}: mejor_mercado inválido");

                continue;
            }
            if (($row['rango_precio'] ?? null) && ! in_array($row['rango_precio'], MarketModel::RANGOS_PRECIO, true)) {
                $skipped++;
                $this->error("\n[SKIP] {$slug}: rango_precio inválido");

                continue;
            }

            if ($this->option('dry-run')) {
                $this->line("\n[DRY] {$slug} -> OK (validated)");

                continue;
            }

            $data = $this->mapRow($row, $org->id, $schemaVersion);
            $existing = MarketModel::where('slug', $slug)->first();

            // #5 — Upsert scoped por organización: si el slug ya pertenece a OTRA
            // org (no global), NO reasignarlo (evita sacar un modelo del alcance
            // de su organización propietaria al importar con --org distinto).
            if ($existing && $existing->organization_id !== null && $existing->organization_id !== $org->id) {
                $skipped++;
                $this->warn("\n[SKIP] {$slug}: pertenece a otra organización (org {$existing->organization_id})");

                continue;
            }

            $isNew = ! $existing;

            if ($existing) {
                // #1 — bucle skill↔SaaS: si el humano corrigió el veredicto, NO sobrescribirlo
                if ($existing->veredicto_fuente === 'humano') {
                    unset($data['veredicto'], $data['veredicto_fuente']);
                }
                $existing->update($data);
                $model = $existing;
            } else {
                $model = MarketModel::create($data);
            }

            // #4 — histórico de precios: solo si la medición cambió respecto a la última del mismo día
            $last = $model->history()->orderByDesc('medido_el')->first();
            $same = $last && $last->medido_el?->isToday()
                && (float) $last->mediana_de === (float) ($data['mediana_de'] ?? 0)
                && (float) $last->mediana_es === (float) ($data['mediana_es'] ?? 0);
            if (! $same && (($data['mediana_de'] ?? null) || ($data['mediana_es'] ?? null))) {
                MarketModelHistory::create([
                    'market_model_id' => $model->id,
                    'mediana_de' => $data['mediana_de'] ?? null,
                    'mediana_es' => $data['mediana_es'] ?? null,
                    'hueco_pct' => $data['hueco_pct'] ?? null,
                    'hueco_neto_pct' => $data['hueco_neto_pct'] ?? null,
                    'fuente_medicion' => $data['fuente_medicion'] ?? 'estudio',
                    'medido_el' => now()->toDateString(),
                ]);
            }

            $isNew ? $created++ : $updated++;
            Log::info('market:import', ['slug' => $slug, 'created' => $isNew]);

            // #10 — vendibilidad fallback: si no vino del estudio, calcúlala
            if ($model->vendibilidad === null && $model->hueco_pct !== null) {
                $model->update(['vendibilidad' => $model->calcularVendibilidad()]);
            }
        }

        $bar->finish();
        $this->newLine(2);

        $this->info('Importación de mercado completada:');
        $this->line("  Creados:      {$created}");
        $this->line("  Actualizados: {$updated}");
        $this->line("  Saltados:     {$skipped}");

        // #11 — Invalidar cache de stats (API pública y puente) para no servir
        // hasta 30 min de datos viejos tras una importación.
        Cache::forget('market:public-stats');
        Cache::forget('market:stats:'.($org->id ?? 'global'));
        Cache::forget('market:stats:global');

        return self::SUCCESS;
    }

    /**
     * Aplana {categorias: {showstoppers: [...], ...}} a filas con categoria.
     *
     * @return array<int, array<string, mixed>>
     */
    private function flatten(array $payload): array
    {
        $rows = [];
        foreach (MarketModel::CATEGORIAS as $categoria) {
            foreach ($payload['categorias'][$categoria] ?? [] as $item) {
                $rows[] = array_merge(['categoria' => $categoria], $item);
            }
        }

        return $rows;
    }

    /**
     * @param  array<string, mixed>  $row
     * @return array<string, mixed>
     */
    private function mapRow(array $row, int $orgId, string $schemaVersion): array
    {
        $alias = collect($row['alias'] ?? [])
            ->map(fn ($a) => trim(mb_strtolower((string) $a)))
            ->filter()
            ->unique()
            ->values()
            ->all();

        return [
            'organization_id' => $orgId,
            'slug' => $row['slug'],
            'alias' => $alias ?: null,
            'categoria' => $row['categoria'],
            'segmento' => $row['segmento'] ?? null,
            'rango_precio' => $row['rango_precio'] ?? null,
            'tipo_cliente' => $row['tipo_cliente'] ?? null,
            'tipos_cliente_secundarios' => $row['tipos_cliente_secundarios'] ?? null,
            'categorias_secundarias' => $row['categorias_secundarias'] ?? null,
            'modelo' => $row['modelo'] ?? Str::headline(Str::replace('-', ' ', $row['slug'])),
            'version' => $row['version'] ?? null,
            'oferta_de' => $row['oferta_de'] ?? null,
            'oferta_es' => $row['oferta_es'] ?? null,
            'mediana_de' => $row['mediana_de'] ?? null,
            'mediana_es' => $row['mediana_es'] ?? null,
            'precio_desde_de' => $row['precio_desde_de'] ?? null,
            'precio_desde_es' => $row['precio_desde_es'] ?? null,
            'sello_precio_de' => $row['sello_precio_de'] ?? null,
            'sello_precio_es' => $row['sello_precio_es'] ?? null,
            'hueco_pct' => $row['hueco_pct'] ?? null,
            'hueco_neto_pct' => $row['hueco_neto_pct'] ?? null,
            'coste_importacion_estimado' => $row['coste_importacion_estimado'] ?? null,
            'iedmt_estimado' => $row['iedmt_estimado'] ?? null,
            'rotacion_dias_de' => $row['rotacion_dias_de'] ?? null,
            'rotacion_dias_es' => $row['rotacion_dias_es'] ?? null,
            'rotacion_fuente' => $row['rotacion_fuente'] ?? null,
            'demanda_trends' => $row['demanda_trends'] ?? null,
            'transferencias_mes_dgt' => $row['transferencias_mes_dgt'] ?? null,
            'matriculaciones_kba' => $row['matriculaciones_kba'] ?? null,
            'veredicto' => $row['veredicto'] ?? 'amarillo',
            'mejor_mercado' => $row['mejor_mercado'] ?? 'DE',
            'fuente_medicion' => $row['fuente_medicion'] ?? 'estudio',
            'confianza_precio' => $row['confianza_precio'] ?? null,
            'oportunidad' => (bool) ($row['oportunidad'] ?? false),
            'publicar_en_catalogo' => (bool) ($row['publicar_en_catalogo'] ?? false),
            'foto_url' => $row['foto_url'] ?? null,
            'vendibilidad' => $row['vendibilidad'] ?? null,
            'pendiente_fase2' => (bool) ($row['pendiente_fase2'] ?? false),
            'query_reejecutable' => $row['query_reejecutable'] ?? null,
            'nota' => $row['nota'] ?? null,
            'tasacion_pro' => $row['tasacion_pro'] ?? null,
            'refrescar_antes_de' => $row['refrescar_antes_de_categoria']
                ?? ($row['refrescar_antes_de'] ?? null),
            'schema_version' => $schemaVersion,
        ];
    }

    private function resolveOrg(): ?Organization
    {
        $name = $this->option('org');
        if ($name) {
            return Organization::where('name', $name)->first();
        }

        return Organization::first();
    }
}
