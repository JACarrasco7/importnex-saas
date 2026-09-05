<?php

namespace App\Http\Controllers;

use App\Models\Car;
use App\Models\Organization;
use App\Services\ValuationImporter;
use App\Services\ValuationPackageIngestor;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;
use Inertia\Response;

class ValuationImportController extends Controller
{
    /**
     * Form de subida de ZIP.
     */
    public function create(): Response
    {
        $dir = storage_path('app/importnex/import');
        $pendingFiles = [];
        if (is_dir($dir)) {
            foreach (scandir($dir) ?: [] as $name) {
                if ($name === '.' || $name === '..') {
                    continue;
                }
                $path = $dir.DIRECTORY_SEPARATOR.$name;
                if (is_file($path) && pathinfo($path, PATHINFO_EXTENSION) === 'json') {
                    $pendingFiles[] = [
                        'name' => $name,
                        'path' => $path,
                        'size' => filesize($path),
                        'mtime' => filemtime($path),
                    ];
                }
            }
            usort($pendingFiles, fn ($a, $b) => $b['mtime'] <=> $a['mtime']);
        }

        return Inertia::render('Cars/ImportValuation', [
            'pending_files' => $pendingFiles,
        ]);
    }

    /**
     * Procesa el ZIP del coche: crea/actualiza coche, adjunta PDFs, sube fotos.
     */
    public function store(
        Request $request,
        ValuationPackageIngestor $ingestor,
        ValuationImporter $importer
    ): RedirectResponse {
        $request->validate([
            'mode' => ['nullable', 'string', 'in:paste,upload,server'],
            'json' => ['nullable', 'string'],
            'path' => ['nullable', 'string'],
            'file' => ['nullable', 'file', 'mimetypes:application/zip,application/x-zip-compressed,application/json', 'max:204800'],
        ]);

        $org = Organization::where('name', 'JJ Import Motors')->first() ?? auth()->user()->organization;

        try {
            $mode = $request->input('mode');

            // Mode 'paste': JSON content directly in 'json' field
            if ($mode === 'paste') {
                if (! $request->filled('json')) {
                    return back()->withErrors(['json' => 'Debes pegar el JSON del informe.']);
                }
                $payload = json_decode($request->input('json'), true);
                if (! is_array($payload)) {
                    return back()->withErrors(['json' => 'JSON inválido.']);
                }

                $car = $this->applyPayload($importer, $payload, $org);

                return redirect()
                    ->route('cars.show', $car->id)
                    ->with('success', 'Coche importado correctamente.');
            }

            // Mode 'server': JSON file from importnex/import directory
            if ($mode === 'server') {
                $path = $request->input('path');
                if (! $path || ! is_file($path)) {
                    return back()->withErrors(['path' => 'Archivo no encontrado.']);
                }
                $content = file_get_contents($path);
                $payload = json_decode($content, true);
                if (! is_array($payload)) {
                    return back()->withErrors(['path' => 'JSON inválido.']);
                }

                $car = $this->applyPayload($importer, $payload, $org);

                // Move file to processed/ with timestamp
                $processedDir = storage_path('app/importnex/processed');
                if (! is_dir($processedDir)) {
                    mkdir($processedDir, 0755, true);
                }
                $basename = basename($path);
                @rename($path, $processedDir.'/'.$basename.'.'.now()->format('Ymd-His'));

                return redirect()
                    ->route('cars.show', $car->id)
                    ->with('success', 'Coche importado correctamente.');
            }

            // Mode 'upload' or default: file upload (ZIP or JSON)
            if (! $request->hasFile('file')) {
                return back()->withErrors(['file' => 'Debes subir un archivo ZIP o pegar JSON.']);
            }

            $uploaded = $request->file('file');
            $ext = strtolower($uploaded->getClientOriginalExtension());

            // JSON file uploaded directly
            if ($ext === 'json') {
                $payload = json_decode(file_get_contents($uploaded->getRealPath()), true);
                if (! is_array($payload)) {
                    return back()->withErrors(['file' => 'JSON inválido.']);
                }
                $car = $this->applyPayload($importer, $payload, $org);

                return redirect()
                    ->route('cars.show', $car->id)
                    ->with('success', 'Coche importado correctamente.');
            }

            // ZIP file (full package)
            $result = $ingestor->ingest($uploaded->getRealPath(), $org);

            return redirect()
                ->route('cars.show', $result['car']->id)
                ->with('success', $this->packageSummary($result));
        } catch (\Throwable $e) {
            Log::error('ValuationImportController::store failed', ['error' => $e->getMessage()]);

            return back()
                ->withErrors(['file' => 'No se pudo importar: '.$e->getMessage()]);
        }
    }

    /**
     * Validate + apply JSON payload to a new car.
     */
    private function applyPayload(ValuationImporter $importer, array $payload, Organization $org): Car
    {
        $payload = $importer->validate($payload);
        $car = new Car(['organization_id' => $org->id]);
        $importer->apply($car, $payload);
        $car->save();

        return $car;
    }

    /**
     * @param  array{car:Car, was_new:bool, photos:int, documents:int, contents?:int, marketing?:int, warnings:array<int,string>}  $result
     */
    private function packageSummary(array $result): string
    {
        $parts = [$result['was_new'] ? 'Coche creado' : 'Coche actualizado'];

        if (($result['contents'] ?? 0) > 0) {
            $parts[] = $result['contents'].' documento(s) de contenido maquetable(s)';
        }
        if (($result['marketing'] ?? 0) > 0) {
            $parts[] = $result['marketing'].' anuncio(s) de marketing importado(s)';
        }
        if ($result['documents'] > 0) {
            $parts[] = $result['documents'].' documento(s) adjuntado(s)';
        }
        if ($result['photos'] > 0) {
            $parts[] = $result['photos'].' foto(s) cargada(s)';
        }
        if ($result['warnings'] !== []) {
            $parts[] = count($result['warnings']).' aviso(s): '.implode(' / ', array_slice($result['warnings'], 0, 3));
        }

        return implode(' · ', $parts).'.';
    }
}
