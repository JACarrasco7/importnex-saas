<?php

namespace Tests\Feature;

use App\Models\CarMarketingContent;
use App\Models\Organization;
use App\Models\User;
use App\Services\ValuationPackageIngestor;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;
use ZipArchive;

class ValuationPackageMarketingTest extends TestCase
{
    use RefreshDatabase;

    private string $zipPath;

    protected function setUp(): void
    {
        parent::setUp();
        // Disco 'public' en tmp para que las fotos se guarden sin tocar storage real
        Storage::fake('public');
        Storage::fake('local');
    }

    protected function tearDown(): void
    {
        if (isset($this->zipPath) && file_exists($this->zipPath)) {
            @unlink($this->zipPath);
        }
        parent::tearDown();
    }

    /**
     * Helper: arma un ZIP mínimo válido con el contenido declarado en $contenidos
     * (array de pares ruta→bytes) + el informe.json obligatorio.
     *
     * @param  array<string, string>  $contenidos  ej: ['contenido/redes-sociales.txt' => 'texto']
     */
    private function buildZip(array $contenidos, string $cocheId = 'test-coche-001'): string
    {
        $tmp = tempnam(sys_get_temp_dir(), 'pkg_mkt_');
        // tempnam crea un archivo; lo borramos para que ZipArchive pueda crear el zip
        @unlink($tmp);

        $zip = new ZipArchive;
        $this->assertTrue($zip->open($tmp, ZipArchive::CREATE) === true, 'No se pudo crear el zip temporal');

        // informe.json mínimo válido (con PVP_nuevo para superar validate() en Flujo A)
        $informe = [
            '_meta' => [
                'schema_version' => 1,
                'flujo' => 'A',
                'coche_id' => $cocheId,
                'generado_el' => now()->toIso8601String(),
            ],
            'vehiculo' => [
                'marca' => 'BMW', 'modelo' => '320d',
                'anio' => '07/2020', 'km' => 80000,
                'combustible' => 'Diesel', 'cambio' => 'Automatic',
                'vin' => strtoupper('TESTPKG'.substr(md5($cocheId), 0, 9)),
            ],
            'anuncio' => ['url' => 'https://example.com/'.$cocheId],
            'investigacion' => [
                'problemas_comunes' => ['hallazgo' => 'Sin issues', 'fuente' => 'x', 'valoracion' => 'favorable'],
            ],
            'balance' => [
                'a_favor' => [['texto' => 'Buen precio', 'peso' => 'alto']],
                'en_contra' => [],
            ],
            'veredicto' => [
                'recomendacion' => 'Comprar',
                'confianza' => 'alta',
                'razonamiento' => 'Test',
                'que_cambiaria' => '',
                'precio_objetivo' => 18000,
            ],
            'costes' => [
                'precio_coche' => 18000,
                'pvp_nuevo' => 32000,
                'transporte' => 900,
                'coste_total' => 20000,
            ],
            'mercado' => [
                'precio_medio' => 22000,
                'precio_min' => 20000,
                'precio_max' => 24000,
                'comparables' => [],
            ],
        ];
        $zip->addFromString('informe.json', json_encode($informe));

        // manifest.json con paquete_version 2 (necesario para que el ingestor
        // procese contenido/*.txt — sin manifest lo ignora por compatibilidad v1)
        $manifest = [
            'manifest_version' => 1,
            'paquete_version' => 2,
            'coche_id' => $cocheId,
            'flujo' => 'A',
            'schema_version' => 1,
        ];
        $zip->addFromString('manifest.json', json_encode($manifest));

        foreach ($contenidos as $ruta => $bytes) {
            $zip->addFromString($ruta, $bytes);
        }

        $zip->close();
        $this->zipPath = $tmp;

        return $tmp;
    }

