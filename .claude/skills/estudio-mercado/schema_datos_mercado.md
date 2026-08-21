# Esquema de `datos_mercado.json` — mapa de mercado persistente

> Este JSON es la **fuente de verdad de criterio** para la skill `importacion-vehiculos` (PASO 0 y FIJAR MODELOS). Mantener el esquema estable: añadir campos, no romper los existentes.

---

## Estructura general

```json
{
  "schema_version": "1.2",
  "generado": "2026-08-17",
  "tipo_estudio": "por_categoria",
  "refrescar_antes_de": { "showstoppers": "2026-08-31", "alta_rotacion": "2026-09-07", "gemas_economicas": "2026-09-14" },
  "marcas": {
    "vw": { "modelos": ["vw-golf-7-gti"], "total": 1 },
    "audi": { "modelos": ["audi-a5-sb-50-tdi"], "total": 1 }
  },
  "cola_trabajo": {
    "siguiente_estudio": "vw-golf-75-tcr",
    "siguiente_busqueda": "bmw-serie-1-m135",
    "estados": { "vw-golf-75-tcr": "pendiente_estudio", "cupra-leon": "pendiente_estudio" }
  },
  "ruta_canonica": "C:/Users/jacar/Desktop/JJImportMotors/datos_mercado.json",
  "fuentes": {
    "es": { "portal": "coches.net", "consulta": "2026-08-17", "estado": "OK" },
    "de": { "portal": "mobile.de", "consulta": "2026-08-17", "estado": "OK" },
    "matriculaciones": { "dgt": "2026-07", "kba": "2026-07" }
  },
  "categorias": {
    "showstoppers": [ { "...modelo": "..." } ],
    "alta_rotacion": [ { "...modelo": "..." } ],
    "gemas_economicas": [ { "...modelo": "..." } ]
  }
}
```

> **Estudios dirigidos (17-ago-2026):** `tipo_estudio` indica cómo se generó el mapa (`por_categoria` · `por_marca` · `por_modelo` · `por_segmento` · `por_tipo_cliente`). Cada pasada incremental hace MERGE por `slug` (no borra entradas de otros estudios) y **recalcula el índice `marcas`** (marca → modelos estudiados + total). Así la BBDD se construye poco a poco y queda consultable por marca.

---

## Objeto modelo (una entrada por modelo/versión)

```json
{
  "slug": "vw-golf-7-gti",
  "alias": ["golf-gti", "golf-vii-gti", "golf 7 gti", "vw-golf-7-gti-performance"],
  "modelo": "VW Golf 7 GTI / R",
  "version": "GTI Performance 245cv",
  "categoria": "showstoppers",
  "segmento": "deportivo",
  "rango_precio": "14-25k",
  "tipo_cliente": "deporte_ocio",
  "tipos_cliente_secundarios": ["impacto_showstopper"],
  "categorias_secundarias": ["alta_rotacion"],
  "oferta_de": 2652,
  "oferta_es": 1167,
  "mediana_de": 23500,
  "mediana_es": 26500,
  "precio_desde_de": 21900,
  "precio_desde_es": 23950,
  "sello_precio_de": "Fairer Preis",
  "sello_precio_es": "Precio justo",
  "hueco_pct": 11.3,
  "hueco_neto_pct": 5.4,
  "coste_importacion_estimado": 1579,
  "iedmt_estimado": 450,
  "rotacion_dias_de": 42,
  "rotacion_dias_es": null,
  "rotacion_fuente": "autouncle",
  "demanda_trends": "estable",
  "transferencias_mes_dgt": null,
  "matriculaciones_kba": null,
  "veredicto": "amarillo",
  "mejor_mercado": "DE",
  "fuente_medicion": "estudio",
  "confianza_precio": 3,
  "oportunidad": false,
  "pendiente_fase2": false,
  "query_reejecutable": { "de": "mobile.de/.../golf-7-gti", "es": "coches.net/.../golf-gti" },
  "equipamiento_nivel": { "de": "full", "es": "medio" },
  "equipamiento_de": ["Virtual Cockpit", "Schiebedach", "LED", "Navi"],
  "equipamiento_es": ["Navi"],
  "equipamiento_ajuste_eur": 1500,
  "nota": "Hueco bruto 11,3% = (26500-23500)/26500 pero neto 5,4% tras costes (1129 fijos + 450 IEDMT est.) — bruto pasa umbral, neto deja poco margen. Falta Golf R puro; doble pasada por kW pendiente",
  "enlaces_muestra": [
    "https://suchen.mobile.de/fahrzeuge/details.html?id=...",
    "https://www.coches.net/..."
  ],
  "tasacion_pro": null,
  "refrescar_antes_de_categoria": "2026-08-31"
}
```

