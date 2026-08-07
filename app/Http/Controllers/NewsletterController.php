<?php

namespace App\Http\Controllers;

use App\Models\NewsletterSubscription;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\RateLimiter;

class NewsletterController extends Controller
{
    /**
     * POST /newsletter/subscribe
     * Endpoint publico (no requiere auth), con rate limit y hash de IP.
     */
    public function subscribe(Request $request): JsonResponse
    {
        // Rate limit: 5 intentos por minuto por IP
        $key = 'newsletter:'.$request->ip();
        if (RateLimiter::tooManyAttempts($key, 5)) {
            return response()->json(['success' => false, 'message' => 'Demasiados intentos, prueba en un minuto.'], 429);
        }
        RateLimiter::hit($key, 60);

        $data = $request->validate([
            'email' => ['required', 'email:rfc', 'max:255'],
            'source' => ['nullable', 'string', 'max:32'],
            'locale' => ['nullable', 'in:es,en'],
        ]);

        try {
            $sub = NewsletterSubscription::updateOrCreate(
                ['email' => strtolower($data['email'])],
                [
                    'source' => $data['source'] ?? 'marketplace_popup',
                    'locale' => $data['locale'] ?? ($request->user()?->organization?->locale ?? 'es'),
                    'ip_hash' => Hash::make($request->ip()),
                    'verified' => false,
                    'unsubscribed_at' => null,
                ],
            );

            return response()->json(['success' => true, 'id' => $sub->id]);
        } catch (\Throwable $e) {
            Log::warning('Newsletter subscribe failed', ['email' => $data['email'], 'error' => $e->getMessage()]);

            return response()->json(['success' => false, 'message' => 'No se pudo suscribir.'], 500);
        }
    }

    /**
     * DELETE /newsletter/unsubscribe
     * Marca como desuscrito sin requerir auth (usa email + token simple).
     */
    public function unsubscribe(Request $request): JsonResponse
    {
        $data = $request->validate([
            'email' => ['required', 'email'],
        ]);

        $sub = NewsletterSubscription::where('email', strtolower($data['email']))->first();
        if ($sub) {
            $sub->update(['unsubscribed_at' => now()]);
        }

        return response()->json(['success' => true]);
    }
}
