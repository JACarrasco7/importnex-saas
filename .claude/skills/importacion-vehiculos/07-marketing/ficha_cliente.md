# 📄 FICHA DEL CLIENTE (el enlace que se le pasa) — spec v2

> **Qué es:** la página que el cliente abre cuando le mandas el enlace del coche
> (`jjimportmotors.on-forge.com/c/<token>`). La genera Laravel desde `contenido/ficha-cliente.txt`.
> **Qué NO es:** ni un anuncio de portal (eso compite por precio) ni el dossier largo de 15 secciones
> (eso es el PDF de cierre). Esta es la pieza intermedia: **la que convence sin necesidad de llamar**.
> Creado 07-sep-2026 tras auditar la ficha real de un Mercedes Clase A.

---

## 0 · El enlace es PÚBLICO

El enlace lleva un token, pero **cualquiera que lo reciba lo puede reenviar**: no hay contraseña, no caduca y el cliente lo comparte por WhatsApp con quien quiera. Se trata, por tanto, **como una página pública**.

**Consecuencia práctica:** todo lo que está prohibido en un anuncio de portal o en un post de Instagram está prohibido aquí. Misma lista: margen, honorarios desglosados, hueco de importación, precio de origen, número de competidores, datos del vendedor, veredicto interno y cualquier score. Si no lo pondrías en Coches.net, no va en la ficha del cliente.

---

## 1 · Diagnóstico de la ficha actual

Lo que la versión de hoy hace bien: precio arriba, km verificados, botones de contacto visibles, fotos reales, equipamiento y desglose de lo que incluye. La estructura básica funciona.

Lo que hay que arreglar:

| # | Problema detectado | Gravedad | Qué se hace |
|---|---|---|---|
| 1 | **Se publican datos internos**: "vendibilidad 84/100", "11 unidades disponibles en España", "ahorro estimado" con mínimo/medio/máximo de mercado | 🔴 Alta — rompe A22 y la regla dura nº 1 del dossier | La vendibilidad y el conteo de competencia **desaparecen**. La banda de mercado se queda, pero como rango con nº de comparables y fecha, sin presentarlo como "lo que te ahorras" |
| 2 | El veredicto sale tal cual ("Excelente compra") | 🔴 Alta — `verdict_reasoning` es interno (A22) | Se sustituye por una **valoración escrita para el cliente**: qué tiene esta unidad, no qué nota le hemos puesto |
| 3 | **Falta la ficha técnica completa**: potencia, tracción, combustible, consumo/emisiones, etiqueta DGT, color, plazas, puertas, 1ª matriculación | 🟠 Media — es lo primero que pregunta cualquiera | Tabla de ficha técnica completa, con "por confirmar" donde no haya dato |
| 4 | **Falta el estado real**: propietarios, historial de mantenimiento, ITV/TÜV, qué está pendiente de comprobar | 🟠 Media — es lo que genera confianza | Bloque de estado en dos columnas: verificado ✅ / pendiente ☐ |
| 5 | No se explica **el proceso ni los plazos** | 🟠 Media | Timeline por semanas, con la advertencia de que son estimaciones |
| 6 | El precio dice "más gastos de gestión" sin decir cuáles | 🟠 Media | El caption se queda como está (**regla de negocio del panel**: `+ gastos gestión de compra`, nunca "IVA incluido" ni "precio final"), pero debajo aparece **qué incluye y qué NO incluye** el servicio |
| 7 | **No hay aviso legal**: no queda claro que no vendemos el coche ni que no damos garantía | 🔴 Alta | Bloque fijo, siempre presente (ver §4) |
| 8 | No hay **fecha de captura** de los datos ni caducidad de la disponibilidad | 🟡 Baja | Fecha visible + "disponibilidad sujeta a confirmación" |
| 9 | No hay **preguntas frecuentes**: el cliente se queda con las dudas y no llama | 🟡 Baja | 6-8 preguntas reales con respuesta corta |
| 10 | **La sección "Lo que hace fuerte a esta unidad" es análisis interno entero**: "hueco de 8.969 € (26,4 %) antes de costes", "solo 11 unidades 2021 en toda España", "vendedor profesional 4.5★ acostumbrado a operaciones de exportación", "vendibilidad alta (84/100)" | 🔴 Alta — es la operación completa contada al cliente | Se sustituye por argumentos de compra: qué tiene la unidad, no cuánto ganamos ni cómo de fácil es revenderla |
| 11 | **Las cifras no cuadran entre sí**: precio 24.990 €, mínimo de mercado 33.959 € y "ahorro estimado 4.214 €". Las tres juntas no se sostienen | 🔴 Alta — un cliente atento lo ve y pierde la confianza en todo lo demás | Se publica el **rango** de mercado con nº de unidades y fecha, y **ninguna cifra de ahorro** |
| 12 | **Muro de ~30 fotos casi idénticas** antes de las secciones que convencen: "Por qué destaca" y el equipamiento quedan enterrados detrás de la galería | 🟠 Media | Galería de 8-10 fotos elegidas + "ver todas"; las secciones de argumento van ANTES |
| 13 | El precio no explica qué se suma | 🟠 Media | El caption `+ gastos gestión de compra` es correcto y se mantiene (no vendemos: no hay "precio final con IVA"); lo que faltaba es el bloque de **qué incluye y qué no** justo debajo |