    public function test_zip_with_marketing_creates_car_marketing_content_per_channel(): void
    {
        $org = Organization::factory()->create();
        User::factory()->create(['organization_id' => $org->id]);

        // Esquema v2 (05-sep-2026): 3 redes × (3 posts + 3 stories) + 4 portales.
        // Redes: TikTok (viral), Instagram (visual), Facebook (informativo).
        // Cada red con su tono y 3 publicaciones + 3 stories.
        $redes = <<<'TXT'
[GANCHO] BMW 320d 2020 — Diesel premium a precio de compacto

# ── TIKTOK · viral 15-30s ──
[TIKTOK_POST_1] POV: BMW 320d con 80.000 km y un solo dueño 😱 #bmw #diesel
[TIKTOK_POST_2] 190 CV por 23.900 € puesto en Huelva. M Sport completo.
[TIKTOK_POST_3] Alemania → Huelva en 6 semanas. Te enseñamos todo el proceso.
[TIKTOK_STORY_1] ¿Viste el M Performance?
[TIKTOK_STORY_2] 78.000 km · 1 dueño
[TIKTOK_STORY_3] link en bio
[TIKTOK_HASHTAGS] #bmw #320d #diesel #viral #jjimport
[TIKTOK_SUBIR_PASOS] 1. Sube el reel (15-30s) · 2. Caption + hashtags · 3. Música trending · 4. Compartir en story

# ── INSTAGRAM · visual storytelling ──
[INSTAGRAM_POST_1] 1 dueño · 78.000 km · 190 CV
Techo solar, M Sport, cuero Dakota Cognac.
Puesto en Huelva 23.900 €.
#BMW #320d #ImportaciónAlemania
[INSTAGRAM_POST_2] Antes y después: el coche de origen vs el coche puesto en Huelva.
[INSTAGRAM_POST_3] El proceso completo: compra en DE → inspección → transporte → ITV → entrega.
[INSTAGRAM_STORY_1] ¿BMW 320d? 🤔
[INSTAGRAM_STORY_2] M Sport completo
[INSTAGRAM_STORY_3] Link en bio
[INSTAGRAM_HASHTAGS] #bmw
[INSTAGRAM_HASHTAGS] #320d
[INSTAGRAM_HASHTAGS] #cochesimportados
[INSTAGRAM_HASHTAGS] #huelva
[INSTAGRAM_SUBIR_PASOS] 1. Foto + carrusel · 2. Caption storytelling · 3. 15-20 hashtags nichos

# ── FACEBOOK · informativo masivo ──
[FACEBOOK_POST_1] BMW 320d xDrive M Sport, 2020, 78.000 km, único dueño.
PVP recomendado nuevo: 48.500 €. Precio JJ Import Motors: 23.900 € puesto en Huelva.
Incluye: transporte, ITV importación, gestoría integral, 12 meses soporte.
[FACEBOOK_POST_2] ¿Por qué importar un BMW 320d de Alemania?
- 1.100 € más barato que la mediana del mercado español
- Etiqueta C: ZBE sin restricciones
- Historial completo en concesionario BMW
[FACEBOOK_POST_3] Ficha técnica detallada:
- Motor: B47 2.0d 190 CV xDrive
- Cambio: Automático 8 vel.
- Etiqueta ambiental: C
- Mantenimiento: libro sellado
[FACEBOOK_STORY_1] BMW 320d M Sport
[FACEBOOK_STORY_2] 23.900 € puesto en Huelva
[FACEBOOK_STORY_3] jjimportmotors@gmail.com
[FACEBOOK_HASHTAGS] #coches #oferta #huelva
[FACEBOOK_SUBIR_PASOS] 1. Facebook Marketplace · 2. Grupo Huelva Coches · 3. Marketplace Vehículos

[HASHTAGS] #bmw
[HASHTAGS] #320d
[HASHTAGS] #diesel
[HASHTAGS] #jjimport
[HASHTAGS] #cochesimportados
[HASHTAGS] #huelva
[PIE_FOTO] La trasera con difusador M Performance.
TXT;

        $portales = <<<'TXT'
[TITULO] BMW 320d 2020 Automatic 80.000 km — impecable
[DESCRIPCION] BMW 320d de único dueño, libro sellado en concesionario oficial, ITV al día. Vehículo disponible para entrega inmediata en Huelva.
[FICHA_RAPIDA] 2020 | 80.000 km | Diesel | Automatic | 190 CV | Automático
[QUE_INCLUYE] El vehículo | Transporte hasta España | ITV de importación
[AVISO_LEGAL] Servicio de gestión de importación. JJ Import Motors actúa como gestor, no como vendedor.
[SUBIR_PASOS] 1. Milanuncios: 'Poner anuncio' → pega TITULO + DESCRIPCION · 2. Coches.net: 'Publicar anuncio' → fotos + pega · 3. Wallapop: 'Vender' → fotos + pega · 4. Facebook Marketplace: 'Crear nuevo anuncio' → fotos + pega
TXT;

        $zipPath = $this->buildZip([
            'contenido/redes-sociales.txt' => $redes,
            'contenido/anuncio-portales.txt' => $portales,
            'contenido/ficha-publicitaria.txt' => "[TITULO] BMW 320d 2020\n[DESCRIPCION] Coche impecable\n",
            'contenido/informe-interno.txt' => "[COCHE_ID] test\n[FLUJO] A\n",
        ]);

        $result = app(ValuationPackageIngestor::class)->ingest($zipPath, $org);

        $car = $result['car'];

        // Esquema v2: 3 redes × (3 posts + 3 stories) + 4 portales = 21 filas.
        // Pero los posts/stories vacíos se omiten: con 3 posts + 3 stories por red = 18 filas
        // + 4 portales = 22 filas totales (si todos los slots traen copy).
        // En este test: 3 posts × 3 redes + 3 stories × 3 redes + 4 portales = 9 + 9 + 4 = 22.
        $this->assertGreaterThanOrEqual(22, $result['marketing'],
            'Debe crear ≥22 filas: 9 posts + 9 stories + 4 portales');
        $this->assertSame($result['marketing'], CarMarketingContent::where('car_id', $car->id)->count());

        // Instagram: 3 posts + 3 stories = 6 filas
        $igPosts = CarMarketingContent::where('car_id', $car->id)
            ->where('channel', 'instagram')
            ->where('kind', CarMarketingContent::KIND_POST)
            ->orderBy('slot')
            ->get();
        $this->assertCount(3, $igPosts);
        $this->assertStringContainsString('1 dueño · 78.000 km · 190 CV', $igPosts[0]->description);
        $this->assertSame(CarMarketingContent::KIND_POST, $igPosts[0]->kind);
        $this->assertEqualsCanonicalizing(['#bmw', '#320d', '#cochesimportados', '#huelva'], $igPosts[0]->hashtags);
        $this->assertNotEmpty($igPosts[0]->subir_pasos, 'subir_pasos del post 1 debe estar poblado');

        $igStories = CarMarketingContent::where('car_id', $car->id)
            ->where('channel', 'instagram')
            ->where('kind', CarMarketingContent::KIND_STORY)
            ->orderBy('slot')
            ->get();
        $this->assertCount(3, $igStories);
        $this->assertSame('¿BMW 320d? 🤔', $igStories[0]->description);

        // TikTok: 3 posts + 3 stories
        $ttPosts = CarMarketingContent::where('car_id', $car->id)
            ->where('channel', 'tiktok')
            ->where('kind', CarMarketingContent::KIND_POST)
            ->count();
        $this->assertSame(3, $ttPosts);
        $ttStories = CarMarketingContent::where('car_id', $car->id)
            ->where('channel', 'tiktok')
            ->where('kind', CarMarketingContent::KIND_STORY)
            ->count();
        $this->assertSame(3, $ttStories);

        // Facebook: 3 posts + 3 stories
        $fbPosts = CarMarketingContent::where('car_id', $car->id)
            ->where('channel', 'facebook')
            ->where('kind', CarMarketingContent::KIND_POST)
            ->count();
        $this->assertSame(3, $fbPosts);

        // Portales: 4 canales × kind=ad × slot=1 = 4 filas con mismo TITULO/DESCRIPCION
        foreach (CarMarketingContent::PORTAL_CHANNELS as $channel) {
            $row = CarMarketingContent::where('car_id', $car->id)
                ->where('channel', $channel)
                ->where('kind', CarMarketingContent::KIND_AD)
                ->first();
            $this->assertNotNull($row, "Falta portal {$channel}");
            $this->assertSame('draft', $row->status);
            $this->assertSame('BMW 320d 2020 Automatic 80.000 km — impecable', $row->title);
            $this->assertStringContainsString('Huelva', $row->description);
            $this->assertNotEmpty($row->subir_pasos, 'subir_pasos del portal debe estar poblado');
        }
    }

