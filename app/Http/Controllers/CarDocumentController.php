<?php

namespace App\Http\Controllers;

use App\Models\Car;
use App\Models\CarDocument;
use App\Support\CarDocumentDefinitions;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\StreamedResponse;

class CarDocumentController extends Controller
{
    public function index(Car $car)
    {
        $documents = $car->documents()->orderBy('uploaded_at', 'desc')->get();

        return response()->json($documents);
    }

    public function store(Request $request, Car $car): RedirectResponse
    {
        // Backwards-compat: accept either doc_key (new) or doc_type (legacy)
        if (! $request->has('doc_key') && $request->has('doc_type')) {
            $request->merge(['doc_key' => $request->input('doc_type')]);
        }

        $request->validate([
            'documents.*' => 'required|file|mimes:pdf,doc,docx,xls,xlsx|max:10240',
            'doc_key' => 'required|string',
            'name' => 'nullable|string|max:255',
        ]);

        $docKey = $request->input('doc_key');
        $customName = $request->input('name');

        // Get document definition from CarDocumentDefinitions
        $definitions = app(CarDocumentDefinitions::class)->all();
        $docDef = collect($definitions)->firstWhere('key', $docKey);

        if (! $docDef) {
            return back()->withErrors(['doc_key' => 'Document type not recognized.']);
        }

        if ($request->hasFile('documents')) {
            foreach ($request->file('documents') as $document) {
                $path = $document->store(
                    "cars/{$car->id}/documents",
                    'public'
                );

                $name = $customName ?: $document->getClientOriginalName();

                CarDocument::create([
                    'car_id' => $car->id,
                    'organization_id' => $car->organization_id,
                    'name' => $name,
                    'doc_key' => $docKey,
                    'doc_type' => $docDef['doc_type'],
                    'group' => $docDef['group'],
                    'url' => $path,
                    'uploaded_at' => now(),
                ]);
            }
        }

        return back()->with('success', 'Documents uploaded successfully');
    }

    public function show(Car $car, CarDocument $document): StreamedResponse|BinaryFileResponse
    {
        if ($document->car_id !== $car->id) {
            abort(403);
        }

        $disk = Storage::disk('public');

        if (! $disk->exists($document->url)) {
            abort(404);
        }

        $mime = $disk->mimeType($document->url);
        $extension = pathinfo($document->url, PATHINFO_EXTENSION);

        if (str_starts_with($mime, 'image/') || $mime === 'application/pdf') {
            return $disk->response($document->url);
        }

        return $disk->download($document->url, $document->name.'.'.$extension);
    }

    public function destroy(Car $car, CarDocument $document): RedirectResponse
    {
        if ($document->car_id !== $car->id) {
            abort(403);
        }

        Storage::disk('public')->delete($document->url);
        $document->delete();

        return back()->with('success', 'Document deleted');
    }
}
