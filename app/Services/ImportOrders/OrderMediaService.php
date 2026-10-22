<?php

namespace App\Services\ImportOrders;

use App\Enums\OrderEventType;
use App\Models\ImportOrder;
use App\Models\OrderMedia;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

/**
 * Fotos y documentos del encargo en disco PRIVADO (`local`). El portal los
 * sirve por ruta con el token del cliente; nunca con URL pública.
 */
class OrderMediaService
{
    public function __construct(private OrderTimeline $timeline) {}

    /**
     * @param  array{collection?: string, visibility?: string, caption?: ?string, approval_id?: ?int}  $options
     */
    public function store(ImportOrder $order, UploadedFile $file, array $options, User $user): OrderMedia
    {
        $path = $file->store("orders/{$order->organization_id}/{$order->id}", 'local');

        $media = $order->media()->create([
            'organization_id' => $order->organization_id,
            'order_approval_id' => $options['approval_id'] ?? null,
            'uploaded_by' => $user->id,
            'collection' => in_array($options['collection'] ?? null, OrderMedia::COLLECTIONS, true) ? $options['collection'] : 'other',
            'disk' => 'local',
            'path' => $path,
            'original_name' => mb_substr($file->getClientOriginalName(), 0, 255),
            'mime' => $file->getMimeType(),
            'size' => $file->getSize() ?: 0,
            'caption' => $options['caption'] ?? null,
            'visibility' => ($options['visibility'] ?? null) === OrderMedia::VISIBILITY_CLIENT ? OrderMedia::VISIBILITY_CLIENT : OrderMedia::VISIBILITY_INTERNAL,
        ]);

        if ($media->visibility === OrderMedia::VISIBILITY_CLIENT && ! $media->order_approval_id) {
            $this->timeline->record($order, OrderEventType::MediaPublished, 'Nueva foto o documento: '.($media->caption ?: $media->original_name), [
                'user' => $user,
                'visible' => true,
                'meta' => ['media_id' => $media->id],
            ]);
        }

        return $media;
    }

    public function update(OrderMedia $media, array $data, User $user): OrderMedia
    {
        $wasInternal = $media->visibility === OrderMedia::VISIBILITY_INTERNAL;
        $media->fill(array_intersect_key($data, array_flip(['caption', 'visibility', 'collection'])))->save();

        if ($wasInternal && $media->visibility === OrderMedia::VISIBILITY_CLIENT) {
            $this->timeline->record($media->importOrder, OrderEventType::MediaPublished, 'Nueva foto o documento: '.($media->caption ?: $media->original_name), [
                'user' => $user,
                'visible' => true,
                'meta' => ['media_id' => $media->id],
            ]);
        }

        return $media;
    }

    public function delete(OrderMedia $media): void
    {
        Storage::disk($media->disk)->delete($media->path);
        $media->delete();
    }
}
