<?php

namespace App\Services;

use App\Models\Car;
use App\Models\CarDocument;
use App\Models\CarMarketingContent;
use App\Models\Organization;
use App\Support\Esqueleto;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use RuntimeException;
use Symfony\Component\Finder\SplFileInfo;
use ZipArchive;

/**
 * Ingesta de un paquete .zip generado por el chat.
 *
 * Un paquete es todo el trabajo de un coche en un solo archivo:
 *
 *   informe.json                  <- contrato de valoracion (schema_version 1)
 *   manifest.json                 <- opcional; describe el resto de archivos
 *   documentos/*.pdf              <- PDFs internos -> expediente, grupo ai_reports
 *   publicidad/*.pdf              <- PDFs para el cliente -> expediente, grupo ai_reports
 *   fotos/*.jpg                   <- fotos del anuncio -> galeria del coche
 *
 * Sin manifest funciona igual: se deduce el papel de cada archivo por la carpeta
 * en la que esta. El manifest solo sirve para poner titulos bonitos y forzar el
 * orden de las fotos.
 *
 * Todo el paquete se procesa en una sola pasada: crear/actualizar el coche,
 * adjuntar los PDFs y cargar las fotos. Una subida, nada mas que hacer.
 */
class ValuationPackageIngestor
{
    public const MANIFEST_VERSION = 1;

    /** paquete_version 2: contenido/*.txt en vez de documentos/*.pdf + publicidad/*.pdf */
    public const PACKAGE_VERSION_2 = 2;

    private const IMAGE_EXTENSIONS = ['jpg', 'jpeg', 'png', 'webp', 'avif', 'gif'];

    private const CONTENT_FOLDER = 'contenido';

    public function __construct(private ValuationImporter $importer) {}

    /**
     * Procesa el zip entero.
     *
     * @return array{car: Car, was_new: bool, photos: int, documents: int, contents: int, marketing: int, warnings: array<int,string>}
     */
    public function ingest(string $zipPath, Organization $org): array
    {
        $workDir = $this->extract($zipPath);
        $warnings = [];

        try {
            $manifest = $this->readManifest($workDir);
            $payload = $this->readReport($workDir, $manifest);

            $payload = $this->importer->validate($payload);

            $car = $this->importer->resolveCar($payload, $org);
            $wasNew = ! $car->exists;

            $packageVersion = (int) ($manifest['paquete_version'] ?? 1);

            $photoFiles = $this->collectPhotos($workDir, $manifest);
            $contentFiles = $packageVersion >= self::PACKAGE_VERSION_2
                ? $this->collectContent($workDir, $manifest)
                : [];
            $docFiles = $this->collectDocuments($workDir, $manifest);

            // Si el paquete trae fotos, no descargamos las del anuncio.
            $this->importer->skipRemotePhotos = count($photoFiles) > 0;

            $this->importer->apply($car, $payload);

            $photos = $this->attachPhotos($car, $photoFiles, $warnings);
            // attachMarketing ANTES de attachContent: este último hace
            // deleteDirectory sobre cars/{id}/contenido/ y podría borrar archivos
            // que aún necesitamos. Los paths de $contentFiles apuntan al
            // workDir temporal, no al storage, pero el orden defensivo es más
            // claro y robusto.
            $marketing = $this->attachMarketing($car, $contentFiles, $warnings);
            $contents = $this->attachContent($car, $contentFiles, $warnings);
            $documents = $this->attachDocuments($car, $docFiles, $warnings);

            $this->warnPackageGaps($car, $packageVersion, $contentFiles, $photos, $warnings);

            return [
                'car' => $car->refresh(),
                'was_new' => $wasNew,
                'photos' => $photos,
                'documents' => $documents,
                'contents' => $contents,
                'marketing' => $marketing,
                'warnings' => $warnings,
            ];
        } finally {
            File::deleteDirectory($workDir);
        }
    }

    // ── Lectura del paquete ──────────────────────────────────────────────────

    private function extract(string $zipPath): string
    {
        $zip = new ZipArchive;
        if ($zip->open($zipPath) !== true) {
            throw new RuntimeException('No se pudo abrir el .zip.');
        }

        $workDir = storage_path('app/importnex/tmp/'.uniqid('pkg_', true));
        File::makeDirectory($workDir, 0755, true);

        // Zip-slip: rechazamos rutas que se salgan del directorio de trabajo.
        for ($i = 0; $i < $zip->numFiles; $i++) {
            $name = $zip->getNameIndex($i);
            if (str_contains($name, '..') || str_starts_with($name, '/')) {
                $zip->close();
                File::deleteDirectory($workDir);
                throw new RuntimeException("Ruta no permitida dentro del zip: {$name}");
            }
        }

        $zip->extractTo($workDir);
        $zip->close();

        return $workDir;
    }

    private function readManifest(string $dir): array
    {
        $path = $this->findFile($dir, fn ($file) => $file->getFilename() === 'manifest.json');

        if (! $path) {
            return [];
        }

        $data = json_decode(File::get($path), true);

        return is_array($data) ? $data : [];
    }

    /**
     * El informe es el JSON con _meta.schema_version. Si el manifest dice cual
     * es, lo usamos; si no, se busca por contenido.
     */
    private function readReport(string $dir, array $manifest): array
    {
        if (! empty($manifest['informe'])) {
            $declared = $this->resolveInside($dir, $manifest['informe']);
            if ($declared && File::exists($declared)) {
                return json_decode(File::get($declared), true, flags: JSON_THROW_ON_ERROR);
            }
        }

        $found = $this->findFile($dir, function ($file) {
            if (strtolower($file->getExtension()) !== 'json') {
                return false;
            }
            $data = json_decode(File::get($file->getPathname()), true);

            return isset($data['_meta']['schema_version']);
        });

        if (! $found) {
            throw new RuntimeException('El zip no contiene ningun informe JSON valido (falta _meta.schema_version).');
        }

        return json_decode(File::get($found), true, flags: JSON_THROW_ON_ERROR);
    }

    /**
     * @return array<int, array{path:string, order:int, type:string}>
     */
    private function collectPhotos(string $dir, array $manifest): array
    {
        $photos = [];

        // 1) Lo que declare el manifest, en su orden.
        foreach ($manifest['fotos'] ?? [] as $index => $entry) {
            $relative = is_array($entry) ? ($entry['archivo'] ?? null) : $entry;
            $path = $relative ? $this->resolveInside($dir, $relative) : null;

            if ($path && File::exists($path)) {
                $photos[$path] = [
                    'path' => $path,
                    'order' => is_array($entry) ? (int) ($entry['orden'] ?? $index + 1) : $index + 1,
                    'type' => is_array($entry) ? ($entry['categoria'] ?? 'exterior') : 'exterior',
                ];
            }
        }

        // 2) Lo que haya en fotos/ y no estuviera declarado.
        foreach ($this->allFiles($dir) as $file) {
            $path = $file->getPathname();
            if (isset($photos[$path])) {
                continue;
            }
            if (! in_array(strtolower($file->getExtension()), self::IMAGE_EXTENSIONS, true)) {
                continue;
            }
            if (! $this->inFolder($dir, $path, 'fotos')) {
                continue;
            }

            $photos[$path] = ['path' => $path, 'order' => count($photos) + 1, 'type' => 'exterior'];
        }

        $list = array_values($photos);
        usort($list, fn ($a, $b) => $a['order'] <=> $b['order']);

        return $list;
    }