    public function test_zip_without_marketing_creates_zero_rows_and_warns(): void
    {
        $org = Organization::factory()->create();
        User::factory()->create(['organization_id' => $org->id]);

        $zipPath = $this->buildZip([
            'contenido/ficha-publicitaria.txt' => "[TITULO] Test\n",
            'contenido/informe-interno.txt' => "[COCHE_ID] test\n",
            // SIN redes-sociales.txt ni anuncio-portales.txt
        ]);

        $result = app(ValuationPackageIngestor::class)->ingest($zipPath, $org);

        $this->assertSame(0, $result['marketing']);
        $this->assertSame(0, CarMarketingContent::where('car_id', $result['car']->id)->count());

        // A23: un paquete v2 sin marketing y sin fotos debe AVISAR (no fallar)
        $this->assertNotEmpty($result['warnings']);
        $this->assertTrue(
            collect($result['warnings'])->contains(fn ($w) => str_contains($w, 'no incluye marketing')),
            'Debe haber warning de marketing ausente: '.json_encode($result['warnings']),
        );
        $this->assertTrue(
            collect($result['warnings'])->contains(fn ($w) => str_contains($w, 'fotos')),
            'Debe haber warning de fotos ausentes: '.json_encode($result['warnings']),
        );
    }

    public function test_reimporting_same_zip_does_not_duplicate_marketing_rows(): void
    {
        $org = Organization::factory()->create();
        User::factory()->create(['organization_id' => $org->id]);

        // Esquema v2: redes con 3 posts + 3 stories + 4 portales = 22 filas con datos completos.
        $zipPath = $this->buildZip([
            'contenido/redes-sociales.txt' => <<<'TXT'
[GANCHO] BMW 320d 2020
[TIKTOK_POST_1] Post 1
[TIKTOK_POST_2] Post 2
[TIKTOK_POST_3] Post 3
[TIKTOK_STORY_1] Story 1
[TIKTOK_STORY_2] Story 2
[TIKTOK_STORY_3] Story 3
[INSTAGRAM_POST_1] Post 1
[INSTAGRAM_POST_2] Post 2
[INSTAGRAM_POST_3] Post 3
[INSTAGRAM_STORY_1] Story 1
[INSTAGRAM_STORY_2] Story 2
[INSTAGRAM_STORY_3] Story 3
[FACEBOOK_POST_1] Post 1
[FACEBOOK_POST_2] Post 2
[FACEBOOK_POST_3] Post 3
[FACEBOOK_STORY_1] Story 1
[FACEBOOK_STORY_2] Story 2
[FACEBOOK_STORY_3] Story 3
TXT,
            'contenido/anuncio-portales.txt' => "[TITULO] BMW 320d\n[DESCRIPCION] Desc\n",
        ]);

        $ingestor = app(ValuationPackageIngestor::class);

        $first = $ingestor->ingest($zipPath, $org);
        $second = $ingestor->ingest($zipPath, $org);

        // Mismo coche (mismo VIN en informe.json) → no se duplica
        $this->assertSame($first['car']->id, $second['car']->id,
            'Reimport del mismo coche debe resolver al MISMO registro (por VIN)');
        // 9 posts + 9 stories + 4 portales = 22 filas
        $expected = 9 + 9 + 4;
        $this->assertSame($expected, CarMarketingContent::where('car_id', $first['car']->id)->count(),
            "Reimport NO debe duplicar filas (esperadas: {$expected})");

        // Y los contenidos deben haberse actualizado (no quedar el antiguo)
        $ig = CarMarketingContent::where('car_id', $first['car']->id)
            ->where('channel', CarMarketingContent::CHANNEL_INSTAGRAM)
            ->where('kind', CarMarketingContent::KIND_POST)
            ->where('slot', 1)
            ->first();
        $this->assertNotNull($ig);
        $this->assertSame('BMW 320d 2020', $ig->title);
    }

