<?php

namespace App\Http\Controllers;

use App\Services\Ai\AiProviderRegistry;
use App\Services\Ai\AiService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class AiChatController extends Controller
{
    public function __construct(
        private readonly AiService $ai,
        private readonly AiProviderRegistry $registry,
    ) {}

    /**
     * Generic AI playground page.
     */
    public function index(): Response
    {
        $org = auth()->user()->organization;

        return Inertia::render('Ai/Chat', [
            'providers' => $this->registry->options(),
            'current' => [
                'provider' => $org->ai_provider,
                'model' => $org->ai_model,
                'has_key' => $org->hasAiConfigured(),
            ],
        ]);
    }

    /**
     * POST /ai/chat — generic chat against the org's configured provider.
     */
    public function send(Request $request): JsonResponse
    {
        $data = $request->validate([
            'messages' => 'required|array|min:1',
            'messages.*.role' => 'required|in:user,assistant,system',
            'messages.*.content' => 'required|string',
            'system' => 'nullable|string',
            'max_tokens' => 'nullable|integer|min:50|max:8000',
            'temperature' => 'nullable|numeric|min:0|max:2',
        ]);

        $org = auth()->user()->organization;

        $result = $this->ai->chat($org, [
            'system' => $data['system'] ?? null,
            'messages' => array_values($data['messages']),
            'max_tokens' => $data['max_tokens'] ?? null,
            'temperature' => $data['temperature'] ?? null,
        ]);

        return response()->json($result, $result['success'] ? 200 : 422);
    }
}