---

## 2 · Las 14 secciones de la ficha v2

| # | Sección | Función | Bloques |
|---|---|---|---|
| 1 | **Cabecera** | Modelo, versión, año, km, precio final y estado del proceso | `FC_TITULO` `FC_SUBTITULO` `FC_PRECIO` `FC_PRECIO_NOTA` `FC_ESTADO_PROCESO` |
| 2 | **En 30 segundos** | Tres líneas: lo bueno, lo que hay que tener en cuenta, el siguiente paso | `FC_RESUMEN_BUENO` `FC_RESUMEN_OJO` `FC_RESUMEN_PASO` |
| 3 | **Ficha técnica** | Todo lo que se pregunta antes de llamar | `FC_SPEC` (lista `Etiqueta \| Valor`) |
| 4 | **Equipamiento** | Lo verificado, separado de lo que falta confirmar | `FC_EQUIP` · `FC_EQUIP_PENDIENTE` |
| 5 | **Estado y antecedentes** | Verificado ✅ frente a pendiente ☐ | `FC_VERIFICADO` · `FC_PENDIENTE_COMPROBAR` |
| 6 | **Fotos reales** | Del vehículo, nunca de catálogo | `FC_FOTOS` |
| 7 | **Por qué esta unidad** | 3-4 argumentos, cada uno con su dato | `FC_ARGUMENTO` |
| 8 | **Qué dice el mercado español** | Rango de precios de unidades parecidas, nº de comparables y fecha | `FC_MERCADO_MIN` `FC_MERCADO_MEDIANA` `FC_MERCADO_MAX` `FC_MERCADO_N` `FC_MERCADO_FECHA` `FC_MERCADO_NOTA` |
| 9 | **Qué incluye el precio** | Y sobre todo **qué no incluye** | `FC_INCLUYE` · `FC_NO_INCLUYE` |
| 10 | **Cómo funciona** | Timeline por semanas, en estimaciones | `FC_PASO` (lista `Semana \| Qué pasa`) |
| 11 | **Qué hacemos y qué no** | El bloque legal-honesto: gestión sí, venta y garantía no | `FC_HACEMOS` · `FC_NO_HACEMOS` · `FC_NO_GARANTIA` |
| 12 | **Preguntas frecuentes** | 6-8, las que se repiten por WhatsApp | `FC_FAQ` (lista `Pregunta \| Respuesta`) |
| 13 | **Siguiente paso** | Un solo CTA | `FC_CTA` · `FC_CONTACTO` |
| 14 | **Pie legal** | Identidad, fecha de datos, aviso | `FC_AVISO_LEGAL` · `FC_FECHA_DATOS` |

**Orden innegociable:** precio y km arriba (es lo que se mira), la pega antes que el argumento de venta (credibilidad), el aviso legal completo antes del CTA (nadie contacta y luego se lleva una sorpresa).

**Y la galería NO va en medio.** En la versión actual entierra todo lo que convence. Orden correcto: resumen → ficha técnica → estado → **8-10 fotos con "ver todas"** → argumentos → mercado → precio → proceso → legal → FAQ → CTA. El resto de fotos se cargan al pulsar, no de golpe.

---

## 3 · Reglas de contenido

1. **Cero datos internos** (A22/A27): vendibilidad, score, margen, honorarios desglosados, número de competidores, precio de origen, URLs de comparables, `verdict_reasoning`, `recommendation`.
2. **El "ahorro" no se vende como cifra propia.** Se muestra el **rango del mercado español** (mín-mediana-máx), el número de unidades comparadas y la fecha. El cliente saca su conclusión; nosotros no prometemos un ahorro.
3. **Una pega visible, sí o sí** (A28). Si de verdad no hay, se dice qué se ha revisado para poder afirmarlo.
4. **Lo no verificado se marca como pendiente**, nunca se omite ni se rellena (A18).
5. **Precio del vehículo + caption `+ gastos gestión de compra`** y, debajo, qué incluye y qué NO incluye el servicio. **Prohibido** "IVA incluido", "precio final", "llave en mano" o "sin sorpresas": no vendemos el coche, así que no emitimos un precio final de venta (ver `.ai/rules/business-model.md` del panel).
6. **Fecha de captura de datos siempre visible.** Un dato sin fecha envejece mal.
7. **Un solo CTA.** "Quiero gestionar la compra" y el teléfono; nada más compitiendo.
8. **Plazos en semanas y como estimación**, nunca fechas cerradas.