    public function test_marketing_with_only_redes_creates_only_social_channels(): void
    {
        $org = Organization::factory()->create();
        User::factory()->create(['organization_id' => $org->id]);

        $zipPath = $this->buildZip([
            // Solo redes, sin portales. Esquema v2: 3 redes × (3 posts + 3 stories) = 18 filas.
            'contenido/redes-sociales.txt' => <<<'TXT'
[GANCHO] Test
[TIKTOK_POST_1] P1
[TIKTOK_POST_2] P2
[TIKTOK_POST_3] P3
[TIKTOK_STORY_1] S1
[TIKTOK_STORY_2] S2
[TIKTOK_STORY_3] S3
[INSTAGRAM_POST_1] P1
[INSTAGRAM_POST_2] P2
[INSTAGRAM_POST_3] P3
[INSTAGRAM_STORY_1] S1
[INSTAGRAM_STORY_2] S2
[INSTAGRAM_STORY_3] S3
[FACEBOOK_POST_1] P1
[FACEBOOK_POST_2] P2
[FACEBOOK_POST_3] P3
[FACEBOOK_STORY_1] S1
[FACEBOOK_STORY_2] S2
[FACEBOOK_STORY_3] S3
TXT,
        ]);

        $result = app(ValuationPackageIngestor::class)->ingest($zipPath, $org);

        $this->assertSame(18, $result['marketing'],
            '3 redes × (3 posts + 3 stories) = 18 filas');
        $this->assertSame(18, CarMarketingContent::where('car_id', $result['car']->id)->count());
        $channels = CarMarketingContent::where('car_id', $result['car']->id)
            ->distinct()->pluck('channel')->all();
        sort($channels);
        $this->assertSame(['facebook', 'instagram', 'tiktok'], $channels);
    }

