# Changelog

Todos los cambios notables en el skill `estudio-mercado` se documentarán en este archivo.

El formato está basado en [Keep a Changelog](https://keepachangelog.com/es-ES/1.0.0/),
y este proyecto adhiere a [Semantic Versioning](https://semver.org/lang/es/).

## [0.3.8] - 2026-08-23 — Flujo ESTRICTO: la nube no improvisa, SI/ENTONCES + checklist obligatorio

> **Motivo:** la IA recibía zonas con "si conviene, si hay muestra, si el usuario lo pide, opcionalmente…" y se salía del flujo. **Esto se acabó.** Cada decisión de 2 formas está resuelta de antemano; la nube NO consulta, NO propone alternativas, NO improvisa.

### 🔒 Reglas estrictas SI/ENTONCES (`informe_mercado.md`)
- **NUEVO §REGLAS ESTRICTAS SI/ENTONCES**: 10 condiciones con comportamiento obligatorio. Cubre: desglose por variables, comparables, formato, gastos fijos, IVA/IEDMT, fiabilidad, cobertura incompleta, prevalencia del JSON, secciones obligatorias, peticiones explícitas.
- **NUEVO §CHECKLIST OBLIGATORIO ANTES DE ENTREGAR**: 3 bloques (Estructura / Datos / Archivos) con ✅/❌ que la nube DEBE rellenar y mostrar.
- **NUEVO §ESTRUCTURA OBLIGATORIA**: tabla con las 10 secciones en orden fijo. Reordenar/fusionar está prohibido.
- **NUEVO §CHECKLIST en el propio informe**: al final del informe la nube autocompleta con ✅/❌ cada punto (estructura, datos, archivos, mensaje de cierre).

### ⛔ FASE 5 rígida en `SKILL.md`
- Renombre FASE 4 ("GUARDAR el mapa") y FASE 5 ("GENERAR EL INFORME") para que estén separadas.
- 8 prohibiciones explícitas en FASE 5 (inventar datos, mezclar recordados, decidir formato/gastos, saltarse secciones, cambiar orden, jerga IA, PDF/ZIP sin pedir, decir "sincronizado" sin verificar).

### 📚 `como_deben_ser_las_sesiones.md`
- **NUEVO §REGLAS DE ORO**: tabla con 6 comportamientos obligatorios + 10 prohibiciones. Si hay duda no resuelta → PARAR y preguntar UNA sola vez.

---

## [0.3.7] - 2026-08-23 — "Puesto en Huelva" + ahorro real con gastos fijos de 1.500 €

> **Motivo:** el usuario pidió ver de un vistazo cuánto le costaría el coche **puesto en casa**, no el cálculo técnico del `hueco_neto_pct` (que incluye IEDMT variable por CO₂). Para decidir si merece la pena importar, basta con una cifra redonda de gastos fijos.

### 📄 Informe (`informe_mercado.md`)
- **NUEVA columna "Puesto en Huelva"** en la tabla resumen y en las 4 tablas de candidatos. `puesto_huelva = precio_alemania + 1.500 €` (1.000 € transporte + 200 € ITV + 300 € gestoría/ausfuhr).
- **NUEVA columna "Ahorro real"** = suelo España − puesto en Huelva. Es lo que el usuario se ahorra de verdad.
- **NUEVA sección "💶 DESGLOSE DE LOS 1.500 € DE GASTOS FIJOS"** al final, antes de la metodología. Explica qué incluye (transporte, ITV, gestoría) y qué NO (IVA importación, IEDMT), con orden de magnitud realista (+3.500 € a +5.500 € "todo incluido").
- **Resumen para copiar actualizado** con los nuevos números "puesto en Huelva" para WhatsApp/notas.

### 📚 Reglas actualizadas
- `SKILL.md` §Cálculo del hueco: nueva subsección "💶 GASTOS FIJOS ESTIMADOS PARA EL INFORME" con la regla, fórmula, qué se incluye y qué no.
- `como_deben_ser_las_sesiones.md` §REGLAS DE ENTREGA punto 9: cifra de 1.500 € por defecto, recalcular si el usuario pide otra cifra.

---

## [0.3.6] - 2026-08-23 — Informe para el usuario: desglose por variables, comparables, resumen para copiar

> **Motivo:** el informe estaba escrito con jerga IA (sincronización, merge, fuente_medicion, bloque de volcado JSON) y el usuario tenía que interpretar términos técnicos para decidir. El informe es **para Jacar**, no para otra IA.

### 📄 Informe (`informe_mercado.md`) reescrito de arriba abajo
- **Tono de persona, no de IA.** Sin "sincronizado", "merge", "volcado", "fuente_medicion". Palabras de negocio.
- **NUEVA sección obligatoria "📊 DESGLOSE POR VARIABLES"**: puertas (3p/5p), cambio (manual/DSG), techo solar y cuadro digital se cruzan con precios reales (cuántos hay + prima +/−). Cuando no hay muestra suficiente se dice en 1 línea y se omite la tabla.
- **NUEVA sección "🧩 COMPARABLES"**: cruza el estudio actual con modelos ya medidos antes (ej. "Astra OPC julio = 30% de hueco, este Golf R = 22%, pero mercado 6× más grande"). Evita tener que abrir 3 informes para poner el dato en contexto.
- **NUEVA sección "📋 RESUMEN PARA COPIAR"**: 1 párrafo autocontenido al final, sin enlaces, listo para WhatsApp/nota/WhatsApp al socio.
- **Reordenado**: CONCLUSION primero, METODOLOGÍA al final (ya estaba, ahora sin BLOQUE DE VOLCADO JSON).
- **Quitado el "📦 BLOQUE DE VOLCADO" JSON.** La nube no tiene acceso al disco del usuario; ahora la última línea dice literalmente: *"Archivo: `informes/mercado/<archivo>.md`. Pásale este MD a Copilot en VS Code y dile 'importa este MD al mapa'."*

### 📚 Reglas de entrega actualizadas (`como_deben_ser_las_sesiones.md`)
- Punto 5: mensaje final humanizado, sin jerga técnica.
- Punto 6 (nuevo): **desglose por variables obligatorio** aunque el usuario no lo pida (ahí se ve el valor real).
- Punto 7 (nuevo): **resumen para copiar** obligatorio.
- Punto 8 (nuevo): **comparables con estudios anteriores** obligatorios.

### 🔧 `SKILL.md` §Output
- Mismas 8 secciones obligatorias documentadas en la skill principal.
- Aclarado que la nube **NO escribe** en el disco del usuario (no tiene acceso).

---

## [0.3.5] - 2026-08-23 — Auditoría flujo de volcado: contrato nube→local + fixes críticos

> **Motivo:** auditoría independiente del flujo investigación→informe→volcado detectó 3 críticos que falseaban datos en `datos_mercado.json` y en el bucle skill↔SaaS.

### 🔴 Fixes críticos
- **`market:export` ya no borra la cola de trabajo** (Laravel): preserva `cola_trabajo`, `hueco_sin_banda`, `notas_metodologicas`, `candidatos_pendientes_de_estudio`, `alcance_pasada`, `costes_referencia`, `contexto_macro` del JSON existente al exportar desde la BD — antes un `market:export` los sobrescribía en silencio.
- **Golf R duplicado resuelto**: `vw-golf-r` (agregado, 🟡 obsoleto) marcado con `reemplazado_por: "vw-golf-75-r"` (nuevo campo, documentado en el schema) para que el SaaS y el enrutador no muestren dos veredictos contradictorios del mismo modelo.
- **`estado_cola` desincronizado corregido**: `vw-golf-75-r` tenía 3 fuentes contradictorias (objeto/cola/nota). Ahora consistente: `pendiente_busqueda` en los 3 sitios.
- **Mojibake reparado**: 12 campos con UTF-8 doble/triple codificado (`vehÃculos`, `â‚¬`, `Ãƒâ€š...`) causado por ediciones repetidas con PowerShell. Reparado con `iconv` iterativo (CP1252↔UTF-8) validando integridad numérica contra backup — 0 restantes, 0 pérdida de datos.

### 📄 Contrato nube→local
- **`informe_mercado.md`**: nueva sección obligatoria "📦 BLOQUE DE VOLCADO" — 1 objeto JSON por variante con los campos mínimos del schema, al final del informe. Cierra el volcado mecánico sin reinterpretar el informe.
- **Advertencia "la nube nunca afirma sincronizado"**: Claude Desktop no puede verificar el JSON del Desktop del usuario — el informe debe decir "pendiente de fusión" en vez de "sincronizado" (evita la falsa sensación de sincronía vista en el informe del Golf R).
- **`MarketModel::FUENTES_MEDICION`** ahora incluye `mini_estudio` (ya estaba en el schema, faltaba en el modelo Eloquent).

### 🛠️ Reglas nuevas documentadas
- **Regla mojibake** en `schema_datos_mercado.md`: preferir PHP sobre PowerShell para editar el JSON (no reinterpreta bytes); reparación con `iconv` iterativo si ya ocurrió.

## [0.3.4] - 2026-08-23 — Plantilla informe: suelo de listado vs verificado

- **`informe_mercado.md`**: nueva nota "🏷️ Fiabilidad de cada suelo" — cada precio lleva ✅ (verificado en ficha) o 👁️ (de listado, pendiente). El suelo oficial es el ✅ más bajo; los 👁️ se anotan aparte como posibles suelos inferiores.
- Encaja con el método oficial de Coches.net por URL (importacion-vehiculos v3.3.4).

## [0.3.3] - 2026-08-23 — Plantilla de informe de mercado al grano + regla MD vs PDF

> **Motivo:** el informe de estudio (ej. Golf 7.5) se generaba con estructura ad-hoc: 10 secciones metodológicas, la conclusión al final, candidatos "a ver" escondidos en tablas largas y duplicación MD+PDF (enlaces muertos en PDF).

### 📄 Informe
- **NUEVO `informe_mercado.md`** — plantilla obligatoria del informe de estudio, estructurada como documento de decisión (1 minuto de lectura):
  1. 🏁 CONCLUSIÓN (resumen + tabla por variante con hueco bruto/neto + veredicto) — lo primero
  2. 🎯 CANDIDATOS A VER (1-2 por variante, URL visible completa, por qué merece la pena)
  3. 📊 POR VARIANTE (3-5 líneas c/u; la segmentación solo si afecta al precio)
  4. ⚠️ AVISOS/TRAMPAS (solo las que cambian la decisión)
  5. 📋 COBERTURA/METODOLOGÍA (al final, no al principio)
- **Regla MD vs PDF:** SIEMPRE Markdown (fuente, enlaces clicables). PDF solo si el usuario lo pide explícitamente; en el PDF la URL va visible porque los enlaces no funcionan. NUNCA MD+PDF por defecto.
- **SKILL.md**: §Output referencia la plantilla obligatoria + §MEJORAS #1 actualizada.

## [0.3.2] - 2026-08-21 — Fixes de auditoría (C1-C4 + medios)

- **schema**: estado `estudiando` añadido al enum (sesión corta/interrumpida) · regla 6 de merge de la cola entre sesiones (E10, solo tocar lo de esta sesión) · `estado_cola` degradado a ⚠️ (fuente de verdad = `cola_trabajo.estados`) · ejemplo de `cola_trabajo` corregido (`cupra-leon=pendiente_estudio`).
- **SKILL.md**: L3 ampliado (medianas las escriben estudio/flujo_b/**mini_estudio**) · §Métricas #13 enum con `mini_estudio`.

## [0.3.1] - 2026-08-21 — Refinado tras dry-run: transiciones por fuente + enum mini_estudio

- **schema_datos_mercado.md**: `fuente_medicion` añade `mini_estudio` (medición inline del Flujo B, confianza 2-3) · nueva tabla "Estado resultante según quién mide" (estudio→estudiado · mini_estudio→estudiado · flujo_b→buscado · flujo_a→buscado/pendiente_estudio · flujo_e_delta→estudiado).
- **`datos_mercado.json`**: `cola_trabajo` inicializada con 35 modelos de los 6 segmentos y `siguiente_estudio=vw-golf-75-tcr`.

## [0.3.0] - 2026-08-21 — Pipeline conjunto con importacion-vehiculos (modelo por modelo)

> **Motivo:** la búsqueda por segmentos de golpe ("Compactos deportivos") durante 3 días no dio resultado (límite 5h, unidades que no encajan). El estudio debe ir modelo por modelo.

### 🔄 Pipeline conjunto
- **SKILL.md §PIPELINE CONJUNTO**: 1 modelo por pasada (ES + DE + cruce), PARADA obligatoria entre modelos, feedback bidireccional con `importacion-vehiculos` (vuelca mediciones reales con `fuente_medicion: flujo_b`).
- **schema_datos_mercado.md §Cola de trabajo**: campo `cola_trabajo` en el JSON (estados + enrutador `siguiente_*`) y campo `estado_cola` en cada modelo. Estados: `pendiente_estudio` → `estudiado` → `pendiente_busqueda` → `buscado` → `descartado`.
- **Referencia al MD maestro** `../importacion-vehiculos/02-flujos/como_deben_ser_las_sesiones.md` (formato de sesión obligatorio).
- **SKILL.md**: bump 0.2.1 → **0.3.0**.

## [0.2.1] - 2026-08-18 — Regla Seat/Cupra corregida + equipamiento máximo
- Regla Seat/Cupra: la nacionalidad NO es criterio; mirar suelo sin banda (Cupra DE 15.500 vs ES 19.500).
- Equipamiento máximo por defecto (5 checkboxes full en mobile.de ES, proxy techo en Coches.net).
- Selectores data-testid estables de mobile.de ES documentados.