> **Ejemplo verificado:** hueco_pct 11,3 = (26500−23500)/26500 · hueco_neto_pct 5,4 = (26500−(23500+1579))/26500. Los números del ejemplo SIEMPRE deben poder reproducirse con las fórmulas de `SKILL.md` §Cálculo.
> **Normalización (L1):** `slug` = minúsculas, sin tildes, `-` por espacios, `golf-7`≡`golf-vii`, sin prefijo de marca ("vw-golf-7" NO "volkswagen-golf-7"). `alias` recoge las variantes reales del usuario/portales para que el lookup siempre acierte.

---

## Campos obligatorios (por objeto modelo)

| Campo | Tipo | Obligatorio | Nota |
|---|---|---|---|
| `slug` | string | ✅ | Clave canónica (L1). Normalizado: minúsculas, sin tildes, `-`, `golf-7`≡`golf-vii`, sin marca |
| `alias` | string[] | ⚠️ | Variantes reales (L1) para que el lookup acierte siempre |
| `modelo` | string | ✅ | Nombre de mercado ("VW Golf 7 GTI / R") |
| `version` | string | ⚠️ | Motorización/acabado; si no se aísla, indicarlo en `nota` |
| `categoria` | string | ✅ | Principal: `showstoppers`\|`alta_rotacion`\|`gemas_economicas` (L5: UNO solo) |
| `segmento` | string | ⚠️ | Tipo de vehículo: `compacto`\|`suv`\|`berlina`\|`deportivo`\|`familiar`\|`urbano` |
| `rango_precio` | string | ⚠️ | Banda: `0-8k`\|`8-14k`\|`14-25k`\|`25k+` (en origen DE) |
| `tipo_cliente` | string | ⚠️ | Perfil de comprador objetivo (ver §TIPOS DE CLIENTE en SKILL) |
| `tipos_cliente_secundarios` | string[] | ⚠️ | Otros perfiles a los que también encaja |
| `categorias_secundarias` | string[] | ⚠️ | Si el modelo también encaja en otra categoría (L5) |
| `oferta_de` / `oferta_es` | int | ✅ | Nº de anuncios (o `null` si no se pudo contar) |
| `mediana_de` / `mediana_es` | int (€) | ✅ | Precio de referencia (mobile.de / Coches.net) |
| `precio_desde_de` / `precio_desde_es` | int (€) | ⚠️ | Suelo verificado |
| `hueco_pct` | float | ✅ | BRUTO: (ES−DE)/ES × 100 — mismo criterio que `modelos-medidos.md` (verificado: Astra 30,9% / 24,4%) |
| `hueco_neto_pct` | float | ⚠️ | NETO: con costes de importación (1129 € fijos + `iedmt_estimado`). Para el veredicto de negocio |
| `coste_importacion_estimado` / `iedmt_estimado` | int (€) | ⚠️ | Usados para el neto; desglose en `nota` |
| `rotacion_dias_de` / `rotacion_dias_es` | int | ⚠️ | Separados (L9); ES a menudo null (difícil de medir) |
| `rotacion_fuente` | string | ⚠️ | `autouncle`\|`cochesnet`\|`null` — documentar de dónde viene |
| `demanda_trends` | string | ⚠️ | creciente/estable/decreciente |
| `transferencias_mes_dgt` / `matriculaciones_kba` | int | ⚠️ | Capa 1 (L8); null si no se consultó |
| `veredicto` | "verde"\|"amarillo"\|"rojo" | ✅ | Según criterio de la categoría |
| `mejor_mercado` | "DE"\|"ES"\|"paridad" | ✅ | Resultado del cruce (neto); NO duplicar con otro campo de origen |
| `fuente_medicion` | "estudio"\|"flujo_b"\|"flujo_a"\|"flujo_e_delta"\|"mini_estudio" | ✅ | L3: quién midió esta entrada. `mini_estudio` = medición rápida inline (4-6 peticiones) hecha por importacion-vehiculos cuando el Flujo B necesita mercado sin estudio previo (confianza 2-3) |
| `confianza_precio` | int (1-5) | ⚠️ | 1=anuncio, 5=tasación pro (Capa 3). El 🟢 puede exigir confianza ≥3 |
| `oportunidad` | bool | ⚠️ | true si `precio_desde_de` >15% bajo `mediana_de` y veredicto verde (chollo) |
| `enlaces_muestra` | string[] | ✅ | 1-2 enlaces de ejemplo (A21) |
| `refrescar_antes_de_categoria` | fecha | ✅ | Caducidad según la categoría del modelo (L7) |
| `equipamiento_nivel` | obj `{de,es}` | ⚠️ | Nivel de la mediana por mercado: `base`\|`medio`\|`full` (18-ago-2026, máx. equipamiento por defecto) |
| `equipamiento_de` / `equipamiento_es` | string[] | ⚠️ | Ítems clave reales (techo, cuadro digital, LED, HUD...) — verificado en ficha/listado |
| `equipamiento_ajuste_eur` | int | ⚠️ | Ajuste aplicado al precio ES si no compara nivel (primas de `comparables.md`) |
| `nota` | string | ⚠️ | Matices, pendientes, motorizaciones no comparables |
| `estado_cola` | string | ⚠️ | Estado en la cola de trabajo (21-ago-2026): `pendiente_estudio` \| `estudiando` \| `estudiado` \| `pendiente_busqueda` \| `buscado` \| `descartado`. Fuente de verdad: `cola_trabajo.estados` (este campo del objeto es espejo informativo, NO dual-write obligatorio) |

