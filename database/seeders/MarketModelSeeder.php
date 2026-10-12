<?php

namespace Database\Seeders;

use App\Models\MarketModel;
use App\Models\Organization;
use Illuminate\Database\Seeder;

/**
 * Seed de muestra del mapa de mercado — evita arrancar el dashboard /mercado vacío.
 * Reemplazar con `php artisan market:import --file=datos_mercado.json` cuando exista el estudio real.
 */
class MarketModelSeeder extends Seeder
{
    public function run(): void
    {
        $samples = [
            [
                'slug' => 'opel-astra-j-opc',
                'alias' => ['astra-opc', 'astra j opc', 'opel-astra-opc'],
                'categoria' => 'showstoppers',
                'segmento' => 'deportivo',
                'rango_precio' => '8-14k',
                'tipo_cliente' => 'deporte_ocio',
                'tipos_cliente_secundarios' => ['impacto_showstopper'],
                'modelo' => 'Opel Astra J OPC',
                'version' => '2.0 Turbo 280cv',
                'oferta_de' => 30,
                'oferta_es' => 12,
                'mediana_de' => 10500,
                'mediana_es' => 15200,
                'precio_desde_de' => 8999,
                'precio_desde_es' => 12500,
                'sello_precio_de' => 'Guter Preis',
                'sello_precio_es' => 'Buen precio',
                'hueco_pct' => 30.9,
                'hueco_neto_pct' => 22.8,
                'coste_importacion_estimado' => 1129,
                'iedmt_estimado' => 0,
                'rotacion_dias_de' => 38,
                'demanda_trends' => 'estable',
                'veredicto' => 'verde',
                'mejor_mercado' => 'DE',
                'confianza_precio' => 4,
                'nota' => 'Medición real 10-ago-2026 (7/7 fuentes). Modelo veterano, cadena distribución pre-2014.',
                'refrescar_antes_de' => now()->addDays(14),
            ],
            [
                'slug' => 'vw-golf-7-gti',
                'alias' => ['golf-gti', 'golf-vii-gti', 'golf 7 gti'],
                'categoria' => 'showstoppers',
                'segmento' => 'compacto',
                'rango_precio' => '14-25k',
                'tipo_cliente' => 'deporte_ocio',
                'tipos_cliente_secundarios' => ['impacto_showstopper'],
                'modelo' => 'VW Golf 7 GTI',
                'version' => 'GTI Performance 245cv',
                'oferta_de' => 2652,
                'oferta_es' => 1167,
                'mediana_de' => 23500,
                'mediana_es' => 26500,
                'precio_desde_de' => 21900,
                'precio_desde_es' => 23950,
                'hueco_pct' => 11.3,
                'hueco_neto_pct' => 5.4,
                'coste_importacion_estimado' => 1129,
                'iedmt_estimado' => 450,
                'rotacion_dias_de' => 42,
                'demanda_trends' => 'estable',
                'veredicto' => 'amarillo',
                'mejor_mercado' => 'DE',
                'confianza_precio' => 3,
                'nota' => 'Bruto pasa umbral, neto deja poco margen. Falta aislar Golf R puro.',
                'refrescar_antes_de' => now()->addDays(14),
            ],
            [
                'slug' => 'bmw-serie-3-m-sport',
                'alias' => ['bmw-320i-m-sport', 'serie 3 m sport', 'bmw-330i-msport'],
                'categoria' => 'alta_rotacion',
                'segmento' => 'berlina',
                'rango_precio' => '14-25k',
                'tipo_cliente' => 'premium_imagen',
                'tipos_cliente_secundarios' => ['negocio_reventa'],
                'modelo' => 'BMW Serie 3 M Sport',
                'version' => '330i 252cv',
                'oferta_de' => 1207,
                'oferta_es' => 4601,
                'mediana_de' => 17000,
                'mediana_es' => 25900,
                'precio_desde_de' => 14000,
                'precio_desde_es' => 22000,
                'hueco_pct' => 34.0,
                'hueco_neto_pct' => 29.0,
                'coste_importacion_estimado' => 1129,
                'iedmt_estimado' => 500,
                'rotacion_dias_de' => 30,
                'demanda_trends' => 'creciente',
                'veredicto' => 'verde',
                'mejor_mercado' => 'DE',
                'confianza_precio' => 3,
                'oportunidad' => true,
                'nota' => 'Alta demanda ES + hueco neto fuerte. Candidato nº1 rotación.',
                'refrescar_antes_de' => now()->addDays(21),
            ],
            [
                'slug' => 'ford-fiesta-st-line',
                'alias' => ['fiesta-stline', 'fiesta st-line', 'ford-fiesta-stline'],
                'categoria' => 'gemas_economicas',
                'segmento' => 'urbano',
                'rango_precio' => '8-14k',
                'tipo_cliente' => 'primer_coche',
                'tipos_cliente_secundarios' => ['diario_eficiencia'],
                'modelo' => 'Ford Fiesta ST-Line',
                'version' => '1.0 EcoBoost 125cv',
                'oferta_de' => 863,
                'oferta_es' => 1505,
                'mediana_de' => 10666,
                'mediana_es' => 13490,
                'precio_desde_de' => 9500,
                'precio_desde_es' => 11800,
                'hueco_pct' => 21.0,
                'hueco_neto_pct' => 15.0,
                'coste_importacion_estimado' => 1129,
                'iedmt_estimado' => 200,
                'rotacion_dias_de' => 25,
                'demanda_trends' => 'estable',
                'veredicto' => 'verde',
                'mejor_mercado' => 'DE',
                'confianza_precio' => 3,
                'nota' => 'Mejor gema económica. Ideal primer coche.',
                'refrescar_antes_de' => now()->addDays(28),
            ],
            [
                'slug' => 'cupra-ateca-vz',
                'alias' => ['ateca-vz', 'cupra-ateca-4drive', 'ateca vz'],
                'categoria' => 'showstoppers',
                'segmento' => 'suv',
                'rango_precio' => '14-25k',
                'tipo_cliente' => 'impacto_showstopper',
                'tipos_cliente_secundarios' => ['deporte_ocio'],
                'modelo' => 'Cupra Ateca VZ',
                'version' => '300cv 4Drive',
                'oferta_de' => 6013,
                'oferta_es' => 660,
                'mediana_de' => 26980,
                'mediana_es' => 26900,
                'precio_desde_de' => 25000,
                'precio_desde_es' => 24800,
                'hueco_pct' => -0.3,
                'hueco_neto_pct' => -5.2,
                'coste_importacion_estimado' => 1129,
                'iedmt_estimado' => 600,
                'rotacion_dias_de' => 45,
                'demanda_trends' => 'creciente',
                'veredicto' => 'rojo',
                'mejor_mercado' => 'paridad',
                'confianza_precio' => 3,
                'nota' => 'Paridad ES≈DE: sin negocio de importación, pero atractivo visual alto para marketing.',
                'refrescar_antes_de' => now()->addDays(14),
            ],
        ];

        // Solo organizaciones públicas son visibles en el catálogo /mercado;
        // si no hay ninguna, se deja null (global) para que las muestras no queden invisibles.
        $orgId = Organization::where('is_public', true)->orderBy('id')->value('id');

        foreach ($samples as $row) {
            $row['organization_id'] = $orgId;
            $row['publicar_en_catalogo'] = true; // #5 — muestras visibles en /mercado
            $model = MarketModel::updateOrCreate(['slug' => $row['slug']], $row);
            if ($model->vendibilidad === null && $model->hueco_pct !== null) {
                $model->update(['vendibilidad' => $model->calcularVendibilidad()]);
            }
        }
    }
}
