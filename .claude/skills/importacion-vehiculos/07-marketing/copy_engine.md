# Motor de copy — `07-marketing/copy_engine.md`

> **Qué es:** el motor que la skill `importacion-vehiculos` usa cuando activa el
> **Flujo M** (ver `SKILL.md` § flujos). Es un sistema determinista, no un
> generador libre: dado un `informe.json`, produce siempre el mismo conjunto
> de piezas con la misma calidad. La pieza puede variar en **ángulo** (uno de
> los 8 de la biblioteca), pero el resto del proceso es cerrado.

---

## 1 · Voz de marca

Definida en positivo (lo que SÍ suena) y negativo (lo que NO suena). Sacada del
plan multicanal §3 y de la skill `brand-review` del plugin.

### 1.1 · Cuatro atributos

| Atributo | Suena así | NO suena así |
|---|---|---|
| **Directo sin ser frío** | "Te paso la ficha completa por DM." | "¡Escríbenos HOY MISMO, te esperamos!!" |
| **Técnico pero legible** | "280 CV, manual de 6." | "Motor potentísimo con una mecánica impecable." |
| **Honesto con la pega** | "Neumáticos al 50 %: se sustituyen antes de la entrega." | "Coche en perfecto estado, ningún detalle." |
| **Gestor no vendedor** | "Gestionamos la compra de este coche por ti." | "Llévate este coche antes de que se acabe." |

### 1.2 · Léxico cerrado

Solo se admiten estos términos:

- `coche`, `unidad`, `vehículo` (no «joya», «maquina», «bomba», «pepinazo»)
- `motor`, `cambio`, `potencia`, `kilómetros`, `etiqueta ambiental`, `matriculación`
- `gestionar`, `búsqueda`, `verificación`, `tramitación`, `gestión integral`
- `importación`, `localizado en [país]`, `historial verificado`
- `precio total cliente` (NO «precio final», «llave en mano», «IVA incluido»)

Prohibidos por anti-patrones A23-A30 + regla de oro `business-model.md`:

- `margen`, `honorarios JJ`, `estrategia de venta`, `vendedor` (datos internos)
- `¡` y `?` en portales
- `stock`, `EN STOCK`, `reserva tu prueba`, `Llave en mano`, `Garantía 12 meses`
- `Chollo`, `Oportunidad única`, `¡Brutal!`, `¡No te lo pierdas!`
- Hashtags en cuerpo (solo al final, solo en redes)
- Emojis decorativos 🔥💥🚀😍 (excepción: 🔥 en gancho showstopper justificado)

---

## 2 · Léxico de iconos (17, cerrados)

Cada icono **informa**, nunca adorna. Reglas:

- **Solo al principio de línea.**
- **Uno por línea.**
- **Cero en portales y FB Marketplace.**
- Si quitas el icono y la frase se entiende igual, **sobra**.

| Icono | Significa | Cuándo usarlo |
|---|---|---|
| 🗓️ | año / fecha | "🗓️ 2015" |
| 🛣️ | kilómetros | "🛣️ 62.400 km" |
| ⚙️ | cambio | "⚙️ Manual de 6" |
| ⛽ | combustible | "⛽ Diésel" |
| 🐎 | potencia | "🐎 280 CV" |
| 🏷️ | etiqueta DGT | "🏷️ Etiqueta C" |
| 👤 | propietarios | "👤 1 propietario" |
| 🔧 | mantenimiento | "🔧 Libro sellado" |
| 📄 | papeles / COC | "📄 COC verificado" |
| 🛡️ | garantía | **Solo si se dice cuál, quién y cuánto** (A28) |
| 📍 | origen | "📍 Alemania" |
| 📦 | qué incluye | "📦 Trámites incluidos" |
| 💶 | precio | "💶 Precio cerrado" |
| ✅ | verificado | "✅ Historial verificado" |
| ⚠️ | a tener en cuenta | "⚠️ Neumáticos al 50 %" (la pega honesta) |
| 🇩🇪 | país DE | Origen Alemania |
| 🇪🇸 | país ES | Origen España |

Prohibidos: 🔥💥🚀😍 (decorativos), ✅ fuera de "verificado", ⚠️ sin
acompañar una pega real.

---

## 3 · Cupo y receta de hashtags

| Canal | Máximo | Receta |
|---|---|---|
| Instagram feed | **5** | modelo · nicho · servicio · local · marca |
| Instagram stories | 0-1 | solo de marca |
| Reel / TikTok / Shorts | ≤4 | en el caption, mezclados |
| Facebook página | ≤2 | al final del texto |
| **FB Marketplace y los 3 portales** | **0** | nunca |

Vetados los genéricos en inglés (`#cars #love #instagood`) y cualquier
hashtag dentro del cuerpo del texto.

---

## 4 · Arquitectura del mensaje (5 piezas por unidad)

Toda pieza larga lleva estas 5 piezas en este orden:

1. **Gancho** — la frase que detiene el scroll. 1 línea. Sin icono.
2. **Ficha** — bloque de 5-7 líneas con iconos. Datos verificados del
   `informe.json`, **trazables** a su campo.
3. **Pega honesta** — `⚠️` + punto flojo real + solución. Regla dura A28.
4. **Argumento de compra** — 1-3 frases. Sin datos internos.
5. **CTA** — un único siguiente paso, claro.