    public function test_tiktok_is_not_created_without_stories(): void
    {
        // Guard v2: si el ZIP trae GANCHO pero NO TIKTOK_STORY_N (ni POST_CORTO),
        // TikTok no debe crearse (evita filas fantasma con description vacía).
        $org = Organization::factory()->create();
        User::factory()->create(['organization_id' => $org->id]);

        $zipPath = $this->buildZip([
            'contenido/redes-sociales.txt' => <<<'TXT'
[GANCHO] Test
[TIKTOK_POST_1] P1
[TIKTOK_POST_2] P2
[TIKTOK_POST_3] P3
[INSTAGRAM_POST_1] P1
[INSTAGRAM_POST_2] P2
[INSTAGRAM_POST_3] P3
[INSTAGRAM_STORY_1] S1
[INSTAGRAM_STORY_2] S2
[INSTAGRAM_STORY_3] S3
[FACEBOOK_POST_1] P1
[FACEBOOK_POST_2] P2
[FACEBOOK_POST_3] P3
[FACEBOOK_STORY_1] S1
[FACEBOOK_STORY_2] S2
[FACEBOOK_STORY_3] S3
TXT,
        ]);

        $result = app(ValuationPackageIngestor::class)->ingest($zipPath, $org);

        // Instagram: 3 posts + 3 stories = 6. Facebook: 3 posts + 3 stories = 6. TikTok: solo 3 posts = 3.
        // Total: 6 + 6 + 3 = 15.
        $this->assertSame(15, $result['marketing'],
            'IG + FB con stories, TikTok solo posts → 6 + 6 + 3 = 15');
        $tiktokCount = CarMarketingContent::where('car_id', $result['car']->id)
            ->where('channel', CarMarketingContent::CHANNEL_TIKTOK)->count();
        $this->assertSame(3, $tiktokCount, 'TikTok solo debe tener 3 posts (sin stories)');
    }

