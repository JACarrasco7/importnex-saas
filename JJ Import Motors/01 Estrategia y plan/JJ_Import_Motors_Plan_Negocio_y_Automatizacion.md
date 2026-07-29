# JJ Import Motors — Plan de negocio, flujo de trabajo y automatización

**Documento maestro** · Preparado para Jose Antonio · Julio 2026
Importación de vehículos UE → España (uso propio y reventa)

Este documento recoge: (1) el flujo de trabajo completo de principio a fin, (2) mejoras concretas a la plantilla y al proceso, (3) el nuevo entregable de folleto PDF, (4) qué se puede automatizar de verdad hoy en redes y portales (y qué no), (5) la lógica de negocio y reglas de decisión, y (6) un roadmap por fases para implementarlo sin agobios.

---

## 1. Visión general del sistema

La idea central es que **cada coche es una "operación"** que pasa siempre por el mismo circuito. Cuanto más estándar sea el circuito, más rápido decides, menos errores cometes y más fácil es delegar o automatizar.

El sistema se apoya en tres activos que ya tienes o que vamos a crear:

1. **La skill de valoración** (`importacion-vehiculos`): convierte un anuncio en una ficha Excel con análisis, investigación y presupuesto. Ya existe.
2. **El folleto PDF de venta**: documento comercial atractivo generado a partir de la misma ficha. Nuevo.
3. **El paquete de contenido**: textos y material listo para publicar en cada plataforma (redes + portales). Nuevo.

El principio rector: **Claude genera todo el contenido; tú controlas las decisiones de dinero y las publicaciones sensibles.** La automatización acelera lo repetitivo, no sustituye tu criterio en la compra ni en la negociación.

---

## 2. Flujo de trabajo completo (end-to-end)

El flujo tiene dos grandes mitades: **COMPRA** (decidir e importar) y **VENTA** (revender en España). El punto de parada obligatorio sigue siendo la confirmación antes de generar nada.

### FASE 0 — Captación
Ves un anuncio interesante (mobile.de, AutoScout24, etc.). Guardas capturas + enlace.

### FASE 1 — Valoración (COMPRA)
Pegas el anuncio aquí. Claude:
- Extrae la ficha del vehículo.
- Investiga en la web: averías típicas de ese motor, recalls pendientes, fiabilidad, comparables de mercado reales.
- Calcula la rentabilidad estimada: precio de compra + costes de importación (transporte, DGT, ITV, IEDMT según CO₂) + tu margen, frente al precio de venta realista en España.
- Marca las banderas rojas.

**→ PUNTO DE PARADA.** Claude presenta el resumen y espera tu "OK" explícito. Aquí decides **GO / NO-GO** (ver reglas en la sección 8).

### FASE 2 — Ficha de operación
Con tu OK, Claude genera el **Excel de la operación** (rellenado desde la plantilla maestra), lo recalcula y lo sube a Drive → carpeta *Vehículos exportación*. Este Excel es tu centro de control: checklists, presupuesto, cronograma, contactos y plantillas de mensaje.

### FASE 3 — Negociación y compra
- Claude genera los mensajes al vendedor en **alemán formal** (estado real, historial de mantenimiento / Scheckheft, COC, si el IVA es deducible/MwSt.).
- Registras respuestas y cierras precio.
- Checklist de documentación: contrato de compraventa (Kaufvertrag), COC, ficha técnica (Fahrzeugschein/-brief), justificante de pago.

### FASE 4 — Logística e importación
- Transporte (grúa o placas provisionales de exportación / Ausfuhrkennzeichen).
- Trámites en España: ITV de importación, homologación si hace falta, IEDMT (impuesto de matriculación), tasas DGT, matriculación.
- El cronograma del Excel te lleva paso a paso con fechas objetivo.

### FASE 5 — Preparación para la venta (VENTA)
Coche ya en España y matriculado (o en proceso). Claude genera **el paquete de venta** a partir de la ficha:
- **Folleto PDF** comercial (ver sección 4).
- **Textos de anuncio** por plataforma (ver sección 5).
- **Guiones y captions** para redes (ver sección 6).

### FASE 6 — Publicación y captación de clientes
Publicas (manual o semi-automático) en portales y redes. Claude puede prepararte todo el material y, según la vía elegida, ayudar a programar/publicar.

### FASE 7 — Cierre de venta y postventa
- Guion para presentar el coche al cliente español y justificar el precio.
- Contrato de venta, factura, cambio de titularidad.
- Registro del resultado real (margen final vs. estimado) → alimenta tu histórico.

---

## 3. Mejoras recomendadas a la plantilla Excel

La plantilla actual está bien estructurada. Estas mejoras suben el nivel sin complicarla:

**Rentabilidad y control de dinero**
- **Escenarios de precio de venta** (pesimista / realista / optimista) en vez de un solo valor, con el margen calculado para cada uno. Así ves el suelo antes de comprar.
- **Punto de equilibrio (break-even)**: precio mínimo de venta para no perder dinero, calculado automáticamente. Es tu línea roja en la negociación de venta.
- **Coste de oportunidad / días en stock**: una celda de "fecha de compra" y otra de "fecha de venta" para medir cuántos días tarda en venderse cada coche. El capital parado cuesta.
- **Margen real vs. estimado**: al cerrar la venta, apuntar cifras reales para comparar con la estimación inicial. Con 10-15 operaciones tendrás tus propios ratios y afinarás las estimaciones.

**Datos y trazabilidad**
- **VIN** como campo destacado (17 dígitos): sirve para recalls, historial y homologación.
- **Estado de la operación** (dropdown): Valorando / Comprado / En tránsito / En trámites / En venta / Vendido. Permite ver de un vistazo dónde está cada coche.
- **Checklist de fotos** para la venta (exterior 4 ángulos, interior, motor, cuentakilómetros, ruedas, detalles de desgaste): garantiza material homogéneo para el folleto y los anuncios.

**Vista de conjunto (nueva hoja "Panel")**
- Una hoja resumen con **todas las operaciones en una tabla**: modelo, estado, inversión, precio de venta objetivo, margen estimado, días en stock. Es tu cuadro de mando del negocio, no solo de un coche.
- KPIs del mes: nº de coches en stock, capital invertido, margen medio, días medios de venta.

**Fiscalidad**
- Bloque claro de **IVA**: IVA soportado en origen (si deducible), régimen aplicable en la reventa (general vs. **REBU** — Régimen Especial de Bienes Usados), para no llevarte sorpresas en el margen. *Nota: confírmalo con tu asesor fiscal; el REBU cambia mucho el cálculo real del beneficio.*

---

## 4. Nuevo entregable: folleto PDF de venta

Un PDF de una o dos páginas, tipo folleto de concesionario, generado automáticamente desde la ficha. Objetivo: que el cliente lo reciba por WhatsApp/email y "se enamore" antes de ver el coche, y que justifique el precio.

**Contenido recomendado del folleto:**
- **Cabecera**: marca + modelo + año, con la marca "JJ Import Motors".
- **Foto principal** (hero) + galería reducida.
- **Precio** destacado + etiqueta de estado (Disponible / Reservado).
- **Ficha técnica rápida**: km, combustible, cambio, potencia (CV/kW), tracción, puertas/plazas, color.
- **Equipamiento destacado** en iconos o lista corta (lo que vende: navegación, cuero, techo, faros LED, asistentes...).
- **Argumentario de venta** (3-4 bullets): por qué este coche merece la pena. Aquí se convierte la investigación en beneficio para el cliente ("importado de Alemania con historial de mantenimiento completo", "un solo propietario", "modelo valorado por su fiabilidad").
- **Garantía y procedencia**: importación transparente, documentación en regla, COC disponible.
- **Datos de contacto** + CTA claro ("Escríbeme por WhatsApp para verlo").
- **Pie legal**: "Fotos reales. Precio y disponibilidad sujetos a confirmación."

**Cómo se genera:** a partir de la misma ficha, sin volver a introducir datos. Formato profesional listo para enviar o imprimir. Se puede usar la skill de PDF con una plantilla de marca fija (colores, logo) para que todos los folletos sean coherentes.

**Recomendación de marca:** define una vez tu identidad (logo, 2 colores, tipografía) y todos los folletos y posts saldrán con la misma imagen. Da sensación de profesionalidad y confianza, que es justo lo que vende un coche importado.

---

## 5. Contenido mínimo de un anuncio (checklist por plataforma)

Todo anuncio, en cualquier portal, debería llevar como mínimo esto. Claude te lo genera adaptado a cada uno:

**Núcleo común (siempre):**
- Título claro: Marca Modelo Motor Año — argumento corto (ej. "1 dueño, full equip").
- Precio.
- Datos clave: km, combustible, cambio, potencia, año/matriculación.
- Descripción: estado, equipamiento, procedencia (importado de Alemania), historial de mantenimiento, qué se ha revisado.
- 8-15 fotos de calidad (exterior 360°, interior, motor, cuentakm, ruedas).
- Contacto y CTA.
- Ubicación aproximada.

**Ajustes por plataforma:**
- **Wallapop / Milanuncios**: tono cercano, título con gancho, palabras clave que la gente busca (modelo + "importado" + extras). Responder rápido; el algoritmo premia actividad.
- **Coches.net**: ficha técnica lo más completa posible, tono más formal/profesional, fotos impecables. Es el portal de referencia para compra seria de ocasión.
- **Instagram / Facebook**: menos ficha, más historia e imagen. Carrusel de fotos + reel corto. Hashtags locales y de modelo.
- **TikTok**: vídeo vertical, primeros 2 segundos con gancho, recorrido rápido del coche + detalle estrella. Formato "coche recién importado".