Si una unidad no tiene pega real, se sustituye por la línea "se ha revisado
qué para poder afirmarlo" (ej: `✅ ITV pasada, 0 defectos en inspección de 150
puntos`).

---

## 5 · Matriz de canales

| Canal | Gancho | Ficha | Historia | Pega | Iconos | Hashtags | CTA |
|---|---|---|---|---|---|---|---|
| IG feed | fuerte | con iconos | corta | sí | ≤7 | 5 | DM |
| IG stories | mínimo | 2 datos | no | no | 1/pantalla | 0-1 | sticker |
| Reel / TikTok / Shorts | en 1 s | 3 datos | no | no | ≤3 | ≤4 | comentario |
| Facebook página | suave | con iconos | larga | sí | ≤7 | ≤2 | WhatsApp |
| FB Marketplace | no | texto plano | no | sí | 0 | 0 | mensaje |
| Coches.net / Milanuncios / Wallapop | no | ficha estructurada | no | **obligatoria** | 0 | 0 | contacto del portal |

**Importante:** los 3 portales comparten **texto base** y solo cambian tres
cosas: título, longitud y cierre. Wallapop es un **recorte** del base
(600-900 caracteres), nunca una reescritura.

---

## 6 · Reglas duras del Flujo M

1. **Solo se activa tras un Flujo A con veredicto 🟢/🔵** (A20). Nunca se
   activa solo, nunca con veredicto 🔴 o sin veredicto.
2. **Cero navegación.** El Flujo M no abre portales, no descarga fotos, no
   scrappea. Solo redacta sobre los datos del ZIP.
3. **Trazabilidad:** cada cifra del copy tiene que poder citar su campo en el
   `informe.json`. El validador (`scripts/check_marketing.py`) lo comprueba.
4. **Datos internos prohibidos** (A23 + regla `business-model.md`):
   `margen`, `honorarios`, `vendedor`, `precio de compra`, `precio de
   negociación`, URLs internas (`mobile.de/...`, `autoscout24.es/...`,
   `kleinanzeigen.de/...`).
5. **Superlativos prohibidos** (A24): `único`, `exclusivo`, `última
   oportunidad`, `no te lo pierdas`, `una vez en la vida`, `chollo`,
   `joya`, `maquina`, `bomba`, `pepinazo`.
6. **Cero emoji decorativo** (A25): 🔥💥🚀😍 solo se permiten como 🔥 en
   gancho de showstopper y solo una vez por pieza.
7. **A26: el aviso legal del portal** debe incluir: identidad del empresario,
   "el vehículo no es propiedad del establecimiento", precio final con
   impuestos, qué NO incluye.
8. **A27: la fecha de primera matriculación** es obligatoria en ficha de
   portal (Real Decreto Legislativo 1/2007, art. 20).
9. **A28: la pega honesta** es obligatoria. Sin pega → la pieza no se publica.
10. **A29: el icono ⚠️** solo aparece acompañando una pega real.
11. **A30: la palabra "garantía"** solo se usa si se dice cuál, quién la da
    y cuánto dura. La garantía legal de un VO es de 3 años reducible por
    pacto a 1 año mínimo.

---

## 7 · Checklist QA (la pasa el validador automático)

El validador (`scripts/check_marketing.py`) corre 30 comprobaciones con
severidad:

- 🔴 **ALTO** → bloquea la publicación.
- 🟠 **MEDIO** → hay que corregir antes de publicar.
- 🟡 **BAJO** → criterio editorial, no bloquea.

Comprobaciones:

1. Bloques obligatorios por canal (definidos en `redes_sociales.md` y
   `portales_anuncio.md`).
2. Cupo de hashtags por canal (ver §3).
3. Receta de hashtags (modelo · nicho · servicio · local · marca).
4. Léxico de iconos cerrado y posición (solo inicio de línea).
5. Cero iconos en portales y FB Marketplace.
6. Cero hashtags en portales y FB Marketplace.
7. Cero enlaces en portales (Coches.net **rechaza el anuncio**).
8. Cero datos de contacto en portales (Coches.net **rechaza el anuncio**).
9. Datos internos prohibidos (A23): `margen`, `honorarios`, `vendedor`,
   `precio de compra`, URL interna.
10. Superlativos prohibidos (A24).
11. Emojis decorativos prohibidos (A25) — solo 🔥 permitido 1×/pieza.
12. Aviso legal completo (A26).
13. Fecha de 1ª matriculación en portal (A27).
14. Pega honesta presente (A28).
15. Icono ⚠️ solo con pega (A29).
16. Uso correcto de "garantía" (A30).
17. Longitudes por canal (Wallapop 600-900 chars, etc.).
18. Textos duplicados entre canales.
19. Campos del formulario sin rellenar.
20. Trazabilidad: cada cifra del copy debe poderse citar a un campo del
    `informe.json`.
21-30. Variaciones de las anteriores (reglas de estilo, formato mecánico de
  precio, fechas DD/MM/YYYY, terminología preferida).

---

## 8 · Proceso del Flujo M (7 pasos)

1. **Recibe** `informe.json` con veredicto 🟢/🔵.
2. **Elige ángulo** — uno de los 8 de `biblioteca_ganchos.md`, según categoría
   del coche.
3. **Escribe portales primero** — texto base + 3 deltas (Coches.net /
   Milanuncios / Wallapop). Recorta Wallapop del base.
4. **Escribe redes canal por canal** — IG feed, stories, reel/TikTok/Shorts,
   Facebook página, FB Marketplace. Cada uno con su plantilla.
5. **Pasa el validador** — `python scripts/check_marketing.py contenido/`.
   Si falla 🔴, corrige y vuelve a pasar. **Sin verde no se publica.**
6. **Empaqueta** — `redes-sociales.txt` + `anuncio-portales.txt` van al ZIP
   del Flujo A (ver `empaquetar.py`). El bloque JSON `marketing` se añade al
   `informe.json` para trazabilidad.
7. **Entrega + plan de publicación** — salida al chat + tabla D0/D1/D3/D7
   (ver §8 del plan multicanal).