    /**
     * @return array<int, array{path:string, title:string}>
     */
    private function collectDocuments(string $dir, array $manifest): array
    {
        $docs = [];

        foreach (['documentos', 'publicidad'] as $section) {
            foreach ($manifest[$section] ?? [] as $entry) {
                $relative = is_array($entry) ? ($entry['archivo'] ?? null) : $entry;
                $path = $relative ? $this->resolveInside($dir, $relative) : null;

                if ($path && File::exists($path)) {
                    $docs[$path] = [
                        'path' => $path,
                        'title' => is_array($entry)
                            ? ($entry['titulo'] ?? basename($path))
                            : basename($path),
                    ];
                }
            }
        }

        foreach ($this->allFiles($dir) as $file) {
            $path = $file->getPathname();
            if (isset($docs[$path]) || strtolower($file->getExtension()) !== 'pdf') {
                continue;
            }

            $docs[$path] = ['path' => $path, 'title' => $file->getFilename()];
        }

        return array_values($docs);
    }

    /**
     * Archivos de contenido (paquete_version 2): contenido/*.txt y contenido/json/*.json
     * que alimentan las vistas Blade (ficha del cliente + informe interno).
     *
     * Los .json los genera `esqueleto_a_json.py` en la skill; el Blade los prefiere
     * al .txt porque vienen ya tipados (spec[], faq[], pasos[]).
     *
     * @return array<int, array{path:string, archivo:string, plantilla:?string, visibilidad:?string}>
     */
    private function collectContent(string $dir, array $manifest): array
    {
        $contenidos = [];

        // 1) Lo que declare el manifest en contenido[].
        foreach ($manifest['contenido'] ?? [] as $entry) {
            $relative = is_array($entry) ? ($entry['archivo'] ?? null) : $entry;
            $path = $relative ? $this->resolveInside($dir, $relative) : null;

            if ($path && File::exists($path)) {
                $contenidos[$path] = [
                    'path' => $path,
                    'archivo' => basename($path),
                    'plantilla' => is_array($entry) ? ($entry['plantilla'] ?? null) : null,
                    'visibilidad' => is_array($entry) ? ($entry['visibilidad'] ?? null) : null,
                ];
            }
        }

        // 2) Cualquier .txt o .json en contenido/ no declarado.
        //    El .json lo genera la skill (contenido/json/*.json) y es lo que consume
        //    el Blade del dossier público: ver 07-marketing/handoff_laravel.md.
        foreach ($this->allFiles($dir) as $file) {
            $path = $file->getPathname();
            if (isset($contenidos[$path]) || ! in_array(strtolower($file->getExtension()), ['txt', 'json'], true)) {
                continue;
            }
            if (! $this->inFolder($dir, $path, self::CONTENT_FOLDER)) {
                continue;
            }

            $contenidos[$path] = [
                'path' => $path,
                'archivo' => $file->getFilename(),
                'plantilla' => null,
                'visibilidad' => null,
            ];
        }

        return array_values($contenidos);
    }

