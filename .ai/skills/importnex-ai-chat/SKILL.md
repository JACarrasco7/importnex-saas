---
name: importnex-ai-chat
description: Widget flotante de AI assistant para usuarios finales. Aplica cuando se habla de AI chat, AiChat, ai.chat, chat assistant, streaming response, SSE, Server-Sent Events, prompt del chat, historial de chat, guardrails de chat, métricas de chat, OpenAI/Anthropic API en backend, token usage, prompt caching, rate limit por usuario, conversations table, messages table, aiChatLauncher.
---

# AI Chat Widget — ImportnexCore

## Componentes

```
resources/js/aiChatLauncher.js        # entry point, monta el widget
resources/js/Components/AIChat*.vue   # widget UI (no commiteado aún)
app/Http/Controllers/AiChatController.php  # backend HTTP entry
app/Services/AI/AiChatService.php     # orquesta provider (Mistral/Anthropic/OpenAI)
database/migrations/*ai_chat*         # conversations, messages tables
routes/web.php                          # POST /ai/chat, GET /ai/conversations
```

## Stack IA

- **Default provider:** Mistral (mistral-large-latest) por coste/latencia.
- **Premium provider:** Anthropic Claude Sonnet para usuarios con plan pro/enterprise.
- **Routing:** `AiChatService` decide según `auth()->user()->organization->plan`.

## Reglas críticas

1. **Rate limit por organización**: 60 mensajes/hora en plan basic, 200 en pro, ilimitado en owner.
2. **Streaming obligatorio** (SSE) para percepción de velocidad (<500ms first byte).
3. **Prompt cache** habilitado (Mistral y Anthropic soportan).
4. **Historial persistente** con trim a últimos 20 mensajes por conversación.
5. **System prompt cacheado en BD** con versionado (no inline).
6. **Guardrails**: max tokens output, stop sequences, NO tool calls en MVP.
7. **Métricas**: tokens_in, tokens_out, latency_ms, error_rate por org.
8. **NO entrenar con datos de clientes** (política privacidad).

## Patrón: SSE streaming

```php
public function stream(Request $request, AiChatService $chat): Response
{
    return response()->stream(function () use ($request, $chat) {
        $org = auth()->user()->organization;
        abort_unless($this->rateLimitOk($org), 429, 'Rate limit exceeded');

        foreach ($chat->stream($request->input('messages')) as $chunk) {
            echo "data: " . json_encode(['delta' => $chunk]) . "\n\n";
            ob_flush(); flush();
        }
    }, 200, [
        'Content-Type' => 'text/event-stream',
        'Cache-Control' => 'no-cache',
        'X-Accel-Buffering' => 'no',
    ]);
}
```

## Patrón: Cliente Vue 3

```vue
<script setup>
import { ref } from 'vue';
const messages = ref([]);
const input = ref('');
const streaming = ref(false);

async function send() {
    messages.value.push({ role: 'user', content: input.value });
    const userMsg = input.value;
    input.value = '';
    streaming.value = true;

    const response = await fetch('/ai/chat', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ messages: messages.value }),
    });

    const reader = response.body.getReader();
    const decoder = new TextDecoder();
    let assistant = { role: 'assistant', content: '' };
    messages.value.push(assistant);

    while (true) {
        const { done, value } = await reader.read();
        if (done) break;
        const chunk = decoder.decode(value);
        const lines = chunk.split('\n').filter(l => l.startsWith('data: '));
        for (const line of lines) {
            const data = JSON.parse(line.slice(6));
            assistant.content += data.delta;
        }
    }
    streaming.value = false;
}
</script>
```

## System prompt por dominio

```
Eres el asistente de ImportnexCore, un SaaS de gestión de vehículos importados.
Respondes en {locale}. Ayudas con:
- Búsqueda de coches en catálogo
- Subida de documentos (DNI, fichas técnicas)
- Estado de pagos y suscripciones
- Preguntas sobre el plan actual

NO respondes preguntas fuera del dominio. Si no sabes, di "No tengo información sobre eso, contacta con soporte@jjimportmotors.com".
```

## Anti-patrones (NUNCA)

- ❌ Esperar respuesta completa antes de renderizar (UX lenta).
- ❌ Almacenar API keys en frontend (siempre backend proxy).
- ❌ Loggear mensajes completos (privacidad + coste).
- ❌ Sin rate limit (un usuario puede fundir el presupuesto).
- ❌ Re-llamar al provider si el cliente cancela (waste).
- ❌ Sin timeout (hang indefinite si provider no responde).

## Variables de entorno

```
AI_CHAT_PROVIDER_DEFAULT=mistral
AI_CHAT_PROVIDER_PREMIUM=anthropic
MISTRAL_API_KEY=...
ANTHROPIC_API_KEY=...
AI_CHAT_RATE_LIMIT_PER_HOUR=60
AI_CHAT_MAX_TOKENS_OUTPUT=1024
```

## Comandos útiles

```bash
# Probar chat sin auth
curl -X POST http://localhost:8000/ai/chat \
    -H "Content-Type: application/json" \
    -d '{"messages":[{"role":"user","content":"hola"}]}' \
    -H "Accept: text/event-stream"
```

## Métricas a monitorizar

- Latencia p50, p95, p99 (objetivo: p95 < 2s first byte).
- Tokens promedio por mensaje (control de coste).
- Errores por provider (4xx, 5xx, rate limit).
- Abandono por timeout (debe ser < 1%).

## Estado actual (2026-08-06)

- ✅ Backend funcional (`AiChatController`).
- ✅ Widget básico (`aiChatLauncher.js`).
- ⚠️ SSE streaming NO implementado (espera completa actual).
- ⚠️ Métricas NO recogidas.
- ⚠️ Provider routing NO implementado.