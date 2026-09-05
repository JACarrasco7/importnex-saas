<?php

namespace App\Http\Controllers;

use App\Models\Car;
use App\Models\CarMarketingContent;
use App\Services\CarMarketingService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class CarMarketingController extends Controller
{
    public function show(Car $car): Response
    {
        $car->load('marketingContents');

        return Inertia::render('Cars/Marketing', [
            'car' => $car,
            'contents' => $car->marketingContents,
        ]);
    }

    public function generate(Request $request, Car $car, CarMarketingService $service): RedirectResponse
    {
        $channel = $request->validate([
            'channel' => 'required|string|in:'.implode(',', CarMarketingContent::CHANNELS),
        ])['channel'];

        $result = $service->generate($car, $channel);

        if (! $result['success']) {
            return back()->with('error', 'Error generando contenido: '.($result['error'] ?? 'unknown'));
        }

        // v2: fijar kind/slot según tipo de canal. El unique es
        // (car_id, channel, kind, slot) — sin esto el updateOrCreate podría
        // actualizar una fila indeterminada (hay hasta 6 por canal) o crear
        // una fila espuria con los defaults.
        //  - Redes sociales (tiktok/instagram/facebook) → regenera el POST 1.
        //  - Portales web (milanuncios/coches_net/wallapop) → la ficha (ad, 1).
        $isSocial = in_array($channel, CarMarketingContent::SOCIAL_CHANNELS, true);
        $kind = $isSocial ? CarMarketingContent::KIND_POST : CarMarketingContent::KIND_AD;

        // Store as draft (or update existing). Origen IA: si la fila venía del
        // ZIP, el contenido nuevo generado la sustituye y pasa a ser 'ai'.
        // published_at se limpia explícitamente porque tras regenerar el
        // contenido es nuevo y la marca de "publicado" anterior ya no aplica.
        CarMarketingContent::updateOrCreate(
            ['car_id' => $car->id, 'channel' => $channel, 'kind' => $kind, 'slot' => 1],
            [
                'title' => $result['data']['title'] ?? null,
                'description' => $result['data']['description'] ?? null,
                'hashtags' => $result['data']['hashtags'] ?? [],
                'photo_tips' => $result['data']['photo_tips'] ?? [],
                'status' => CarMarketingContent::STATUS_DRAFT,
                'generated_at' => now(),
                'published_at' => null,
                'source' => CarMarketingContent::SOURCE_AI,
            ]
        );

        return back()->with('success', 'Contenido generado para '.$channel.'.');
    }

    public function save(Request $request, Car $car): RedirectResponse
    {
        $data = $request->validate([
            'channel' => 'required|string|in:'.implode(',', CarMarketingContent::CHANNELS),
            'kind' => 'nullable|string|in:'.implode(',', CarMarketingContent::KINDS),
            'slot' => 'nullable|integer|min:1|max:3',
            'title' => 'nullable|string|max:255',
            'description' => 'nullable|string',
            'hashtags' => 'nullable|array',
            'photo_tips' => 'nullable|array',
            'subir_pasos' => 'nullable|string',
        ]);

        // kind/slot: para portales web siempre (ad, 1); para redes sociales,
        // se respeta lo que envía el frontend (post/story + 1..3).
        $isSocial = in_array($data['channel'], CarMarketingContent::SOCIAL_CHANNELS, true);
        $kind = $isSocial ? ($data['kind'] ?? CarMarketingContent::KIND_POST) : CarMarketingContent::KIND_AD;
        $slot = $isSocial ? ($data['slot'] ?? 1) : 1;

        // IMPORTANTE: NO incluir 'source' en el array de update. updateOrCreate
        // solo sobreescribe los campos presentes; al omitirlo, conservamos el
        // origen original (zip/ai/manual). Esto cumple la regla: editar un
        // borrador del ZIP NO cambia su origen.
        CarMarketingContent::updateOrCreate(
            [
                'car_id' => $car->id,
                'channel' => $data['channel'],
                'kind' => $kind,
                'slot' => $slot,
            ],
            [
                'title' => $data['title'],
                'description' => $data['description'],
                'hashtags' => $data['hashtags'],
                'photo_tips' => $data['photo_tips'],
                'subir_pasos' => $data['subir_pasos'] ?? null,
                'status' => CarMarketingContent::STATUS_DRAFT,
            ]
        );

        return back()->with('success', 'Borrador guardado.');
    }

    public function publish(Request $request, Car $car): RedirectResponse
    {
        $data = $request->validate([
            'channel' => 'required|string|in:'.implode(',', CarMarketingContent::CHANNELS),
            // Opcionales (v2): si llegan, se publica SOLO esa pieza (post/story N
            // o la ficha del portal). Si no llegan, se publican TODAS las piezas
            // del canal — el botón del panel es "canal publicado".
            'kind' => 'nullable|string|in:'.implode(',', CarMarketingContent::KINDS),
            'slot' => 'nullable|integer|min:1|max:3',
        ]);

        $query = CarMarketingContent::where('car_id', $car->id)
            ->where('channel', $data['channel']);

        if (! empty($data['kind'])) {
            $query->where('kind', $data['kind']);
        }
        if (! empty($data['slot'])) {
            $query->where('slot', $data['slot']);
        }

        $contents = $query->get();

        if ($contents->isEmpty()) {
            return back()->with('error', 'No hay contenido para publicar.');
        }

        foreach ($contents as $content) {
            $content->update([
                'status' => CarMarketingContent::STATUS_PUBLISHED,
                'published_at' => now(),
            ]);
        }

        return back()->with('success', 'Anuncio marcado como publicado.');
    }
}
