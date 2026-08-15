# Contrato JSON Claude → Laravel

> Define el formato exacto del JSON que se entrega a `dev.aktive.cloud/importnexcore`.
> Cargar al generar el paquete final o al escribir el JSON de un coche.
>
> **Adaptado a los 4 flujos** (A: UNIDAD, B: MODELO, C: MERCADO, D: DESCUBRIMIENTO). El Flujo D no genera JSON de contrato (su entregable es el INFORME DE MODELOS); su embudo deriva a Flujo B/A.

---

## 📋 Estructura por flujo

| Flujo | Archivo | Estructura JSON | Uso en Laravel |
|---|---|---|---|
| **A: UNIDAD** | `informe.json` dentro del ZIP | Vista completa, un solo coche | Crea/actualiza `Car` |
| **B: MODELO** | `export/flujo-b-<modelo>-<fecha>.json` | Igual que A, sin `publicidad` | Histórico cacheable |
| **C: MERCADO** | `export/flujo-c-<fecha>.json` | Estructura agregada con N modelos | Tabla scouting |

---

## 🔁 Estructura base (común a A y B)

Bloques de primer nivel (siempre presentes en A y B):

| Bloque | Contenido |
|---|---|
| `_meta` | Versión esquema, fecha, origen, `coche_id`, `flujo` |
| `vehiculo` | Identificación y ficha técnica |
| `anuncio` | Portal, URL, vendedor, precio, fotos |
| `investigacion` | 9 aspectos, cada uno con hallazgo, fuente, valoración |
| `balance` | Puntos a favor y en contra con peso |
| `veredicto` | Recomendación razonada + precio objetivo |
| `costes` | Desglose completo puesto en España |
| `mercado` | Comparables reales + semáforo |
| `avisos` | Cosas sin confirmar |
| `publicidad` | *(solo Flujo A)* Texto de venta para ficha cliente |

---

## `_meta`

```json
{
  "schema_version": 1,
  "flujo": "A",
  "generado_el": "2026-08-11T12:00:00+02:00",
  "origen": "chat-ia",
  "coche_id": "opel-astra-opc-2014-a1b2c3",
  "client_id": null
}
```

- `schema_version`: 1 (actual)
- `flujo`: "A" | "B" | "C"
- `coche_id`: nombre archivo respaldo
- `client_id`: si se rellena, Laravel enlaza el coche a ese cliente

El importador de Laravel (`ValuationImporter.php`) empareja por **VIN**, luego por **`anuncio.url`**.

---

## `vehiculo`

```json
{
  "marca": "Opel",
  "modelo": "Astra",
  "version": "2.0 Turbo OPC",
  "anio": 2014,
  "km": 102000,
  "combustible": "Gasolina",
  "cambio": "Manual",
  "traccion": "Delantera",
  "carroceria": "Sportback",
  "puertas": 3,
  "plazas": 5,
  "potencia_cv": 280,
  "co2_gkm": 189,
  "co2_confirmado": true,
  "vin": "W0L...",
  "color_exterior": "Blanco",
  "color_interior": "Negro",
  "propietarios": 2,
  "equipamiento": ["Techo panorámico", "Navegador", "Asientos calefactables"],
  "garantia": "Sin garantía de fábrica restante",
  "accidentes_declarados": "El vendedor declara libre de accidentes",
  "historial_mantenimiento": "Libro de revisiones sellado",
  "fotos": ["https://..."]
}
```

Campos no confirmados van a `null`, **nunca inventados**. `co2_confirmado: false` → el dato viene del anuncio o estimación, no del COC. ⚠️ **El CO₂ determina el tramo del IEDMT.**

> 🔴 **`equipamiento` = lista COMPLETA del anuncio** (sección `Ausstattung`/features), NO solo los 15 destacados del `informe_tecnico.md`. Esa lista de 15 es para el informe humano; en el JSON van TODOS los items vistos en la captura. Laravel la muestra en la ficha y la usa para el ajuste de comparable y la ficha publicitaria. Sin equipamiento completo, el coche parece menos equipado de lo que está.

---

## `anuncio`

