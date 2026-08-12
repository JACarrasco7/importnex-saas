---
name: importacion-vehiculos
description: >
  Negocio JJ Import Motors (Huelva): importar coches de Alemania sin stock,
  cobrando honorarios. Tres flujos: UNIDAD (URL concreta), MODELO (buscar un modelo),
  MERCADO (escanear oportunidades). Usa 7 fuentes. Genera ZIP para Laravel.
triggers:
  - evalúa este coche
  - evalúa este anuncio
  - mira este mobile.de
  - busca golf gti
  - busca modelo
  - qué hay de mercedes
  - que merece la pena
  - qué oportunidades hay
  - escanea el mercado
  - dime 10 modelos rentables
  - busca para cliente
  - cliente quiere un coche
  - calcular coste importacion
  - iedmt
  - precio maximo de compra
---

# Importación de vehículos UE → España — JJ Import Motors

Localizar coches en la UE y **ofertar su importación** a clientes españoles. Sin stock. Honorarios.

> 📁 **Compañeros:** `extractores.md` (scraping, URLs, trampas) · `contrato.md` (JSON + esqueleto) · `operaciones.md` (carpetas, scripts) · **`anti_patrones.md`** (reglas duras 6)
> 
> 📚 **Módulos especializados:** `comparables.md` (ajuste 9 claves) · `costes.md` (IEDMT + desglose) · `riesgos.md` (motores problemáticos) · `operaciones_cierre.md` (cierre + KPIs + sync)

---

## ⚡ REFERENCIA RÁPIDA

```
Umbrales objetivo: Nicho ≥10% | Rotación ≥10% | Tramo 8-14k ≥12%
Umbrales mínimos (EXIT 3): Nicho 8% | Rotación 10% | Tramo 8-14k 12%
Costes fijos: ver `costes.md` (transporte + ausfuhr + ITV + honorarios) — §1.4 single source of truth
Costes fijos: Transporte 900€ + Ausfuhr 114€ + ITV 115€ + Honorarios 1.500-2.250€
Fuentes: 7 (Wallapop, Milanuncios, Coches.net, mobile.de, AS24.de, AutoUncle, kleinanzeigen.de)
Trampas top 3: countryCode SIEMPRE | __INITIAL_PROPS__ esperar hidratación | mobile.de directo NUNCA saltar
Anti-patrones bloqueados: 6 (ver §Anti-patrones)
Checkpoints: CP1 tras criba | CP2 tras comparable | CP3 antes de veredicto
```

---

## 🎯 LOS 3 FLUJOS — leer PRIMERO

| Flujo | Disparador | Profundidad | Output | ZIP Laravel |
|---|---|---|---|---|
| **A: UNIDAD** | URL pegada o "evalúa este" | Fase 1 + Fase 2 | Informe completo 11 secciones | ✅ Sí |
| **B: MODELO** | "busca [modelo]" sin URL | Fase 1 + Fase 2 si pasa | Informe MODELO + top 5 | ❌ No |
| **C: MERCADO** | "qué merece la pena", "top modelos" | Solo Fase 1, N modelos | Informe BUSQUEDA | ❌ No |

### Detección automática de flujo

```
¿Hay URL en el input?
├── SÍ → FLUJO A (UNIDAD)
├── NO ↓
¿Hay modelo+versión concreto?
├── SÍ → FLUJO B (MODELO)
├── NO ↓
→ FLUJO C (MERCADO) — preguntar preferencias al usuario
```

En caso de duda: **preguntar antes de gastar tokens**.

---

## ⛔ ARRANQUE OBLIGATORIO — Tabla de cobertura 7 fuentes

> Fallo real 10-ago-2026: se midieron 2 fuentes, se dieron 5 candidatos y veredicto. Un anuncio de 8.999 € en mobile.de ganaba por >3.000 €.

**Rellenar ANTES de cualquier candidato, mediana o veredicto:**

| # | Fuente | País | Fase 1 (sondeo) | Fase 2 (profunda) |
|---|---|---|---|---|
| 1 | Wallapop | ES | ❌ | ✅ |
| 2 | Milanuncios | ES | ❌ | ✅ |
| 3 | Coches.net | ES | ✅ | ✅ |
| 4 | mobile.de | DE | ✅ | ✅ |
| 5 | AutoScout24.de | DE | ❌ | ✅ |
| 6 | AutoUncle | DE | ✅ | ✅ |
| 7 | kleinanzeigen.de | DE | ❌ | ✅ |