    /**
     * Importa los esqueletos de marketing v2 (05-sep-2026) a `car_marketing_contents`.
     *
     * Esquema: cada fila es única por (car_id, channel, kind, slot).
     *
     *  - redes-sociales.txt → 3 redes × (3 posts + 3 stories) = hasta 18 filas:
     *      · tiktok    [TIKTOK_POST_1..3] + [TIKTOK_STORY_1..3]    (viral 15-30s)
     *      · instagram [INSTAGRAM_POST_1..3] + [INSTAGRAM_STORY_1..3] (visual)
     *      · facebook  [FACEBOOK_POST_1..3] + [FACEBOOK_STORY_1..3] (informativo)
     *    Cada red: hashtags propios (fallback [HASHTAGS] globales) y
     *    [RED]_SUBIR_PASOS (se guarda solo en el post 1).
     *
     *  - anuncio-portales.txt → 1 ficha base reutilizada en 4 portales (4 filas
     *    kind=ad slot=1): milanuncios, coches_net, wallapop, facebook marketplace.
     *
     * Robustez: solo crea filas con contenido real. Post vacío → warning;
     * story vacío → se omite en silencio; portal sin TITULO o DESCRIPCION →
     * warning y no se crea ninguno de los 4.
     *
     * Idempotente: updateOrCreate sobre (car_id, channel, kind, slot).
     * Reimportar el mismo paquete sustituye, NO duplica. Status inicial: `published`
     * (el ZIP trae copy listo de Claude). Reimportar PRESERVA el status actual
     * (no revierte un published a draft: respeta el trabajo del operador).
     *
     * Limpia emojis problemáticos para MySQL utf8mb3 de Forge (ver sanitizeForMysql).
     *
     * @param  array<int, array{path:string, archivo:string, plantilla:?string, visibilidad:?string}>  $contenidos
     * @param  array<int, string>  $warnings
     */
    private function attachMarketing(Car $car, array $contenidos, array &$warnings): int
    {
        if ($contenidos === []) {
            return 0;
        }

        $redesPath = null;
        $portalesPath = null;
        $redesJsonPath = null;
        $portalesJsonPath = null;
        foreach ($contenidos as $c) {
            if ($c['archivo'] === 'redes-sociales.txt') {
                $redesPath = $c['path'];
            } elseif ($c['archivo'] === 'redes-sociales.json') {
                $redesJsonPath = $c['path'];
            } elseif ($c['archivo'] === 'anuncio-portales.txt') {
                $portalesPath = $c['path'];
            } elseif ($c['archivo'] === 'anuncio-portales.json') {
                $portalesJsonPath = $c['path'];
            }
        }

        if (! $redesPath && ! $redesJsonPath && ! $portalesPath) {
            return 0;
        }

        // A4 auditoría 09-sep-2026: si el ZIP trae redes-sociales.json (vocabulario
        // v2 con bloques canónico: canales.instagram_feed.{gancho,ficha,…}), lo
        // preferimos sobre el TXT. El TXT era lo que generaba v1.5 de la skill
        // y tenía el vocabulario que el panel ya importaba correctamente.
        $redesV2 = null;
        if ($redesJsonPath) {
            $decoded = json_decode(File::get($redesJsonPath), true);
            if (is_array($decoded) && isset($decoded['canales']) && is_array($decoded['canales'])) {
                $redesV2 = $decoded['canales'];
            } else {
                $warnings[] = 'contenido/json/redes-sociales.json no tiene estructura {canales:*}: fallback a TXT.';
            }
        }
        $redes = $redesV2 === null && $redesPath ? Esqueleto::desde(File::get($redesPath)) : null;
        $portales = $portalesPath ? Esqueleto::desde(File::get($portalesPath)) : null;

        // A11 auditoría 12-sep-2026: anuncio-portales.json (v2, bloques PT_*)
        // tiene prioridad sobre el TXT (v1, [TITULO]/[DESCRIPCION]). Ver
        // ingestarPortalesV2() para el porqué: el v1 copiaba el MISMO texto
        // a los 4 portales, que es justo el bug reportado.
        $portalesV2 = null;
        if ($portalesJsonPath) {
            $decodedPt = json_decode(File::get($portalesJsonPath), true);
            if (is_array($decodedPt) && isset($decodedPt['portales']['base']) && is_array($decodedPt['portales']['base'])) {
                $portalesV2 = $decodedPt['portales']['base'];
            } else {
                $warnings[] = 'contenido/json/anuncio-portales.json no tiene estructura {portales:{base:*}}: fallback a TXT.';
            }
        }

        $saved = 0;
        $now = now();

        // ───────────────────────────────────────────────────────────────────
        // redes-sociales.json (v2) tiene prioridad sobre el TXT (v1).
        // El v2 trae la estructura canónica de la skill (canales.* con bloques
        // gancho/ficha/contexto/argumento/pega/cta/hashtags). A4 auditoría
        // 09-sep-2026: el panel mostraba 78 chars porque solo leía el TXT.
        // ───────────────────────────────────────────────────────────────────
        if ($redesV2 !== null) {
            $saved += $this->ingestarRedesV2($car, $redesV2, $warnings, $now);
            // B7 auditoría 09-sep-2026: el v2 también emite stories en su
            // propio canal (canales.stories), que se ingiere abajo.
        } elseif ($redes) {
            // Hashtags globales: aplicables a las 3 redes.
            // Esqueleto::lista() ya hace trim + filtra vacíos internamente.
            $hashtagsGlobales = array_values($redes->lista('HASHTAGS'));
            $pieFoto = array_values($redes->lista('PIE_FOTO'));
            $gancho = trim((string) $redes->uno('GANCHO'));

            if ($gancho === '') {
                $warnings[] = 'redes-sociales.txt sin [GANCHO]: no se creó contenido de redes.';
            }

            // TikTok — viral, 15-30s reels. 3 posts + 3 stories.
            $tiktokPosts = array_values(array_filter(array_map('trim', [
                $redes->uno('TIKTOK_POST_1') ?? '',
                $redes->uno('TIKTOK_POST_2') ?? '',
                $redes->uno('TIKTOK_POST_3') ?? '',
            ])));
            $tiktokStories = array_values(array_filter(array_map('trim', [
                $redes->uno('TIKTOK_STORY_1') ?? '',
                $redes->uno('TIKTOK_STORY_2') ?? '',
                $redes->uno('TIKTOK_STORY_3') ?? '',
            ])));
            $tiktokHashtags = array_values(array_filter(array_map('trim', $redes->lista('TIKTOK_HASHTAGS'))));
            $tiktokHashtags = $tiktokHashtags ?: $hashtagsGlobales;
            $tiktokSubirPasos = $redes->uno('TIKTOK_SUBIR_PASOS');

            // Instagram — visual, copy medio. 3 posts + 3 stories.
            $instagramPosts = array_values(array_filter(array_map('trim', [
                $redes->uno('INSTAGRAM_POST_1') ?? '',
                $redes->uno('INSTAGRAM_POST_2') ?? '',
                $redes->uno('INSTAGRAM_POST_3') ?? '',
            ])));
            $instagramStories = array_values(array_filter(array_map('trim', [
                $redes->uno('INSTAGRAM_STORY_1') ?? '',
                $redes->uno('INSTAGRAM_STORY_2') ?? '',
                $redes->uno('INSTAGRAM_STORY_3') ?? '',
            ])));
            $instagramHashtags = array_values(array_filter(array_map('trim', $redes->lista('INSTAGRAM_HASHTAGS'))));
            $instagramHashtags = $instagramHashtags ?: $hashtagsGlobales;
            $instagramSubirPasos = $redes->uno('INSTAGRAM_SUBIR_PASOS');

            // Facebook — informativo masivo. 3 posts + 3 stories.
            $facebookPosts = array_values(array_filter(array_map('trim', [
                $redes->uno('FACEBOOK_POST_1') ?? '',
                $redes->uno('FACEBOOK_POST_2') ?? '',
                $redes->uno('FACEBOOK_POST_3') ?? '',
            ])));
            $facebookStories = array_values(array_filter(array_map('trim', [
                $redes->uno('FACEBOOK_STORY_1') ?? '',
                $redes->uno('FACEBOOK_STORY_2') ?? '',
                $redes->uno('FACEBOOK_STORY_3') ?? '',
            ])));
            $facebookHashtags = array_values(array_filter(array_map('trim', $redes->lista('FACEBOOK_HASHTAGS'))));
            $facebookHashtags = $facebookHashtags ?: $hashtagsGlobales;
            $facebookSubirPasos = $redes->uno('FACEBOOK_SUBIR_PASOS');

            // Helper local para crear las 3+3 filas de cada red social.
            // Capturamos $car, $this, $redes, $red->lista etc. en el `use`.
            $createSocialSet = function (string $channel, array $posts, array $stories, array $hashtags, ?string $subirPasos) use ($car, $gancho, $pieFoto, $now, &$saved, &$warnings) {
                if ($gancho === '') {
                    return;
                }
                // Crear hasta 3 posts (slot 1..3). Faltan slots → warning, no error.
                for ($slot = 1; $slot <= 3; $slot++) {
                    $copy = $posts[$slot - 1] ?? '';
                    if ($copy === '') {
                        $warnings[] = 'redes-sociales.txt sin ['.strtoupper($channel)."_POST_{$slot}]: no se creó el post {$slot} de {$channel}.";

                        continue;
                    }
                    $this->upsertMarketing($car, $channel, [
                        'kind' => CarMarketingContent::KIND_POST,
                        'slot' => $slot,
                        'title' => $gancho,
                        'description' => $copy,
                        'hashtags' => $hashtags,
                        'photo_tips' => $slot === 1 ? $pieFoto : [],
                        'subir_pasos' => $slot === 1 ? ($subirPasos ?? '') : '',
                        'generated_at' => $now,
                    ]);
                    $saved++;
                }
                // Crear hasta 3 stories.
                for ($slot = 1; $slot <= 3; $slot++) {
                    $copy = $stories[$slot - 1] ?? '';
                    if ($copy === '') {
                        continue; // stories sin copy se omiten en silencio (no crítico)
                    }
                    $this->upsertMarketing($car, $channel, [
                        'kind' => CarMarketingContent::KIND_STORY,
                        'slot' => $slot,
                        'title' => $gancho,
                        'description' => $copy,
                        'hashtags' => $hashtags,
                        'photo_tips' => [],
                        'subir_pasos' => '',
                        'generated_at' => $now,
                    ]);
                    $saved++;
                }
            };

            $createSocialSet('tiktok', $tiktokPosts, $tiktokStories, $tiktokHashtags, $tiktokSubirPasos);
            $createSocialSet('instagram', $instagramPosts, $instagramStories, $instagramHashtags, $instagramSubirPasos);
            $createSocialSet('facebook', $facebookPosts, $facebookStories, $facebookHashtags, $facebookSubirPasos);
        }

        // ───────────────────────────────────────────────────────────────────
        // B7 auditoría 09-sep-2026: cuando el ZIP trae redes-sociales.json
        // (v2), emitimos también las stories del canal "canales.stories" —
        // el generador legacy solo las creaba en la rama v1 (TXT).
        // ───────────────────────────────────────────────────────────────────
        if ($redesV2 !== null && isset($redesV2['stories']) && is_array($redesV2['stories'])) {
            $saved += $this->ingestarStoriesV2($car, $redesV2['stories'], $warnings, $now);
        }

        // ───────────────────────────────────────────────────────────────────
        // anuncio-portales.json (v2) tiene prioridad sobre el TXT (v1).
        // A11 auditoría 12-sep-2026: el v1 (rama `elseif` de abajo) copiaba
        // el MISMO [TITULO]+[DESCRIPCION] a los 4 canales de
        // PORTAL_CHANNELS — bug reportado ("los 4 portales reciben el mismo
        // texto"). El v2 diferencia lo que pide 07-marketing/copy_engine.md
        // §5: título A/B, Wallapop como recorte del base (nunca una
        // reescritura) y Facebook Marketplace con sus propios bloques FBMP_.
        // ───────────────────────────────────────────────────────────────────
        if ($portalesV2 !== null) {
            $saved += $this->ingestarPortalesV2($car, $portalesV2, $redesV2, $warnings, $now);
        } elseif ($portales) {
            // Vocabulario v1 (ZIPs antiguos sin contenido/json/anuncio-portales.json):
            // 1 ficha base reutilizada en los 4 portales web. Misma TITULO +
            // DESCRIPCION + FICHA_RAPIDA + QUE_INCLUYE + AVISO_LEGAL para
            // milanuncios, coches_net, wallapop, facebook marketplace.
            // SUBIR_PASOS indica cómo pegarlo en cada portal (1 entrada común).
            $titulo = trim((string) $portales->uno('TITULO'));
            $descripcion = trim((string) $portales->uno('DESCRIPCION'));
            $subirPasos = $portales->uno('SUBIR_PASOS');

            if ($titulo === '' || $descripcion === '') {
                $warnings[] = 'anuncio-portales.txt sin [TITULO] o [DESCRIPCION]: no se crearon los anuncios de portales.';
            }
            if ($titulo !== '' && $descripcion !== '') {
                foreach (CarMarketingContent::PORTAL_CHANNELS as $channel) {
                    $this->upsertMarketing($car, $channel, [
                        'kind' => CarMarketingContent::KIND_AD,
                        'slot' => 1,
                        'title' => $titulo,
                        'description' => $descripcion,
                        'hashtags' => [],
                        'photo_tips' => [],
                        'subir_pasos' => $subirPasos ?? '',
                        'generated_at' => $now,
                    ]);
                    $saved++;
                }
            }
        }

        return $saved;
    }

