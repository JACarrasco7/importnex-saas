<?php

namespace App\Http\Controllers;

use App\Models\Car;
use App\Models\Organization;
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
        return Inertia::render('Cars/ImportValuation');
    }

    /**
     * Procesa el ZIP del coche: crea/actualiza coche, adjunta PDFs, sube fotos.
     */
    public function store(
        Request $request,
        ValuationPackageIngestor $ingestor
    ): RedirectResponse {
        $request->validate([
            'file' => ['required', 'file', 'mimetypes:application/zip,application/x-zip-compressed', 'max:204800'],
        ]);

        $org = Organization::where('name', 'JJ Import Motors')->first() ?? auth()->user()->organization;

        try {
            $result = $ingestor->ingest($request->file('file')->getRealPath(), $org);

            return redirect()
                ->route('cars.show', $result['car']->id)
                ->with('success', $this->packageSummary($result));
        } catch (\Throwable $e) {
            Log::error('ValuationImportController::store failed', ['error' => $e->getMessage()]);

            return back()
                ->withErrors(['file' => 'No se pudo importar: ' . $e->getMessage()]);
        }
    }

    /**
     * @param  array{car:Car, was_new:bool, photos:int, documents:int, contents?:int, warnings:array<int,string>}  $result
     */
    private function packageSummary(array $result): string
    {
        $parts = [$result['was_new'] ? 'Coche creado' : 'Coche actualizado'];

        if (($result['contents'] ?? 0) > 0) {
            $parts[] = $result['contents'] . ' documento(s) de contenido maquetable(s)';
        }
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
}