**Flujo A y B:** Fase 1 rellena 3 filas (Coches.net, mobile.de, AutoUncle). Fase 2 rellena las 4 restantes.
**Flujo C:** Solo Fase 1 por modelo (3 filas). No hay Fase 2.

Estados: `OK` · `0 resultados` · `bloqueada (motivo + intentos)` · `sin extractor`. Fuente sin cubrir → informe marcado **PARCIAL**.

**3 reglas duras:**
1. **No parar al tener candidatos.** Se recorren las que apliquen y luego se ordena.
2. **Fuente bloqueada → reintentar.** mobile.de: `www.` → `web_fetch` ficha → `web_fetch` listado → solo entonces bloquear.
3. **Método degradado se declara.** Si Coches.net pierde `__INITIAL_PROPS__` y se lee texto visible, decirlo.

Para Alemania, orden: mobile.de directo → AutoScout24.de directo → AutoUncle (NUNCA única) → kleinanzeigen.de.

---

## 🔄 FASES — Sistema de dos pasadas

```
FASE 1: SONDEO RÁPIDO                    FASE 2: INVESTIGACIÓN PROFUNDA
Solo Coches.net + mobile.de + AutoUncle  Las 4 que faltan
Solo precios, año, km, versión          Fichas mobile.de, descripciones
15-20 peticiones                        30-40 peticiones
Objetivo: test de hueco (≥15%)          Objetivo: veredicto completo
```

### Token budget consciente (ANTES de empezar)

Al detectar flujo, declara al usuario el presupuesto estimado:

| Flujo | Fase 1 | Fase 2 | Total máx |
|---|---:|---:|---:|
| **A: UNIDAD** | 15-20 | 35-50 | 70 |
| **B: MODELO** | 15-20 | 20-30 | 50 |
| **C: MERCADO** | 12-18 por modelo | — | 100 (7 modelos) |

**Frase de apertura:**
> "Esto gastará ~{N} peticiones. ¿Procedo?"

En Flujo C con muchos modelos, avisar cuando se supera 50% del budget.

### Contador de peticiones (§2.4 — trackear en tiempo real)

Llevar cuenta mental de peticiones por fuente durante la sesión. Avisar al usuario al llegar a umbrales:

```
Contador manual:
  mobile.de:   X / 45 (avisar a 35 = 78%)
  AutoScout24: X / 36 (6 llamadas de 12 máx, pausa 0.7s)
  Coches.net:  X / 35
  Resto:       X / 20

Avisar al 50% del budget total → "Vamos por ~{N} peticiones de {máx}. ¿Sigo?"
Avisar al 80% del budget total → "Ya vamos por {N}. Si no hay hueco claro, paramos."
```

**Reglas:**
- **Flujo A:** total máx 70. Avisar a 35 (50%) y a 56 (80%).
- **Flujo B:** total máx 50. Avisar a 25 (50%) y a 40 (80%).
- **Flujo C:** total máx 100 (7 modelos). Avisar al 50% (50) y al 80% (80).
- **mobile.de:** NUNCA >45 en una sesión. Avisar a 35 (regla dura, ver extractores.md §Presupuesto).

**Si se supera el budget sin veredicto:** STOP. Mostrar resumen parcial + preguntar si invertir más o cerrar como PARCIAL.

### Early exits (ABORTAR si se cumple)

| Exit | Condición | Acción |
|---|---|---|
| **EXIT 1** | Hueco <8% O <3 comparables ES | Informe rápido. "No sale." Actualizar `datos_mercado.json`. FIN. |
| **EXIT 2** | Hueco 8-15% | "Justo. ¿Invierto en Fase 2?" PREGUNTAR. |
| **EXIT 3** | Margen < umbral mínimo (Nicho 8%, Rotación 10%, 8-14k 12%) | Informe reducido sin publicidad. Entre umbral mínimo y objetivo (ej: Nicho 8-10%), avisar "margen justo, posible si vendibilidad ≥70". |

### Priorización por ROI (Flujo B y C)

Cuando hay >3 modelos sin medir, aplicar scoring automático **antes** de empezar:

```
PRIORIDAD = MargenEstimado × VendibilidadEstimada × Urgencia
```

| Factor | Cálculo | Ejemplo |
|---|---|---|
| **MargenEstimado** | Ratio histórico del segmento | Nicho: 18%, Rotación: 10% |
| **VendibilidadEstimada** | Atractivo del modelo | Deportivo: 80, Premium: 60, Utilitario: 40 |
| **Urgencia** | ¿Hay cliente esperando? | Cliente concreto: 100, Sin cliente: 30, >1 mes sin medir: +20 |