    /**
     * A11 auditoría 12-sep-2026: ingestar el vocabulario v2 de portales
     * (contenido/json/anuncio-portales.json → portales.base, bloques PT_*).
     *
     * Antes de esto, la rama v1 de `attachMarketing()` solo leía
     * [TITULO]/[DESCRIPCION] de anuncio-portales.txt y copiaba el MISMO
     * texto a los 4 canales de PORTAL_CHANNELS — bug reportado: "los 4
     * portales reciben el mismo texto". El v2 ya diferencia tres cosas por
     * portal, como pide 07-marketing/copy_engine.md §5:
     *
     *  - Milanuncios / Coches.net: título A + ficha completa (texto base).
     *  - Wallapop: título B + el mismo cuerpo RECORTADO a 600-900 caracteres
     *    (nunca una reescritura — es un recorte del base, cortado en un
     *    límite de frase; ver recortarParaWallapop()).
     *  - Facebook Marketplace (channel 'facebook', kind=ad): usa sus
     *    propios bloques FBMP_* (vienen en redes-sociales.json →
     *    canales.fb_marketplace, NO en anuncio-portales.json) porque el
     *    copy engine ya los redacta distintos (sin iconos, con "Escríbeme
     *    por Messenger…"). Si el ZIP es antiguo y no trae ese bloque, cae
     *    al texto base — mismo comportamiento que antes, no una regresión.
     *
     * @param  array<string, mixed>  $base  anuncio-portales.json → portales.base
     * @param  array<string, mixed>|null  $canalesRedesV2  redes-sociales.json → canales (para fb_marketplace)
     * @param  array<int, string>  $warnings
     */
    private function ingestarPortalesV2(Car $car, array $base, ?array $canalesRedesV2, array &$warnings, $now): int
    {
        $tituloA = trim((string) ($base['titulo_a'] ?? ''));
        $tituloB = trim((string) ($base['titulo_b'] ?? '')) ?: $tituloA;
        $cuerpo = $this->componerCuerpoPortalV2($base);

        if ($tituloA === '' || $cuerpo === '') {
            $warnings[] = 'anuncio-portales.json (v2) sin título o ficha: no se crearon los anuncios de portales.';

            return 0;
        }

        $saved = 0;
        $subirPasosRaw = $base['subir_pasos'] ?? '';
        $subirPasos = is_array($subirPasosRaw) ? implode("\n", $subirPasosRaw) : (string) $subirPasosRaw;

        // Milanuncios y Coches.net: texto base completo, título A.
        foreach (['milanuncios', 'coches_net'] as $channel) {
            $this->upsertMarketing($car, $channel, [
                'kind' => CarMarketingContent::KIND_AD,
                'slot' => 1,
                'title' => $tituloA,
                'description' => $cuerpo,
                'hashtags' => [],
                'photo_tips' => [],
                'subir_pasos' => $subirPasos,
                'generated_at' => $now,
            ]);
            $saved++;
        }

        // Wallapop: título B (variante) + recorte a 600-900 caracteres
        // (copy_engine.md §5: "Wallapop es un recorte del base, nunca una
        // reescritura"). El recorte quita antes lo narrativo (equipamiento,
        // "cómo funciona"); el aviso legal (A26/A27) y la pega honesta (A28)
        // no se tocan — son obligatorios en todo anuncio de portal.
        $this->upsertMarketing($car, 'wallapop', [
            'kind' => CarMarketingContent::KIND_AD,
            'slot' => 1,
            'title' => $tituloB,
            'description' => $this->componerCuerpoPortalWallapop($base),
            'hashtags' => [],
            'photo_tips' => [],
            'subir_pasos' => $subirPasos,
            'generated_at' => $now,
        ]);
        $saved++;

        // Facebook Marketplace (channel 'facebook', kind=ad): bloques FBMP_
        // propios, si el ZIP los trae (redes-sociales.json → canales.fb_marketplace).
        $fbmp = is_array($canalesRedesV2['fb_marketplace'] ?? null) ? $canalesRedesV2['fb_marketplace'] : null;
        $fbmpTitulo = trim((string) ($fbmp['titulo'] ?? ''));
        $fbmpDescripcion = trim((string) ($fbmp['descripcion'] ?? ''));
        if ($fbmpTitulo !== '' && $fbmpDescripcion !== '') {
            $fbmpCuerpo = $fbmpDescripcion;
            if (! empty($fbmp['pega'])) {
                $fbmpCuerpo .= "\n\n".$fbmp['pega'];
            }
            if (! empty($fbmp['contacto'])) {
                $fbmpCuerpo .= "\n\n".$fbmp['contacto'];
            }
            $this->upsertMarketing($car, 'facebook', [
                'kind' => CarMarketingContent::KIND_AD,
                'slot' => 1,
                'title' => $fbmpTitulo,
                'description' => $fbmpCuerpo,
                'hashtags' => [],
                'photo_tips' => [],
                'subir_pasos' => $subirPasos,
                'generated_at' => $now,
            ]);
        } else {
            // ZIP antiguo sin FBMP_*: mismo comportamiento que antes (no
            // regresión), pero avisamos para que se note en el log.
            $warnings[] = 'anuncio-portales.json (v2) sin bloques FBMP_* para Facebook Marketplace: se usó el texto base de portal (regenera el ZIP con la skill actualizada para diferenciarlo).';
            $this->upsertMarketing($car, 'facebook', [
                'kind' => CarMarketingContent::KIND_AD,
                'slot' => 1,
                'title' => $tituloA,
                'description' => $cuerpo,
                'hashtags' => [],
                'photo_tips' => [],
                'subir_pasos' => $subirPasos,
                'generated_at' => $now,
            ]);
        }
        $saved++;

        return $saved;
    }

