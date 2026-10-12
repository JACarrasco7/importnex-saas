<?php

namespace Tests\Feature;

use App\Models\Car;
use App\Models\CarMarketingContent;
use App\Models\Organization;
use App\Models\User;
use App\Services\ValuationPackageIngestor;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * A4 auditoría 09-sep-2026: el ingestor lee `contenido/json/redes-sociales.json`
 * (vocabulario v2 con bloques gancho/ficha/contexto/argumento/pega/cta) y
 * mapea a filas de car_marketing_contents.
 *
 * B7: el v2 también emite `canales.stories[]`, que se ingiere con su slot.
 */
class ValuationPackageIngestorRedesV2Test extends TestCase
{
    use RefreshDatabase;

    public function test_ingesta_copia_v2_desde_json_y_no_desde_txt(): void
    {
        $zip = $this->zipDeCoche([
            'redes_sociales_txt' => $this->redesTxtLegacy(),
            'redes_sociales_json' => $this->redesJsonV2(),
        ]);

        $car = $this->ingestar($zip);

        // Instagram: 3 posts (slot 1 con cuerpo completo, slot 2 con gancho_b,
        // slot 3 con send_ask).
        $igPost1 = CarMarketingContent::query()
            ->where('car_id', $car->id)
            ->where('channel', 'instagram')
            ->where('kind', CarMarketingContent::KIND_POST)
            ->where('slot', 1)
            ->first();
        $this->assertNotNull($igPost1, 'A4: Instagram post 1 debe existir');
        $this->assertStringContainsString('Arteon R Shooting Brake', $igPost1->title);
        // Cuerpo compuesto: contexto + viñetas de argumento + pega + cta.
        $this->assertStringContainsString('tracción total', $igPost1->description);
        $this->assertStringContainsString('•', $igPost1->description);
        $this->assertStringContainsString('320 CV', $igPost1->description);
        // El foto_tips (ficha) debe llevar los datos clave del coche.
        $this->assertIsArray($igPost1->photo_tips);
        $this->assertNotEmpty($igPost1->photo_tips);
        // Hashtags del JSON.
        $this->assertContains('#arteonr', $igPost1->hashtags);

        // Slot 2 con gancho_b.
        $igPost2 = CarMarketingContent::query()
            ->where('car_id', $car->id)->where('channel', 'instagram')
            ->where('kind', CarMarketingContent::KIND_POST)->where('slot', 2)->first();
        $this->assertNotNull($igPost2, 'A4: Instagram post 2 (gancho_b) debe existir');
        $this->assertStringContainsString('320 caballos', $igPost2->title);

        // Slot 3 con send_ask.
        $igPost3 = CarMarketingContent::query()
            ->where('car_id', $car->id)->where('channel', 'instagram')
            ->where('kind', CarMarketingContent::KIND_POST)->where('slot', 3)->first();
        $this->assertNotNull($igPost3, 'A4: Instagram post 3 (send_ask) debe existir');
        $this->assertStringContainsString('pregunta', $igPost3->description);
    }

    public function test_v2_tambien_emite_stories_del_canal_canales_stories(): void
    {
        $zip = $this->zipDeCoche([
            'redes_sociales_json' => $this->redesJsonV2(),
        ]);

        $car = $this->ingestar($zip);

        // B7: al menos una story de Instagram en BD.
        $stories = CarMarketingContent::query()
            ->where('car_id', $car->id)
            ->where('channel', 'instagram')
            ->where('kind', CarMarketingContent::KIND_STORY)
            ->count();
        $this->assertGreaterThan(0, $stories, 'B7: deben existir stories de Instagram');
    }

    public function test_si_no_hay_json_cae_a_txt_legacy(): void
    {
        $zip = $this->zipDeCoche([
            'redes_sociales_txt' => $this->redesTxtLegacy(),
        ]);

        $car = $this->ingestar($zip);

        // TXT legacy: 3 posts (INSTAGRAM_POST_1..3) + 3 stories.
        $this->assertSame(3, CarMarketingContent::query()
            ->where('car_id', $car->id)
            ->where('channel', 'instagram')
            ->where('kind', CarMarketingContent::KIND_POST)
            ->count(), 'Fallback TXT: 3 posts');
    }