**Tabla de priorización:**

| Modelo | Segmento | Margen est. | Vend. est. | Urgencia | PRIORIDAD |
|---|---|---:|---:|---:|---:|
| Golf 8 GTI CS | Nicho | 18% | 90 | 50 | **8.100** |
| Mercedes CLA | Rotación | 10% | 65 | 110 | **7.150** |
| BMW M240i | Nicho | 18% | 85 | 30 | **4.590** |
| Volvo XC60 T8 | Nicho | 15% | 70 | 30 | **3.150** |

**Regla:** Antes de cada sesión, puntuar los "sin medir" y proponer el top 3 al usuario.

### Deduplicación entre fuentes

**Problema:** Mismo coche en Wallapop y Milanuncios cuenta 2 veces. Infla la muestra.

**Solución:** Normalizar antes de contar:

```
Para cada coche en el pool:
  huella = (año, km_redondeado(±2%), cv, precio_redondeado(±3%), combustible)

Si huella ya existe → es duplicado → contar 1 vez, anotar fuentes
```

**Output:** "8 coches únicos en España (12 anuncios contando duplicados: 4 en Wallapop, 5 en Milanuncios, 3 en Coches.net)"

**Cuándo aplicar:**
- **Fase 1:** Después de recolectar Coches.net + mobile.de + AutoUncle
- **Fase 2:** Después de recolectar las 4 fuentes restantes
- **Flujo C:** Después de cada modelo escaneado

**Regla:** Si la huella coincide pero el precio difiere >10%, NO es duplicado (puede ser versión distinta).

---

## 📊 TIPOS DE INFORME — uno por flujo

### INFORME TIPO BUSQUEDA (Flujo C)

Tabla multi-modelo. Cada fila: modelo, segmento, hueco%, N uds DE, vendibilidad estimada, enlace al mejor anuncio. Sin comparable ajustado, sin IEDMT. Solo Fase 1.

**Dimensiones de atractivo** — cuando el usuario da preferencias libres ("algunos atractivos pasionales y otros económicos funcionales"), clasificar hallazgos en:

| Dimensión | Qué busca | Ejemplos |
|---|---|---|
| 🔥 **Pasionales** | Deportivos, icónicos, deseables | GTI, OPC, M, RS, Type R, N |
| 💼 **Premium funcional** | Calidad, comodidad, imagen | Arteon, Clase A, A3, Serie 1 |
| 🛠️ **Económico funcional** | Buen precio, fiable, revende bien | León FR, Octavia, Golf TSI |
| 🌱 **Eco/Etiqueta** | CERO/ECO, PHEV, bajo consumo | GTE, 330e, e-tron, Niro |

El informe BUSQUEDA agrupa por estas 4 dimensiones y el usuario elige cuáles profundizar (Flujo B).

**Variaciones estacionales:** Anotar en §10 si el análisis es en temporada baja (otoño/invierno): "Precio de temporada baja, posible subida 5-8% en primavera". Ver tabla completa en `costes.md`.

### INFORME TIPO MODELO (Flujo B)

- Tabla cobertura 7 fuentes (Fase 2)
- Mediana y cuartil bajo ES + DE
- Vendibilidad estimada (5 factores)
- Top 5 candidatos con enlaces
- Sin desglose por unidad
- Cacheable 2-3 semanas. Delta updates al refrescar.

### INFORME TIPO UNIDAD (Flujo A) — el completo, 11 secciones

1. Tabla cobertura 7 fuentes
2. Oferta española (fila × unidad + mediana/cuartil bajo/Puerta A)
3. Oferta alemana (ídem + días publicado + portal origen)
4. Candidato (ficha completa + enlace + km77)
5. **Comparable ajustado** → Ver `comparables.md` para detalles de ajuste línea a línea
6. **Coste puesto en Huelva** → Ver `costes.md` para IEDMT y desglose completo
7. Margen y veredicto (contra mediana Y cuartil bajo, matriz, factores)
8. **Riesgos y banderas** → Ver `riesgos.md` si el motor tiene problemas conocidos
9. Alternativas
10. Qué hacer (pasos numerados)
11. Pie de fuentes + "lo que es estimación" + aviso legal

---

## 🔁 CONTROL DE FRESCURA — DELTA UPDATES

Antes de rehacer, consultar `indice.json` SIEMPRE primero.

