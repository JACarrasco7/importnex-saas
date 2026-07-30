<?php

namespace App\Http\Controllers;

use App\Models\Car;
use App\Models\Organization;
use App\Services\ValuationImporter;
use App\Services\ValuationPackageIngestor;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;
use Inertia\Response;

class ValuationImportController extends Controller
{
    /**
     * Show the import-from-chat form.
     */
    public function create(): Response
    {
        $existing = $this->listPendingFiles();

        return Inertia::render('Cars/ImportValuation', [
            'pending_files' => $existing,
        ]);
    }

    /**
     * Import a report from pasted JSON, uploaded file, or a path on the server.
     */
    public function store(
        Request $request,
        ValuationImporter $importer,
        ValuationPackageIngestor $ingestor
    ): RedirectResponse {
        $request->validate([
            'mode'  => ['required', 'in:paste,upload,server,zip'],
            'json'  => ['nullable', 'string', 'required_if:mode,paste'],
            'file'  => ['nullable', 'file', 'max:204800', 'required_if:mode,upload,zip'],
            'path'  => ['nullable', 'string', 'required_if:mode,server'],
        ]);

        $org = Organization::where('name', 'JJ Import Motors')->first() ?? auth()->user()->organization;

        try {
            // El paquete .zip lo hace todo de una vez: coche + PDFs + fotos.
            if ($request->input('mode') === 'zip') {
                $result = $ingestor->ingest($request->file('file')->getRealPath(), $org);

                return redirect()
                    ->route('cars.show', $result['car']->id)
                    ->with('success', $this->packageSummary($result));
            }

            $payload = match ($request->input('mode')) {
                'paste'  => json_decode($request->input('json'), true, flags: JSON_THROW_ON_ERROR),
                'upload' => json_decode(File::get($request->file('file')->getRealPath()), true, flags: JSON_THROW_ON_ERROR),
                'server' => $this->loadFromServer($request->input('path'), $importer),
            };

            $payload = $importer->validate($payload);

            $car = $importer->resolveCar($payload, $org);
            $wasNew = ! $car->exists;
            $importer->apply($car, $payload);

            return redirect()
                ->route('cars.show', $car->id)
                ->with('success', ($wasNew ? 'Coche creado' : 'Coche actualizado') . ' desde el informe.');
        } catch (\Throwable $e) {
            Log::error('ValuationImportController::store failed', ['error' => $e->getMessage()]);

            return back()
                ->withInput()
                ->withErrors(['json' => 'No se pudo importar: ' . $e->getMessage()]);
        }
    }

    /**
     * @param  array{car:Car, was_new:bool, photos:int, documents:int, warnings:array<int,string>}  $result
     */
    private function packageSummary(array $result): string
    {
        $parts = [$result['was_new'] ? 'Coche creado' : 'Coche actualizado'];

        if ($result['documents'] > 0) {
            $parts[] = $result['documents'] . ' documento(s) adjuntado(s)';
        }
        if ($result['photos'] > 0) {
            $parts[] = $result['photos'] . ' foto(s) cargada(s)';
        }
        if ($result['warnings'] !== []) {
            $parts[] = count($result['warnings']) . ' aviso(s): ' . implode(' / ', array_slice($result['warnings'], 0, 3));
        }

        return implode(' · ', $parts) . '.';
    }

    /**
     * AJAX endpoint: list files currently waiting in the import dir.
     */
    public function pending(): JsonResponse
    {
        return response()->json([
            'files' => $this->listPendingFiles(),
        ]);
    }

    private function loadFromServer(string $path, ValuationImporter $importer): array
    {
        $path = trim($path);

        // Accept absolute or relative paths inside storage/app/importnex/import/
        $org = auth()->user()->organization;
        $orgDirName = str_replace(' ', '_', $org->name);
        $candidates = [$path, storage_path('app/importnex/import/' . $orgDirName . '/vehicles/' . $path)];

        foreach ($candidates as $candidate) {
            if (File::exists($candidate) && File::isReadable($candidate)) {
                $data = json_decode(File::get($candidate), true, flags: JSON_THROW_ON_ERROR);
                $validated = $importer->validate($data);

                // After a successful import, move the file to a "processed" folder for this organization
                $processedDir = storage_path('app/importnex/import/' . $orgDirName . '/processed');
                if (! File::isDirectory($processedDir)) {
                    File::makeDirectory($processedDir, 0755, true);
                }
                File::move($candidate, $processedDir . '/' . basename($candidate) . '.' . now()->format('Ymd-His'));

                return $validated;
            }
        }

        throw new \RuntimeException("Fichero no encontrado: {$path}");
    }

    /**
     * @return array<int, array{name:string,size:int,mtime:string}>
     */
    private function listPendingFiles(): array
    {
        $org = auth()->user()->organization;
        if (!$org) {
            return [];
        }

        // Crear estructura de carpetas: organization_name/vehicles/
        $orgDirName = str_replace(' ', '_', $org->name);
        $dir = storage_path('app/importnex/import/' . $orgDirName . '/vehicles');

        if (! File::isDirectory($dir)) {
            File::makeDirectory($dir, 0755, true);
            return [];
        }

        $files = [];
        foreach (File::allFiles($dir, true) as $file) {
            if ($file->getExtension() !== 'json') {
                continue;
            }
            $files[] = [
                'name'  => $file->getRelativePathname(),
                'path'  => $file->getPathname(),
                'size'  => $file->getSize(),
                'mtime' => date('Y-m-d H:i:s', $file->getMTime()),
            ];
        }
        // newest first
        usort($files, fn ($a, $b) => strcmp($b['mtime'], $a['mtime']));
        return $files;
    }
}
