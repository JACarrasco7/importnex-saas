<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Car;
use App\Models\CarMarketingContent;
use App\Services\CarMarketingService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\View;
use Inertia\Inertia;
use Inertia\Response;

class CarMarketingController extends Controller
{
    public function show(Car $car): Response
    {
        $contents = CarMarketingContent::where('car_id', $car->id)->get();

        return Inertia::render('Cars/Marketing', [
            'car' => $car,
            'contents' => $contents,
        ]);
    }

    public function generate(Request $request, Car $car, CarMarketingService $service): RedirectResponse
    {
        $channel = $request->validate([
            'channel' => 'required|string|in:' . implode(',', CarMarketingContent::CHANNELS),
        ])['channel'];

        $result = $service->generate($car, $channel);

        if (!$result['success']) {
            return back()->with('error', 'Error generando contenido: ' . ($result['error'] ?? 'unknown'));
        }

        // Store as draft (or update existing)
        CarMarketingContent::updateOrCreate(
            ['car_id' => $car->id, 'channel' => $channel],
            [
                'title' => $result['data']['title'] ?? null,
                'description' => $result['data']['description'] ?? null,
                'hashtags' => $result['data']['hashtags'] ?? [],
                'photo_tips' => $result['data']['photo_tips'] ?? [],
                'status' => 'draft',
                'generated_at' => now(),
            ]
        );

        return back()->with('success', 'Contenido generado para ' . $channel . '.');
    }

    public function save(Request $request, Car $car): RedirectResponse
    {
        $data = $request->validate([
            'channel' => 'required|string|in:' . implode(',', CarMarketingContent::CHANNELS),
            'title' => 'nullable|string|max:255',
            'description' => 'nullable|string',
            'hashtags' => 'nullable|array',
            'photo_tips' => 'nullable|array',
        ]);

        CarMarketingContent::updateOrCreate(
            ['car_id' => $car->id, 'channel' => $data['channel']],
            [
                'title' => $data['title'],
                'description' => $data['description'],
                'hashtags' => $data['hashtags'],
                'photo_tips' => $data['photo_tips'],
                'status' => 'draft',
            ]
        );

        return back()->with('success', 'Borrador guardado.');
    }

    public function publish(Request $request, Car $car): RedirectResponse
    {
        $data = $request->validate([
            'channel' => 'required|string|in:' . implode(',', CarMarketingContent::CHANNELS),
        ]);

        $content = CarMarketingContent::where('car_id', $car->id)
            ->where('channel', $data['channel'])
            ->first();

        if (!$content) {
            return back()->with('error', 'No hay contenido para publicar.');
        }

        $content->update([
            'status' => 'published',
            'published_at' => now(),
        ]);

        return back()->with('success', 'Anuncio marcado como publicado.');
    }

    /**
     * Generate a marketing briefing PDF for the car with the same design
     * as the JJ Import Motors folleto.
     */
    public function briefing(Car $car)
    {
        $logoPath = public_path('images/jj-import/logo-horizontal-blanco.png');
        $logoBase64 = file_exists($logoPath)
            ? 'data:image/png;base64,' . base64_encode(file_get_contents($logoPath))
            : null;

        $qrUrl = route('cars.show', $car->id);
        $qrSvg = \SimpleSoftwareIO\QrCode\Facades\QrCode::format('svg')
            ->size(280)
            ->margin(1)
            ->errorCorrection('H')
            ->backgroundColor(255, 255, 255)
            ->color(6, 16, 31)
            ->generate($qrUrl);

        $contents = CarMarketingContent::where('car_id', $car->id)->get();

        // Resolve main car photo (local -> base64 so it renders in the PDF)
        $carPhotoBase64 = null;
        $car->load('photos');
        $photoUrl = $car->photos->first()?->url ?? ($car->fotos_json[0] ?? null);
        if ($photoUrl) {
            if (preg_match('#^https?://#', $photoUrl)) {
                // Remote URL: keep as-is (Browsershot can fetch it)
                $carPhotoBase64 = $photoUrl;
            } else {
                // Local storage path
                $abs = str_starts_with($photoUrl, '/storage/')
                    ? public_path($photoUrl)
                    : storage_path('app/public/' . ltrim($photoUrl, '/'));
                if (file_exists($abs)) {
                    $mime = mime_content_type($abs) ?: 'image/jpeg';
                    $carPhotoBase64 = 'data:' . $mime . ';base64,' . base64_encode(file_get_contents($abs));
                }
            }
        }

        $viewData = [
            'car' => $car,
            'contents' => $contents,
            'logo_base64' => $logoBase64,
            'car_photo_base64' => $carPhotoBase64,
            'qr_svg' => $qrSvg,
            'qr_url' => $qrUrl,
            'precio_honorarios' => '1.500 €',
            'telefono_1' => '675 70 14 39',
            'telefono_2' => '691 48 59 27',
            'email' => 'jjimportmotors@gmail.com',
        ];

        // Prefer generating a real PDF (requires Chrome/Chromium via Browsershot)
        if (class_exists(\Spatie\Browsershot\Browsershot::class)) {
            try {
                $chromePath = \App\Support\ChromePath::resolve();
                if ($chromePath) {
                    $html = View::make('jj-import.briefing', $viewData)->render();

                    $pdfPath = storage_path('app/public/briefing-' . $car->id . '.pdf');
                    $browser = \Spatie\Browsershot\Browsershot::html($html)
                        ->setNodeBinary(\App\Support\ChromePath::nodeBinary())
                        ->setChromePath($chromePath)
                        ->format('A4')
                        ->landscape(false)
                        ->margins(0, 0, 0, 0)
                        ->showBackground()
                        ->waitUntilNetworkIdle()
                        ->deviceScaleFactor(2)
                        ->scale(1)
                        ->savePdf($pdfPath);

                    return response()->download($pdfPath, 'Briefing_' . $car->brand . '_' . $car->model . '.pdf');
                }
            } catch (\Throwable $e) {
                \Illuminate\Support\Facades\Log::warning('Briefing PDF fallback (no Chrome): ' . $e->getMessage());
                // Fall through to HTML view if Chrome is not available
            }
        }

        return View::make('jj-import.briefing', $viewData);
    }
}