Opcionales (Capa 3, futuro): `tasacion_pro` (int €), `tasacion_pro_fuente` ("DAT"\|"Eurotax").

---

## Cola de trabajo compartida (21-ago-2026)

> La `cola_trabajo` es el **enrutador que dice "cuál es el siguiente"** para que las dos skills (estudio-mercado e importacion-vehiculos) trabajen en cascada sin pisarse ni repetir.

**Estados de cada modelo (`estados.<slug>`):**

| Estado | Quién lo pone | Significado | Acción siguiente |
|---|---|---|---|
| `pendiente_estudio` | Usuario/propuesta | Falta medir su mercado (hueco/demanda/rotación) | estudio-mercado lo mide (1 pasada modelo-por-modelo) |
| `estudiando` | estudio-mercado / mini-estudio | Medición EN CURSO (sesión corta o a medias) — guardado parcial si se interrumpe | Reanudar en la siguiente sesión (leer `nota`/progreso) |
| `estudiado` | estudio-mercado | Mercado medido y volcado al mapa | importacion-vehiculos puede buscar unidades si veredicto 🟢/🟡 |
| `pendiente_busqueda` | estudio-mercado tras 🟢/🟡 | Hay hueco, toca buscar unidades | importacion-vehiculos Flujo B (1 modelo por pasada) |
| `buscado` | importacion-vehiculos | Unidades buscadas y candidatos entregados | Feedback: volcar medición real al mapa (L3/L4) |
| `descartado` | Cualquiera | Sin hueco o sin encaje | No tocar; solo re-estudiar si cambia el mercado |

**Reglas:**
1. `siguiente_estudio` = el modelo `pendiente_estudio` con prioridad (caducidad más próxima o prioridad del usuario).
2. `siguiente_busqueda` = el modelo `pendiente_busqueda` más prioritario.
3. Al cerrar una pasada, la skill actualiza el estado y el puntero (`siguiente_*`).
4. Si un modelo está `pendiente_busqueda` y su `refrescar_antes_de_categoria` caducó → volver a `pendiente_estudio`.
5. Método de trabajo completo: ver `../importacion-vehiculos/02-flujos/como_deben_ser_las_sesiones.md`.
6. **Sesión corta o interrumpida (auditoría 21-ago):** si se corta a mitad de FASE B (estudio) o D (búsqueda), marcar el modelo `estudiando` con `nota` del progreso (fase alcanzada, listados leídos, pendientes) para reanudar en la siguiente sesión — nunca dejar el progreso solo en el chat.
7. **Merge de la cola entre sesiones (auditoría 21-ago):** al escribir `cola_trabajo`, NO sobrescribir los estados de modelos que esta sesión no tocó: leer el JSON actual y hacer MERGE por slug (mismo principio E10 que las entradas). Solo cambian `siguiente_*` y los `estados.<slug>` que esta sesión modificó.

