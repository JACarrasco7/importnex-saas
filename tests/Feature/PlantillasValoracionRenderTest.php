<?php

namespace Tests\Feature;

use App\Models\Car;
use App\Support\Esqueleto;
use Illuminate\Support\Facades\View;
use Tests\TestCase;

/**
 * Valida que las plantillas de valoración de JJ Import Motors renderizan sin
 * error y muestran las secciones premium (KPI, coverage, verdict, badges).
 *
 * No necesita BD: renderiza la vista directamente con un Esqueleto de ejemplo.
 * (El controlador devuelve HTML cuando no hay Chrome, así que el render es
 * lo único que hay que garantizar aquí.)
 */
class PlantillasValoracionRenderTest extends TestCase
{
    private function esqueletoInformeCompleto(): string
    {
        return implode("\n", [
            '# Informe interno de ejemplo',
            '[COCHE_ID] vw-golf-gti-clubsport-2021-8b3f4a2c',
            '[TITULO] VW Golf GTI Clubsport 2021',
            '[FECHA_INFORME] 2026-08-12 14:32',
            '[SCORE_GLOBAL] 84',
            '[RECOMENDACION] COMPRA PRIORITARIA',
            '[SEMAFORO] verde',
            '[DICTAMEN] Compra prioritaria',
            '[CONFIANZA] Alta',
            '[RESUMEN] Buena operación con **margen** saneado.',
            '[COBERTURA] mobile.de | OK | 5 | mediana DE, 12 fichas',
            '[COBERTURA] coches.net | OK | 4 | mediana ES, Q1, N=8',
            '[COBERTURA] wallapop | degradada | 0 | bloqueada tras 2 intentos',
            '[MERCADO_ES_MEDIANA] 34500',
            '[MERCADO_DE_MEDIANA] 26800',
            '[CAND_URL] https://www.mobile.de/fahrzeuge/details.html?id=123456',
            '[CAND_VENDEDOR] Autohaus München Nord GmbH',
            '[CAND_VENDEDOR_TIPO] Profesional',
            '[CAND_VENDEDOR_RATING] 4.7',
            '[CAND_CIUDAD] Múnich',
            '[CAND_PRECIO] 26800',
            '[CAND_PRECIO_OBJ] 25950',
            '[CAND_DIAS] 45',
            '[CAND_CAMBIO_PRECIO] -850 € (hace 12 días)',
            '[COSTE] Compra del vehículo (negociado) | 25.950 €',
            '[COSTE] Transporte DE → ES (camión) | 900 €',
            '[TOTAL] Precio cliente | 28.500 €',
            '[MARGEN] Mediana ES | 6000 | 21,1 | green',
            '[MARGEN] Mínimo español | 3900 | 13,7 | amber',
            '[SCORE_DIM] Margen vs objetivo | 25 | 21',
            '[SCORE_DIM] Vendibilidad | 25 | 21',
            '[VENDIBILIDAD_TOTAL] 84',
            '[VENDIBILIDAD_FACTOR] Demanda | 30 | 26 | Top-10 golf GTI',
            '[VENTA] base | 33200 | 30-45 días | 4700 € | 16,5%',
            '[VENTA] conservador | 34500 | 50-70 días | 6000 € | 21,1%',
            '[VENTA_RECOMENDADA] BASE',
            '[A_FAVOR] Vendedor profesional con 4.7★ | alto',
            '[EN_CONTRA] 45 días publicado | medio',
            '[COMPARABLE] Golf GTI Clubsport Perf 2021 | 45.000 | 32.400 € | https://www.mobile.de/fahrzeuge/details.html?id=123456',
            '[COMPARABLE] Golf GTI Clubsport 2021 | 38.000 | 34.500 € | https://www.coches.net/...',
            '[FUENTE_LISTA] Recalls | kfz-rueckrufe.de | https://kfz-rueckrufe.de/...',
            '[BANDERA_AMARILLA] 45 días publicado → posible sobreprecio inicial',
            '[RIESGO] CO₂ en COC distinto | Media | Alto | Solicitar COC antes pago',
            '[ACCION] Enviar email negociación a vendedor (25.950 €)',
            '[ACCION] Solicitar COC al vendedor en paralelo',
            '[ACCION_PLAZO] 7 días (antes 19-08-2026)',
            '[VEREDICTO] COMPRA PRIORITARIA',
            '[PIE] Fuentes · Análisis interno JJ Import Motors · no distribuir',
        ]);
    }

    public function test_informe_interno_renderiza_secciones_premium(): void
    {
        $e = Esqueleto::desde($this->esqueletoInformeCompleto());

        $html = View::make('jj-import.informe-interno', [
            'e' => $e,
            'car' => new Car,
            'logo_base64' => null,
            'telefono_1' => '675 70 14 39',
            'telefono_2' => '691 48 59 27',
            'email' => 'jjimportmotors@gmail.com',
        ])->render();

        // KPI cards
        $this->assertStringContainsString('Score global', $html);
        $this->assertStringContainsString('84', $html);
        // Coverage grid con estados
        $this->assertStringContainsString('Cobertura de fuentes', $html);
        $this->assertStringContainsString('mobile.de', $html);
        $this->assertStringContainsString('cov-dot deg', $html);
        // Candidato
        $this->assertStringContainsString('Candidato analizado', $html);
        $this->assertStringContainsString('Autohaus München Nord GmbH', $html);
        // Margen / score / vendibilidad / venta
        $this->assertStringContainsString('Margen vs. mercado', $html);
        $this->assertStringContainsString('Score global desglosado', $html);
        $this->assertStringContainsString('Predicción de venta en España', $html);
        // Comparables premium con badge DE y fila pick
        $this->assertStringContainsString('badge-origen de', $html);
        $this->assertStringContainsString('ELEGIDO', $html);
        // Riesgos, banderas y acciones
        $this->assertStringContainsString('Riesgos y banderas', $html);
        $this->assertStringContainsString('Acción inmediata', $html);
        // Veredicto final
        $this->assertStringContainsString('Veredicto final', $html);
        $this->assertStringContainsString('COMPRA PRIORITARIA', $html);
    }

