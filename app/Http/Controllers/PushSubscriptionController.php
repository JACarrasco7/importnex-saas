<?php

namespace App\Http\Controllers;

use App\Models\PushSubscription;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class PushSubscriptionController extends Controller
{
    /**
     * POST /push/subscribe
     * Registra una suscripción Web Push del usuario actual.
     *
     * Espera JSON con la forma del PushSubscription de la Web Push API:
     *   { endpoint: "https://...", keys: { p256dh: "...", auth: "..." } }
     */
    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'endpoint' => ['required', 'url', 'max:512'],
            'keys.p256dh' => ['required', 'string', 'max:256'],
            'keys.auth' => ['required', 'string', 'max:128'],
        ]);

        $sub = PushSubscription::updateOrCreate(
            [
                'user_id' => $request->user()->id,
                'endpoint' => $data['endpoint'],
            ],
            [
                'p256dh' => $data['keys']['p256dh'],
                'auth' => $data['keys']['auth'],
                'user_agent' => substr((string) $request->userAgent(), 0, 255),
                'last_seen_at' => now(),
            ],
        );

        return response()->json(['success' => true, 'id' => $sub->id]);
    }

    /**
     * DELETE /push/subscribe
     * Elimina la suscripción del endpoint dado (del usuario actual).
     */
    public function destroy(Request $request): JsonResponse
    {
        $data = $request->validate([
            'endpoint' => ['required', 'url'],
        ]);

        $deleted = PushSubscription::query()
            ->where('user_id', $request->user()->id)
            ->where('endpoint', $data['endpoint'])
            ->delete();

        return response()->json(['success' => true, 'deleted' => $deleted]);
    }

    /**
     * GET /push/vapid-public-key
     * Devuelve la clave pública VAPID para que el service worker la use.
     * Stub por ahora — se enchufa cuando se apruebe la dep.
     */
    public function vapidKey(): JsonResponse
    {
        $key = config('services.vapid.public_key');
        if (! $key) {
            return response()->json([
                'enabled' => false,
                'message' => 'VAPID not configured yet. Push notifications will be a no-op until minishlink/web-push is installed.',
            ], 200);
        }

        return response()->json(['enabled' => true, 'public_key' => $key]);
    }
}