    /**
     * Compone el cuerpo del anuncio de portal a partir de los bloques PT_*
     * ya estructurados por `esqueleto_a_json.py` (resumen, ficha, estado
     * verificado + pega honesta [A28], equipamiento, qué incluye, cómo
     * funciona, aviso legal [A26/A27]). Se omite cualquier parte vacía.
     *
     * @param  array<string, mixed>  $base
     */
    private function componerCuerpoPortalV2(array $base): string
    {
        $partes = [];

        $resumen = $base['resumen'] ?? '';
        $resumen = is_array($resumen) ? implode("\n", $resumen) : (string) $resumen;
        if (trim($resumen) !== '') {
            $partes[] = trim($resumen);
        }

        $ficha = $base['ficha'] ?? [];
        if (is_array($ficha) && $ficha !== []) {
            $lineas = [];
            foreach ($ficha as $item) {
                if (is_array($item) && isset($item['etiqueta'], $item['valor'])) {
                    $lineas[] = trim((string) $item['etiqueta']).': '.trim((string) $item['valor']);
                }
            }
            if ($lineas !== []) {
                $partes[] = implode("\n", $lineas);
            }
        }

        // PT_ESTADO: estado verificado + la pega honesta (A28). Obligatorio
        // por regla dura — nunca se omite si viene en el JSON.
        $estado = $this->normalizarLista($base['estado'] ?? []);
        if ($estado !== []) {
            $partes[] = implode("\n", $estado);
        }

        $equipamiento = $base['equipamiento'] ?? '';
        $equipamiento = is_array($equipamiento) ? implode("\n", $equipamiento) : (string) $equipamiento;
        if (trim($equipamiento) !== '') {
            $partes[] = trim($equipamiento);
        }

        $queIncluye = $this->normalizarLista($base['que_incluye'] ?? []);
        if ($queIncluye !== []) {
            $partes[] = "Incluye:\n".implode("\n", array_map(fn ($i) => '• '.$i, $queIncluye));
        }

        $comoFunciona = $base['como_funciona'] ?? '';
        $comoFunciona = is_array($comoFunciona) ? implode("\n", $comoFunciona) : (string) $comoFunciona;
        if (trim($comoFunciona) !== '') {
            $partes[] = trim($comoFunciona);
        }

        $aviso = $base['aviso'] ?? '';
        $aviso = is_array($aviso) ? implode("\n", $aviso) : (string) $aviso;
        if (trim($aviso) !== '') {
            $partes[] = trim($aviso);
        }

        return implode("\n\n", $partes);
    }

    /**
     * A11 auditoría 12-sep-2026: cuerpo COMPACTO para Wallapop (600-900
     * caracteres, copy_engine.md §5). No es una reescritura: es el mismo
     * cuerpo base con las partes menos esenciales (equipamiento, "cómo
     * funciona") quitadas primero — igual que se acortaría un anuncio real
     * a mano. El aviso legal (A26/A27) y la pega honesta (A28) NUNCA se
     * quitan: son obligatorios en todo anuncio de portal, Wallapop incluido.
     * Si aun así sobra, se aplica el recorte por caracteres como último
     * recurso (recortarParaWallapop()).
     */
    private function componerCuerpoPortalWallapop(array $base, int $max = 900): string
    {
        $aviso = $base['aviso'] ?? '';
        $aviso = trim(is_array($aviso) ? implode("\n", $aviso) : (string) $aviso);

        // La pega honesta (A28) es el último elemento de PT_ESTADO — así la
        // genera la skill: verificados primero, ⚠️ pega al final (ver
        // empaquetar.py::_pega_del_payload). Se protege igual que el aviso
        // legal: nunca se recorta.
        $estadoLista = $this->normalizarLista($base['estado'] ?? []);
        $pega = '';
        $verificados = $estadoLista;
        foreach (array_reverse($estadoLista) as $item) {
            if (str_contains($item, "\u{26A0}")) {
                $pega = $item;
                $verificados = array_values(array_diff($estadoLista, [$item]));
                break;
            }
        }

        // Bloque protegido: pega honesta + aviso legal. Nunca se recortan
        // (A28 y A26/A27 son reglas duras) — lo demás sí es recortable.
        $protegido = implode("\n\n", array_filter([$pega, $aviso], fn ($s) => $s !== ''));

        $resto = $base;
        unset($resto['equipamiento'], $resto['como_funciona'], $resto['aviso']);
        $resto['estado'] = $verificados;
        $cuerpoResto = $this->componerCuerpoPortalV2($resto);

        if ($protegido === '') {
            // Sin pega ni aviso en el JSON (no debería pasar: son
            // obligatorios en el origen), recorte simple sobre todo el cuerpo.
            return $this->recortarParaWallapop($cuerpoResto, $max);
        }

        $separador = "\n\n";
        $espacioParaResto = $max - mb_strlen($protegido) - mb_strlen($separador);
        if ($espacioParaResto < 100) {
            // Lo protegido por sí solo ocupa casi todo el hueco: se
            // prioriza completo y delante solo va el resumen, recortado a
            // lo que quepa (puede superar levemente el máximo: la banda
            // 600-900 es un objetivo editorial, nunca a costa de la pega
            // honesta o el aviso legal).
            $resumen = trim((string) ($base['resumen'] ?? ''));
            $cuerpoResto = $resumen !== '' ? mb_substr($resumen, 0, max(0, $espacioParaResto)) : '';
        } else {
            $cuerpoResto = $this->recortarParaWallapop($cuerpoResto, $espacioParaResto);
        }

        return trim($cuerpoResto.$separador.$protegido);
    }

    /**
     * A11 auditoría 12-sep-2026: recorta el cuerpo del anuncio a 600-900
     * caracteres para Wallapop (07-marketing/copy_engine.md §5: "Wallapop
     * es un recorte del base, nunca una reescritura"). Corta en el último
     * punto o salto de párrafo antes del máximo para no partir una frase a
     * la mitad; si no hay ninguno razonablemente cerca, corta en el último
     * espacio. Si el texto ya cabe en el máximo, se devuelve intacto — la
     * banda 600-900 es un objetivo editorial, no un mínimo que rellenar.
     */
    private function recortarParaWallapop(string $texto, int $max = 900): string
    {
        $texto = trim($texto);
        if (mb_strlen($texto) <= $max) {
            return $texto;
        }

        $corte = mb_substr($texto, 0, $max);
        $ultimoPunto = max(
            mb_strrpos($corte, '.') ?: 0,
            mb_strrpos($corte, "\n") ?: 0,
        );
        if ($ultimoPunto > $max * 0.5) {
            return rtrim(mb_substr($corte, 0, $ultimoPunto + 1));
        }
        $ultimoEspacio = mb_strrpos($corte, ' ');
        if ($ultimoEspacio !== false) {
            $corte = mb_substr($corte, 0, $ultimoEspacio);
        }

        return rtrim($corte, " \n.,;:").'…';
    }

