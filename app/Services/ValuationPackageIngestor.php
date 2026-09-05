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
     * Archivos de contenido (paquete_version 2): contenido/*.txt que alimentan
     * las vistas Blade (ficha del cliente + informe interno).
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

        // 2) Cualquier .txt en contenido/ no declarado.
        foreach ($this->allFiles($dir) as $file) {
            $path = $file->getPathname();
            if (isset($contenidos[$path]) || strtolower($file->getExtension()) !== 'txt') {
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
     * Reimportar el mismo paquete sustituye, NO duplica. Status siempre `draft`;
     * una fila en `published` se revierte a draft al reimportar (el operador
     * debe revisar antes de republicar).
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
        foreach ($contenidos as $c) {
            if ($c['archivo'] === 'redes-sociales.txt') {
                $redesPath = $c['path'];
            } elseif ($c['archivo'] === 'anuncio-portales.txt') {
                $portalesPath = $c['path'];
            }
        }

        if (! $redesPath && ! $portalesPath) {
            return 0;
        }

        $redes = $redesPath ? Esqueleto::desde(File::get($redesPath)) : null;
        $portales = $portalesPath ? Esqueleto::desde(File::get($portalesPath)) : null;

        $saved = 0;
        $now = now();

        // ───────────────────────────────────────────────────────────────────
        // redes-sociales.txt → 3 redes × (3 posts + 3 stories) = 18 filas
        // Esquema por canal (3 redes): TikTok (viral, 15-30s), Instagram (visual),
        // Facebook (informativo masivo). Cada red con su tono propio en cada slot.
        // ───────────────────────────────────────────────────────────────────
        if ($redes) {
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
                        'status' => CarMarketingContent::STATUS_DRAFT,
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
                        'status' => CarMarketingContent::STATUS_DRAFT,
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
        // anuncio-portales.txt → 1 ficha base reutilizada en los 4 portales web.
        // Misma TITULO + DESCRIPCION + FICHA_RAPIDA + QUE_INCLUYE + AVISO_LEGAL
        // para milanuncios, coches_net, wallapop, facebook marketplace.
        // SUBIR_PASOS indica cómo pegarlo en cada portal (1 entrada común).
        // ───────────────────────────────────────────────────────────────────
        if ($portales) {
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
                        'status' => CarMarketingContent::STATUS_DRAFT,
                        'generated_at' => $now,
                    ]);
                    $saved++;
                }
            }
        }

        return $saved;
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
        $kind = $attributes['kind'] ?? CarMarketingContent::KIND_AD;
        $slot = $attributes['slot'] ?? 1;

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
