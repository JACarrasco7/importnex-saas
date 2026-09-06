# Copywriting de marketing — guía de redacción (06-sep-2026)

> **Cargar cuando:** se van a redactar `redes.gancho`, `redes[tiktok|instagram|facebook].posts/stories`, `redes.hashtags` o `publicidad.portales.{titulo,descripcion}` del `informe.json` (Flujo A/B). Es la guía de **CALIDAD DE TEXTO**, complementaria al esquema técnico de `contrato.md` (qué bloques existen) — este archivo dice **cómo redactarlos bien**.

---

## ⚠️ Restricción técnica real (leer antes de usar emoji)

La base de datos de producción (Forge/MySQL) usa charset `utf8mb3`, que **NO admite emoji de 4 bytes** (🚀🔥💯👍💰🚗🤔😊🙂👏⭐ y casi todos los emoji "de color" modernos, rango Unicode >U+FFFF). El ingestor de Laravel los **elimina silenciosamente** al importar. Consecuencia práctica:

- **NO uses emoji de color** (🔥🚀💯⭐🚗👍💰😊) en ningún bloque de marketing — desaparecerán del texto final y pueden dejar espacios raros.
- Si necesitas una viñeta de lista, usa un guion `-` o el carácter `•` (bullet, se admite bien) en vez de ✅/✓/☑️.
- Para dar énfasis usa **mayúscula selectiva**, signos de exclamación (`¡Precio de derribo!`) o `**negrita**` (Markdown, donde el destino lo soporte) — nunca emoji.
- Esta restricción aplica a **todos** los bloques: `GANCHO`, `POST`, `STORY`, `TITULO`, `DESCRIPCION`, `FICHA_RAPIDA`, `QUE_INCLUYE`.

---

## 📏 Límites recomendados por canal (guía visual en Laravel, no bloqueantes)

| Canal | Título/Gancho | Cuerpo | Hashtags | Nota |
|---|---|---|---|---|
| **TikTok** | ≤150 car. | ≤2200 car. | 3-5 | Guion pensado para vídeo hablado/subtítulos, no solo texto leído |
| **Instagram** | ≤125 car. (se corta a "ver más") | ≤2200 car. | 15-20 | Las primeras 125 car. son las que MÁS se leen |
| **Facebook** | ≤100 car. | ≤2200 car. | 3-5 | Público más generalista; menos hashtags, más datos duros |
| **Milanuncios** | ≤60 car. | ≤4000 car. | — | Título = lo que más se lee en el listado |
| **Coches.net** | ≤60 car. | ≤4000 car. | — | Prioriza estructura técnica clara |
| **Wallapop** | ≤80 car. | ≤3000 car. | — | Primera línea de la descripción = lo que se ve en listado |

---

## 🎣 Fórmula del GANCHO (común a las 3 redes sociales)

El `[GANCHO]` es el titular que se reutiliza como `title` en TikTok/Instagram/Facebook. Debe funcionar solo, sin contexto.

**Estructura recomendada:** `[Dato sorprendente o precio] + [Marca Modelo Año] + [motivo de urgencia/escasez opcional]`

✅ Buenos ejemplos:
- `Mercedes-AMG A35 4MATIC 2019 — 306 CV a precio de compacto`
- `Solo 45.000 km: el BMW 320d que no vas a encontrar dos veces`
- `Golf GTI Clubsport 2021, 190 CV, importado y con ITV puesta`

❌ Evitar:
- Empezar con la marca sin gancho: `BMW 320d 2020. Buen coche.` (plano, sin hook)
- Genérico sin datos: `¡Oportunidad única, no te la pierdas!` (sin sustancia verificable)
- Mayúsculas sostenidas: `OFERTA BRUTAL BMW 320D` (parece spam)
- Emoji al inicio como gancho visual: `🔥 BMW 320d...` (se elimina, deja espacio raro)

---

## 📱 Tono y estructura por red social

