<?php

namespace App\Services;

use App\Models\Car;
use App\Models\CarMarketingContent;
use App\Services\Ai\AiService;

/**
 * Generates marketing content (titles, descriptions, hashtags, photo tips)
 * for a car on a specific channel using the org's configured AI provider.
 */
class CarMarketingService
{
    public function __construct(private readonly AiService $ai) {}

    /**
     * Generate marketing content for a car on a given channel.
     *
     * @param  Car  $car
     * @param  string  $channel  One of CarMarketingContent::CHANNELS
     * @return array{success: bool, data?: array, error?: string}
     */
    public function generate(Car $car, string $channel): array
    {
        $org = $car->organization;
        if (!$org) {
            return ['success' => false, 'error' => 'Car has no organization'];
        }

        $prompt = $this->buildPrompt($channel);
        $carData = $this->buildCarData($car);

        $result = $this->ai->chatJson(
            $org,
            $prompt,
            json_encode($carData, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE),
        );

        if (!$result['success']) {
            return ['success' => false, 'error' => $result['error'] ?? 'unknown'];
        }

        $parsed = $result['data'] ?? [];

        $content = [
            'title' => $parsed['title'] ?? '',
            'description' => $parsed['description'] ?? '',
            'hashtags' => $parsed['hashtags'] ?? [],
            'photo_tips' => $parsed['photo_tips'] ?? [],
        ];

        return [
            'success' => true,
            'data' => $content,
            'provider' => $result['provider'] ?? null,
            'model' => $result['model'] ?? null,
        ];
    }

    /**
     * Build the system prompt for a specific channel.
     */
    protected function buildPrompt(string $channel): string
    {
        $base = "Eres un experto en marketing de coches de importación alemana a España. Tu tarea es generar contenido publicitario optimizado para el canal especificado.\n\n";

        $channelPrompts = [
            'milanuncios' => $base . "Canal: Milanuncios (portal de clasificados)\n\nGenera:\n1. Un título SEO-optimizado (máximo 60 caracteres) que incluya marca, modelo, año y un beneficio clave\n2. Una descripción detallada (máximo 1500 caracteres) que destaque: equipamiento, estado, kilómetros, precio, garantía, y ventajas de importar desde Alemania\n3. Un array de hashtags relevantes (máximo 5)\n4. Un array de tips para fotos (máximo 5) con consejos específicos para Milanuncios\n\nResponde SOLO con JSON válido.",

            'coches_net' => $base . "Canal: Coches.net (portal de coches)\n\nGenera:\n1. Un título profesional (máximo 60 caracteres) con marca, modelo, año y precio\n2. Una descripción técnica (máximo 1500 caracteres) enfocada en especificaciones, equipamiento y condición\n3. Un array de hashtags (máximo 5)\n4. Un array de tips para fotos (máximo 5) optimizados para Coches.net\n\nResponde SOLO con JSON válido.",

            'wallapop' => $base . "Canal: Wallapop (marketplace móvil)\n\nGenera:\n1. Un título casual y atractivo (máximo 60 caracteres)\n2. Una descripción conversacional (máximo 1000 caracteres) como si fuera un particular vendiendo su coche\n3. Un array de hashtags (máximo 5)\n4. Un array de tips para fotos (máximo 5) para Wallapop\n\nResponde SOLO con JSON válido.",

            'tiktok' => $base . "Canal: TikTok (red social de vídeo)\n\nGenera:\n1. Un título hook (máximo 60 caracteres) que haga clic en el espectador\n2. Una descripción corta y viral (máximo 500 caracteres) con storytelling emocional\n3. Un array de hashtags trending + nicho (máximo 10)\n4. Un array de tips para fotos/vídeo (máximo 5) para TikTok\n\nResponde SOLO con JSON válido.",

            'instagram' => $base . "Canal: Instagram (red social visual)\n\nGenera:\n1. Un título para el caption (máximo 60 caracteres)\n2. Una descripción larga y visual (máximo 2000 caracteres) con emojis y storytelling\n3. Un array de hashtags por nicho (máximo 20)\n4. Un array de tips para fotos (máximo 5) para Instagram\n\nResponde SOLO con JSON válido.",

            'facebook' => $base . "Canal: Facebook Marketplace\n\nGenera:\n1. Un título claro (máximo 60 caracteres)\n2. Una descripción informativa (máximo 1500 caracteres)\n3. Un array de hashtags (máximo 5)\n4. Un array de tips para fotos (máximo 5)\n\nResponde SOLO con JSON válido.",
        ];

        return $channelPrompts[$channel] ?? $channelPrompts['milanuncios'];
    }

    /**
     * Build the car data payload for the AI.
     */
    protected function buildCarData(Car $car): array
    {
        $photos = $car->photos->map(fn ($p) => $p->url)->toArray();

        return [
            'brand' => $car->brand,
            'model' => $car->model,
            'version' => $car->version,
            'year' => $car->year,
            'mileage' => $car->mileage,
            'fuel' => $car->fuel,
            'transmission' => $car->transmission,
            'cv' => $car->cv,
            'color' => $car->color,
            'price' => $car->purchase_price,
            'equipment' => $car->equipment,
            'description' => $car->description,
            'valuation' => $car->valuation,
            'recommendation' => $car->recommendation,
            'verdict' => $car->verdict,
            'market_avg' => $car->market_avg,
            'pros' => $car->pros,
            'cons' => $car->cons,
            'photo_count' => count($photos),
            'photo_urls' => $photos,
        ];
    }
}