    public function test_tiktok_recibe_canales_video(): void
    {
        $zip = $this->zipDeCoche([
            'redes_sociales_json' => $this->redesJsonV2(),
        ]);

        $car = $this->ingestar($zip);

        // Mapa v2 → BD: 'video' → 'tiktok'.
        $this->assertGreaterThan(0, CarMarketingContent::query()
            ->where('car_id', $car->id)
            ->where('channel', 'tiktok')
            ->count(), 'TikTok debe tener al menos 1 fila desde canales.video');
    }

    public function test_warning_si_json_no_tiene_estructura_canales(): void
    {
        $zip = $this->zipDeCoche([
            'redes_sociales_json' => ['tipo' => 'algo_raro'],
        ]);

        $car = $this->ingestar($zip);

        // anuncio-portales.txt siempre crea 4 filas (milanuncios, coches_net,
        // wallapop, facebook marketplace). Las de redes (instagram, tiktok,
        // facebook) deben ser 0.
        $this->assertSame(0, CarMarketingContent::query()
            ->where('car_id', $car->id)
            ->whereIn('channel', ['instagram', 'tiktok', 'facebook'])
            ->where('kind', CarMarketingContent::KIND_POST)
            ->count(), 'Sin JSON ni TXT de redes, 0 posts en redes sociales');
    }

    // ─── helpers ────────────────────────────────────────────────────────────

    private function redesJsonV2(): array
    {
        return [
            // Sin _meta.schema_version: ese campo es del informe.json.
            // Si lo ponemos aquí, el ingestor cree que ESTE es el informe
            // y revienta con "Schema version 2 not supported".
            'tipo' => 'redes_sociales_v2',
            'canales' => [
                'instagram_feed' => [
                    'gancho' => 'Arteon R Shooting Brake: 320 CV y tracción total',
                    'gancho_b' => 'Una ranchera con 320 caballos',
                    'ficha' => ['2023', '49.420 km', '320 CV', 'Automático DSG'],
                    'contexto' => 'Volkswagen Arteon R Shooting Brake con tracción total 4Motion.',
                    'argumento' => ['Tracción total utilizable todo el año', 'Maletero de 565L', 'Motor 2.0 TSI 320 CV con cambio DSG'],
                    'pega' => '⚠️ Solo se matricula a tu nombre: no somos stock',
                    'cta' => '¿Te lo conseguimos? Escríbenos.',
                    'send_ask' => '¿Tienes alguna pregunta sobre este coche?',
                    'hashtags' => ['#arteonr', '#shootingbrake', '#vw', '#jjimport', '#coche'],
                ],
                'video' => [
                    'gancho' => 'POV: tu proximo coche es este Arteon R',
                    'gancho_b' => '320 CV en una ranchera familiar',
                    'ficha' => ['320 CV', '0-100 en 4.9s'],
                    'contexto' => 'Volkswagen Arteon R SB con DSG 7v.',
                    'argumento' => ['Tracción total', 'Maletero amplio'],
                    'pega' => '',
                    'cta' => 'Link en bio',
                    'send_ask' => '',
                    'hashtags' => ['#vw', '#arteon', '#tiktok'],
                ],
                'facebook_pagina' => [
                    'gancho' => 'Volkswagen Arteon R SB 2023',
                    'gancho_b' => '',
                    'ficha' => [],
                    'contexto' => 'Arteon R Shooting Brake con 49.420 km.',
                    'argumento' => ['Tracción 4Motion', 'Motor 2.0 TSI'],
                    'pega' => '',
                    'cta' => 'Mensaje para más info',
                    'send_ask' => '',
                    'hashtags' => [],
                ],
                'marketplace' => [
                    'gancho' => 'VW Arteon R SB 2023',
                    'gancho_b' => '',
                    'ficha' => [],
                    'contexto' => 'Ranchera con 320 CV.',
                    'argumento' => [],
                    'pega' => '',
                    'cta' => '',
                    'send_ask' => '',
                    'hashtags' => [],
                ],
                'stories' => [
                    ['canal' => 'instagram', 'copy' => 'Nuevo en catalogo: Arteon R SB'],
                    ['canal' => 'instagram', 'copy' => 'Lo has visto en persona?'],
                ],
            ],
        ];
    }

