# JJ Import Motors — Plan del ecosistema digital

**Documento de planificación** · Julio 2026
Para implementar por fases, con tu OK. Reúne: rigor legal/fiscal de la calculadora, mejoras de las fichas y el panel, un módulo de finanzas con gráficas, la arquitectura profesional del sistema y un roadmap.

> Nota: las partes fiscales y legales son orientativas y deben validarse con un gestor/asesor y un abogado. Aquí se documenta el criterio para que el sistema sea lo más fiel posible.

---

## 1. Rigor legal y fiscal (que la calculadora sea "de ley")

### 1.1. IEDMT (impuesto de matriculación) — cómo hacerlo exacto
- **Tramos por CO₂ (oficiales, península):** 0 % (<120 g/km) · 4,75 % (120–159) · 9,75 % (160–199) · 14,75 % (≥200). *Canarias, Ceuta y Melilla tienen tipos propios.*
- **Base imponible:** NO es el precio de compra. Es el **valor de mercado** = **precio medio de venta oficial del modelo (tabla BOE, Orden anual de Hacienda) × coeficiente de depreciación por antigüedad** (100 % <1 año … 10 % >12 años).
- **Estado actual del panel:** usa la depreciación oficial, pero con un "precio nuevo" aproximado que introducimos a mano. **Mejora a implementar:** que en cada evaluación yo **busque el valor BOE real** del modelo/versión/año y lo cargue como base. Así el IEDMT sale prácticamente clavado.
- **Dato crítico:** el CO₂ homologado exacto sale del **COC**. Sin él, Hacienda puede aplicar el tramo alto. La ficha debe marcarlo como "pendiente COC" hasta confirmarlo.
- **Liquidación:** modelo **576**, en los **30 días** siguientes a la entrada en España.

### 1.2. IVA — los dos escenarios (y por qué normalmente no se paga dos veces)
- **Caso habitual (coche usado a particular o con Differenzbesteuerung §25a — IVA de margen):** el IVA ya está pagado en origen, **no se desglosa ni se deduce, y no se vuelve a pagar en España**. Solo se paga el IEDMT. Es el caso más común y el más simple.
- **Caso B2B con NIF intracomunitario:** si se comprara con factura sin IVA alemán, se **autoliquida** en España (adquisición intracomunitaria). Cambia el circuito fiscal.
- **En el servicio:** tu tarifa de servicio sí lleva IVA español (21 %) y la declaras (303/390). El coche lo importa el cliente.
- **A implementar en la ficha:** un selector "IVA del coche: margen / deducible / particular" que documente el caso y ajuste notas (no cambia el IEDMT, pero deja claro el escenario para el gestor).

### 1.3. Trámites de importación (secuencia y documentos)
Alemania: Kaufvertrag, COC, Fahrzeugbrief/Fahrzeugschein, **Abmeldung + Ausfuhrkennzeichen** (matrícula de exportación, válida hasta 12 meses; con Grenzversicherung 30–60 €) o transporte en camión (900–1.200 €).
España (orden importa): **ITV de importación** → homologación si aplica → **IEDMT (mod. 576)** → tasas DGT → **matriculación** → IVTM. Plazo típico 2–4 semanas.

### 1.4. Responsabilidad legal (recordatorio)
Actúas como **gestor de importación**, no como vendedor (define tu exposición a garantía). En el canal catálogo, dejar explícito en anuncio y contrato. Validar con abogado (ver documento maestro).

### 1.5. Qué automatizo yo por coche
Búsqueda del valor BOE del modelo, confirmación de tramos vigentes, comparables de mercado reales, recalls, y el desglose fiscal. Tú aportas el CO₂ del COC cuando lo tengas y validas el circuito con gestor una vez.

---

## 2. Mejoras de la ficha del vehículo y del panel

**Ficha "Vehículo":**
- **VIN (nº de bastidor)** destacado + botón a informe de historial (tipo Carvertical/CarVertical/autoDNA) para km reales, siniestros y procedencia.
- **Resumen del Scheckheft / historial de mantenimiento** y garantía de fábrica restante.
- **Fotos categorizadas** (exterior / interior / motor / detalles / defectos) y soporte de **vídeo**.
- **Distancia origen→destino** calculada, para **estimar el transporte automáticamente** (km × tarifa €/km).
- **ITV/HU alemana** (fecha) y estado.

**Números / valoración:**
- **Base IEDMT desde BOE** (sección 1.1).
- **Semáforo automático** (verde/ámbar/rojo) calculado por el margen del total vs. precio medio de comparables.
- **Precio de venta sugerido** y margen (por si algún día haces stock, modelo B).
- **Comparables con enlaces reales** y media automática.

**Operativa:**
- **Adjuntar documentos** por coche (COC, contrato, ITV) — enlaces a Drive.
- **Historial de estados con fechas** → permite medir días en cada fase (KPIs).
- **Botón "Generar" por coche:** folleto PDF, anuncio y posts, desde la ficha (ya tenemos el motor; falta integrarlo en el panel).
- **Vista Kanban** por estado (columnas Localizado→…→Entregado), además de la lista.

---

## 3. Módulo de finanzas de la empresa (con gráficas)

Un apartado nuevo del panel para llevar el dinero del negocio, no solo por coche sino a nivel empresa.

**Qué registra:**
- **Ingresos:** honorarios por operación (real vs. estimado), y otros.
- **Gastos:** transporte, peritaje, desplazamientos, gestoría, publicidad, cuota de autónomo, herramientas/software, dietas, etc. Con **categorías**.
- **Vinculación con cada coche:** cada operación aporta su ingreso y sus gastos reales, para comparar con lo estimado en su ficha.
- **Caja:** entradas/salidas y saldo.