> **Origen (12-ago-2026):** `pais_origen` acepta `"Alemania"` (importación) o `"España"` (compra nacional). Los costes dependen de este valor (ver `costes.md` §Origen).

```json
{
  "portal": "mobile.de",
  "url": "https://www.mobile.de/...",
  "pais_origen": "Alemania",
  "ciudad": "Múnich",
  "precio_publicado": 12900,
  "precio_negociado": 12550,
  "moneda": "EUR",
  "dias_publicado": 45,
  "tuv_vigente_hasta": "04/2027",
  "vendedor_tipo": "Profesional",
  "vendedor_nombre": "Autohaus Beispiel GmbH",
  "fecha_captura": "2026-08-11",
  "lat": 52.3069,
  "lng": 9.7889,
  "descripcion_original": "Texto literal COMPLETO del anuncio...",
  "descripcion_traducida": "Traducción completa..."
}
```

> 🆕 **`lat`/`lng` (15-ago-2026):** coordenadas de la ciudad del anuncio (opcional). Si van, Laravel las guarda tal cual y el coche aparece en el mapa. Si no van, Laravel geolocaliza `ciudad` automáticamente (Nominatim con cache).

> 🔴 **`descripcion_original` = texto literal COMPLETO del anuncio, pegado tal cual** (el que ve el cliente en el portal, incluidas mayúsculas/errores del vendedor). NO resumir, NO corregir, NO inventar. `descripcion_traducida` = la traducción completa al español. Si solo hay una, las dos llevan el mismo texto. Laravel muestra ambas en la ficha (pestaña Resumen: "Texto original" / "Traducción").

> 🆕 **Campos extra del anuncio (12-ago-2026):** `dias_publicado` (señal de demanda: muchos días = baja rotación DE), `tuv_vigente_hasta` (TÜV/HU en DE, el equivalente a la ITV — caduca o no: dato clave para importación), `precio_publicado` vs `precio_negociado` (sirve de pista de negociación). El importer los guarda en `Car.notes`.

> ⚠️ **Las fotos van en `vehiculo.fotos`, NO en `anuncio.fotos`** — `ValuationImporter::savePhotosAndFiles()` lee `vehiculo.fotos`. Si las fotos van en `anuncio.fotos`, Laravel no las descarga.

---

## `investigacion` — 9 aspectos (siempre presentes en A y B)

```json
{
  "problemas_comunes":  {"hallazgo": "...", "fuente": "https://...", "valoracion": "desfavorable", "fecha": "11/08/2026"},
  "recalls":            {"hallazgo": "...", "fuente": "https://kfz-rueckrufe.de/...", "valoracion": "favorable", "fecha": "11/08/2026"},
  "precio_mercado":     {"hallazgo": "...", "fuente": "https://...", "valoracion": "neutro", "fecha": "11/08/2026"},
  "fiabilidad":         {"hallazgo": "...", "fuente": "https://...", "valoracion": "favorable", "fecha": "11/08/2026"},
  "homologacion":       {"hallazgo": "...", "fuente": "https://...", "valoracion": "favorable", "fecha": "11/08/2026"},
  "etiqueta_ambiental": {"hallazgo": "...", "fuente": "https://...", "valoracion": "neutro", "fecha": "11/08/2026"},
  "seguro":             {"hallazgo": "...", "fuente": "https://...", "valoracion": "neutro", "fecha": "11/08/2026"},
  "piezas":             {"hallazgo": "...", "fuente": "https://...", "valoracion": "favorable", "fecha": "11/08/2026"},
  "otros":              {"hallazgo": "...", "fuente": "https://...", "valoracion": "", "fecha": "11/08/2026"}
}
```

`valoracion`: solo `favorable`, `neutro`, `desfavorable` o vacío. Hallazgo vacío = "no se investigó". Si se investigó y salió limpio, el texto lo dice con valoración `favorable`. **No perder esta distinción al importar.**