    private function redesTxtLegacy(): string
    {
        return
            "[GANCHO]\nArteon R SB 2023 — 320 CV · 4Motion\n".
            "[HASHTAGS]\n#arteonr\n#vw\n".
            "[PIE_FOTO]\nFoto 1: frente del coche\n".
            "[INSTAGRAM_POST_1]\nPost IG 1 cuerpo completo\n".
            "[INSTAGRAM_POST_2]\nPost IG 2 cuerpo variante\n".
            "[INSTAGRAM_POST_3]\nPost IG 3 cuerpo variante\n".
            "[INSTAGRAM_STORY_1]\nStory IG 1\n".
            "[INSTAGRAM_STORY_2]\nStory IG 2\n".
            "[INSTAGRAM_STORY_3]\nStory IG 3\n".
            "[TIKTOK_POST_1]\nTT 1\n[TikTok_POST_2]\nTT 2\n[TikTok_POST_3]\nTT 3\n";
    }

    /**
     * Crea un zip con el manifiesto y los archivos dados. Estructura:
     *   informe.json
     *   manifest.json
     *   contenido/redes-sociales.txt? (si 'redes_sociales_txt')
     *   contenido/json/redes-sociales.json? (si 'redes_sociales_json')
     *   contenido/anuncio-portales.txt (placeholder)
     *
     * @param  array{redes_sociales_txt?: string|array, redes_sociales_json?: array}  $parts
     */
    private function zipDeCoche(array $parts): string
    {
        $path = tempnam(sys_get_temp_dir(), 'zip-test-');
        $zip = new \ZipArchive;
        $zip->open($path, \ZipArchive::CREATE | \ZipArchive::OVERWRITE);

        $zip->addFromString('informe.json', json_encode([
            '_meta' => ['schema_version' => 1, 'coche_id' => 'test-001', 'flujo' => 'A'],
            'vehiculo' => [
                'marca' => 'VW',
                'modelo' => 'Arteon',
                'version' => 'R SB',
                'anio' => 2023,
                'km' => 49420,
                'combustible' => 'Gasolina',
                'cambio' => 'Automático',
                'traccion' => 'Total',
                'carroceria' => 'Familiar',
                'potencia_cv' => 320,
                'color_exterior' => 'Azul',
            ],
            'informe' => ['veredicto' => 'Comprar', 'valoracion' => 'OK'],
        ]));
        $zip->addFromString('manifest.json', json_encode([
            'paquete_version' => 2,
            'coche_id' => 'test-001',
        ]));
        $zip->addFromString('contenido/anuncio-portales.txt', "[TITULO]\nTest\n[DESCRIPCION]\nDesc test\n");

        if (isset($parts['redes_sociales_txt'])) {
            $zip->addFromString('contenido/redes-sociales.txt', $parts['redes_sociales_txt']);
        }
        if (isset($parts['redes_sociales_json'])) {
            $zip->addFromString('contenido/json/redes-sociales.json', json_encode($parts['redes_sociales_json']));
        }

        $zip->close();

        return $path;
    }

    private function ingestar(string $zipPath): Car
    {
        $org = Organization::factory()->create();
        User::factory()->create(['organization_id' => $org->id]);

        $ingestor = app(ValuationPackageIngestor::class);
        $result = $ingestor->ingest($zipPath, $org);
        @unlink($zipPath);

        return $result['car'];
    }
}