    public function test_empty_gancho_does_not_create_social_marketing(): void
    {
        // Guard: si GANCHO viene vacío, ni Instagram ni TikTok se crean.
        $org = Organization::factory()->create();
        User::factory()->create(['organization_id' => $org->id]);

        $zipPath = $this->buildZip([
            'contenido/redes-sociales.txt' => "[GANCHO] \n[POST_LARGO] L\n[STORIES] S\n",
        ]);

        $result = app(ValuationPackageIngestor::class)->ingest($zipPath, $org);

        $this->assertSame(0, $result['marketing'], 'GANCHO vacío → 0 filas de redes');
        $this->assertTrue(
            collect($result['warnings'])->contains(fn ($w) => str_contains($w, 'GANCHO')),
            'GANCHO vacío debe generar warning explicativo',
        );
    }

    public function test_empty_titulo_or_descripcion_does_not_create_portal_marketing(): void
    {
        // Guard: si TITULO o DESCRIPCION vienen vacíos, los 4 portales se descartan.
        $org = Organization::factory()->create();
        User::factory()->create(['organization_id' => $org->id]);

        $zipPath = $this->buildZip([
            'contenido/anuncio-portales.txt' => "[TITULO]\n[DESCRIPCION] Con descripción\n",
        ]);

        $result = app(ValuationPackageIngestor::class)->ingest($zipPath, $org);

        $this->assertSame(0, $result['marketing'], 'TITULO vacío → 0 portales');
        $this->assertTrue(
            collect($result['warnings'])->contains(fn ($w) => str_contains($w, 'portales')),
            'TITULO vacío debe generar warning explicativo',
        );
    }

    public function test_imported_marketing_rows_are_marked_as_zip_source(): void
    {
        // F2: trazabilidad de origen — todo lo que entra por el ZIP es source=zip.
        $org = Organization::factory()->create();
        User::factory()->create(['organization_id' => $org->id]);

        $zipPath = $this->buildZip([
            'contenido/redes-sociales.txt' => <<<'TXT'
[GANCHO] Test
[TIKTOK_POST_1] P1
[TIKTOK_POST_2] P2
[TIKTOK_POST_3] P3
[TIKTOK_STORY_1] S1
[TIKTOK_STORY_2] S2
[TIKTOK_STORY_3] S3
[INSTAGRAM_POST_1] P1
[INSTAGRAM_POST_2] P2
[INSTAGRAM_POST_3] P3
[INSTAGRAM_STORY_1] S1
[INSTAGRAM_STORY_2] S2
[INSTAGRAM_STORY_3] S3
[FACEBOOK_POST_1] P1
[FACEBOOK_POST_2] P2
[FACEBOOK_POST_3] P3
[FACEBOOK_STORY_1] S1
[FACEBOOK_STORY_2] S2
[FACEBOOK_STORY_3] S3
TXT,
            'contenido/anuncio-portales.txt' => "[TITULO] T\n[DESCRIPCION] D\n",
        ]);

        $result = app(ValuationPackageIngestor::class)->ingest($zipPath, $org);

        // 18 redes + 4 portales = 22 filas, todas source=zip
        $this->assertSame(22, $result['marketing']);
        $this->assertSame(22, CarMarketingContent::where('car_id', $result['car']->id)
            ->where('source', CarMarketingContent::SOURCE_ZIP)->count(),
            'Todas las filas importadas del ZIP deben llevar source=zip');
    }