> **Mapeo al importar (auditoría #12):** `ValuationImporter` traduce `valoracion` (español) a `rating` al guardarlo en `Car.research` y `InvestigationCache` (mapea `favorable→favorable`, `neutro→neutral`, `desfavorable→unfavorable` vía `RATING_MAP`). En cambio, `InvestigationCache` conserva la clave `valoracion` en el JSON crudo de `aspectos`. Es intencional: `Car` usa `rating` (normalizado), `InvestigationCache` guarda el payload original. No mezclar ambos nombres al consultar.

### `boe_confirmed` (auditoría #13)

`Car.boe_confirmed` indica si la base imponible proviene de precio BOE verificado. El flujo actual del skill **siempre** usa `manual_tax_base` (= `pvp_nuevo` del chat); `boe_confirmed` queda `false` y `Car::calculateIEDMT()` usa `manual_tax_base`. El campo solo pasa a `true` cuando un proceso futuro confirme el PVP en BOE; entonces `calculateIEDMT()` cambiaría a usar `new_price`. No asumir que está activo hoy.

Caducidades: recalls 6 meses · seguro y piezas 12 · averías 18 · homologación 24.

---

## `balance` y `veredicto`

```json
{
  "balance": {
    "a_favor":  [{"texto": "Kilometraje bajo para el año", "peso": "alto"}],
    "en_contra": [{"texto": "Distribución sin cambiar", "peso": "alto"}]
  },
  "veredicto": {
    "recomendacion": "Comprar si baja de precio",
    "confianza": "media",
    "razonamiento": "Tres a cinco líneas...",
    "que_cambiaria": "Si el COC confirma 189 g/km...",
    "precio_objetivo": 11800,
    "fecha": "11/08/2026"
  }
}
```

`recomendacion`: `Comprar`, `Comprar si baja de precio`, `Dudoso`, `Descartar`.
`peso`: `alto`/`medio`/`bajo`. `confianza`: `alta`/`media`/`baja`.
🔴 **`precio_objetivo` es obligatorio cuando la recomendación es "Comprar si baja de precio".**

---

## `costes`

```json
{
  "precio_coche": 12900,
  "pvp_nuevo": 32250,
  "transporte": 900,
  "itv_matriculacion": 95,
  "tasa_dgt": 20.61,
  "iedmt_estimado": 280,
  "iedmt_sin_minoracion": 365,
  "iedmt_metodologia": "Base = PVP × coef Anexo IV; minoración art.69; IEDMT = base × 14.75% (≥200 g/km).",
  "gestoria": 0,
  "otros": 114,
  "coste_total": 14309.61,
  "honorarios": 1500,
  "precio_cliente": 15809.61,
  "iedmt_es_estimacion": true
}
```

> 📐 **Cálculo del IEDMT:** single source of truth en [`costes.md` §IEDMT](costes.md#-iedmt-orden-hac15012025-vigor-1-ene-2026) (Orden HAC/1501/2025, vigor 1-ene-2026). Ahí está la fórmula completa, los tramos de CO₂ y los coeficientes por antigüedad.

🔴 **`pvp_nuevo` es OBLIGATORIO.** Laravel recalcula el IEDMT con `Car::calculateIEDMT()` a partir del PVP nuevo, antigüedad y CO₂. Sin él, IEDMT = 0 € y el coste total sale mal. Pasar el PVP **sin depreciar**: el coeficiente de antigüedad lo aplica la app.

**`iedmt_sin_minoracion`** (NUEVO, 11-ago-2026): Claude calcula dos veces el IEDMT, con y sin minoración art.69, para que el gestor fiscal elija. Laravel verifica y, si difieren >10%, marca en `avisos`.

**`iedmt_metodologia`**: cadena corta describiendo cómo se calculó (ej: `"PVP km77: 32.250€. Antigüedad: 8 años (37%). CO₂: 145 g/km (9,75%). Con minoración art.69: 280€. Sin minoración: 365€."`). Sirve de pista al gestor fiscal sin tener que desglosar la fórmula completa en este contrato.

`honorarios` + `gestoria` se suman en la columna `professional_fees` de la app.

`otros` = matrículas temporales (~114 € `Ausfuhrkennzeichen` + seguro). Si el coche va en camión, 0 €.

`iedmt_es_estimacion` casi siempre `true`: Hacienda usa sus propias tablas.

---

## `mercado`

```json
{
  "comparables": [
    {"titulo": "Opel Astra OPC 2014", "precio": 16400, "km": 79000, "url": "https://...", "pais": "España"}
  ],
  "precio_medio": 16250,
  "precio_min": 15400,
  "precio_max": 17200,
  "ahorro_estimado": 534.39,
  "semaforo": "green"
}
```

`semaforo`: `green` (precio ≤ media), `amber` (hasta +5%), `red` (>+5%).

> ⚠️ **`semaforo` es informativo — NO se persiste.** Laravel lo recalcula automáticamente en `CarObserver::saving()` a partir del coste total y la media de mercado (`traffic_light`). El valor del chat sirve para verificar coherencia, pero el que manda en la app es el recalculado.

🔴 **Comparables SIEMPRE con URL del anuncio DIRECTO.** Cada `comparables[].url` es la ficha del vehículo (p.ej. `https://suchen.mobile.de/fahrzeuge/details.html?id=123456`), construida con el id del anuncio. **NUNCA** una URL de búsqueda/filtro del portal (`/fahrzeuge/...?sortOption=...&categories=...`) — eso lleva al cliente a una lista, no al coche. Sin URL = la fila no cuenta.

---

## `avisos`

```json
[
  "El CO2 no está confirmado por COC: el IEDMT puede variar.",
  "El cálculo de Claude y Laravel difieren >10% en IEDMT"
]
```

Lista de strings. Mostrar siempre junto a la ficha.

---

## `publicidad` (solo Flujo A)

```json
{
  "publicidad": {
    "titular": "Opel Astra OPC 280 CV — el GTI que nadie espera",
    "claim": "280 caballos, un solo dueño y papeles al día.",
    "argumentos": [
      "**Etiqueta C de la DGT:** entra en ZBE sin restricciones.",
      "**Muy poco uso:** 8.800 km/año de media."
    ],
    "incluye": ["El vehículo", "Transporte hasta España", "ITV de importación"]
  }
}
```

**`argumentos` nunca del `balance.a_favor`.** Eso es análisis interno (margen, reputación vendedor, riesgo) y no se muestra al cliente. Énfasis con `**negrita**` (no HTML).

---

## `dossier` (solo Flujo A · ver `dossier_cliente.md`)

Bloque opcional que activa la generación del `dossier-cliente.txt` (PDF profesional para cliente).
Solo se rellena si el veredicto es `Comprar` o `Comprar si baja de precio`.

```json
{
  "dossier": {
    "dossier_num": "JJM-2026-08-12-0042",
    "carta_presentacion": "Estimado cliente, en JJ Import Motors...",
    "resumen_30s": {
      "oportunidades": ["Configuración deseada", "Estado verificado"],
      "atencion": ["CO₂ puede variar ±5 g/km → IEDMT ±50-80 €"],
      "proximo_paso": "Reserva 1.000 € para iniciar compra"
    },
    "equipamiento_destacado": ["Pack Performance", "Asientos Alcantara", "Techo"],
    "estado_verificado": ["1 propietario particular", "Libro sellado", "TÜV vigente"],
    "estado_pendiente": ["Neumáticos", "Frenos", "COC completo"],
    "mercado_es": {
      "min": 32400, "q1": 33200, "mediana": 34500, "q3": 36100, "max": 39900, "n": 8
    },
    "nuestra_oferta": 28500,
    "ahorro_eur": 6000,
    "ahorro_pct": 17.4,
    "de_vs_es": {
      "precio_de": 26800, "precio_es": 34500,
      "uds_de": 12, "uds_es": 8,
      "hueco_pct": 22.4
    },
    "eval_tecnica": {
      "motor": "EA888 2.0 TSI 300 CV",
      "fiabilidad": "Buena",
      "problemas_conocidos": ["Bobinas 60k", "Cadena distribución 100k"],
      "recalls_activos": false
    },
    "coste_transparente": [
      {"concepto": "Compra del vehículo (DE)", "importe": 21950, "nota": ""},
      {"concepto": "Transporte DE → ES", "importe": 900, "nota": ""},
      {"concepto": "ITV + tasas DGT", "importe": 115, "nota": ""},
      {"concepto": "Impuesto matriculación (IEDMT)", "importe": 830, "nota": "* Estimado"},
      {"concepto": "Matrícula + gestoría", "importe": 305, "nota": ""},
      {"concepto": "Honorarios JJ Import Motors", "importe": 4400, "nota": "** Fijos declarados"}
    ],
    "coste_total": 28500,
    "timeline": [
      {"semana": "0", "fase": "Reserva y encargo"},
      {"semana": "1", "fase": "Compra y verificación"},
      {"semana": "2-3", "fase": "Transporte y trámites DE"},
      {"semana": "3-4", "fase": "Llegada y trámites ES"},
      {"semana": "5-6", "fase": "Entrega"}
    ],
    "garantia_incluido": ["Gestión integral", "Verificación documental", "Inspección previa", "Soporte 30 días"],
    "garantia_no_incluido": ["Garantía mecánica", "Problemas ocultos", "Mantenimiento ordinario"],
    "faq": [
      {"q": "¿Puedo ver el coche antes?", "a": "Vídeo inspección 60 fotos + 5 min."},
      {"q": "¿Y si hay problemas al llegar?", "a": "30 días para reportar incidencias."}
    ],
    "pasos": [
      "Reserva 1.000 € → bloqueo vehículo",
      "Vídeo inspección (48-72h)",
      "Validación: confirmación o reembolso",
      "Firma contrato + pago 40%",
      "Inicio del proceso de importación"
    ]
  }
}
```

🔴 **Regla dura:** `dossier.coste_transparente` suma EXACTAMENTE `dossier.coste_total`.
El importe de "Compra del vehículo" incluye margen interno camuflado — NUNCA reflejar
el precio real del anuncio alemán en este bloque (iría al `informe-interno.txt`).

🔴 **`dossier_num`** generado por Claude con formato `JJM-AAAA-MM-DD-####` (4 dígitos,
secuencial diario). El contador vive en `datos/contador_dossier.json` en Desktop.

---

## 📄 Estructura JSON para Flujo C (MERCADO)

Estructura agregada con N entradas. NO tiene los bloques `vehiculo`, `anuncio`, `investigacion`, `costes`, `publicidad` por unidad.

```json
{
  "_meta": {
    "schema_version": 1,
    "flujo": "C",
    "generado_el": "2026-08-11T...:00+02:00",
    "origen": "chat-ia",
    "scouting_id": "scouting-2026-08-11-deportivos-25k",
    "preferencias_usuario": "Deportivos y premium, 15-40k€, gasolina, automático"
  },
  "modelos_escaneados": 7,
  "modelos_con_hueco": 4,
  "modelos_sin_hueco": 3,
  "modelos": [
    {
      "modelo": "VW Golf GTI Clubsport",
      "segmento": "Nicho",
      "hueco_pct": 22.4,
      "n_uds_de": 12,
      "mediana_es": 34500,
      "mediana_de": 26800,
      "vendibilidad_estimada": 85,
      "recomendacion_aprox": "🟢 Medir ya",
      "mejor_anuncio_url": "https://www.mobile.de/...",
      "fuente_cobertura": {
        "coches_net": "OK",
        "mobile_de": "OK",
        "auto_uncle": "OK"
      }
    },
    {
      "modelo": "BMW M240i",
      "segmento": "Nicho",
      "hueco_pct": 14.8,
      "n_uds_de": 6,
      "vendibilidad_estimada": 82,
      "recomendacion_aprox": "🟡 Justo",
      "mejor_anuncio_url": "https://www.autoscout24.de/..."
    }
  ],
  "resumen_ejecutivo": "2 modelos con hueco claro, 1 dudoso, 3 descartados."
}
```

**Compara con Flujo A y B:**
- ❌ NO `vehiculo`, `anuncio`, `investigacion`, `costes`, `publicidad`
- ✅ `modelos[]` agregado, N entradas
- ✅ Cada modelo tiene `mejor_anuncio_url` (no todos los anuncios)
- ✅ `fuente_cobertura` solo con las 3 fuentes de Fase 1
- ✅ `recomendacion_aprox` usa emoji: 🟢 🟡 🔴

---

## 🎯 Estructura JSON para Flujo B (MODELO)

Idéntica a Flujo A, con estas diferencias:

| Diferencia | Por qué |
|---|---|
| `_meta.flujo` = "B" | Para que Laravel no confunda con un coche evaluable |
| **NO** incluye `publicidad` | El Flujo B es investigación, no venta |
| `veredicto.recomendacion` puede ser "🟡 Investigar más" | No se toman decisiones de venta aquí |
| `mercado.precio_medio` puede ser más amplio | Es un muestreo, no un comparable ajustado |

---

## `extras` (solo Flujo A · ver `informe_tecnico.md`)

Bloque opcional con el análisis técnico avanzado para el informe interno. Solo se rellena
en Flujo A cuando se va a decidir la compra.

```json
{
  "extras": {
    "score_global": 84,
    "score_dim": {
      "margen_vs_objetivo": 21,
      "vendibilidad": 21,
      "cobertura_fuentes": 14,
      "calidad_datos_vendedor": 13,
      "riesgo_residual": 8,
      "confianza_veredicto": 7
    },
    "margen_vs_referencias": {
      "vs_mediana": {"eur": 6000, "pct": 21.1, "semaforo": "green"},
      "vs_q1": {"eur": 4700, "pct": 16.5, "semaforo": "green"},
      "vs_comp_ajustado": {"eur": 5800, "pct": 20.3, "semaforo": "green"},
      "vs_min": {"eur": 3900, "pct": 13.7, "semaforo": "amber"}
    },
    "iedmt_sensibilidad": {
      "co2_mas_5g": 475,
      "co2_menos_5g": 305,
      "sin_minoracion": 1301,
      "rango_min": 305,
      "rango_max": 1301
    },
    "prediccion_venta": {
      "optimista": {"precio": 31500, "dias_min": 18, "dias_max": 25, "margen_eur": 3000, "margen_pct": 10.5},
      "base": {"precio": 33200, "dias_min": 30, "dias_max": 45, "margen_eur": 4700, "margen_pct": 16.5},
      "conservador": {"precio": 34500, "dias_min": 50, "dias_max": 70, "margen_eur": 6000, "margen_pct": 21.1},
      "pesimista": {"precio": 36100, "dias_min": 75, "dias_max": 90, "margen_eur": 7600, "margen_pct": 26.7},
      "recomendada": "base"
    },
    "negociacion": {
      "precio_publicado": 26800,
      "precio_objetivo": 25950,
      "precio_tope": 26500,
      "mensaje_aleman": "Sehr geehrte Damen und Herren...",
      "backup_b_url": "https://www.autoscout24.de/...",
      "backup_c_url": "https://www.mobile.de/..."
    },
    "riesgos_mitigacion": [
      {"riesgo": "CO₂ COC distinto", "probabilidad": "Media", "impacto": "Alto", "mitigacion": "Solicitar COC antes pago"},
      {"riesgo": "Vendedor no negocia", "probabilidad": "Baja", "impacto": "Medio", "mitigacion": "Backup B/C"}
    ],
    "banderas": {
      "rojas": [],
      "amarillas": ["45 días publicado", "Pack Performance mantenimiento caro"]
    },
    "accion_inmediata": [
      "Enviar email negociación",
      "Preparar contrato servicios",
      "Solicitar COC",
      "Pedir reserva 1.000 €",
      "Confirmar transporte",
      "Preparar dossier cliente",
      "Enviar dossier + vídeo"
    ],
    "plazo_objetivo_dias": 7
  }
}
```

`score_global` se compone de 6 dimensiones (25+25+15+15+10+10 = 100).
Ver `informe_tecnico.md` §15 para la fórmula de cálculo de cada dimensión.

`prediccion_venta.recomendada` es uno de `optimista|base|conservador|pesimista`.
Sirve para fijar el precio de salida en la ficha publicitaria.

---

## 📝 Formato esqueleto `.txt` — para plantillas Blade

`empaquetar.py` ya NO genera PDFs (ni de marketing ni de investigación). Escribe archivos `.txt` con bloques `[MARCADOR]` que las plantillas Blade de Laravel (`jj-import/folleto.blade.php`, `jj-import/ficha-coche.blade.php`, `jj-import/informe-interno.blade.php`) convierten a PDF con Browsershot. El documento del cliente en Laravel es `ficha-coche` (desde `ficha-publicitaria.txt`); `dossier-cliente.txt` es el esqueleto extendido (15 secciones) que el ingestor guarda en `cars/{id}/contenido/`. Los PDFs de investigación (búsqueda/unidad) los genera Claude aparte (ver SKILL.md §Quién genera cada PDF) y se entregan al usuario; NO van dentro del ZIP.

> **Documento del cliente en Laravel:** `ficha-coche.blade.php` (desde `ficha-publicitaria.txt`). El `dossier-cliente.txt` (15 secciones) es el esqueleto extendido que el ingestor guarda en `cars/{id}/contenido/`; sirve de base al dossier PDF que genera Claude (investigación) si aplica.

### Reglas del formato

1. Línea que empieza por `[NOMBRE]` **abre un bloque**
2. Texto en misma línea = contenido del bloque
3. Sin texto = líneas siguientes hasta el próximo bloque
4. Líneas con `#` al inicio = comentarios, se descartan
5. Mismo `[NOMBRE]` repetido = lista (orden de aparición)
6. Campos múltiples con ` | ` (espacio-barra-espacio)
7. Énfasis con `**negrita**` (no HTML)
8. Bloques vacíos **no se escriben**

### Ejemplo

```
[TITULO] Opel Astra OPC 280 CV
[PRECIO] 15.810 EUR
[AHORRO] +2.386 EUR (17,3%)
[SPEC] 2014 | 102.000 km | Gasolina | Manual | 280 CV
[ETIQUETA] C

[DESCRIPCION]
El Opel Astra J OPC es el último gran GTi de Opel. 280 CV...

[QUE_INCLUYE] El vehículo | Transporte hasta España | ITV de importación

[AVISO_LEGAL]
Servicio de gestión de importación. El vehículo se importa y matricula a nombre del cliente.
```

### Parser PHP

```php
namespace App\Support;

class Esqueleto
{
    public array $nombrados = [];
    public array $orden = [];

    public static function desde(string $contenido): self { /* ver FORMATO_ESQUELETO.md */ }
    public function uno(string $nombre): ?string { /* primer valor */ }
    public function todos(string $nombre): array { /* todos los valores */ }
}
```

### Bloques por archivo (Flujo A)

| Archivo | Bloques esperados |
|---|---|
| `ficha-publicitaria.txt` | TITULO, SUBTITULO, PRECIO, AHORRO, ESPECIFICACIONES, ETIQUETA, DESTACADOS, DESCRIPCION, QUE_INCLUYE, AVISO_LEGAL, FOTOS, CONTACTO |
| `informe-interno.txt` | Ver `informe_tecnico.md` §formato-txt (15 secciones, ~60 bloques). **Los bloques `MARGEN`, `VENTA`, `IEDMT_SENSIBILIDAD`, `SCORE_DIM`, `RIESGO`, `BANDERA_ROJA/AMARILLA`, `COBERTURA`, `CAND_*`, `NEG_*`, `COMP_AJUSTE`, `VENDIBILIDAD_FACTOR`, `ACCION` se renderizan como filas/tablas en `informe-interno.blade.php`.** |
| `dossier-cliente.txt` | Ver `dossier_cliente.md` §formato-txt (15 secciones, ~50 bloques). **Los bloques `FICHA_TECNICA`, `EQUIPAMIENTO`, `MERCADO_*`, `COSTE_LINEA`, `TIMELINE_SEMANA`, `FAQ_Q/A`, `PASOS`, `GARANTIA_*`, `ESTADO_*`, `DE_VS_ES`, `EVAL_*` se renderizan en el documento del cliente de Laravel (`ficha-coche.blade.php`); `dossier.blade.php` NO existe.** |
| `redes-sociales.txt` | GANCHO, POST_LARGO, POST_CORTO, STORIES, HASHTAGS, PIE_FOTO |
| `anuncio-portales.txt` | TITULO, DESCRIPCION, FICHA_RAPIDA, QUE_INCLUYE, AVISO_LEGAL |

> **Nota ingestor:** `ValuationPackageIngestor` guarda **cualquier `.txt` dentro de `contenido/`** del ZIP en `cars/{id}/contenido/`. Por tanto `dossier-cliente.txt` se persiste automáticamente sin tocar el ingestor. Rutas disponibles: `cars.ficha`, `cars.dossier` (autenticado), `cars.informe-interno` (solo owner/operator).

---

## 📦 Datos de marca

```json
{
  "nombre": "JJ Import Motors",
  "lema": "Confianza · Rapidez · Exclusividad",
  "descriptor": "Importación de vehículos",
  "telefono": "675 70 14 39",
  "telefono_2": "691 48 59 27",
  "whatsapp": "",
  "email": "jjimportmotors@gmail.com",
  "web": "",
  "instagram": "",
  "ciudad_entrega": "",
  "formulario": "https://dev.aktive.cloud/importnexcore/request/jj-import-motors",
  "plazo_entrega": "4-6 semanas desde la reserva",
  "logo": "marca/JJ_logo_horizontal_blanco.png",
  "isotipo": "marca/JJ_logo_insignia.png",
  "colores": {
    "principal": "#1A306D",
    "secundario": "#38393D",
    "claro": "#BEC0C3",
    "acento": "#E8590C"
  },
  "legal": "Servicio de gestión de importación. El vehículo se importa y matricula a nombre del cliente; JJ Import Motors actúa como gestor de importación, no como vendedor del vehículo. Precio sujeto a confirmación de emisiones (CO2/COC) y a disponibilidad del vehículo en origen."
}
```

---

## 🔌 Endpoint de importación

```
POST https://dev.aktive.cloud/importnexcore/api/import-valuation
Header: X-Import-Token: <token>
Body: application/json (Flujo A, B, C). El ZIP con fotos se sube por la ruta web `POST /cars/import-valuation` (panel).
```

Comando local: `php artisan importnex:import-valuation --file=<ruta-json>` (lee de `storage/app/importnex/import/`).

**Qué enviar según flujo:**

| Flujo | Endpoint | Estado | Formato |
|---|---|---|---|
| A | `POST /api/import-valuation` | ✅ Implementado (jul-2026) | JSON (el ZIP va por web `/cars/import-valuation`) |
| B | `POST /api/import-modelo` | ✅ Implementado (ago-2026) | JSON |
| C | `POST /api/import-mercado` | ✅ Implementado (ago-2026) | JSON |

**Autenticación:** Todos los endpoints requieren cabecera `X-Import-Token` con el token configurado en `config('services.importnex_chat.token')`.

**Uso desde el chat (curl):**

```bash
# Flujo A (UNIDAD) - Coche individual
curl -X POST https://jjimportmotors.on-forge.com/api/import-valuation \
  -H "X-Import-Token: <token>" \
  -H "Content-Type: application/json" \
  --data @informe.json

# Flujo B (MODELO) - Investigación de modelo
curl -X POST https://jjimportmotors.on-forge.com/api/import-modelo \
  -H "X-Import-Token: <token>" \
  -H "Content-Type: application/json" \
  --data @flujo-b-golf-gti-2026-08-11.json

# Flujo C (MERCADO) - Scouting de mercado
curl -X POST https://jjimportmotors.on-forge.com/api/import-mercado \
  -H "X-Import-Token: <token>" \
  -H "Content-Type: application/json" \
  --data @flujo-c-2026-08-11.json
```

**Respuestas:**

| Flujo | Éxito | Error |
|---|---|---|
| A | `{"status":"created","car_id":123,"car_url":"..."}` | `{"error":"..."}` |
| B | `{"status":"created","flujo":"B","car_id":123,"car_url":"..."}` | `{"error":"..."}` |
| C | `{"status":"created","scouting_id":"...","modelos_count":7}` | `{"error":"..."}` |

**Notas:**

- **Flujo B:** Valida que `_meta.flujo = "B"`. Elimina bloque `publicidad` si viene (Flujo B no debe tener decisión de venta).
- **Flujo C:** Valida que `_meta.flujo = "C"`. Crea/actualiza `ScoutingMercado` por `scouting_id` (idempotente).
- **Flujo C:** Si el `scouting_id` ya existe, elimina los modelos anteriores y crea los nuevos.