---

## 6. Automatización de publicaciones: qué es posible hoy (realidad julio 2026)

Aquí está la parte importante y hay que ser honesto, porque no todo lo que suena bien es viable ni recomendable. Separo **crear el contenido** (100% automatizable y donde está el mayor ahorro de tiempo) de **publicarlo** (automatización parcial y desigual según plataforma).

### 6.1. Crear el contenido — SÍ, totalmente automatizable
Claude puede generar por cada coche, en un solo paso: captions para Instagram/Facebook, guion + texto para TikTok, texto de anuncio para Wallapop/Milanuncios/coches.net, hashtags, y el folleto PDF. **Esto es el 80% del ahorro de tiempo y no depende de ninguna API externa.** Recomendación: empezar por aquí sí o sí.

### 6.2. Publicar automáticamente — depende de la plataforma

| Plataforma | ¿Publicación automática? | Cómo | Fricción / realidad |
|---|---|---|---|
| **Instagram** | Sí, vía API | Instagram Graph API (foto, carrusel, reel, stories) | Requiere cuenta **Business/Creator** + página de Facebook + app de Meta + **revisión de app (2-4 semanas)**. Límite 100 posts/24h. |
| **Facebook** | Sí, vía API | Misma API de Meta | Igual que Instagram; se configuran juntas. |
| **TikTok** | Sí, vía API | Content Posting API (Direct Post) | Requiere OAuth + **auditoría de la app**; hasta pasarla, los posts quedan **privados**. ~15-25 vídeos/día. |
| **YouTube (Shorts)** | Sí, vía API | YouTube Data API v3 | Cuota diaria; setup moderado. Opcional. |
| **Wallapop** | Solo profesional | **Wallapop Motor** + hub tipo Inventario.pro | No hay API abierta para vendedor pequeño. Requiere **contrato profesional Motor**. |
| **Milanuncios / coches.net** | Solo profesional | Feed/API vía DMS o hub multipublicación | Milanuncios: 2 anuncios gratis; más, vía coches.net profesional. Integración por hub. |

### 6.3. Vías recomendadas (según tu volumen)

**Realidad para un importador pequeño:** montar integraciones directas con las APIs de Instagram y TikTok es posible pero tiene semanas de configuración y revisiones. **No merece la pena al principio.** Hay caminos más listos:

- **Redes (IG, FB, TikTok, YouTube): usa un programador de publicaciones.** Herramientas como **Metricool, Publer, Buffer o Later** ya resolvieron el problema de las APIs y auditorías por ti. Claude te genera el contenido → tú lo pegas/programas en el planificador → se publica solo a la hora fijada, en todas las redes a la vez. Es el 90% del beneficio con el 10% del esfuerzo. Metricool tiene plan gratuito y va bien para autónomos.
- **Portales (Wallapop / Milanuncios / coches.net): dos opciones.**
  - **Ahora (bajo volumen):** publicación manual con el texto y fotos que Claude te prepara. 5 minutos por portal. Perfectamente viable con pocos coches al mes.
  - **Si creces (10+ coches/mes):** contratar un **hub de multipublicación / DMS** (Inventario.pro, dealcar.io, maxterauto y similares). Metes el coche una vez y lo publica en todos los portales a la vez, en tiempo real. Es una cuota mensual; solo compensa con volumen.

- **Evita los "bots" de Wallapop** (tipo renovadores automáticos no oficiales). Van contra las condiciones de uso del portal y arriesgan el bloqueo de tu cuenta. No merece la pena para un negocio serio.

### 6.4. Publicación automática end-to-end desde Claude
Si en el futuro quieres que Claude **publique directamente** (no solo genere), lo más realista es empezar por Instagram/Facebook vía Meta o por un conector del programador que uses. Se puede montar cuando lo decidas, pero **la recomendación es fase 2**: primero afianza generación de contenido + publicación manual/programada, y automatiza el posting cuando el volumen lo justifique.

---

## 7. El "paquete de operación" que genera Claude

Para estandarizar, cada coche que pasa el GO produce este paquete completo (todo desde la misma ficha, sin reescribir datos):

1. **Excel de operación** (control interno) → Drive.
2. **Folleto PDF de venta** (cliente).
3. **Textos de anuncio** para Wallapop, Milanuncios, coches.net.
4. **Captions + hashtags** para Instagram/Facebook.
5. **Guion de vídeo** para TikTok/Reels.
6. **Mensajes al vendedor** en alemán (fase compra).
7. **Guion de venta** para el cliente español.

Con decir "genera el paquete de venta de este coche", tendrías del 5 al 7 listos. Esto es lo que convierte tu operativa en un sistema repetible.

