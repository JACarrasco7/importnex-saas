# Plan de mejora de las skills: v4 «más simple, más fiable, menos tokens»

**Fecha:** 7-oct-2026 · **Base:** las 3 auditorías del 7-oct y tus 9 respuestas · **Alcance:** `importacion-vehiculos` + `estudio-mercado` (e-commerce queda fuera, como pediste) · **Lo aplica:** Copilot en VS Code, fase a fase, con un commit por fase.

> Decisiones cerradas al final (§8). El plan ya está listo para que lo aplique Copilot.

---

## 0 · Cómo entiendo el uso

```
Encargo
  │
  ├─ ¿El cliente no tiene modelo? ──► DESCUBRIMIENTO: presupuesto + requisitos → lista corta de modelos
  │                                        │
  ▼                                        ▼
SONDEO (el informe estándar): rápido, ES vs DE, Coches.net frente a mobile.de
  · organizado marca › modelo › generación › motorización (› acabado)
  · por cada "celda": nº de anuncios, mediana, cuartil bajo, hueco bruto y neto, ENLACES para que lo compruebes
  │
  ▼
TÚ revisas a mano con los enlaces y eliges unidades concretas
  │
  ▼
UNIDAD: informe completo de ese coche (15 secciones, precio máximo, recalls, IEDMT exacto) + ZIP para Laravel
  │
  ▼
MARKETING (solo si la unidad sale 🟢 o 🔵 y lo pides)
```

Cada informe sale **dos veces con la misma estructura**: `.md` (para la IA y Laravel) y `.pdf` (para vosotros).

**Segundo uso muy habitual: CATÁLOGO** (familiares, SUV, hatchback… para tener coches en el panel, anunciarlos y moverlos en redes):

```
Segmento + banda de precio (p. ej. "familiares 15-30 k€")
  │
  ▼
SONDEO por segmento → 1 informe por MARCA (.md + .pdf), una sección por modelo, + 1 índice del segmento
  │                     con casillas [ ] para que marques lo que te gusta
  ▼
TÚ marcas modelos/motorizaciones en el índice
  │
  ▼
Claude busca 3-5 anuncios reales por cada marcado (enlaces a la ficha) → tú eliges cuáles
  │
  ▼
UNIDAD de cada elegido → ZIP → Laravel (estado "Localizado", visible en /marketplace)
  │
  ▼
MARKETING: anuncios de portales + redes por coche, y plan editorial del segmento
```

---

## 1 · Tus decisiones convertidas en reglas

| # | Lo que has decidido | Regla nueva (texto que entra en la skill) | Sustituye a / cierra |
|---|---|---|---|
| 1 | El IVA no se calcula: el coche se paga en Alemania con su IVA dentro | "**El IVA no es una línea de coste.** El precio alemán ya lo lleva. Si el anuncio dice *MwSt. ausweisbar* (IVA deducible), se indica en el informe de UNIDAD como dato útil para un cliente empresa o autónomo. Única excepción legal que se avisa, sin calcularla: coche con menos de 6 meses o menos de 6.000 km." | EM-C1 (IVA fantasma) y todas las frases "IVA de importación" |
| 2 | Siempre `.md` para la IA y `.pdf` para vosotros, con la misma estructura | "**Todo informe = un `.md` + su `.pdf` gemelo**, generados por el mismo script desde los mismos datos. Nunca uno sin el otro. El PDF lleva enlaces clicables, la URL visible y un pie con contexto en cada página." | IV-C1, EM-I5, DR-I2 (las 5 frases contradictorias) |
| 3 | Organizar por marca › modelo › generación › combustible › versión | Ver §2.1: lo adopto con un ajuste (la "versión" se parte en *motorización* y *acabado*). | IV-I14, EM-I3 |
| 4 | El estándar es un sondeo rápido ES vs DE; lo completo solo en la unidad | "**SONDEO** = 1 tabla por celda, con conteo, mediana, cuartil bajo, hueco y enlaces. Sin rotación ni demanda. Lo exhaustivo vive en UNIDAD." | EM-I4. Nota: esto **cambia** lo que me dijiste antes ("profundidad completa en los estudios"); a partir de ahora manda esta respuesta. |
| 5 | ~1.500 € de gastos de gestión + 1.000 € fijos de honorarios, siempre aparte | "**Coste puesto en Huelva = precio DE + ~1.500 € de gastos + IEDMT estimado**. **Honorarios: 1.000 € fijos en todo (sondeo y unidad), siempre en línea aparte**, nunca metidos dentro de los gastos. Redondeo prudente: costes hacia arriba y ahorro hacia abajo, a la centena." | EM-C1 (desglose 1.000/200/300), IV-C3; el colchón de 371 € desaparece |
| 6 | No sabes qué copia de `datos_mercado.json` es la buena | Ver §2.3: lo decide un diff en la Fase 0, y a partir de ahí hay un único máster. | DR-C2, EM-I6 |
| 7 | Validador: lo que yo vea mejor | Ver Fase 2: **un único** `check_informe.py`, que además **bloquea** la generación del PDF y del ZIP si hay 🔴. | IV-I4, EM-I7, IV-C2 |
| 8 | Texto libre: unas veces bien, otras con trampas | Ver §2.2: el texto libre **nunca define** la celda; solo afina dentro de una celda ya filtrada, y con muestra verificada. | EM-C2, IV-I8 (sustituye la prohibición absoluta del 7-oct) |
| 9 | E-commerce fuera | No se toca `ecommerce-tuning`. | — |