    /**
     * A4 auditoría 09-sep-2026: ingestar el vocabulario v2 de la skill
     * (contenido/json/redes-sociales.json → canales.*). El panel ya muestra
     * este copy estructurado cuando está en BD, pero antes solo leíamos el
     * TXT y mostraba los 78 caracteres del vocabulario legacy.
     *
     * Estructura esperada por canal (instagram_feed, video, facebook_pagina,
     * marketplace):
     *   { gancho, gancho_b, ficha:[], contexto, argumento:[], pega, cta,
     *     send_ask, hashtags:[] }
     *
     * Genera hasta 3 posts por canal (slot 1..3):
     *  - slot 1: gancho + ficha + contexto + argumento (3 viñetas) + pega + cta
     *  - slot 2: gancho_b + contexto + argumento (si existen)
     *  - slot 3: send_ask + contexto (CTA de preguntas)
     *
     * @param  array<string, array<string, mixed>>  $canales
     * @param  array<int, string>  $warnings
     */
    private function ingestarRedesV2(Car $car, array $canales, array &$warnings, $now): int
    {
        $saved = 0;

        // Mapa canal_v2 → canal_BD. La skill usa 'instagram_feed' / 'video'
        // (TikTok) / 'facebook_pagina' / 'marketplace'; la BD usa nombres
        // cortos: instagram / tiktok / facebook / wallapop, etc.
        $mapaCanales = [
            'instagram_feed' => 'instagram',
            'video' => 'tiktok',
            'facebook_pagina' => 'facebook',
            'marketplace' => 'wallapop',
        ];

        foreach ($mapaCanales as $canalSkill => $canalBd) {
            if (! isset($canales[$canalSkill]) || ! is_array($canales[$canalSkill])) {
                continue;
            }
            $c = $canales[$canalSkill];
            $gancho = trim((string) ($c['gancho'] ?? ''));
            if ($gancho === '') {
                continue;
            }

            $ficha = $this->normalizarLista($c['ficha'] ?? []);
            $argumento = $this->normalizarLista($c['argumento'] ?? []);
            $hashtags = $this->normalizarLista($c['hashtags'] ?? []);

            // slot 1: post principal con todo el cuerpo.
            $cuerpo1 = $this->componerCuerpoPostV2(
                $gancho,
                $ficha,
                (string) ($c['contexto'] ?? ''),
                $argumento,
                (string) ($c['pega'] ?? ''),
                (string) ($c['cta'] ?? '')
            );
            $this->upsertMarketing($car, $canalBd, [
                'kind' => CarMarketingContent::KIND_POST,
                'slot' => 1,
                'title' => $gancho,
                'description' => $cuerpo1,
                'hashtags' => $hashtags,
                'photo_tips' => $ficha,
                'subir_pasos' => '',
                'generated_at' => $now,
            ]);
            $saved++;

            // slot 2: variante A/B con gancho_b si existe.
            $ganchoB = trim((string) ($c['gancho_b'] ?? ''));
            if ($ganchoB !== '') {
                $cuerpo2 = $this->componerCuerpoPostV2(
                    $ganchoB,
                    [],
                    (string) ($c['contexto'] ?? ''),
                    $argumento,
                    '',
                    (string) ($c['cta'] ?? '')
                );
                $this->upsertMarketing($car, $canalBd, [
                    'kind' => CarMarketingContent::KIND_POST,
                    'slot' => 2,
                    'title' => $ganchoB,
                    'description' => $cuerpo2,
                    'hashtags' => $hashtags,
                    'photo_tips' => [],
                    'subir_pasos' => '',
                    'generated_at' => $now,
                ]);
                $saved++;
            }

            // slot 3: send_ask (CTA de preguntas, formato típico de Instagram).
            $sendAsk = trim((string) ($c['send_ask'] ?? ''));
            if ($sendAsk !== '') {
                $this->upsertMarketing($car, $canalBd, [
                    'kind' => CarMarketingContent::KIND_POST,
                    'slot' => 3,
                    'title' => $sendAsk,
                    'description' => $sendAsk,
                    'hashtags' => $hashtags,
                    'photo_tips' => [],
                    'subir_pasos' => '',
                    'generated_at' => $now,
                ]);
                $saved++;
            }
        }

        return $saved;
    }

    /**
     * B7 auditoría 09-sep-2026: ingestar `canales.stories[]` del v2. Cada
     * item es { canal, copy } o un string. Genera hasta 3 stories por red.
     *
     * @param  array<int, mixed>  $stories
     * @param  array<int, string>  $warnings
     */
    private function ingestarStoriesV2(Car $car, array $stories, array &$warnings, $now): int
    {
        $saved = 0;
        $porCanal = []; // 'instagram' => [story1, story2, ...]

        foreach ($stories as $item) {
            if (is_string($item)) {
                // Formato legacy: el item es el copy directo (sin canal). Lo
                // asumimos para Instagram, que es lo que generaba la skill.
                $porCanal['instagram'][] = ['canal' => 'instagram', 'copy' => $item];
            } elseif (is_array($item) && isset($item['copy'])) {
                $canal = strtolower(trim((string) ($item['canal'] ?? 'instagram')));
                $porCanal[$canal][] = ['canal' => $canal, 'copy' => (string) $item['copy']];
            }
        }

        foreach ($porCanal as $canal => $items) {
            $slot = 0;
            foreach ($items as $item) {
                $slot++;
                if ($slot > 3) {
                    break;
                }
                $copy = trim($item['copy']);
                if ($copy === '') {
                    continue;
                }
                $this->upsertMarketing($car, $canal, [
                    'kind' => CarMarketingContent::KIND_STORY,
                    'slot' => $slot,
                    'title' => '',
                    'description' => $copy,
                    'hashtags' => [],
                    'photo_tips' => [],
                    'subir_pasos' => '',
                    'generated_at' => $now,
                ]);
                $saved++;
            }
        }

        return $saved;
    }

    /**
     * Compone el cuerpo de un post v2 a partir de sus bloques. El bloque
     * `argumento` puede ser lista o string; lo formateamos como viñetas
     * con "•". Si la ficha contiene emojis de check (✓ ✅), se mantienen
     * — el sanitizador de MySQL los convierte a "•".
     */
    private function componerCuerpoPostV2(string $gancho, array $ficha, string $contexto, array $argumento, string $pega, string $cta): string
    {
        $partes = [];

        if ($contexto !== '') {
            $partes[] = $contexto;
        }

        if ($argumento !== []) {
            $viñetas = array_map(
                fn ($a) => '• '.ltrim((string) $a),
                $argumento
            );
            $partes[] = implode("\n", $viñetas);
        }

        if ($pega !== '') {
            $partes[] = $pega;
        }

        if ($cta !== '') {
            $partes[] = $cta;
        }

        return implode("\n\n", $partes);
    }