---

## 8. Lógica de negocio y reglas de decisión

Definir reglas claras evita comprar por impulso y hace el GO/NO-GO objetivo.

**Regla de margen (GO / NO-GO en la compra):**
- Fija un **margen mínimo objetivo** (ej. X € o X% sobre inversión total puesta en España). Si la estimación no lo alcanza en el escenario **realista**, es NO-GO por defecto.
- Si el escenario **pesimista** da pérdidas, NO-GO salvo que haya un motivo muy claro.

**Banderas rojas automáticas (NO-GO o investigación extra):**
- Precio muy por debajo de mercado sin explicación → posible problema oculto.
- Recall grave sin resolver.
- Avería conocida y cara típica de ese motor (ej. cadena de distribución, DSG, turbo según modelo).
- Muchos propietarios en poco tiempo.
- Vendedor profesional que oculta historial / sin COC.

**Reglas de pricing de venta:**
- Precio de salida = comparables de mercado España, ligeramente por encima para dejar margen de negociación.
- Precio suelo = break-even + margen mínimo. Nunca bajar de ahí.
- Revisar precio si el coche pasa de X días en stock (el capital parado cuesta).

**Reglas de caja:**
- No tener más de X coches / X € invertidos a la vez sin vender (según tu liquidez).
- Priorizar rotación sobre margen unitario si el capital es limitado: es mejor ganar menos por coche pero girar más veces.

---

## 9. Riesgos y cumplimiento

- **Fiscal (lo más importante):** el **IEDMT** depende del CO₂ homologado real (por eso el COC es crítico; sin él, el impuesto puede dispararse). El **IVA** en la reventa (general vs. REBU) cambia el margen real. **Confirma ambos con un asesor fiscal** antes de escalar; un error aquí se come el beneficio de varias operaciones.
- **Homologación / ITV de importación:** algunos modelos o extras pueden requerir homologación individual. Verificar antes de comprar coches "raros".
- **Condiciones de los portales:** publicar con bots no oficiales o multicuenta puede acarrear bloqueos. Usar siempre vías oficiales (profesional / hub).
- **RGPD:** si guardas datos de clientes (nombre, teléfono, email), hazlo de forma ordenada y con su consentimiento. El Excel de contactos ya debería tratarse con cuidado.
- **Fotos y textos reales:** nada de fotos de catálogo si vendes un coche concreto. Genera confianza y evita reclamaciones.

---

## 10. KPIs a seguir (cuadro de mando)

Con el histórico de operaciones podrás medir lo que de verdad importa:
- **Margen medio por coche** (estimado vs. real).
- **Días medios en stock** (velocidad de rotación).
- **Tasa de conversión de anuncios** (contactos → visitas → ventas).
- **Capital invertido vs. disponible.**
- **Coste real de importación medio** (para afinar futuras estimaciones).
- **Canal que más vende** (¿Wallapop? ¿coches.net? ¿Instagram?) para invertir esfuerzo donde funciona.

---

## 11. Roadmap de implementación por fases

**FASE 1 — Ahora (esta semana, coste 0):**
- Añadir a la plantilla: escenarios de precio, break-even, estado de operación, VIN, hoja "Panel". (Mejoras de la sección 3.)
- Crear la plantilla de **folleto PDF** con tu marca.
- Estandarizar el **paquete de contenido** (que Claude genere los textos de todas las plataformas de una vez).
- Definir tus **reglas de margen y banderas rojas** (sección 8) para que el GO/NO-GO sea objetivo.

**FASE 2 — Corto plazo (1-2 meses):**
- Definir identidad de marca (logo, colores) y aplicarla a folleto + posts.
- Empezar a publicar en redes con un **programador** (Metricool/Publer): Claude genera, tú programas.
- Llevar el **histórico** de operaciones reales para afinar estimaciones.

**FASE 3 — Cuando haya volumen (10+ coches/mes):**
- Valorar un **hub de multipublicación / DMS** para portales.
- Valorar **publicación automática directa** en Instagram/TikTok vía API o conector.
- Automatizar el **panel de KPIs** (informe mensual).

**Principio general:** automatiza en este orden → 1º generación de contenido, 2º programación en redes, 3º publicación directa y hub de portales. No inviertas en lo caro/complejo hasta que el volumen lo pida.

---

## Resumen ejecutivo

Ya tienes lo más difícil montado (valoración + Excel + Drive). Lo que más valor añade ahora, con coste casi cero: **el folleto PDF**, el **paquete de contenido multiplataforma** generado de una vez, y **reglas de decisión claras**. La publicación en redes se resuelve mejor con un **programador** (no con integraciones de API caras) y los portales, de momento, a mano con el material que Claude prepara. Automatización directa y hubs: cuando el volumen lo justifique, no antes.
