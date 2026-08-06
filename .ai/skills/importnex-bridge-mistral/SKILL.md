---
name: importnex-bridge-mistral
description: Servicio de scraping/valoración con Mistral AI bridge. Aplica cuando se habla de Mistral, bridge, scraping de coches, valoración automática, valoración de vehículo, prompt cache, model choice, fallback model, JSON mode, structured output, mistral-large, mistral-small, pixtral, vision, URL a coche, extraer datos de URL, prompt engineering, token economy, rate limit Mistral.
---

# Bridge Mistral — ImportnexCore

## Arquitectura

```
URL coche (Wallapop, Milanuncios, Coches.net, Facebook Marketplace)
  ↓
ScrapingService (Spatie Browsershot) → HTML + screenshot
  ↓
MistralBridge (este servicio) → prompt estructurado
  ↓
Mistral API (mistral-large-latest) → JSON {brand, model, year, km, price, ...}
  ↓
CarsController → upsert en DB
```

## Stack

- `spatie/browsershot` v5.4 → captura HTML renderizado.
- HTTP client: Laravel `Http` facade con retry exponencial.
- Cache: `Cache::remember('mistral:urlhash', ttl=86400)` para no re-procesar.
- Config: `config/services.php` (mistral.api_key, mistral.model, mistral.fallback).

## Modelos soportados (2026-08)

| Modelo | Cuándo | Coste aprox |
|---|---|---|
| `mistral-large-latest` | Default. Valoración compleja, JSON mode | $$$ |
| `mistral-small-latest` | Bulk scraping, portales simples | $$ |
| `pixtral-12b-2409` | Screenshots con OCR + extracción visual | $$$ |

## Reglas críticas

1. **Cache SIEMPRE** (`Cache::remember`). Misma URL → mismo response, sin re-procesar.
2. **Retry exponencial**: 3 intentos, 1s/2s/4s, en errores 5xx y rate limit (429).
3. **JSON mode** (`response_format: {type: json_object}`) para output estructurado.
4. **Prompt cache**: si la URL tiene >500 chars, separar el prompt base + variable.
5. **Fallback**: si `mistral-large` falla 3 veces → degradar a `mistral-small` con flag `confidence: low`.
6. **Validar JSON** antes de persistir: si Mistral devuelve algo raro, NO guardar.
7. **Rate limit**: máximo 60 req/min a Mistral. Respetar `Retry-After` header.

## Patrón: Llamada con cache y retry

```php
public function valueFromUrl(string $url): array
{
    $cacheKey = 'mistral:' . md5($url);

    return Cache::remember($cacheKey, 86400, function () use ($url) {
        $html = Browsershot::url($url)->bodyHtml();
        $screenshot = Browsershot::url($url)->screenshot();

        return retry(3, function () use ($html, $screenshot, $url) {
            $response = Http::withToken(config('services.mistral.key'))
                ->timeout(30)
                ->post('https://api.mistral.ai/v1/chat/completions', [
                    'model' => config('services.mistral.model', 'mistral-large-latest'),
                    'response_format' => ['type' => 'json_object'],
                    'messages' => [
                        ['role' => 'system', 'content' => $this->systemPrompt()],
                        ['role' => 'user', 'content' => [
                            ['type' => 'text', 'text' => "URL: $url\nHTML: " . substr($html, 0, 50000)],
                            ['type' => 'image_url', 'image_url' => ['url' => $screenshot]],
                        ]],
                    ],
                ]);

            $response->throw();

            return json_decode($response->json('choices.0.message.content'), true, flags: JSON_THROW_ON_ERROR);
        }, 1000);
    });
}
```

## Prompt engineering

- **System prompt** cacheado en BD con hash, no inline.
- **Few-shot examples** (3 ejemplos de coches reales) en el system prompt.
- **Output schema** definido explícitamente con JSON Schema en el system.
- **Negative prompting**: "NO inventes datos; si falta, devuelve null".

## Variables de entorno

```
MISTRAL_API_KEY=...
MISTRAL_MODEL=mistral-large-latest
MISTRAL_FALLBACK=mistral-small-latest
MISTRAL_RATE_LIMIT=60
```

## Anti-patrones (NUNCA)

- ❌ Llamar Mistral en loop por cada línea de HTML.
- ❌ Sin cache → facturar 2× la misma URL.
- ❌ Hardcodear prompts en código (usar config o DB).
- ❌ Confiar en JSON sin validar (puede traer HTML si falla).
- ❌ Olvidar el `response_format` y parsear regex.
- ❌ Reintentar en 4xx (no tiene sentido, no va a cambiar).

## Archivos críticos

- `app/Services/Scraping/MistralBridge.php` (servicio principal)
- `app/Services/Scraping/CarScrapingService.php` (orquestador)
- `app/Http/Controllers/Cars/ScrapingController.php` (HTTP entry)
- `config/services.php` (mistral config)
- `tests/Unit/Services/Scraping/MistralBridgeTest.php` (unit tests)
- `tests/Feature/CarScrapingTest.php` (integration)