    /**
     * Normaliza un campo `lista` del JSON v2: acepta array, string con
     * saltos de línea, o string con bullets. Devuelve array de strings
     * limpios (sin bullets ni espacios redundantes).
     *
     * @return array<int, string>
     */
    private function normalizarLista(mixed $valor): array
    {
        if (is_string($valor)) {
            $valor = preg_split('/\R+/u', $valor);
        }
        if (! is_array($valor)) {
            return [];
        }
        $out = [];
        foreach ($valor as $item) {
            if (! is_string($item)) {
                continue;
            }
            $limpio = trim(preg_replace('/^[•·\-*]+\s*/u', '', $item) ?? '');
            if ($limpio !== '') {
                $out[] = $limpio;
            }
        }

        return $out;
    }

    /**
     * Limpia caracteres problemáticos para MySQL antes de escribir.
     *
     * B4 auditoría 09-sep-2026: la BD Forge YA es utf8mb4 (verificado en
     * config/database.php). Ya no eliminamos emojis >U+FFFF (🗓️🛣️🐎🏷️
     * forman parte del vocabulario canónico de la skill y se usan en
     * `contenido/json/redes-sociales.json → canales.instagram_feed.ficha`).
     * Solo mantenemos:
     *   - Reparación de encoding si la cadena NO es UTF-8 válido (defensiva).
     *   - Invisibles/control que ensucian el copy.
     *   - Checkmarks ✓ ✅ ✔ ✍ → viñeta "•" (BMP, sin pérdida semántica).
     *   - Limpieza de espacios/puntuación tras normalizar.
     *
     * Aplica solo a strings (recursivo en arrays).
     *
     * @param  array<mixed,mixed>  $attributes
     * @return array<mixed,mixed>
     */
    private function sanitizeForMysql(array $attributes): array
    {
        // Checkmarks/ticks → viñeta "•" (BMP, seguro en utf8mb3) para no perder
        // el efecto de lista en copys tipo "✅ 306 CV · cambio automático".
        $bulletMap = [
            "\u{2705}" => '•', // ✅ White Heavy Check Mark
            "\u{2713}" => '•', // ✓ Check Mark
            "\u{2714}" => '•', // ✔ Heavy Check Mark
            "\u{270D}" => '•', // ✍ Writing Hand (usado como bullet en algunos ZIPs)
        ];

        $sanitize = function (string $value) use ($bulletMap): string {
            // Reparar encoding SOLO si la cadena no es UTF-8 válido (mb_check_encoding
            // es una comprobación estricta, no una heurística como mb_detect_encoding,
            // que puede "adivinar mal" y corromper UTF-8 ya correcto — pasó en pruebas
            // reales: convirtió ✅ válido en basura de doble codificación).
            if (! mb_check_encoding($value, 'UTF-8')) {
                $converted = @mb_convert_encoding($value, 'UTF-8', 'Windows-1252');
                if (is_string($converted) && $converted !== '' && mb_check_encoding($converted, 'UTF-8')) {
                    $value = $converted;
                }
            }

            // Quitar zero-width invisibles (U+200B-U+200D, U+FEFF).
            $value = preg_replace('/[\x{200B}-\x{200D}\x{FEFF}]/u', '', $value) ?? $value;
            // Quitar caracteres de control ASCII (excepto \n \r \t).
            $value = preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/u', '', $value) ?? $value;

            // Checkmarks → viñeta "•".
            $value = strtr($value, $bulletMap);

            // Resto del bloque Dingbats (U+2700-27BF) y Miscellaneous Symbols
            // and Arrows (U+2B00-2BFF) → ELIMINADOS. Antes (B4 auditoría)
            // también se eliminaban los emojis 4-byte (U+1F300-1F9FF) y todo
            // >U+FFFF; con utf8mb4 ya no hace falta: 🗓️🛣️🐎🏷️ del
            // vocabulario de la skill sobreviven intactos. Tampoco se eliminan
            // los BMP (U+2728 ⭐, U+2600 ☀, etc.) — el cliente los usa como
            // decoración visual del copy.
            $value = preg_replace('/[\x{2B00}-\x{2BFF}]/u', '', $value) ?? $value;

            // Limpieza tras quitar emojis: colapsar espacios dobles (por línea,
            // sin tocar saltos de línea) y quitar el espacio que queda antes
            // de puntuación (p.ej. "texto ." → "texto.").
            $value = preg_replace('/[ \t]{2,}/', ' ', $value) ?? $value;
            $value = preg_replace('/ +([.,;:!?])/', '$1', $value) ?? $value;
            // Trim por línea (un emoji al principio/final de línea deja
            // espacio suelto) sin perder la estructura multilínea.
            $value = implode("\n", array_map('trim', explode("\n", $value)));

            return $value;
        };

        $walker = function ($value) use (&$walker, $sanitize) {
            if (is_string($value)) {
                return $sanitize($value);
            }
            if (is_array($value)) {
                return array_map($walker, $value);
            }

            return $value;
        };

        return array_map($walker, $attributes);
    }

    /**
     * updateOrCreate sobre (car_id, channel, kind, slot). Si el registro
     * existía con status=published, el ZIP lo devuelve a draft (es lo correcto:
     * el operador debe revisar antes de republicar tras una reimportación).
     * Todo lo que entra por aquí viene del ZIP (source=zip, fijo por el ingestor;
     * el operador puede regenerar con IA y entonces source pasa a 'ai' vía
     * CarMarketingController::generate).
     */
    private function upsertMarketing(Car $car, string $channel, array $attributes): CarMarketingContent
    {
        // Limpia caracteres problemáticos para MySQL antes de escribir:
        // - Invisibles (zero-width) y controles ASCII.
        // - Checkmarks ✅ ✓ ✔ ✍ → viñeta "•" (BMP, sin pérdida semántica).
        // Los emojis 4-byte (🗓️🛣️🐎🏷️ del vocabulario v2) y la mayoría de
        // BMP se CONSERVAN: la BD Forge es utf8mb4 desde B4 auditoría
        // 09-sep-2026.
        $attributes = $this->sanitizeForMysql($attributes);

        $kind = $attributes['kind'] ?? CarMarketingContent::KIND_AD;
        $slot = $attributes['slot'] ?? 1;

        $existing = CarMarketingContent::where([
            'car_id' => $car->id,
            'channel' => $channel,
            'kind' => $kind,
            'slot' => $slot,
        ])->first();

        // El contenido del ZIP ya llega listo de Claude (no es un borrador):
        // se publica al importar. En reimportaciones se respeta el status
        // actual para no deshacer publicaciones o ediciones del operador.
        if (! $existing) {
            $attributes['status'] = CarMarketingContent::STATUS_PUBLISHED;
            $attributes['published_at'] = now();
        } else {
            unset($attributes['status'], $attributes['published_at']);
        }

        return CarMarketingContent::updateOrCreate(
            [
                'car_id' => $car->id,
                'channel' => $channel,
                'kind' => $kind,
                'slot' => $slot,
            ],
            array_merge($attributes, ['source' => CarMarketingContent::SOURCE_ZIP]),
        );
    }

