# Módulo `07-marketing/` — visión general

> Sistema multicanal de copy para JJ Import Motors. **Solo se activa tras un
> Flujo A con veredicto 🟢/🔵.** Genera un único bloque de copy por unidad,
> con la misma información en 6 canales: 3 portales + 3 redes sociales.

## Contenido del módulo

| Archivo | Qué hace |
|---|---|
| `copy_engine.md` | Motor principal: voz, iconos, hashtags, 5 piezas, matriz de canales, 11 reglas duras (A23-A30), proceso del Flujo M |
| `redes_sociales.md` | Spec por canal: Instagram feed/stories/Reel, Facebook página, FB Marketplace |
| `portales_anuncio.md` | Spec de portales: Coches.net, Milanuncios, Wallapop (texto base + 3 deltas) + aviso legal completo |
| `biblioteca_ganchos.md` | 8 ángulos de venta con proof point, aperturas prohibidas, cierres, fórmula de la pega honesta |
| `fuentes_y_evidencia.md` | Reglas de citación, fuentes permitidas (DE/ES), plantilla de cita, auditoría mensual |
| `evidencia_externa.md` | **(07-sep-2026)** De dónde sale cada regla importada de fuera: normas de Coches.net y Milanuncios, art. 20 TRLGDCU y VO expuesto, hashtags y sends per reach de Instagram, vídeo corto, Open Graph de WhatsApp |
| `ficha_cliente.md` | **(07-sep-2026)** La página que se manda al cliente por enlace: 14 secciones, qué sale y qué entra, orden, bloque fijo de "gestor sin garantía" (A31) y diagnóstico de la ficha en producción |
| `handoff_laravel.md` | **(07-sep-2026)** Entrega técnica al panel: JSON canónico en vez de parsear texto, mapa bloque → componente Blade con fallback, Open Graph para WhatsApp, animaciones en 3 capas y modo PDF, checklist de aceptación |
| `plantillas/ficha-cliente.txt` | Esqueleto de la ficha del cliente (+ `plantillas/ejemplo/` relleno como patrón de calidad y fixture) |

## Activación

El módulo se activa solo cuando el ZIP del Flujo A lleva un `informe.json`
con `veredicto` ∈ {🟢 OPORTUNIDAD, 🔵 SOLO ESTE COLOR}.

No se activa con:
- 🔴 NO INTERESA
- Sin veredicto (estado "Pendiente de revisar")

## Salida

| Canal | Salida |
|---|---|
| Coches.net | `anuncio-coches-net.txt` (1.200-1.800 chars) |
| Milanuncios | `anuncio-milanuncios.txt` (800-1.500 chars) |
| Wallapop | `anuncio-wallapop.txt` (600-900 chars) |
| Instagram feed | `redes-sociales.txt` (bloque `[IG_*]`) |
| Instagram stories | `redes-sociales.txt` (bloque `[IG_STORIES_*]`) |
| Reel / TikTok / Shorts | `redes-sociales.txt` (bloque `[VT_*]`) |
| Facebook página | `redes-sociales.txt` (bloque `[FB_*]`) |
| FB Marketplace | `redes-sociales.txt` (bloque `[FBMP_*]`) |
| Ficha del cliente (enlace `/c/<token>`) | `ficha-cliente.txt` (bloques `[FC_*]`) → `contenido/json/ficha-cliente.json` |

## Validación

Antes de generar el ZIP final del Flujo A:

```bash
python scripts/check_marketing.py contenido/redes-sociales.txt contenido/anuncio-*.txt
```

Si el validador falla 🔴, no se publica. 🟠 se corrige antes de publicar.
🟡 es criterio editorial, se anota pero no bloquea.

## Trazabilidad

Cada cifra del copy tiene que poder citar su fuente: o bien URL externa (DE,
ES, normativa) o bien `informe.json:{campo}`. Sin fuente = no se publica.