---

## 2 · Mis respuestas a tus preguntas abiertas

### 2.1 Organización: ¿marca › modelo › generación › combustible › versión?

Es casi la buena. Le haría **un cambio**: partir "versión" en dos cosas distintas, porque en la práctica significa dos cosas y es justo donde se cuelan los errores.

| Nivel | Ejemplo | Cómo se filtra en el portal | ¿Fiable? |
|---|---|---|---|
| **Marca** | Audi | ID de marca | ✅ |
| **Modelo + carrocería** | A3 **Sportback** (no Sedán) | ID de modelo + carrocería | ✅ |
| **Generación** | 8Y (2020-hoy) | años de corte de `references/generaciones.json` (ya existe) | ✅ si se usa el corte seguro |
| **Motorización** = combustible + potencia + cambio | Gasolina · 150 cv · automático | combustible + rango de cv + cambio | ✅ es lo que los portales filtran bien |
| **Acabado** (opcional) | S line | **no hay filtro estructurado fiable** → texto libre | ⚠️ ver §2.2 |

**Por qué potencia en vez de "versión":** "35 TFSI S line" mezcla motor y acabado. El motor se filtra exacto por cv en los dos portales; el acabado no. Si la versión es la unidad de medida, el error del acabado contamina toda la celda.

**La "celda"** (lo que se compara entre ES y DE) = modelo + carrocería + generación + motorización, con **los mismos filtros en los dos portales** (años, km, cv, cambio y la banda de precio del encargo en ambos mercados).

**Carpetas:** solo `informes\<marca>\<modelo>\`. La generación, la motorización y el acabado van **dentro del informe** (secciones) y en el JSON, no como carpetas. Cinco niveles de carpeta parten un mismo sondeo en muchos ficheros y no se puede comparar una generación con otra de un vistazo.

```
informes\audi\a3-sportback\
   sondeo_audi-a3-sportback_2026-10-07.md
   sondeo_audi-a3-sportback_2026-10-07.pdf
   sondeo_audi-a3-sportback_2026-10-07.json      ← los datos (ver §3.3)
   unidad_audi-a3-sportback_<id-anuncio>_2026-10-09.md / .pdf / .zip
