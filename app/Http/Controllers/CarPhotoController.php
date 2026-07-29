<?php

namespace App\Http\Controllers;

use App\Models\Car;
use App\Models\CarPhoto;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Storage;

class CarPhotoController extends Controller
{
    public function index(Car $car)
    {
        $photos = $car->photos()->orderBy('sort_order')->get();

        return response()->json($photos);
    }

    public function store(Request $request, Car $car): RedirectResponse
    {
        $request->validate([
            'photos.*' => 'required|image|mimes:jpeg,png,jpg,gif,webp|max:5120',
            'photo_type' => 'required|in:exterior,interior,engine,defect,document',
        ]);

        $type = $request->input('photo_type', 'exterior');
        $order = $car->photos()->max('sort_order') ?? 0;

        if ($request->hasFile('photos')) {
            foreach ($request->file('photos') as $photo) {
                $order++;
                $path = $photo->store(
                    "cars/{$car->id}/photos",
                    'public'
                );

                CarPhoto::create([
                    'car_id' => $car->id,
                    'organization_id' => $car->organization_id,
                    'url' => $path,
                    'photo_type' => $type,
                    'sort_order' => $order,
                ]);
            }
        }

        return back()->with('success', 'Photos uploaded successfully');
    }

    public function destroy(Car $car, CarPhoto $photo): RedirectResponse
    {
        if ($photo->car_id !== $car->id) {
            abort(403);
        }

        Storage::disk('public')->delete($photo->url);
        $photo->delete();

        return back()->with('success', 'Photo deleted');
    }

    public function reorder(Request $request, Car $car)
    {
        $request->validate([
            'order' => 'required|array',
            'order.*' => 'integer|exists:car_photos,id',
        ]);

        foreach ($request->order as $index => $photoId) {
            CarPhoto::where('id', $photoId)
                ->where('car_id', $car->id)
                ->update(['sort_order' => $index + 1]);
        }

        return response()->json(['success' => true]);
    }
}