**Estado resultante según quién mide (transiciones por fuente):**

| Origen de la medición | `fuente_medicion` | `estado_cola` resultante |
|---|---|---|
| estudio-mercado (pasada completa ES+DE+cruce) | `estudio` | `estudiado` (o `descartado` si 🔴 neto<0) |
| Mini-estudio inline (Flujo B sin mapa, 4-6 peticiones) | `mini_estudio` | `estudiado` (confianza 2-3) o `descartado` |
| Flujo B (barrido 7 fuentes de un modelo) | `flujo_b` | `buscado` |
| Flujo A (unidad evaluada, modelo nuevo con medianas) | `flujo_a` | `buscado`; sin medianas → `pendiente_estudio` |
| Flujo C/E (escaneo multi-modelo = estudio de facto) | `flujo_e_delta` | `estudiado` por cada modelo tocado |

---

## Convenciones de `veredicto`

| Categoría | 🟢 verde | 🟡 amarillo | 🔴 rojo |
|---|---|---|---|
| Showstoppers | atractivo alto + demanda creciente | atractivo alto, hueco bajo | sin atractivo ni demanda |
| Alta rotación | hueco bruto ≥10% **y neto >0** + oferta ES alta | bruto 0-10% o neto ≈0 | neto <0 (paridad o ES mejor) |
| Gemas | precio bajo + rotación rápida + fiable | accesible pero rotación lenta | caro de mantener/asegurar |

> El veredicto se calcula con datos del mapa, NO por intuición. Toda entrada lleva su `nota` explicando el porqué. Umbrales (Nicho ≥10%, EXIT <8%) se aplican sobre el **bruto**; el **neto** decide si hay negocio de importación real.

---

## Reglas de integridad

1. **Todo dato con fuente**: cada cifra procede de un listado/portal/estadística consultada en la fecha de `fuentes` (A21).
2. **Sin inventar**: si una métrica no se pudo medir, se deja `null` y se anota el motivo en `nota` (A9/A18).
3. **Motorizaciones no comparables**: si ES y DE no comparan la misma versión (ej. 115cv ES vs 190cv DE), marcar `pendiente_fase2: true` y NO dar hueco como definitivo.
4. **Caducidad**: al pasar `refrescar_antes_de_categoria` (por modelo, según su categoría), el modelo se re-estudia (o revalida con 1 lectura) antes de usarse en una búsqueda.

## 🔄 Campos que el SaaS puede devolver (market:export → leer y respetar)

El export de Laravel añade por modelo campos que esta skill debe RESPETAR al escribir (no sobrescribir):

| Campo | Significado | Regla para la skill |
|---|---|---|
| `veredicto_fuente` | `ia` \| `humano` | Si `humano`, NO sobrescribir el veredicto en la próxima pasada (calibración final) |
| `vendibilidad` | 0-100 (score compuesto) | Si viene, respetarlo; si falta, calcular con la fórmula de `market_models` |
| `publicar_en_catalogo` | bool | Si el SaaS lo publica, la skill puede conservarlo; el alta/retirada del catálogo la gestiona el admin |
| `foto_url` | string | URL de foto de muestra; la skill puede refrescarla si caduca |
| `historial` | array (últimas 5) | Se usa en el refresco para detectar tendencias (comparar mediana actual vs anterior >8% → anotar en `nota`) |

> **Regla de oro:** el JSON del SaaS (con correcciones humanas) es la versión más fiable del mapa. Al escribir, MERGE por `slug` respetando estos campos; nunca regenerar desde cero.

---

## Cómo lo consume `importacion-vehiculos`

- **PASO 0 cache:** leer `datos_mercado.json` → si un modelo está fresco (`refrescar_antes_de_categoria` en futuro), NO re-sondear; usar sus métricas.
- **FIJAR MODELOS (PASO 3b):** proponer la lista de candidatos ordenada por `veredicto` + `hueco_pct` + `demanda_trends`, citando el motivo ("Mercedes CLA: hueco X%, demanda creciente → entra").
- **Flujo E (stock):** el informe de búsqueda parte del mapa; solo se navega para refrescar lo caducado o llenar huecos.