```

Dentro del sondeo:

```
## A3 Sportback · 8Y (2020-hoy)
### Gasolina · 150 cv · automático          ← celda: tabla ES | DE | hueco + enlaces
### Gasolina · 310 cv · automático (S3)
#### Acabado S line (submuestra)           ← solo si se pide, y con la marca de verificación
## A3 Sportback · 8V (2016-2020)
...
```

### 2.2 Texto libre (`q=` / `Versions[`): cómo hacerlo bien

Tienes razón en los dos sentidos: prohibirlo del todo deja sin medir los acabados, y usarlo a pelo mete coches que no son y deja fuera otros. Propuesta en 3 escalones:

| Escalón | Cuándo | Cómo | Ejemplo |
|---|---|---|---|
| **1. Estructural** (siempre primero) | Siempre | Solo ID de modelo, carrocería, años de generación, combustible, cv, cambio y km | RS3 = 400 cv; GTI = 245/265 cv; S3 = 310 cv. **No hace falta texto**: la potencia ya lo define |
| **2. Texto libre como submuestra** | Cuando el encargo es un **acabado** que no se distingue por cv (S line, R-Line, M Sport, AMG Line, FR…) | El texto se añade **encima** de los filtros del escalón 1, nunca solo. Claude abre los **10 primeros anuncios** y cuenta cuántos son de verdad ese acabado | "A3 8Y · 150 cv · automático" + `q=S line` → 9 de 10 lo son → vale |
| **3. Descartar** | Si la muestra sale menos de 8/10 | No se da cifra de ese acabado: "**no medible por texto**", y se usa la celda del escalón 1 | `q=S line` en mobile.de trae paquetes "S line exterior" sueltos → 5/10 → no medible |

Reglas que lo acompañan:
- Toda URL con texto libre lleva al lado la etiqueta **`[texto libre · submuestra 9/10]`**. Sin esa etiqueta, el validador la rechaza.
- La cifra del acabado **nunca sustituye** a la de la celda: va debajo, como dato adicional.
- Si algún portal tiene un filtro estructurado de acabado para esa marca, se usa ese en lugar del texto. **SIN VERIFICAR** qué marcas lo tienen en mobile.de y en Coches.net: se comprueba en el piloto (Fase 6) y se apunta en `references/filtros-portales.md`.

### 2.3 ¿Qué `datos_mercado.json` es el bueno?

No hace falta que lo sepas; lo decide el contenido:

1. **Fase 0:** Copilot compara las 3 copias (Drive del 28-sep, 100.527 B; Desktop del 13-sep, 71.131 B; la del repo) por marcas, modelos, nº de celdas y fecha más reciente de cada entrada. Para eso no se carga la base de datos: son ficheros JSON.
2. Se queda la **más completa y reciente**; si cada copia tiene algo que la otra no, se fusionan por fecha de entrada.
3. **Máster a partir de ahí: el del repo** (lleva historial con git y es el que lee Laravel). Desktop y Drive pasan a ser **copias que genera un script** y nadie edita a mano.
4. Además el mapa deja de ser un único fichero de 100 KB que Claude lee entero (unos 30.000 tokens, estimación mía): pasa a consultarse con `mapa.py consultar audi a3`, que devuelve solo esas celdas (ver §3.4).

---

## 3 · Arquitectura propuesta: más simple, más fiable, menos tokens

### 3.1 De 8 flujos a 3 motores + 1 cadena (+ marketing)

| Hoy | Propuesta | Por qué |
|---|---|---|
| B (modelo), C (mercado), E (stock por categorías), F-EM (estudio de mercado) | **SONDEO**, con un parámetro de alcance: `modelo` · `marca` · `segmento` · `categoría` | Los cuatro hacen lo mismo: medir celdas ES vs DE y dar enlaces. Hoy son 4 plantillas, 4 nombres de fichero y 0 validadores |
| D (descubrimiento) | **DESCUBRIMIENTO** (sin cambios de fondo) → termina lanzando un SONDEO | Ya es así; solo le falta plantilla propia |
| A (unidad) | **UNIDAD** | Sin cambios de fondo |
| M (marketing) | **MARKETING**, complemento de UNIDAD | Sin cambios de fondo |
| F-GM (guía de marcas) | Pasa a ser un **script de mantenimiento**, no un flujo | No lo lanzas tú en un encargo |
| E (stock por categorías) en su parte de "llenar el panel" | **CATÁLOGO** = cadena SONDEO (segmento) → tu selección → UNIDAD × N → MARKETING | No es un motor nuevo: reutiliza sondeo y unidad y solo añade el índice con casillas, la búsqueda de 3-5 anuncios por modelo marcado y el plan editorial (§3.6) |

Menos flujos significa menos ficheros que leer, una sola plantilla de sondeo, un solo perfil de validador y desaparecen las contradicciones entre flujos que en realidad eran el mismo.

### 3.2 Una sola skill en vez de dos (decidido)

Hoy hay dos skills (96 KB + 47 KB de `SKILL.md`) que se referencian entre sí con rutas `../`, tienen ficheros duplicados (`mobile-de-ids.json`, reglas de costes) y van en versiones distintas en cada sitio (3.10.5/0.5.0 en la cuenta, 3.9.10/0.4.6 en Desktop, 3.9.3/0.4.0 en el manifiesto).

**Propuesta:** fusionarlas en **una** skill `jj-import-motors` v4.0 con un `SKILL.md` "enrutador" pequeño que carga solo el módulo del flujo que toca.

| | Dos skills (hoy) | Una skill (propuesta) |
|---|---|---|
| Lo que se carga al arrancar | ~143 KB de `SKILL.md` si se disparan las dos | ≤10 KB de enrutador + el módulo del flujo |
| Versiones que sincronizar | 2 (y ya han divergido) | 1 |
| Referencias cruzadas `../` | sí (frágiles) | no |
| Ficheros duplicados | sí | no |
| Riesgo | — | migración grande: se hace en una rama y se prueba en el piloto antes de instalar |

### 3.3 "Claude rellena datos, los scripts escriben el informe"

Hoy Claude redacta el informe entero a mano. Ahí nacen casi todos los fallos de la auditoría: cuentas mal (IEDMT), cifras al euro, frases viejas copiadas (IVA), estructura que cambia de un informe a otro y PDF distinto del `.md`.

**Propuesta:** Claude solo **mide y rellena un JSON**; los scripts hacen el resto.

```
Claude navega ──► sondeo_<...>.json  (celdas: filtros, URLs, nº anuncios, precios leídos, notas)
                       │
                       ├─► stats.py      → mediana, cuartil bajo, n (nada de cuentas de cabeza)
                       ├─► costes.py     → gastos ~1.500 + IEDMT estimado + honorarios aparte, redondeo prudente
                       ├─► check_informe.py → 🔴 bloquea / 🟠 avisa
                       ├─► render_informe.py → .md  y  .pdf  (misma plantilla, mismo contenido)
                       └─► mapa.py importar  → actualiza datos_mercado (el "flujo que faltaba" nº 2)
```

Esquema mínimo del JSON del sondeo (lo detalla Copilot en `schema_sondeo.json`):

```json
{
  "tipo": "sondeo", "fecha": "2026-10-07", "alcance": "modelo",
  "marca": "audi", "modelo": "a3-sportback",
  "banda_precio": [15000, 30000],
  "celdas": [{
    "generacion": "8Y", "anios": [2020, 2024],
    "combustible": "gasolina", "cv": [150, 150], "cambio": "automatico", "km": [0, 80000],
    "es": {"url": "…", "anuncios": 0, "precios": []},
    "de": {"url": "…", "anuncios": 0, "precios": []},
    "acabado": null,
    "notas": "texto libre de Claude (conclusión de la celda)"
  }]
}
```

Lo que gana: estructura idéntica siempre, `.md` y `.pdf` gemelos garantizados, cifras correctas por construcción, validación trivial (contra el esquema) y la importación al mapa sale gratis. Claude escribe menos texto de relleno: solo las notas.

### 3.4 Ahorro de tokens: medidas concretas

| Medida | Qué cambia | Efecto esperado |
|---|---|---|
| Enrutador ≤10 KB | `SKILL.md` = tabla de flujos + 12 reglas de una línea + "qué leer cuándo" | Se deja de cargar ~96 KB en cada arranque |
| Reglas consolidadas | Las 34 reglas A1-A33 + A22b (29 KB) pasan a ~15 reglas de una línea, agrupadas; los casos reales y la historia van a `historial_reglas.md`, que no se carga | Menos lectura y menos contradicciones. Además hay numeración rota: A34 ya está usado en `generaciones.json` para "separar por generación" y a la vez propuesto como "vigencia" en marketing |
| Mapa consultable | `mapa.py consultar <marca> <modelo>` en vez de leer el JSON entero | Se lee solo lo que hace falta |
| Leer texto, no capturas | En cada página de resultados: leer el **texto** (conteo y precios) con la herramienta de lectura de Chrome; **1 captura solo si la página falla**. Sigue siendo navegación real en tu Chrome, sin fetch ni inyección de JS | Una captura cuesta mucho más que el texto de la misma página |
| Mediana por bisección | Si una celda tiene muchos anuncios (p. ej. >60), en vez de paginar todo se usa el filtro de precio máximo del portal y se lee **solo el conteo**: el precio en el que quedan la mitad de los anuncios es la mediana (y una cuarta parte, el cuartil bajo). Unas 6 consultas en vez de muchas páginas | Mismo resultado con una fracción de la lectura. Se valida en el piloto contra la paginación completa |
| Caché de motores | `motores.json`: CO₂ y cv por motorización (de km77), para no buscarlo cada vez | Una búsqueda por motor, una sola vez |
| Caché de celdas | Si la celda se midió hace menos de 14 días, se reutiliza y se dice ("medido el …") | Sondeos repetidos casi gratis |

**Objetivo del piloto:** medir tokens por celda antes y después. Meta: al menos −50 %. Es un objetivo, no una cifra medida.

### 3.5 Estructura nueva de la skill (una sola)

```
jj-import-motors/
├── SKILL.md                      ← enrutador ≤10 KB
├── flujos/
│   ├── sondeo.md                 ← cómo medir una celda (escalones §2.2), ≤15 KB
│   ├── unidad.md                 ← informe de 15 secciones + ZIP
│   ├── descubrimiento.md
│   └── marketing.md              ← lo actual de 07-marketing, condensado
├── reglas/
│   ├── reglas.md                 ← ~15 reglas de una línea (se carga)
│   └── historial_reglas.md       ← casos y porqués (NO se carga)
├── negocio/
│   ├── costes.md                 ← modelo de costes nuevo (§1, punto 5) + IEDMT
│   └── riesgos.md
├── plantillas/                   ← sondeo.md.j2, unidad.md.j2, pdf.css (las usa render_informe.py)
├── references/                   ← mobile-de-ids.json, generaciones.json, motores.json, filtros-portales.md
├── scripts/                      ← fuentes.py, stats.py, costes.py, check_informe.py, render_informe.py, mapa.py, empaquetar.py, …
└── memoria/                      ← trampas, vendedores, filtros (se actualiza al cerrar cada encargo)
```

### 3.6 Flujo CATÁLOGO, en detalle (encaja con lo que ya tiene Laravel)

Lo he contrastado con el repo (`C:\laragon\www\importnexcore`), que ahora sí puedo leer:

| Paso | Qué sale | Dónde aterriza en Laravel (ya existe) |
|---|---|---|
| 1. Sondeo del segmento | `informes\mercado\<segmento>\<marca>_<segmento>_<fecha>.md/.pdf` (uno por marca, una sección por modelo) + `00_indice_<segmento>_<fecha>.md/.pdf` (ranking por hueco neto y casillas `[ ]`) | `mapa.py` exporta el formato de `datos_mercado.json` → `php artisan market:import` → tabla `market_models` (catálogo `/mercado`, solo si `publicar_en_catalogo=true`). `rotacion_*` y `demanda_trends` aceptan vacío, así que el sondeo sin rotación encaja sin tocar Laravel |
| 2. Tu selección | Marcas `[x]` en el índice (o escribes "A3 8Y 150 cv, Tiguan 2.0 TDI") | Más adelante, opcional: un botón "buscar unidades" en el admin de Mercado |
| 3. Búsqueda de unidades | `candidatos_<segmento>_<fecha>.md/.pdf`: 3-5 anuncios por modelo marcado, enlace a la **ficha** (no a la búsqueda), precio frente a la mediana de su celda | — |
| 4. Unidad | Informe completo + ZIP por coche elegido | `POST /cars/import-valuation` o `/api/import-valuation` → `Car` en estado `Located`; con `is_marketplace=true` sale en `/marketplace` (regla `marketplace.md`) |
| 5. Marketing | `redes-sociales.txt` + `anuncio-portales.txt` por coche (ya en el ZIP) + `plan_editorial_<segmento>_<mes>.md` | `CarMarketingContent` |

**Ojo legal (A32 / `business-model.md`):** en los textos públicos de este flujo no puede aparecer "stock", "en stock", "vendemos" ni "nuestro coche". El coche es del vendedor original: "localizado", "disponible para gestionar su compra". Internamente lo llamamos **CATÁLOGO**, no STOCK.

**Preferencias que ya me diste y que este flujo respeta:** un informe por marca con una sección por modelo; ejecutar todas las marcas seguidas y entregar al final, sin paradas intermedias; la banda de precio en los dos mercados; y guardar todo en `Desktop\JJImportMotors\informes`.

---

## 4 · Plan por fases (para Copilot)

> Un commit por fase. Antes de la Fase 0: backup de la carpeta de skills y de `datos_mercado.json` (las 3 copias). No hay que instalar paquetes, salvo **Jinja2** si `render_informe.py` lo usa: **pendiente de tu OK** (alternativa: plantillas con `string.Template` de Python, sin dependencias).

### Fase 0 · Datos y seguridad (½ día)
| Tarea | Ficheros | Aceptación |
|---|---|---|
| Backup de las skills y las 3 copias del mapa | `_archive/backup_2026-10-07/` | Existe y se abre |
| Diff de las 3 copias de `datos_mercado.json` (marcas, celdas, fechas) | script de un solo uso | Tabla con el resultado en `HANDOFF.md` |
| Fijar el máster en el repo y generar copias a Desktop/Drive | `sync` | Las 3 copias con el mismo `sha256` |
| Rama `skill-v4` para todo lo demás | git | Nada se instala en Desktop hasta la Fase 6 |

### Fase 1 · Reglas de negocio (1 día)
| Tarea | Cierra |
|---|---|
| Reescribir `negocio/costes.md`: gastos ~1.500, honorarios 1.000 fijos aparte (fuera M1/M2/M3, el rango 1.500-2.250 € y la tarifa ES ~500 €), IEDMT con su tabla, el IVA no se calcula (+ *MwSt. ausweisbar* y la excepción 6/6000 solo como aviso), ejemplo recalculado | EM-C1, IV-C3 |
| `reglas/reglas.md`: ~15 reglas de una línea (incluye md+pdf, texto libre en 3 escalones, celda estructural, una regla se propaga a todos los sitios, ningún informe sin validador, citas legales con BOE) | IV-I2, IV-M1, numeración A34 duplicada |
| Corregir citas legales: "RDL 1/2007 modificado por RDL 7/2021" (enlace BOE: verificar) | IV-I1 |
| Corregir A31→A32 en los validadores | IV-I2 |
| `riesgos.md`: "Club Audi", columna "fuente / fecha" | IV-I11 |
| Borrar de **todos** los ficheros: "IVA de importación", "valor en aduana", "1.000 + 200 + 300", "colchón 371", "Ley 11/2022", "30 checks" | regla "se propaga a todo" |

**Aceptación:** `grep` de cada frase vieja = 0 en la rama.

### Fase 2 · Scripts (2-3 días)
| Script | Hace | Cierra |
|---|---|---|
| `costes.py` | Coste puesto en Huelva, IEDMT (con y sin minoración), honorarios aparte, redondeo prudente. **Tests con el ejemplo recalculado** (1.349 / 1.764 €) | IV-C3 |
| `stats.py` | n, mediana, cuartil bajo; si n<5 → "muestra insuficiente", sin hueco | EM-I2 |
| `fuentes.py` v2 | URLs desde la celda (IDs, generación, cv, combustible **por celda**, cambio, km, banda de precio en los dos mercados); texto libre solo con `--submuestra` | IV-I9, EM-C2 |
| `check_informe.py` | Un validador, perfiles `sondeo` / `unidad` / `descubrimiento`. Comprueba el JSON contra el esquema y el `.md` contra frases prohibidas, URLs con texto libre sin etiqueta, mobile.de presente, fuentes con conteo y fecha, cifras derivadas a la centena, nombre canónico y que exista el `.pdf` gemelo | IV-I4, EM-I7 |
| `render_informe.py` | JSON → `.md` y `.pdf` con la misma plantilla. Pie en cada página (marca, modelo, fecha de medición, p. N/M), URL visible, enlaces clicables. **Se niega a generar si `check_informe` da 🔴** | IV-C1, EM-I1 |
| `mapa.py` | `consultar`, `importar <sondeo.json>`, `caducados`, `exportar` (formato que lee `php artisan market:import`) | EM-I6, flujo que faltaba nº 2 |
| `empaquetar.py` | Validar también `anuncio-portales.txt`; usar `check_informe --perfil unidad` | IV-C2 |

**Aceptación:** tests de cada script en verde; un sondeo de prueba pasa de JSON a `.md` + `.pdf` en un comando.

### Fase 3 · Enrutador, módulos y plantillas (1-2 días)
| Tarea | Cierra |
|---|---|
| Fusionar en `jj-import-motors` con la estructura de §3.5 | IV-I5, IV-I13 |
| `SKILL.md` ≤10 KB; `flujos/sondeo.md` ≤15 KB con los 3 escalones y el método de lectura | D6 |
| Plantillas `sondeo` y `unidad` con la jerarquía de §2.1; `descubrimiento` con plantilla propia | IV-I14, D sin plantilla |
| Quitar del texto el "checklist que se marca a sí mismo" (lo sustituye el validador) | EM-I7 |
| Plantillas de CATÁLOGO: `indice_segmento` (ranking + casillas), `candidatos` (3-5 anuncios por modelo marcado), `plan_editorial` | §3.6 |
| `entregables.md` → una tabla de 6 filas (sondeo, catálogo-índice, candidatos, unidad, descubrimiento, marketing): nombre, carpeta, formato `.md`+`.pdf`, perfil de validador, carpeta de Drive | D12 |

**Aceptación:** `verify_skill_refs.py` sin enlaces rotos; `wc -c SKILL.md` < 10.000.

### Fase 4 · Contexto, memoria y textos de arranque (½ día)
| Tarea | Cierra |
|---|---|
| Instrucciones del proyecto de claude.ai: texto nuevo ≤1,5 KB que apunta a la skill | IV-I7 |
| `CLAUDE.md` de Desktop: mismo texto corto, sin reglas duplicadas | IV-I6 |
| Memoria de la skill: una entrada por cada lección de agosto-octubre | IV-I6 |
| `HANDOFF.md`: una entrada por fase | DR-I7 |

### Fase 5 · Drive y sincronización (½ día)
| Tarea | Cierra |
|---|---|
| Política única: "Desktop/repo mandan; Drive es entrega y lectura desde el móvil" en los 4 sitios | DR-C1 |
| `google_drive.md` v2: **corrección a la auditoría**: `update_file` del conector **solo cambia el nombre o la carpeta, no el contenido** (comprobado hoy al cargarlo). Protocolo: buscar por nombre → si existe, renombrar el viejo a `…_obsoleto` (o moverlo a `99 Archivo`) y crear el nuevo con `create_file`; sin conversión; exclusiones (`.tmp`, `__pycache__`, `.bak`, zips) | DR-I4, DR-I1 |
| Carpetas `Informes/<marca>/<modelo>/` y renombrar `Infomes` (a mano o con tu OK). La papelera, carpeta a carpeta y con tu visto bueno | DR-I1, DR-I3 |
| `sync.manifest.json` con la versión única y el hash del mapa; el sync nunca copia un fichero más viejo sobre uno más nuevo | DR-C2, IV-I6 |

### Fase 6 · Piloto y puesta en marcha (1 día)
| Prueba | Qué se mide |
|---|---|
| Sondeo **Audi A3 Sportback** (8V y 8Y, 2-3 motorizaciones + submuestra S line) | Que salga igual que a mano; precisión de la submuestra; mediana por bisección frente a paginación completa; tokens por celda antes y después |
| Sondeo **VW Golf** (GTI/R por cv, sin texto) | El escalón 1 solo basta |
| Unidad de uno de los anuncios del sondeo | ZIP válido, IEDMT igual que `Car::calculateIEDMT()`, anuncio de portales validado |
| Comprobar qué marcas tienen filtro de acabado estructurado en cada portal | Se apunta en `filtros-portales.md` |
| **CATÁLOGO completo de un segmento** (p. ej. familiares 15-30 k€, 3-4 marcas) → marcas 2 modelos → candidatos → 1 unidad → ZIP → Laravel | Que la cadena entera funcione sin paradas innecesarias; que `market:import` acepte el export de `mapa.py`; que el coche salga en `/marketplace` |

**Aceptación final:** los 3 informes del piloto pasan el validador, tú los das por buenos y entonces se instala la v4 en Desktop y en la cuenta y se archivan la 3.x y la 0.x.

### Fase 7 · Informes antiguos (opcional)
Los 19 estudios de septiembre-octubre no se regeneran uno a uno: se marcan como **"versión anterior (método 3.x)"** con una línea al principio. Se rehacen con el método nuevo solo los segmentos que vayas a usar, con el caché de celdas para que salga barato.

---

## 5 · Qué cierra cada fase

| Fase | Hallazgos de la auditoría que cierra |
|---|---|
| 0 | DR-C2, EM-I6 (parte) |
| 1 | EM-C1, IV-C3, IV-I1, IV-I2, IV-I11, IV-M1, IV-M3, IV-M4 |
| 2 | IV-C2, IV-I3, IV-I4, IV-I9, IV-I10 (parte), EM-C2, EM-I2, EM-I3, EM-I7 |
| 3 | IV-C1, IV-I5, IV-I8, IV-I13, IV-I14, EM-I1, EM-I4, EM-I5, IV-M2 |
| 4 | IV-I6, IV-I7 |
| 5 | DR-C1, DR-I1…I7, IV-I12, EM-I8 |
| 6 | verificación de todo lo anterior; IV-I10 (revisión visual de fotos dentro de UNIDAD) |
| Fuera | EC-1…EC-5 (e-commerce, otro momento); D9 (repo) lo verifica Copilot de paso |

---

## 6 · Riesgos y cómo se cubren

| Riesgo | Cobertura |
|---|---|
| La fusión rompe algo que hoy funciona | Rama aparte, piloto antes de instalar, y la 3.x archivada para volver atrás |
| La lectura de texto no saca bien precios en algún portal | El piloto lo prueba por portal; si falla, en ese portal se vuelve a la captura |
| La mediana por bisección da algo distinto de la real | Se compara en el piloto; si la diferencia pasa del 3 % (umbral propuesto, ajustable), se pagina completa |
| Los portales bloquean por demasiadas consultas | Pausas humanas entre consultas y caché de 14 días |
| Se cuela una frase vieja | Regla "se propaga a todo" + lista de frases prohibidas dentro del validador |

---

## 7 · Esfuerzo estimado

Unos 6-8 días de trabajo de Copilot, repartidos en 7 commits. Es una estimación mía, no una medida. La Fase 0 y la Fase 1 son las que más rinden (cifras correctas y sin contradicciones) y se pueden hacer ya, antes de la fusión.

---

## 8 · Decisiones cerradas (7-oct, 19:50)

| Pregunta | Respuesta | Efecto en el plan |
|---|---|---|
| ¿Fusionar las dos skills? | **Sí, en una** (`jj-import-motors` v4.0) | Fase 3 tal cual |
| Honorarios de 1.000 € | **En todo** | `costes.md`: honorarios = 1.000 € fijos en sondeo y unidad. Desaparecen el rango 1.500-2.250 €, las modalidades M1/M2/M3 y la tarifa ES de ~500 €. El "techo de compra" pasa a ser: presupuesto − gastos − IEDMT − 1.000 € |
| IEDMT en el sondeo | **Sí, estimado por motorización** | `costes.py` + `motores.json` en la Fase 2 |
| Lectura de páginas | **Texto + 1 captura solo si falla** | `flujos/sondeo.md` en la Fase 3; se valida en el piloto |

**Pendiente solo de tu OK puntual durante la ejecución:** Jinja2 o plantillas sin dependencias (Fase 2), renombrar `Infomes` y la papelera de Drive carpeta a carpeta (Fase 5).