**Gráficas (con Chart.js):**
- Ingresos vs. gastos por mes (barras).
- **Beneficio neto mensual** (línea).
- **Gastos por categoría** (tarta).
- Nº de operaciones/mes y **beneficio medio por coche**.
- **Días medios en stock/proceso** (velocidad de rotación).
- Margen estimado vs. real.

**Salidas útiles:**
- Resumen para el **asesor** (trimestral: base IVA repercutido, gastos deducibles).
- KPIs del mes en una tarjeta (facturación, beneficio, nº operaciones, conversión de leads).
- Export a Excel/CSV.

**Cómo se guarda:** igual que los coches — un `finanzas.json` (o una hoja en la Google Sheet). Ligero y suficiente.

---

## 4. Arquitectura profesional (cómo lo hacen los del sector)

Los profesionales usan un **DMS (Dealer Management System)**: gestionan stock, CRM, multipublicación en portales y contabilidad (ej. Inventario.pro, dealcar, maxterauto). Nosotros construimos una **versión ligera y a medida del servicio de importación**, sin pagar cuotas hasta que el volumen lo pida.

**Almacenamiento de datos (evolución recomendada):**
1. **Ahora (test):** `coches.json` + `finanzas.json` en el navegador (localStorage) + respaldo a Drive.
2. **Siguiente:** **Google Sheet como base de datos** (ya usas Drive; la editas en Sheets o desde el panel; funciona en el móvil). Punto dulce simple/pro.
3. **Escala:** backend real (Supabase/Firebase, plan gratis) con app alojada — multi-dispositivo y en tiempo real.

**Alojamiento (hosting):**
- Local (archivo) para probar → **Netlify/Cloudflare Pages (gratis)** para tenerlo online con enlace → dominio propio `jjimportmotors.com` (~10–15 €/año) cuando quieras imagen.

**Integración de Claude (yo):**
- **Ahora:** flujo de chat — pegas enlace → yo evalúo (anuncio + fotos + BOE + comparables + cálculo) → te devuelvo el coche en JSON para el panel, o te actualizo el `coches.json` de Drive.
- **Después:** botón en el panel que me dispara la evaluación; **tareas programadas** (recordatorios de seguimiento de leads, informe mensual de KPIs, alertas).
- **Límite honesto:** el "scraping" y la investigación siguen ejecutándose en un turno mío; el panel los dispara, no los hace solo.

**Copias de seguridad:** export automático del JSON a Drive (para no perder datos del navegador).

**Privacidad (RGPD):** los datos de clientes (nombre, DNI, teléfono) se guardan con cuidado, base legal y sin compartir.

---

## 5. Otras ideas para profesionalizar y agilizar

- **Informe de historial del vehículo** (Carvertical/autoDNA) integrado en la ficha: km reales, siniestros, procedencia. Muy vendedor y anti-fraude.
- **Estimación de transporte por distancia** automática (tabla €/km o API de rutas).
- **Alertas de búsquedas guardadas** de mobile.de/AutoScout → me pasas los nuevos y los pre-valoro.
- **Generación en lote** de anuncio + posts + folleto desde la ficha, con un botón.
- **Calculadora pública** en la web (para clientes: "¿cuánto te costaría importar este coche?") → capta leads.
- **CRM de clientes** ligado a coches (quién preguntó por qué coche).
- **Firma digital de contratos** (cuando legalices).
- **Multi-idioma** en la web pública (ES/EN, y DE para vendedores).
- **Panel de KPIs mensual** automático (tarea programada que te lo manda).

---

## 6. Roadmap de implementación (orden sugerido)

**FASE 1 — Rigor y fichas (rápido, alto valor):**
- Calculadora con **base BOE real** por coche + selector de escenario de IVA.
- Semáforo automático por margen; comparables con enlaces; estimación de transporte por distancia.
- VIN + adjuntos + historial de estados con fechas.

**FASE 2 — Finanzas con gráficas:**
- Módulo de ingresos/gastos por operación y empresa + gráficas (Chart.js) + KPIs + export para asesor.

**FASE 3 — Centralizar y alojar:**
- Pasar la base de datos a **Google Sheet** (o backend) + **respaldo automático a Drive** + **hosting** (Netlify) para usarlo desde el móvil.

**FASE 4 — Automatizar:**
- Botón "evaluar enlace" que me dispara; **tareas programadas** (seguimiento de leads, informe mensual de KPIs); generación en lote de contenido.

**FASE 5 — Extras pro:**
- Informe de historial, calculadora pública para clientes, CRM, firma digital, multi-idioma.

**Qué haces tú vs. qué hago yo:**
- **Tú:** decidir hosting/dominio, crear las cuentas (Netlify/Sheet), validar el circuito fiscal con un gestor una vez, y darme los enlaces de coches.
- **Yo:** construir el panel y los módulos, investigar BOE/comparables/recalls por coche, generar contenido y documentos, y mantener el JSON/base de datos.

---

## 7. Estado actual (lo que ya funciona hoy)
- Panel con fotos, mapa, estados, buscador y filtros.
- Ficha por pestañas tipo Excel (Resumen, Vehículo, Números, Inspección/Documentación por módulos, Cronograma, Contactos).
- Calculadora IEDMT con **depreciación oficial por antigüedad** (falta enchufar el valor BOE exacto por coche — Fase 1).
- Calculadora rápida en el header.
- Biblioteca de mensajes ES/EN/DE con respuestas a objeciones.
- Coche de test real (Opel Astra OPC) valorado.

*Cuando des el OK, empezamos por la Fase 1. Este documento se irá actualizando a medida que avancemos.*
