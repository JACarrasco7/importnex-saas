<?php

namespace App\Observers;

use App\Models\CarChecklist;
use App\Models\CarDocument;
use App\Support\CarDocumentDefinitions;

class CarDocumentObserver
{
    public function updated(CarDocument $document): void
    {
        if (! $document->isDirty('status')) {
            return;
        }
        if ($document->status !== CarDocument::STATUS_RECEIVED) {
            return;
        }

        $milestoneKey = CarDocumentDefinitions::DOC_TO_MILESTONE[$document->doc_key] ?? null;
        if (! $milestoneKey) {
            return;
        }

        CarChecklist::where('car_id', $document->car_id)
            ->where('item_key', $milestoneKey)
            ->where('completed', false)
            ->update([
                'completed' => true,
                'completed_at' => now(),
                'updated_at' => now(),
            ]);
    }
}
