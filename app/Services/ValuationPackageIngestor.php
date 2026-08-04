<?php

namespace App\Services;

use App\Models\Car;
use App\Models\CarDocument;
use App\Models\Organization;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use RuntimeException;
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

    public function __construct(private ValuationImporter $importer)
    {
    }

    /**
     * Procesa el zip entero.
     *
     * @return array{car: Car, was_new: bool, photos: int, documents: int, contents: int, warnings: array<int,string>}
     */
    public function ingest(string $zipPath, Organization $org): array
    {
        $workDir = $this->extract($zipPath);
        $warnings = [];

        try {
            $manifest = $this->readManifest($workDir);
            $payload  = $this->readReport($workDir, $manifest);

            $payload = $this->importer->validate($payload);

            $car    = $this->importer->resolveCar($payload, $org);
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

            $photos    = $this->attachPhotos($car, $photoFiles, $warnings);
            $contents  = $this->attachContent($car, $contentFiles, $warnings);
            $documents = $this->attachDocuments($car, $docFiles, $warnings);

            return [
                'car'       => $car->refresh(),
                'was_new'   => $wasNew,
                'photos'    => $photos,
                'documents' => $documents,
                'contents'  => $contents,
                'warnings'  => $warnings,
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

        $workDir = storage_path('app/importnex/tmp/' . uniqid('pkg_', true));
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
                    'path'  => $path,
                    'order' => is_array($entry) ? (int) ($entry['orden'] ?? $index + 1) : $index + 1,
                    'type'  => is_array($entry) ? ($entry['categoria'] ?? 'exterior') : 'exterior',
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
                        'path'  => $path,
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
                    'path'        => $path,
                    'archivo'     => basename($path),
                    'plantilla'   => is_array($entry) ? ($entry['plantilla'] ?? null) : null,
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
                'path'        => $path,
                'archivo'     => $file->getFilename(),
                'plantilla'   => null,
                'visibilidad' => null,
            ];
        }

        return array_values($contenidos);
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

        $dir = 'cars/' . $car->id . '/contenido';
        Storage::disk('local')->deleteDirectory($dir);

        $saved = 0;
        foreach ($contenidos as $c) {
            try {
                $archivo = $this->safeFilename($c['archivo']);
                Storage::disk('local')->put($dir . '/' . $archivo, File::get($c['path']));
                $saved++;
            } catch (\Throwable $e) {
                $warnings[] = 'No se pudo guardar el contenido ' . $c['archivo'] . ': ' . $e->getMessage();
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
                    'url'             => $target,
                    'sort_order'      => $saved,
                    'photo_type'      => $this->normalizePhotoType($photo['type']),
                ]);
            } catch (\Throwable $e) {
                $saved--;
                $warnings[] = 'No se pudo guardar la foto ' . basename($photo['path']) . ': ' . $e->getMessage();
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
                    'name'            => $doc['title'],
                    'doc_type'        => 'other',
                    'group'           => CarDocument::GROUP_AI_REPORTS,
                    'status'          => CarDocument::STATUS_RECEIVED,
                    'url'             => $target,
                    'uploaded_at'     => now(),
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
                $warnings[] = 'No se pudo adjuntar ' . basename($doc['path']) . ': ' . $e->getMessage();
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

        $candidates = [$dir . '/' . $relative];

        foreach (File::directories($dir) as $sub) {
            $candidates[] = $sub . '/' . $relative;
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

        return str_contains('/' . strtolower($relative), '/' . strtolower($folder) . '/');
    }

    /** @return \Symfony\Component\Finder\SplFileInfo[] */
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
