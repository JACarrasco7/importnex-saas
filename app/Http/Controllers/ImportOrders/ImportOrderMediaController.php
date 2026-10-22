<?php

namespace App\Http\Controllers\ImportOrders;

use App\Http\Controllers\Controller;
use App\Models\ImportOrder;
use App\Models\OrderMedia;
use App\Services\ImportOrders\OrderMediaService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Symfony\Component\HttpFoundation\StreamedResponse;

/** Fotos y documentos del encargo (disco privado). */
class ImportOrderMediaController extends Controller
{
    use AuthorizesImportOrder;

    public function __construct(private OrderMediaService $media) {}

    public function store(Request $request, ImportOrder $importOrder): RedirectResponse
    {
        $this->authorizeOrder($importOrder);
        $maxKb = (int) config('import_orders.media_max_kb', 15360);

        $data = $request->validate([
            'files' => ['required', 'array', 'min:1', 'max:20'],
            'files.*' => ['file', "max:{$maxKb}", 'mimes:jpg,jpeg,png,webp,heic,pdf,mp4,mov'],
            'collection' => ['required', Rule::in(OrderMedia::COLLECTIONS)],
            'visibility' => ['required', Rule::in([OrderMedia::VISIBILITY_CLIENT, OrderMedia::VISIBILITY_INTERNAL])],
            'caption' => ['nullable', 'string', 'max:255'],
        ]);

        foreach ($request->file('files') as $file) {
            $this->media->store($importOrder, $file, $data, $request->user());
        }

        $count = count($request->file('files'));

        return back()->with('success', $data['visibility'] === OrderMedia::VISIBILITY_CLIENT
            ? "{$count} archivo(s) subidos y visibles para el cliente."
            : "{$count} archivo(s) subidos (solo equipo).");
    }

    public function update(Request $request, ImportOrder $importOrder, OrderMedia $media): RedirectResponse
    {
        $this->authorizeChild($importOrder, $media);
        $data = $request->validate([
            'visibility' => ['sometimes', Rule::in([OrderMedia::VISIBILITY_CLIENT, OrderMedia::VISIBILITY_INTERNAL])],
            'caption' => ['nullable', 'string', 'max:255'],
            'collection' => ['sometimes', Rule::in(OrderMedia::COLLECTIONS)],
        ]);

        $this->media->update($media, $data, $request->user());

        return back()->with('success', 'Archivo actualizado.');
    }

    public function destroy(ImportOrder $importOrder, OrderMedia $media): RedirectResponse
    {
        $this->authorizeChild($importOrder, $media);
        $this->media->delete($media);

        return back()->with('success', 'Archivo eliminado.');
    }

    public function show(ImportOrder $importOrder, OrderMedia $media): StreamedResponse
    {
        $this->authorizeChild($importOrder, $media);
        abort_unless(Storage::disk($media->disk)->exists($media->path), 404);

        return Storage::disk($media->disk)->response($media->path, $media->original_name, [], $media->isImage() || $media->mime === 'application/pdf' ? 'inline' : 'attachment');
    }
}