    /**
     * A23 (03-sep-2026): fotos y marketing son OBLIGATORIOS en paquetes v2.
     * Si falta alguno, el import entra pero se avisa — el operador tiene que
     * saber que la ficha/módulo de marketing quedará cojo antes de publicar.
     *
     * @param  array<int, array{path:string, archivo:string, plantilla:?string, visibilidad:?string}>  $contenidos
     * @param  array<int, string>  $warnings
     */
    private function warnPackageGaps(Car $car, int $packageVersion, array $contenidos, int $photos, array &$warnings): void
    {
        $hasMarketingTxt = collect($contenidos)->contains(
            fn ($c) => in_array($c['archivo'], ['redes-sociales.txt', 'anuncio-portales.txt'], true),
        );

        if ($packageVersion >= self::PACKAGE_VERSION_2 && ! $hasMarketingTxt) {
            $warnings[] = 'El paquete v2 no incluye marketing: faltan contenido/redes-sociales.txt y contenido/anuncio-portales.txt — el módulo de marketing quedará vacío para este coche.';
        }

        if ($photos === 0 && $car->photos()->count() === 0) {
            $warnings[] = 'El paquete no incluye fotos y el coche no tenía galería — la ficha quedará sin fotos.';
        }
    }

    /**
     * Guarda los esqueletos .txt en storage local (privado): cars/{id}/contenido/.
     * Reimportar el mismo coche sustituye los anteriores, no los duplica.
     *
     * @param  array<int, array{path:string, archivo:string, plantilla:?string, visibilidad:?string}>  $contenidos
     * @param  array<int, string>  $warnings
     */
    private function attachContent(Car $car, array $contenidos, array &$warnings): int
    {
        if ($contenidos === []) {
            return 0;
        }

        $dir = 'cars/'.$car->id.'/contenido';
        Storage::disk('local')->deleteDirectory($dir);

        $saved = 0;
        foreach ($contenidos as $c) {
            try {
                $archivo = $this->safeFilename($c['archivo']);
                Storage::disk('local')->put($dir.'/'.$archivo, File::get($c['path']));
                $saved++;
            } catch (\Throwable $e) {
                $warnings[] = 'No se pudo guardar el contenido '.$c['archivo'].': '.$e->getMessage();
                Log::warning('Package content failed', ['car_id' => $car->id, 'error' => $e->getMessage()]);
            }
        }

        return $saved;
    }

    // ── Persistencia ─────────────────────────────────────────────────────────

    /**
     * @param  array<int, array{path:string, order:int, type:string}>  $photos
     * @param  array<int, string>  $warnings
     */
    private function attachPhotos(Car $car, array $photos, array &$warnings): int
    {
        if ($photos === []) {
            return 0;
        }

        // Reimportar el mismo coche sustituye la galeria, no la duplica.
        foreach ($car->photos()->get() as $existing) {
            Storage::disk('public')->delete($existing->url);
            $existing->delete();
        }

        $saved = 0;

        foreach ($photos as $photo) {
            try {
                $extension = strtolower(pathinfo($photo['path'], PATHINFO_EXTENSION)) ?: 'jpg';
                $saved++;
                $target = sprintf('cars/%d/photos/%03d.%s', $car->id, $saved, $extension);

                Storage::disk('public')->put($target, File::get($photo['path']));

                $car->photos()->create([
                    'organization_id' => $car->organization_id,
                    'url' => $target,
                    'sort_order' => $saved,
                    'photo_type' => $this->normalizePhotoType($photo['type']),
                ]);
            } catch (\Throwable $e) {
                $saved--;
                $warnings[] = 'No se pudo guardar la foto '.basename($photo['path']).': '.$e->getMessage();
                Log::warning('Package photo failed', ['car_id' => $car->id, 'error' => $e->getMessage()]);
            }
        }

        return $saved;
    }

    /**
     * @param  array<int, array{path:string, title:string}>  $documents
     * @param  array<int, string>  $warnings
     */
    private function attachDocuments(Car $car, array $documents, array &$warnings): int
    {
        $saved = 0;

        foreach ($documents as $doc) {
            try {
                $filename = $this->safeFilename(basename($doc['path']));
                $target = sprintf('cars/%d/documents/%s', $car->id, $filename);

                Storage::disk('public')->put($target, File::get($doc['path']));

                // Un informe por nombre: reimportar sustituye, no acumula copias.
                $existing = $car->documents()
                    ->where('group', CarDocument::GROUP_AI_REPORTS)
                    ->where('name', $doc['title'])
                    ->first();

                $attributes = [
                    'organization_id' => $car->organization_id,
                    'name' => $doc['title'],
                    'doc_type' => 'other',
                    'group' => CarDocument::GROUP_AI_REPORTS,
                    'status' => CarDocument::STATUS_RECEIVED,
                    'url' => $target,
                    'uploaded_at' => now(),
                ];

                if ($existing) {
                    if ($existing->url !== $target) {
                        Storage::disk('public')->delete($existing->url);
                    }
                    $existing->update($attributes);
                } else {
                    $car->documents()->create($attributes);
                }

                $saved++;
            } catch (\Throwable $e) {
                $warnings[] = 'No se pudo adjuntar '.basename($doc['path']).': '.$e->getMessage();
                Log::warning('Package document failed', ['car_id' => $car->id, 'error' => $e->getMessage()]);
            }
        }

        return $saved;
    }

    // ── Utilidades ───────────────────────────────────────────────────────────

    private function normalizePhotoType(?string $type): string
    {
        $allowed = ['exterior', 'interior', 'engine', 'defect', 'document'];
        $type = strtolower((string) $type);

        $aliases = ['motor' => 'engine', 'defecto' => 'defect', 'documento' => 'document'];
        $type = $aliases[$type] ?? $type;

        return in_array($type, $allowed, true) ? $type : 'exterior';
    }

    private function safeFilename(string $name): string
    {
        $name = preg_replace('/[^A-Za-z0-9._-]+/', '-', $name) ?? 'documento.pdf';

        return trim($name, '-') ?: 'documento.pdf';
    }

    /**
     * Convierte una ruta relativa del manifest en ruta real, sin permitir salir
     * del directorio extraido. Tolera que el zip tenga una carpeta raiz.
     */
    private function resolveInside(string $dir, string $relative): ?string
    {
        $relative = ltrim(str_replace('\\', '/', $relative), '/');

        $candidates = [$dir.'/'.$relative];

        foreach (File::directories($dir) as $sub) {
            $candidates[] = $sub.'/'.$relative;
        }

        foreach ($candidates as $candidate) {
            $real = realpath($candidate);
            if ($real && str_starts_with($real, realpath($dir))) {
                return $real;
            }
        }

        return null;
    }

    private function inFolder(string $dir, string $path, string $folder): bool
    {
        $relative = str_replace('\\', '/', substr($path, strlen($dir) + 1));

        return str_contains('/'.strtolower($relative), '/'.strtolower($folder).'/');
    }

    /** @return SplFileInfo[] */
    private function allFiles(string $dir): array
    {
        return File::allFiles($dir);
    }

    private function findFile(string $dir, callable $matcher): ?string
    {
        foreach ($this->allFiles($dir) as $file) {
            if ($matcher($file)) {
                return $file->getPathname();
            }
        }

        return null;
    }
}