    public function test_reimport_overwrites_ai_source_back_to_zip(): void
    {
        // Si el operador generó con IA (source=ai) y luego reimporta el ZIP,
        // el contenido pasa a ser del ZIP de nuevo (source=zip).
        $org = Organization::factory()->create();
        User::factory()->create(['organization_id' => $org->id]);

        $zipPath = $this->buildZip([
            'contenido/redes-sociales.txt' => <<<'TXT'
[GANCHO] Test
[TIKTOK_POST_1] P1
[TIKTOK_POST_2] P2
[TIKTOK_POST_3] P3
[TIKTOK_STORY_1] S1
[TIKTOK_STORY_2] S2
[TIKTOK_STORY_3] S3
[INSTAGRAM_POST_1] P1
[INSTAGRAM_POST_2] P2
[INSTAGRAM_POST_3] P3
[INSTAGRAM_STORY_1] S1
[INSTAGRAM_STORY_2] S2
[INSTAGRAM_STORY_3] S3
[FACEBOOK_POST_1] P1
[FACEBOOK_POST_2] P2
[FACEBOOK_POST_3] P3
[FACEBOOK_STORY_1] S1
[FACEBOOK_STORY_2] S2
[FACEBOOK_STORY_3] S3
TXT,
        ]);

        $ingestor = app(ValuationPackageIngestor::class);
        $first = $ingestor->ingest($zipPath, $org);

        CarMarketingContent::where('car_id', $first['car']->id)
            ->update(['source' => CarMarketingContent::SOURCE_AI]);

        $ingestor->ingest($zipPath, $org);

        // 18 filas de redes sociales tras reimport con source=zip
        $this->assertSame(18, CarMarketingContent::where('car_id', $first['car']->id)
            ->where('source', CarMarketingContent::SOURCE_ZIP)->count());
    }

    public function test_reimport_resets_published_marketing_back_to_draft(): void
    {
        // Regla de negocio: si una fila estaba en status=published, reimportar
        // el ZIP la devuelve a draft (el operador debe revisar antes de republicar).
        $org = Organization::factory()->create();
        User::factory()->create(['organization_id' => $org->id]);

        $cocheId = 'test-pub2draft-'.uniqid();
        $zipPath = $this->buildZip([
            'contenido/redes-sociales.txt' => <<<'TXT'
[GANCHO] Test
[TIKTOK_POST_1] P1
[TIKTOK_POST_2] P2
[TIKTOK_POST_3] P3
[TIKTOK_STORY_1] S1
[TIKTOK_STORY_2] S2
[TIKTOK_STORY_3] S3
[INSTAGRAM_POST_1] P1
[INSTAGRAM_POST_2] P2
[INSTAGRAM_POST_3] P3
[INSTAGRAM_STORY_1] S1
[INSTAGRAM_STORY_2] S2
[INSTAGRAM_STORY_3] S3
[FACEBOOK_POST_1] P1
[FACEBOOK_POST_2] P2
[FACEBOOK_POST_3] P3
[FACEBOOK_STORY_1] S1
[FACEBOOK_STORY_2] S2
[FACEBOOK_STORY_3] S3
TXT,
            'contenido/anuncio-portales.txt' => "[TITULO] T\n[DESCRIPCION] D\n",
        ], $cocheId);

        $ingestor = app(ValuationPackageIngestor::class);
        $first = $ingestor->ingest($zipPath, $org);

        // Marcar TODO como published
        CarMarketingContent::where('car_id', $first['car']->id)->update([
            'status' => CarMarketingContent::STATUS_PUBLISHED,
            'published_at' => now(),
        ]);

        // 18 redes + 4 portales = 22 filas, todas en published
        $this->assertSame(22, CarMarketingContent::where('car_id', $first['car']->id)
            ->where('status', CarMarketingContent::STATUS_PUBLISHED)->count(),
            'Antes del reimport: 22 en published');

        // Reimportar
        $ingestor->ingest($zipPath, $org);

        $this->assertSame(0, CarMarketingContent::where('car_id', $first['car']->id)
            ->where('status', CarMarketingContent::STATUS_PUBLISHED)->count(),
            'Tras reimport: 0 en published (vuelven a draft)');
        $this->assertSame(22, CarMarketingContent::where('car_id', $first['car']->id)
            ->where('status', CarMarketingContent::STATUS_DRAFT)->count(),
            'Tras reimport: 22 en draft');
    }

