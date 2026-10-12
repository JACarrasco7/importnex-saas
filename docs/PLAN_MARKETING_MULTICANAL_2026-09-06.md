# 📣 Plan de marketing multicanal — JJ Import Motors
**Fecha:** 06-sep-2026 (v2, revisión con fuentes externas) · **Ámbito:** skill `importacion-vehiculos` · **Estado:** Fases 1 y 1.5 IMPLEMENTADAS

---

## 1 · Diagnóstico: qué había (y por qué salía plano)

Todo el marketing de la skill cabía en **dos líneas** de `03-informes/contrato.md`:

```
| redes-sociales.txt  | GANCHO, POST_LARGO, POST_CORTO, STORIES, HASHTAGS, PIE_FOTO |
| anuncio-portales.txt| TITULO, DESCRIPCION, FICHA_RAPIDA, QUE_INCLUYE, AVISO_LEGAL |
```

Eso es un **contenedor**, no un método. De ahí venían los cinco problemas:

| # | Problema | Causa raíz |
|---|---|---|
| 1 | Textos genéricos, todos parecidos | No existía spec por canal: un `POST_LARGO` servía para Instagram, Facebook y portal |
| 2 | Muros de hashtags | `HASHTAGS` sin cupo ni receta → la IA rellenaba con lo genérico (#cars #instagood) |
| 3 | Emojis decorativos | Sin léxico ni reglas → 🔥🚀😍 como adorno en vez de iconos que informan |
| 4 | Nada específico de portal | Coches.net, Milanuncios y Wallapop no aparecían por ningún sitio |
| 5 | Sin control de calidad | Ningún validador: se podía colar "margen", "chollo" o una cifra inventada |

Además, el único blindaje existente (A22) protegía **el folleto del cliente**, pero no las redes ni los portales: el texto público podía llevar datos internos sin saltar ninguna alarma.

---

## 2 · Qué se ha construido (Fase 1 · ya dentro de la skill)

Módulo nuevo `07-marketing/` + validador + integración en el contrato y las reglas duras.

```
07-marketing/
├── copy_engine.md        Motor: voz de marca · léxico de iconos · cupos de hashtags ·
│                         arquitectura del mensaje (5 piezas) · matriz de canales ·
│                         reglas duras del Flujo M · checklist QA · proceso en 7 pasos
├── redes_sociales.md     Spec por canal: IG feed / stories / reel · TikTok · Shorts ·
│                         Facebook página · Facebook Marketplace (+ ejemplos y cadencia)
├── portales_anuncio.md   Coches.net · Milanuncios · Wallapop: texto base único + 3 deltas,
│                         fórmula de título, mapeo al formulario, checklist de publicación
├── biblioteca_ganchos.md 8 ángulos de venta · ángulo por categoría · aperturas prohibidas ·
│                         banco de cierres · cómo declarar la pega honesta
└── plantillas/
    ├── redes-sociales.txt      Esqueleto v2 multicanal
    ├── anuncio-portales.txt    Esqueleto v2 de portales
    └── ejemplo/                Ejemplo relleno completo (patrón de calidad + fixture)

scripts/check_marketing.py      Validador automático (30 comprobaciones)
```

**Integrado en:**
- `SKILL.md` — nuevo **Flujo M** en la tabla de flujos + estructura del ZIP actualizada + resumen A23-A29.
- `03-informes/contrato.md` — bloques de los dos esqueletos reescritos + nuevo bloque JSON `marketing`.
- `06-reglas/anti_patrones.md` — **A23 a A29**.
- `CHANGELOG.md` — entrada del cambio.
- `importacion-vehiculos.skill.zip` regenerado (copia del anterior en `_archive/`).

---

## 3 · Las tres decisiones que cambian el resultado

### 3.1 · Iconos = etiquetas de campo, no decoración

Léxico **cerrado** de 17 iconos, cada uno atado a un dato: 🗓️ año · 🛣️ km · ⚙️ cambio · ⛽ combustible · 🐎 CV · 🏷️ etiqueta DGT · 👤 propietarios · 🔧 mantenimiento · 📄 papeles · 🛡️ garantía · 📍 origen · 📦 qué incluye · 💶 precio · ✅ verificado · ⚠️ a tener en cuenta · 🇩🇪/🇪🇸 país.

Reglas: **solo al principio de línea**, **uno por línea**, **cero en portales y Marketplace**, y prohibidos 🔥💥🚀😍 (excepción: un 🔥 en el gancho de un showstopper). *Si quitas el icono y la frase se entiende igual, el icono sobra.*

### 3.2 · Hashtags con cupo y receta

| Canal | Máximo | Receta |
|---|---|---|
| Instagram feed | **5** | modelo · nicho · servicio · local · marca |
| Stories | 0-1 | solo de marca |
| Reel / TikTok / Shorts | ≤4 | en el caption |
| Facebook página | ≤2 | al final |
| Marketplace y **los 3 portales** | **0** | — |

Vetados los genéricos en inglés y cualquier hashtag dentro del cuerpo del texto.

### 3.3 · Un texto por canal (lo que más se notará)

| | Gancho | Ficha | Historia | Pega | Iconos | Hashtags | CTA |
|---|---|---|---|---|---|---|---|
| IG feed | fuerte | con iconos | corta | sí | ≤7 | 5 | DM |
| IG stories | mínimo | 2 datos | no | no | 1/pantalla | 0-1 | sticker |
| Reel/TikTok/Shorts | en 1 s | 3 datos | no | no | ≤3 | ≤4 | comentario |
| Facebook página | suave | con iconos | larga | sí | ≤7 | ≤2 | WhatsApp |
| FB Marketplace | no | texto plano | no | sí | 0 | 0 | mensaje |
| Coches.net · Milanuncios · Wallapop | no | ficha estructurada | no | **obligatoria** | 0 | 0 | contacto del portal |

Los **tres portales comparten texto base** (el comprador es el mismo) y solo cambian en tres cosas: título, longitud y cierre. Wallapop es un **recorte** del base (600-900 caracteres), nunca una reescritura.

---

## 4 · Antes y después (mismo coche)

**Antes** — lo que producía el modelo viejo:

> 🔥🔥 ¡BRUTAL Opel Astra OPC! 🚀 Una auténtica joya, un chollo que no puedes dejar
> escapar 😍 280cv de pura diversión!! Escríbenos ya 📲
> #cars #carsofinstagram #instacar #love #instagood #coches #cochesdeocasion #opel
> #astra #opc #tuning #speed #turbo #madrid #sevilla #huelva #import ...

**Después** — Instagram feed con el motor nuevo:

> 280 CV, un solo dueño y 8.800 km al año. Los tres a la vez casi no se ven.
>
> El Astra OPC es el compacto deportivo que nadie mira cuando busca un GTI, y por eso
> todavía se puede comprar bien. Este lo localizamos en Alemania y ya está verificado.
>
> 🗓️ 2015 · 🛣️ 62.400 km · 🐎 280 CV, manual de 6
> 👤 1 propietario · 🔧 Libro de mantenimiento sellado · 🏷️ Etiqueta C
>
> ⚠️ Neumáticos al 50 %: se sustituyen antes de la entrega.
>
> Entra en las ZBE sin restricciones y su precio queda por debajo de la mediana española.
> 💶 Precio cerrado con transporte, ITV de importación y trámites incluidos.
>
> Te paso la ficha completa por DM.
>
> #AstraOPC #CompactosDeportivos #ImportacionDeCoches #Huelva #JJImportMotors

Y el **mismo coche en Coches.net** no se parece: título con los filtros que la gente escribe (`Opel Astra OPC 280CV manual · 2015 · 62.400 km · 1 dueño`), descripción en bloques escaneables (RESUMEN / FICHA / ESTADO / EQUIPAMIENTO / QUÉ INCLUYE / CÓMO FUNCIONA / AVISO), cero iconos y cero hashtags.

---

## 5 · El detalle que más vende: la pega honesta

Es regla dura (**A28**): toda pieza larga declara un punto flojo con su solución.

```
⚠️ Neumáticos al 50 %: se sustituyen antes de la entrega.
⚠️ Roce en el paragolpes trasero, visible en la foto 7. Descontado del precio.
```

Recibes menos llamadas, pero **mucho mejores**, y hace creíble el resto del anuncio. Si de verdad no hay pega, se escribe qué se revisó para poder afirmarlo.

---

## 6 · Control de calidad automático

```bash
python scripts/check_marketing.py contenido/
```

Comprueba 30 cosas: bloques obligatorios, cupo y receta de hashtags, léxico y posición de iconos, cero iconos/hashtags/enlaces en portales, datos internos prohibidos (margen, honorarios, mobile.de, vendedor…), superlativos vetados, longitudes por canal, textos duplicados entre canales, pega declarada, campos del formulario sin rellenar, precio al contado y trazabilidad de cada cifra a su campo del `informe.json`.

- Sobre el ejemplo de referencia: **✅ 0 errores**.
- Sobre un texto malo de prueba (hashtag wall + emojis + "chollo" + "margen"): **❌ 30 errores detectados**.

**Sin validador en verde no se publica.** Es el paso 5 del Flujo M.

---

## 7 · Cómo se usa a partir de ahora

1. Terminas un **Flujo A** con veredicto 🟢/🔵.
2. Dices: **"hazme el marketing de este coche"** → se activa el **Flujo M** (nunca se activa solo, A20).
3. La skill rellena el bloque `marketing` del JSON, elige **un** ángulo según la categoría, escribe primero los portales y después las redes canal por canal.
4. Pasa el validador y te entrega `redes-sociales.txt` + `anuncio-portales.txt` (al chat y dentro del ZIP), con la lista de fotos necesarias y el orden de publicación:

| Día | Publicación |
|---|---|
| D0 | Coches.net + Milanuncios + Wallapop + Facebook Marketplace |
| D1 | Instagram feed + Facebook página + stories apuntando al feed |
| D3 | Reel / TikTok |
| D7 | Stories de recordatorio **con un dato nuevo** |

**Coste:** el Flujo M no navega. Cero peticiones a portales.

---

## 8 · Fase 2 — lo siguiente (propuesta, no ejecutado)

| # | Mejora | Por qué | Esfuerzo |
|---|---|---|---|
| 1 | **Captura de argumentos en Fase 2** de la investigación: al leer la ficha, guardar ya `pruebas[]` con su campo, la pega y las 5 fotos buenas | Hoy el marketing recupera datos a posteriori; capturándolos en la investigación el copy sale completo a la primera | Medio |
| 2 | **Plantilla visual de marca** (HTML→PNG) para post y story: franja azul `#1A306D` + acento naranja `#E8590C`, tipografía Inter, ficha en iconos | Ahora mismo el texto es pro pero la imagen es la foto pelada | Medio |
| 3 | **Variantes A/B del gancho** (2 ganchos por coche con ángulos distintos) + registro de cuál funcionó en `memoria/` | Convierte el marketing en algo medible en vez de opinable | Bajo |
| 4 | **Biblioteca de resultados**: qué modelo/ángulo generó más mensajes, por canal | Alimenta la elección de ángulo de la biblioteca con datos reales tuyos | Bajo |
| 5 | **Contenido de marca mensual** (4 formatos ya definidos en `biblioteca_ganchos.md` §6) programado | Mantiene el perfil vivo cuando no hay coche que publicar | Bajo |
| 6 | **WhatsApp difusión y Google Business** | Quedaron fuera del alcance de hoy; son los canales con más conversión local | Bajo |
| 7 | **Blade en Laravel** que renderice el `redes-sociales.txt` como tarjetas listas para copiar/pegar | Publicar sin abrir el .txt | Medio |

---

## 9 · Archivos tocados

| Archivo | Cambio |
|---|---|
| `07-marketing/` (4 .md + 5 plantillas) | **Nuevo** |
| `scripts/check_marketing.py` | **Nuevo** — validador |
| `SKILL.md` | Flujo M, índice de compañeros, estructura del ZIP, resumen A23-A29 |
| `03-informes/contrato.md` | Bloques v2 de los dos esqueletos + bloque JSON `marketing` |
| `06-reglas/anti_patrones.md` | A23-A29 + tabla de origen |
| `CHANGELOG.md` | Entrada 06-sep-2026 |
| `importacion-vehiculos.skill.zip` | Regenerado · copia previa en `_archive/importacion-vehiculos.skill_backup_2026-09-06.zip` |

---

## 10 · Fase 1.5 — qué cambió al contrastarlo con fuentes externas

Tras montar el módulo lo he contrastado con tres cosas: la **documentación de los propios portales**, la **normativa española de información al consumidor** y las **skills de marketing** del plugin (brand-review, campaign-plan, content-creation). Salieron nueve mejoras que ya están dentro. Todas las fuentes quedan con su fecha en `07-marketing/fuentes_y_evidencia.md`, para poder revisarlas dentro de seis meses.

### 10.1 · Lo que dicen los portales (y no estaba en el plan)

| Hallazgo | Qué he cambiado |
|---|---|
| Meter **datos de contacto en la descripción** hace que Coches.net **rechace el anuncio** | Prohibición dura + comprobación automática de teléfonos, emails, WhatsApp y webs |
| Coches.net desaconseja los **signos de exclamación** ("dificultan la lectura y hacen menos atractivo tu anuncio") | Cero `!` en portales, validado |
| El tope real de descripción en **Coches.net PRO son 3.000 caracteres** | Mantengo la diana en 1.200-1.800 (nadie lee 3.000), pero ya sé cuál es el techo |
| El portal recomienda **frases de búsqueda relevantes** en el texto; el 61 % de las búsquedas de VO en Google acaban en Coches.net | La fórmula de título y el primer párrafo se escriben con las palabras que la gente teclea |
| **Milanuncios: 2 anuncios de coche gratis**; a partir de ahí, Coches.net | A tener en cuenta al publicar varias unidades a la vez |

### 10.2 · Lo legal (esto era el hueco más serio)

El art. 20 del texto refundido de la Ley de Consumidores obliga a que toda oferta comercial indique el **precio final completo con impuestos**, la **identidad del empresario** y las **características esenciales** — y deja claro que **la carga de la prueba de esa información es del empresario**. Además, un vehículo de ocasión ofertado por un profesional debe informar de antigüedad, kilometraje, **fecha de primera matriculación**, uso anterior, precio, garantía y **si es o no propiedad del establecimiento**.

Como vosotros sois **gestores y no vendedores**, eso último es a vuestro favor, pero hay que decirlo. Cambios:

- Aviso legal reescrito: identidad + "el vehículo no es propiedad del establecimiento" + precio final con impuestos + qué no incluye.
- Ficha mínima con **fecha de 1ª matriculación** (no estaba).
- La palabra **"garantía" solo si se dice cuál, quién la da y cuánto dura** — la garantía legal de un VO es de 3 años reducible por pacto a 1 año mínimo, así que usarla a la ligera es un riesgo real.
- Nuevo anti-patrón **A30**, y todo esto lo comprueba el validador.

### 10.3 · Lo que ha cambiado en redes

| Hallazgo | Qué he cambiado |
|---|---|
| Mosseri (jul-2026): *"Hashtags work, but they've never been a good way to actually increase your reach"* — sirven para **categorizar**, no para alcanzar | Confirma el cupo de 5 y le quita importancia al hashtag: el trabajo lo hace el gancho |
| La **búsqueda de Instagram lee el texto del caption**, no los hashtags | **Regla de la primera línea:** marca + modelo + versión en las dos primeras líneas, escritos como se buscan |
| **Sends per reach** (envíos por DM ÷ alcance) es hoy una de las señales de ranking más fuertes; 3-5 % es una referencia buena | Bloque nuevo **`[IG_SEND_ASK]`**: una línea que nombra al destinatario concreto ("mándaselo a quien lleve medio año buscando uno"). Nunca "comparte con 5 amigos" |
| Vídeo corto: la ventana crítica son los **3 primeros segundos** y la franja de mejor finalización es **9-15 s**; rótulos de 6-10 palabras; **zona segura** de 120 px arriba y abajo; mínimo **2 variantes de gancho** | Guion reescrito de 15-30 s a **12-15 s** con cinco escenas cortas, límite de palabras por rótulo, aviso de zona segura y gancho B obligatorio |

### 10.4 · Lo que he copiado de las skills de marketing

| Mecanismo | De dónde | Cómo queda aquí |
|---|---|---|
| **Severidad Alto/Medio/Bajo** en la revisión | `brand-review` | El validador ya no es pasa/no pasa: 🔴 bloquea (dato interno, superlativo, precio sin impuestos, pega ausente, contacto en portal) · 🟠 se corrige (iconos, hashtags, longitudes, formato) · 🟡 criterio editorial (terminología, campos, frases repetidas) |
| **Bloque de compliance siempre activo** | `brand-review` | §5.2 del motor + A30 |
| **Voz definida en positivo** (suena así / no suena así) | `brand-review` | 4 atributos con ejemplo bueno y malo: directo sin ser frío, técnico pero legible, honesto con la pega, gestor no vendedor |
| **Tono por situación** | `brand-review` | Pega grave · unidad vendida · sube el coste · comparación con otro importador · cliente que no contestó |
| **Estilo mecánico y terminología preferida** | `brand-review` | `280 CV` · `62.400 km` · `18.900 €` · `03/2015`; "unidad" no "coche", "trámites" no "papeleo", "daños declarados" no "accidentado" — todo comprobado por regex |
| **Proof point por pilar de mensaje** | `campaign-plan` | Cada uno de los 8 ángulos lleva ahora el campo del `informe.json` que lo sostiene. Si el campo está vacío, ese ángulo no se usa con ese coche |
| **2-3 variantes de titular por defecto** | `draft-content` | Deja de ser Fase 2: **dos ganchos A/B por coche** son ya un paso del Flujo M (`[IG_GANCHO_B]`, `[PT_TITULO_B]`) |
| **Métricas por tipo de campaña** | `campaign-plan` | Tabla de qué medir por tipo de publicación + `memoria/marketing-resultados.md` para registrar qué ángulo ganó por categoría |

### 10.5 · Cómo queda el validador

```
🔴 BLOQUEADO — no se publica   anuncio-portales.txt
   🔴 ALTO  [PT_RESUMEN] datos de contacto en la descripción: el portal rechaza el anuncio
   🔴 ALTO  [PT_AVISO] el precio debe declararse final e impuestos incluidos (art. 20 TRLGDCU)
   🔴 ALTO  [PT_FICHA] falta la fecha de primera matriculación
   🟠 MEDIO [PT_PRECIO] formato mecánico — precio: escribe «18.900 €»
   🟡 BAJO  [PT_EQUIPAMIENTO] terminología: usa «trámites» en vez de «papeleo»
```

Solo lo 🔴 bloquea. Antes todo pesaba igual y un emoji de más paraba la publicación tanto como una fuga de datos internos.

### 10.6 · Lo que sigue pendiente (Fase 2 revisada)

Se cierra el punto 3 de la Fase 2 (A/B de ganchos: ya está integrado) y el 4 queda a medias (la plantilla de registro existe; faltan datos tuyos). Sigue pendiente: captura de argumentos durante la Fase 2 de la investigación, plantilla visual de marca para post y story, contenido de marca mensual programado, WhatsApp difusión + Google Business, y el Blade de Laravel que renderice los `.txt` como tarjetas listas para copiar.

---

## 11 · Fuentes consultadas

| Fuente | Estado | URL |
|---|---|---|
| Coches.net · cómo debe ser la descripción | ✅ Consultada | https://ayuda.coches.net/hc/es/articles/7487662867218--C%C3%B3mo-te-recomendamos-que-sea-la-descripci%C3%B3n-de-tu-anuncio-en-coches-net |
| Coches.net · 5 consejos para anuncios eficaces (blog profesionales) | ✅ Consultada | https://www.coches.net/blog-profesionales/5-consejos-que-te-ayudaran-a-crear-anuncios-mas-eficaces-y-exitosos/ |
| Milanuncios · ayuda, sección Motor | ✅ Consultada (parcial: no publica normas de contenido) | https://ayuda.milanuncios.com/hc/es/articles/360007480719-Motor |
| Art. 20 TRLGDCU · información en la oferta comercial | ✅ Consultada | https://www.iberley.es/legislacion/articulo-20-ley-defensa-consumidores-usuarios |
| Comunidad de Madrid · coches de segunda mano y garantía | ✅ Consultada | https://www.comunidad.madrid/servicios/consumo/coches-segunda-mano-compras-garantia |
| Instagram · declaraciones de Mosseri sobre hashtags (jul-2026) | ✅ Consultada (fuente secundaria) | https://www.kontentino.com/q-and-a/instagram-hashtags-reach/ |
| Instagram · playbook de *sends per reach* | ✅ Consultada (fuente secundaria) | https://influencermarketinghub.com/instagram-sends-per-reach-playbook/ |
| Buenas prácticas creativas de vídeo corto (TikTok) | ✅ Consultada (agencia, no oficial) | https://www.mbadv.agency/tiktok-ads/creative-best-practices |
| Skills del plugin de marketing (brand-review, campaign-plan, content-creation, draft-content) | ✅ Leídas | plugin local |

> Wallapop no publica guía de contenido para venta de coches: sus reglas se han derivado del comportamiento del portal (móvil, texto corto, chat como contacto) y quedan marcadas como criterio propio, no como norma del portal.