### TikTok (`redes.tiktok`) — viral 15-30s
- **Post 1-3:** cada uno es el guion de un vídeo corto distinto (no repetir el mismo ángulo). Ejemplos de ángulos: "POV: encuentras este coche a mitad de precio", "Antes/después del proceso de importación", "3 razones por las que este motor es una joya".
- **Hook en la primera frase** — se pierde la audiencia en el segundo 1-2 si no engancha.
- **Story 1-3:** frases sueltas de 3-8 palabras, tipo titular de story (se leen en 1-2 segundos): `¿Viste el interior?`, `78.000 km, 1 dueño`, `Link en bio`.
- **Hashtags (3-5):** mezcla nicho (`#bmw320d`) + trending genérico del momento (`#cochesimportados`, `#viral`).

### Instagram (`redes.instagram`) — visual, storytelling
- **Post 1-3:** más narrativos que TikTok, con más detalle técnico/emocional. Formato recomendado: primera línea = gancho (se ve sin "ver más"), luego 2-4 líneas cortas con datos, cierre con CTA (`Escríbenos para más info`).
- **Story 1-3:** preguntas o datos sueltos que inviten a interactuar (`¿BMW o Mercedes?`, `78.000 km · 1 dueño`, `Desliza para ver el interior`).
- **Hashtags (15-20):** mezcla de nicho específico (`#bmw320d #e90`), comunidad (`#cochesimportados #jjimportmotors`) y localización (`#huelva #andalucia`). Evitar hashtags genéricos saturados (`#coche #car`) que no aportan alcance real.

### Facebook (`redes.facebook`) — informativo masivo
- **Post 1-3:** el público de FB es más generalista y mayor edad media — prioriza datos concretos y visibles: precio, km, año, garantía, forma de contacto. Menos "storytelling", más "ficha resumida y clara".
- **Story 1-3:** igual que Instagram pero más directo, menos "estético".
- **Hashtags (3-5):** mínimos, casi decorativos. En FB los hashtags no aportan alcance como en IG/TikTok.

---

## 🌐 Portales web (`publicidad.portales`) — Milanuncios/Coches.net/Wallapop/FB Marketplace

- **TITULO:** el dato más buscado primero (marca+modelo+año), luego el diferenciador (versión, km, "único dueño"). Sin emoji, sin mayúsculas sostenidas.
- **DESCRIPCION:** estructura recomendada (en este orden):
  1. **Gancho de 1 línea** (igual criterio que el GANCHO de redes).
  2. **Estado general** (kilometraje, dueños, historial, ITV).
  3. **Equipamiento destacado** (3-5 puntos, los que de verdad diferencian el coche).
  4. **Mecánica** (motor, cambio, tracción, consumo si es dato de venta).
  5. **Precio y forma de contacto** (cierre claro, sin ambigüedad).
- **FICHA_RAPIDA:** datos objetivos separados por `|` (año, km, combustible, cambio, potencia) — NO frases, solo datos.
- **QUE_INCLUYE:** lista de lo que cubre el servicio (transporte, ITV, matriculación, garantía) — argumento de venta del servicio de importación, no del coche.

---

## 🚫 Anti-patrones de copy (además de A1-A21 en `anti_patrones.md`)

- **A-COPY1 — Emoji de color en cualquier bloque de marketing.** Se eliminan al importar (charset utf8mb3). Usa texto/mayúscula/exclamación para énfasis.
- **A-COPY2 — Gancho vacío o genérico.** `[GANCHO]` vacío hace que Laravel NO cree ninguna fila de redes sociales (contrato.md). Siempre debe tener un dato concreto.
- **A-COPY3 — Repetir el mismo texto en los 3 posts de una red.** Cada post/story debe tener un ángulo distinto (dato, pregunta, comparación) — repetir el mismo texto en los 3 slots reduce el valor del contenido a 1/3.
- **A-COPY4 — Datos inventados en el copy.** Todo dato (km, CV, año, precio) debe coincidir EXACTAMENTE con lo verificado en el informe técnico — nunca redondear "para que suene mejor".
- **A-COPY5 — CTA ausente en portales.** `DESCRIPCION` de portales siempre debe cerrar con una llamada a la acción clara (contacto, precio, "consúltanos").

---

## 🔗 Referencias
- Esquema técnico de bloques: `../03-informes/contrato.md` §Marketing importado
- Reglas duras generales (A1-A21): `anti_patrones.md`
- Regla de oro enlaces (toda afirmación con URL de fuente): `../SKILL.md` §REGLA DE ORO