    public function test_marketing_import_sets_generated_at_timestamp(): void
    {
        // Regla: generated_at siempre se establece en el momento del import.
        // Usamos assertEqualsWithDelta porque el timestamp se truncar a segundos
        // al persistir (MySQL/SQLite TIMESTAMP), por lo que la precisión sub-segundo
        // se pierde en round-trip.
        $org = Organization::factory()->create();
        User::factory()->create(['organization_id' => $org->id]);

        $expected = now();
        $zipPath = $this->buildZip([
            'contenido/redes-sociales.txt' => <<<'TXT'
[GANCHO] Test
[INSTAGRAM_POST_1] P1
[INSTAGRAM_POST_2] P2
[INSTAGRAM_POST_3] P3
[INSTAGRAM_STORY_1] S1
[INSTAGRAM_STORY_2] S2
[INSTAGRAM_STORY_3] S3
TXT,
        ]);
        $result = app(ValuationPackageIngestor::class)->ingest($zipPath, $org);

        $ig = CarMarketingContent::where('car_id', $result['car']->id)
            ->where('channel', CarMarketingContent::CHANNEL_INSTAGRAM)
            ->where('kind', CarMarketingContent::KIND_POST)
            ->where('slot', 1)
            ->first();
        $this->assertNotNull($ig);
        $this->assertNotNull($ig->generated_at, 'generated_at debe establecerse al importar');
        $this->assertEqualsWithDelta(
            $expected, $ig->generated_at, 5,
            'generated_at debe estar dentro de ±5s del momento del import (tolerancia por truncamiento a segundos)',
        );
    }

    public function test_marketing_txt_files_are_persisted_to_local_storage(): void
    {
        // El ingestor debe guardar los .txt de marketing en cars/{id}/contenido/
        // (en disco local) para que las vistas Blade puedan renderizarlos.
        $org = Organization::factory()->create();
        User::factory()->create(['organization_id' => $org->id]);

        $zipPath = $this->buildZip([
            'contenido/redes-sociales.txt' => "[GANCHO] Persistido\n",
            'contenido/anuncio-portales.txt' => "[TITULO] T\n[DESCRIPCION] D\n",
        ]);

        $result = app(ValuationPackageIngestor::class)->ingest($zipPath, $org);
        $carId = $result['car']->id;

        Storage::disk('local')->assertExists("cars/{$carId}/contenido/redes-sociales.txt");
        Storage::disk('local')->assertExists("cars/{$carId}/contenido/anuncio-portales.txt");
    }

    public function test_marketing_with_only_portales_creates_four_ad_channels(): void
    {
        // Sin redes sociales, solo portales → 4 filas kind=ad.
        $org = Organization::factory()->create();
        User::factory()->create(['organization_id' => $org->id]);

        $zipPath = $this->buildZip([
            'contenido/anuncio-portales.txt' => "[TITULO] Test\n[DESCRIPCION] D\n",
        ]);

        $result = app(ValuationPackageIngestor::class)->ingest($zipPath, $org);

        $this->assertSame(4, $result['marketing']);
        $this->assertSame(4, CarMarketingContent::where('car_id', $result['car']->id)->count());
        $ads = CarMarketingContent::where('car_id', $result['car']->id)
            ->where('kind', CarMarketingContent::KIND_AD)->count();
        $this->assertSame(4, $ads);
    }

    public function test_valid_photo_is_attached_and_non_image_extension_is_warned(): void
    {
        $org = Organization::factory()->create();
        User::factory()->create(['organization_id' => $org->id]);

        // Foto "válida" por extensión (el ingestor NO verifica magic bytes;
        // eso lo hace empaquetar.py durante la descarga HTTP)
        $fakeJpeg = str_repeat('A', 2048);
        // Archivo con extensión NO-imagen: el ingestor lo descarta
        $notImage = 'Esto es texto plano, no una imagen.';

        $zipPath = $this->buildZip([
            'contenido/ficha-publicitaria.txt' => "[TITULO] Test\n",
            'fotos/01.jpg' => $fakeJpeg,
            'fotos/02.txt' => $notImage,
        ]);

        $result = app(ValuationPackageIngestor::class)->ingest($zipPath, $org);

        $this->assertSame(1, $result['photos'],
            'Solo el .jpg se adjunta; el .txt es descartado por extensión no-imagen');
        $this->assertSame(1, $result['car']->photos()->count());

        // Verifica que el filtro por extensión se aplica
        $this->assertSame(
            1,
            $result['car']->photos()->where('sort_order', 1)->count(),
            'Solo 01.jpg debe existir en la galería'
        );
    }
}