    public function test_ficha_coche_renderiza_kpi_y_badge_origen(): void
    {
        $contenido = implode("\n", [
            '# Ficha de ejemplo',
            '[TITULO] Opel Astra J OPC',
            '[CLAIM] Importado y verificado',
            '[SPEC] KM | 120.000',
            '[SPEC] Año | 2013',
            '[SPEC] Combustible | Gasolina',
            '[PRECIO] 14.900 €',
            '[AHORRO] 1.500 €',
            '[H2] Descripción',
            '[ARGUMENTO] Coche en perfecto estado',
        ]);

        $e = Esqueleto::desde($contenido);
        $car = new Car(['pais_origen' => 'Alemania']);

        $html = View::make('jj-import.ficha-coche', [
            'e' => $e,
            'car' => $car,
            'logo_base64' => null,
            'fotos' => [],
            'telefono_1' => '675 70 14 39',
            'telefono_2' => '691 48 59 27',
            'email' => 'jjimportmotors@gmail.com',
        ])->render();

        // KPI cards derivadas de SPEC/PRECIO/AHORRO
        $this->assertStringContainsString('Kilómetros', $html);
        $this->assertStringContainsString('120.000', $html);
        $this->assertStringContainsString('Año', $html);
        $this->assertStringContainsString('Precio final', $html);
        $this->assertStringContainsString('Ahorro', $html);
        // Badge de origen DE
        $this->assertStringContainsString('badge-origen de', $html);
        $this->assertStringContainsString('DE', $html);
    }

    public function test_folleto_coche_renderiza_veredicto_y_cta(): void
    {
        $contenido = implode("\n", [
            '# Folleto de ejemplo',
            '[TITULO] Opel Astra J OPC',
            '[CLAIM] Importado y verificado',
            '[SPEC] KM | 120.000',
            '[SPEC] Año | 2013',
            '[SPEC] Combustible | Gasolina',
            '[SPEC] Potencia | 200 CV',
            '[SPEC] Cambio | Manual',
            '[SPEC] Color | Azul',
            '[PRECIO] 14.900 €',
            '[AHORRO] 1.500 €',
            '[ARGUMENTO] Coche en perfecto estado | sin detalles',
            '[EQUIPAMIENTO] Techo panorámico',
            '[EQUIPAMIENTO] Navegación',
        ]);

        $e = Esqueleto::desde($contenido);
        $car = new Car([
            'pais_origen' => 'Alemania',
            'traffic_light' => 'green',
        ]);

        // 1px GIF en base64 para simular fotos del coche.
        $gif = 'data:image/gif;base64,R0lGODlhAQABAIAAAAAAAP///yH5BAEAAAAALAAAAAABAAEAAAIBRAA7';
        $fotos = array_fill(0, 4, $gif);

        $html = View::make('jj-import.folleto-coche', [
            'e' => $e,
            'car' => $car,
            'logo_base64' => null,
            'fotos' => $fotos,
            'telefono_1' => '675 70 14 39',
            'telefono_2' => '691 48 59 27',
            'email' => 'jjimportmotors@gmail.com',
        ])->render();

        // Portada / precio protagonista
        $this->assertStringContainsString('FOLLETO DEL COCHE', $html);
        $this->assertStringContainsString('14.900 €', $html);
        // KPI grid
        $this->assertStringContainsString('Kilómetros', $html);
        $this->assertStringContainsString('120.000', $html);
        // Veredicto con semáforo verde
        $this->assertStringContainsString('Veredicto:', $html);
        $this->assertStringContainsString('Excelente compra', $html);
        $this->assertStringContainsString('#10b981', $html);
        // Galería: hero + grid de 4 (primera grande + 3 del grid)
        $this->assertStringContainsString('hero-photo', $html);
        $this->assertStringContainsString('gallery', $html);
        $this->assertStringContainsString('class="shot"', $html);
        // Ficha técnica completa (SPEC grid)
        $this->assertStringContainsString('Ficha técnica', $html);
        $this->assertStringContainsString('spec-row', $html);
        $this->assertStringContainsString('200 CV', $html);
        // Highlights como tarjetas
        $this->assertStringContainsString('arg-card', $html);
        $this->assertStringContainsString('Coche en perfecto estado', $html);
        // Equipamiento como lista con check
        $this->assertStringContainsString('equip-item', $html);
        $this->assertStringContainsString('Techo panorámico', $html);
        $this->assertStringContainsString('Navegación', $html);
        // CTA + QR
        $this->assertStringContainsString('¿Te interesa este coche?', $html);
        $this->assertStringContainsString('Escanea', $html);
        $this->assertStringContainsString('675 70 14 39', $html);
    }
}