| Antigüedad | Acción |
|---|---|
| < 2-3 semanas | Chequeo rápido. Si no hay mejora → CERRADO. Mostrar solo **cambios** (delta). |
| > 3 semanas o sin datos | Búsqueda completa (Fase 1 + Fase 2). |

**Formato delta al refrescar modelo cacheado:**

```
🔄 ACTUALIZACIÓN Golf GTI Clubsport (hace 18 días)

CAMBIOS:
- Mediana ES: 34.500 → 33.800 (-700€, -2.0%) 📉
- Hueco: 22.4% → 19.8% (-2.6pp) ⚠️ Se estrecha

LO DEMÁS: sin cambios significativos.
¿Rehacer análisis completo? (tokens: ~40)
```

---

## 📈 VENDIBILIDAD — 5 factores, 100 puntos

| # | Factor | Peso | Fuente | Estado |
|---|---:|---|---|---|
| 1 | Demanda del modelo | 30 | Coches.net `publicationDate` (mediana días) | ✅ |
| 2 | Escasez configuración | 25 | AS24.es `countryCode` + recuento | ✅ |
| 3 | Atractivo | 20 | Criterio cualitativo | Manual |
| 4 | Equipamiento sobre std ES | 15 | mobile.de `features` vs acabado ES | ✅ |
| 5 | Km e historial | 10 | mobile.de ficha: propietarios, ITV, km/año | ✅ |

**Puntuación:** Demanda: top-10=30, fuerte=22, minoritario=14, nicho=6 · Escasez: ≤20=25, 20-50=21, 50-100=16, 100-300=10, >300=4 · Atractivo: icónico=18-20, deportivo=14-17, premium=10-13, utilitario=4-8 · Equipamiento: techo=4, cuero=3, AWD=3, LED=2, audio=2, HUD=1 · Historial: libro=3, 1dueño=2, <15k/año=3, ITV=2.

### Matriz de decisión (solo Flujo A)

| | Margen ≥10% | Margen <10% |
|---|---|---|
| **Vendibilidad ≥65** | 🟢 COMPRA PRIORITARIA | 🔵 OFERTA DE CONTENIDO |
| **Vendibilidad <65** | 🟡 SOLO BAJO PEDIDO | 🔴 DESCARTAR |

> La casilla azul se ignora siempre: coche con 5% margen y vendibilidad alta **sí se oferta**. Trae los clientes de los 3 siguientes.

---

## 🛡️ ANTI-PATRONES BLOQUEADOS

Las 6 reglas duras (A1-A6) viven en `anti_patrones.md`. Cargarlas cuando se duda de una práctica o antes de cerrar un informe.

**Resumen rápido:**
- **A1** No descartar por silencio (sello `man`, no exclusión)
- **A2** mobile.de SIEMPRE en cobertura (OK o bloqueada+intentos)
- **A3** CO₂ y PVP de km77 o BOE, nunca estimación
- **A4** Veredicto contra mediana Y cuartil bajo
- **A5** Precio máximo de compra en todo informe Flujo A
- **A6** Tablas con columna ENLACE clickable

---

## 🚪 LAS DOS PUERTAS

### Puerta A · ¿Hay comparable español? (Flujo A)

| Coches ES reales | Lectura |
|---|---|
| ≥15 | Comparable sólido |
| 5-14 | Justo: mediana **y** cuartil bajo |
| 1-4 | No hay comparable. Ni medianas ni %. |
| 0 | Exclusividad pura |

### Puerta B · Test de nivel de precio (Flujo A y B)

| DE vs ES | Acción |
|---|---|
| ≥25% por debajo | Rastreo completo |
| 15-25% | Rastreo, avisando del margen justo |
| 0-15% | Solo cuartil bajo. Dilo antes de empezar. |
| Por encima | No rastrear. Solo cliente concreto.

---

## 📦 ZIP PARA LARAVEL — solo Flujo A

```
[coche_id].zip
├── informe.json                    ← JSON completo del CONTRATO (contrato.md)
├── manifest.json                   ← Metadatos del paquete
├── contenido/
│   ├── ficha-publicitaria.txt      ← Esqueleto [BLOQUE] → folleto.blade.php
│   ├── informe-interno.txt         ← Esqueleto [BLOQUE] → briefing.blade.php
│   ├── redes-sociales.txt          ← [GANCHO] [POST_LARGO] [STORIES] [HASHTAGS]
│   └── anuncio-portales.txt        ← [TITULO] [DESCRIPCION] [AVISO_LEGAL]
└── fotos/
```