---

## 4 · Bloque fijo: qué hacemos y qué no (texto de referencia)

> Este texto es **obligatorio y no se reescribe libremente**. Se puede adaptar el detalle, nunca el fondo.

```
QUÉ HACEMOS
- Localizamos la unidad y comprobamos su historial y su documentación.
- Negociamos y coordinamos la compra con el vendedor.
- Organizamos el transporte hasta España.
- Tramitamos la ITV de importación, los impuestos y la matriculación.
- Te acompañamos hasta que tienes las llaves y el coche a tu nombre.

QUÉ NO HACEMOS
- No vendemos coches: JJ Import Motors no es el vendedor ni el propietario del
  vehículo. La compraventa es entre el vendedor y tú, y el coche se matricula
  directamente a tu nombre.
- No ofrecemos garantía de ningún tipo sobre el vehículo. Cualquier garantía o
  responsabilidad que exista corresponde al vendedor, según la ley que le aplique.
- No respondemos de averías, desgastes o defectos que no sean visibles en la
  documentación y en la inspección previa.
- No hacemos mantenimiento ni reparaciones.

Nuestro trabajo es la GESTIÓN de la búsqueda, la verificación y la importación,
con unos honorarios fijos acordados de antemano.
```

**Nunca** se escribe "garantizamos", "te aseguramos", "respondemos del coche", "nuestro coche", "lo vendemos", "en venta" ni "concesionario". Si conviene recomendar una garantía mecánica de terceros, se dice que es **de terceros** y que se contrata aparte.

---

## 5 · Qué tiene que añadir Laravel

La plantilla `ficha-coche.blade.php` ya pinta título, precio, fotos, equipamiento y "qué incluye". Los bloques **nuevos** que hay que soportar:

| Bloque | Render sugerido |
|---|---|
| `FC_ESTADO_PROCESO` | Píldora arriba: *Disponible · Reservado · En tránsito · Entregado* |
| `FC_RESUMEN_BUENO` / `FC_RESUMEN_OJO` / `FC_RESUMEN_PASO` | Tarjeta de 3 líneas con icono ✅ ⚠️ 📍 |
| `FC_SPEC` | Tabla de dos columnas, dos por fila en escritorio |
| `FC_VERIFICADO` / `FC_PENDIENTE_COMPROBAR` | Dos columnas: ✅ verificado · ☐ pendiente |
| `FC_MERCADO_*` | Barra de rango con mín-mediana-máx + "n unidades comparadas el dd/mm/aaaa" |
| `FC_NO_INCLUYE` | Lista en gris bajo "qué incluye" |
| `FC_PASO` | Timeline horizontal por semanas |
| `FC_HACEMOS` / `FC_NO_HACEMOS` / `FC_NO_GARANTIA` | Bloque a dos columnas + aviso destacado |
| `FC_FAQ` | Acordeón |
| `FC_FECHA_DATOS` | Línea pequeña bajo el pie |

**Quitar de la plantilla actual:** la vendibilidad, el conteo de unidades disponibles en España y el "ahorro estimado" como cifra destacada.

**Cómo se integra técnicamente** (JSON en vez de parsear texto, componentes Blade, Open Graph para WhatsApp, animaciones y modo PDF): `handoff_laravel.md`.

Mockup de referencia con datos de ejemplo: `informes\marketing\mockup_ficha_cliente_v2.html` (en la carpeta del negocio).

---

## 6 · Diferencia entre las tres piezas del cliente

| | Anuncio de portal | **Ficha del cliente (enlace)** | Dossier PDF |
|---|---|---|---|
| Para quién | Comprador frío que compara | Cliente que ya habló contigo | Cliente a punto de reservar |
| Longitud | 1.200-1.800 caracteres | Página completa, 14 secciones | 8-12 páginas |
| Mercado | No | Rango y nº de comparables | Estudio completo |
| Costes | Precio final y qué incluye | Precio final + qué no incluye | Desglose línea a línea con honorarios |
| Proceso | 3 líneas | Timeline por semanas | Timeline + FAQ + próximos pasos |
| Aviso de gestor y sin garantía | Sí, breve | **Sí, completo** | Sí, completo |

---

## 🔗 Referencias
- Motor y reglas comunes: `copy_engine.md`
- Esqueleto: `plantillas/ficha-cliente.txt` · ejemplo: `plantillas/ejemplo/ficha-cliente.txt`
- Dossier largo: `../03-informes/dossier_cliente.md`
- Contrato: `../03-informes/contrato.md` §formato esqueleto
