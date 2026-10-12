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

**Importante:** los 3 portales web (Coches.net / Milanuncios / Wallapop)
comparten **texto base** y solo cambian tres cosas: título, longitud y
cierre. Wallapop es un **recorte** del base (600-900 caracteres), nunca
una reescritura. **Facebook Marketplace es un canal aparte** (bloques
`FBMP_*`, sin iconos, sin hashtags, "Escríbeme por Messenger…" como
cierre): no comparte el texto base de los portales web. **Corrección
12-sep-2026:** hasta la v3.9.1 los 4 (los 3 portales + Marketplace)
recibían el mismo texto en el panel porque `ValuationPackageIngestor`
solo leía el vocabulario v1 (`[TITULO]`/`[DESCRIPCION]`); ya lee el v2
(`PT_*` para los 3 portales, `FBMP_*` para Marketplace) y los diferencia.

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
12. **M-12: el link original del anuncio SIEMPRE acompaña al copy.** Cada
    pieza de marketing (redes + portales) lleva el link directo al anuncio
    origen (`url_link` del `informe.json`) en el bloque `[PT_FUENTES]` o
    `[IG_FUENTES]`. El cliente/potencial comprador debe poder abrir el
    anuncio original para comprobar disponibilidad. **Prohibido** citar el
    link como "fuente interna" (A23) — el link del anuncio es público y
    necesario, no es un dato interno.

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
17. Longitudes por canal. **Aclaración 12-sep-2026:** las bandas que valida
    este script (`check_marketing.py`) son sobre el **texto base** que
    genera la skill (`PT_*`, banda única para los 3 portales web). El
    recorte de Wallapop a 600-900 caracteres (§5) lo aplica **Laravel**
    al montar el anuncio final (`ValuationPackageIngestor::ingestarPortalesV2()`),
    no la skill — por eso no hay una banda "Wallapop" separada aquí. El
    recorte nunca toca la pega honesta (A28) ni el aviso legal (A26/A27):
    si hace falta, se acorta antes el resto (ficha, equipamiento).
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

---

## 9 · Addendum 07-sep-2026 — lo que aporta la investigación externa

> Cada punto con su fuente y su fecha en `evidencia_externa.md`. Revisar en 6 meses.

### 9.1 · El *send-ask* (Instagram prioriza los envíos por DM)

Mosseri (jul-2026): *"Hashtags work, but they've never been a good way to actually increase your reach"* — categorizan, no alcanzan. Lo que sí pesa hoy es **sends per reach** (envíos por DM ÷ alcance; 3-5 % es una buena referencia).

- Toda pieza de feed lleva, **además del CTA**, una línea de envío con destinatario concreto: *"Mándaselo a quien lleve medio año buscando un compacto así"*. Nunca "comparte con 5 amigos".
- No cuenta como segundo CTA: el CTA apunta hacia nosotros, el send-ask hacia un tercero.
- **Regla de la primera línea:** marca + modelo + versión en las dos primeras líneas, escritos como se buscan. El buscador de Instagram lee el caption, no las etiquetas.

### 9.2 · Tono por situación

| Situación | Dial | Qué hacer | Qué no |
|---|---|---|---|
| Pega grave que hay que declarar | Honesto y tranquilo | Decirla pronto, con la solución al lado | Esconderla al final |
| La unidad se ha vendido | Breve y útil | Cerrar el post y ofrecer buscar una igual | Presumir de rapidez |
| Sube el coste (transporte, impuestos) | Transparente | Explicar la causa y qué implica | Dar cifras internas |
| Comparación con otro importador | Seguro, sin nombres | Explicar qué hacemos nosotros | Nombrar o insinuar |
| Cliente que preguntó y no contestó | Cercano | Un dato nuevo como excusa | Urgencias falsas |

### 9.3 · Dos ganchos por coche (A/B por defecto)

Cada unidad sale con **gancho A y gancho B, de ángulos distintos** (`[IG_GANCHO_B]`, `[PT_TITULO_B]`). Uno va a Instagram y el otro al título del portal o al segundo vídeo. Después se anota en `../memoria/marketing-resultados.md` cuál trajo contacto. En 8-10 unidades se sabe qué ángulo funciona **con datos propios**.

### 9.4 · Vídeo corto: números de partida

Ventana crítica **0-3 s** · duración con mejor finalización **9-15 s** · rótulo de gancho 6-10 palabras, beneficio 5-8, CTA 2-3 · **zona segura**: nada de texto en los ~120 px superiores ni inferiores · caption de TikTok ≤100 caracteres · mínimo **2 variantes de gancho**.

### 9.5 · Qué se mide

| Publicación | Métrica principal | Referencia |
|---|---|---|
| Unidad en redes | Mensajes recibidos · sends per reach | 3-5 % es bueno |
| Unidad en portales | Contactos · días hasta el primer contacto | Comparar los 3 portales |
| Reel / TikTok | Tasa de finalización | ~75 % en piezas de 15 s |
| Ángulo/gancho | Contactos por ángulo y categoría | Alimenta `biblioteca_ganchos.md` |

### 9.6 · Precio: cómo se escribe (A31)

Se publica el **precio del vehículo** con el caption `+ gastos gestión de compra` y, debajo, qué incluye y **qué no** el servicio. **Prohibido** «IVA incluido», «precio final», «llave en mano» o «sin sorpresas»: no vendemos el coche (ver `.ai/rules/business-model.md` del panel y A31).

### 9.7 · El enlace del cliente es público (A22b)

La ficha `/c/<token>` se reenvía por WhatsApp: aplica la misma lista de prohibiciones que un anuncio de portal. Spec completa en `ficha_cliente.md`; entrega técnica en `handoff_laravel.md`.