**Qué hace Laravel con cada archivo:** ver `contrato.md`.

**Lo que NUNCA va en el ZIP:** PDFs pre-generados, Excels, datos de otros coches, anotaciones internas tipo "(NUEVO)" o "revisión anterior".

---

## 📄 JSON por flujo

| Flujo | Salida JSON | Estructura |
|---|---|---|
| **A: UNIDAD** | `informe.json` dentro del ZIP | `{_meta, vehiculo, anuncio, investigacion, balance, veredicto, costes, mercado, avisos, publicidad}` — un solo coche, contrato completo |
| **B: MODELO** | `informe.json` suelto en `export/` | Misma estructura que A, pero SIN `publicidad` (no se generan esqueletos de venta). El usuario decide si promover a Flujo A después. |
| **C: MERCADO** | `informe.json` agregado | `{_meta, modelos: [{modelo, segmento, hueco_pct, n_uds_de, vendibilidad_estimada, mejor_anuncio_url}, ...]}` — N entradas, sin detalle por unidad. Se guarda en `export/scouting_<fecha>.json` para histórico. |

---

## ✅ CHECKLIST

**Antes de gastar**
- [ ] Detecté el flujo correcto (A/B/C)
- [ ] Tabla cobertura con las fuentes que apliquen al flujo
- [ ] Consulté `indice.json` y comprobé frescura
- [ ] Miré el registro de clientes (Flujo B)

**Al medir**
- [ ] Fase 1 con las 3 fuentes obligatorias (Coches.net, mobile.de, AutoUncle)
- [ ] `powertype=kw` · Verifiqué `initialSearch` en Coches.net
- [ ] PVP y CO₂ de km77 (si Flujo A)
- [ ] Medí DE en mobile.de directo, no solo AutoUncle
- [ ] Ante bloqueo, probé `www.` + `web_fetch` antes de marcar
- [ ] No descarté por silencio (A1) · mobile.de en cobertura OK (A2)
- [ ] CO₂ de km77 o BOE, no estimación (A3)

**Al evaluar (Flujo A)**
- [ ] Filtro admisión: ±2 años, ±40% km, ajustes capados ±25%
- [ ] <15 comparables → rango · Ajuste línea a línea · Descripción entera
- [ ] Regla 6/6000 · CO₂ (estimado → decirlo)
- [ ] Ahorro contra mediana **y** cuartil bajo (A4)
- [ ] Precio máximo de compra en informe (A5)
- [ ] Tablas con columna ENLACE (A6)

**Al cerrar**
- [ ] Actualicé `datos/registro_cierres.json` → Ver `operaciones_cierre.md`
- [ ] Calculé KPIs mensuales si aplica → Ver `operaciones_cierre.md`

---

## 📚 MÓDULOS ESPECIALIZADOS

Cargar solo cuando se necesite la sección específica:

| Módulo | Cuándo cargar | Contenido |
|---|---|---|
| **`comparables.md`** | Flujo A: ajuste de comparable | 9 claves, filtro admisión, comparable sin muestra, detección competencia, primas equipamiento |
| **`costes.md`** | Flujo A: desglose económico | IEDMT (fórmula + tabla), precio máximo de compra, IVA, moneda extranjera, ejemplo completo |
| **`riesgos.md`** | Flujo A/B: motor problemático | Tabla riesgos por motor (DQ200, EA888, N47, etc.), verificación, reglas descarte automático |
| **`operaciones_cierre.md`** | Cierre de venta + KPIs | Registro cierre JSON, KPIs, changelog, sync Desktop ↔ Skill, encargo permanente |

---

## 🏢 NEGOCIO

**JJ Import Motors** (Huelva, España)
- **Modelo:** Importación de coches UE sin stock. Honorarios por gestión.
- **Segmentos:** Nicho (≥20k€, margen ≥15%) y Rotación (8-20k€, margen ≥10%)
- **Fuentes:** 7 portales (3 ES + 4 DE)
- **Entregable:** ZIP con informe + esqueletos Blade + fotos → Laravel
- **Caché:** Endpoint `/api/investigation-cache` para reutilizar investigación por modelo

**Datos de marca:**
- Teléfono: `675 70 14 39`
- Email: `jjimportmotors@gmail.com`
- Web: `https://dev.aktive.cloud/importnexcore`
- Colores: `#1A306D` (estoril), `#38393D` (asphalt), `#BEC0C3` (platinum), `#E8590C` (accent)
