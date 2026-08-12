# Contrato JSON Claude → Laravel

> Define el formato exacto del JSON que se entrega a `dev.aktive.cloud/importnexcore`.
> Cargar al generar el paquete final o al escribir el JSON de un coche.
>
> **Adaptado a los 3 flujos** (A: UNIDAD, B: MODELO, C: MERCADO). Cada flujo tiene una estructura JSON distinta.

---

## 📋 Estructura por flujo

| Flujo | Archivo | Estructura JSON | Uso en Laravel |
|---|---|---|---|
| **A: UNIDAD** | `informe.json` dentro del ZIP | Vista completa, un solo coche | Crea/actualiza `Valoracion` |
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
  "historial_mantenimiento": "Libro de revisiones sellado"
}
```

Campos no confirmados van a `null`, **nunca inventados**. `co2_confirmado: false` → el dato viene del anuncio o estimación, no del COC. ⚠️ **El CO₂ determina el tramo del IEDMT.**

---

## `anuncio`

```json
{
  "portal": "mobile.de",
  "url": "https://www.mobile.de/...",
  "pais_origen": "Alemania",
  "ciudad": "Múnich",
  "precio_publicado": 12900,
  "moneda": "EUR",
  "vendedor_tipo": "Profesional",
  "vendedor_nombre": "Autohaus Beispiel GmbH",
  "fecha_captura": "2026-08-11",
  "fotos": ["https://..."],
  "descripcion_original": "Texto original...",
  "descripcion_traducida": "Traducción..."
}
```

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

> **Mapeo al importar (auditoría #12):** `ValuationImporter` traduce `valoracion` (español) a `rating` (inglés) al guardarlo en `Car.research` y `InvestigationCache` (mapea `favorable→good`, `neutro→neutral`, `desfavorable→bad` vía `RATING_MAP`). En cambio, `InvestigationCache` conserva la clave `valoracion` en el JSON crudo de `aspectos`. Es intencional: `Car` usa `rating` (normalizado), `InvestigationCache` guarda el payload original. No mezclar ambos nombres al consultar.

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

🔴 **Comparables SIEMPRE con URL.** Cada `comparables[].url` se construye con el id del anuncio. Sin URL = la fila no cuenta.

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

## 📝 Formato esqueleto `.txt` — para plantillas Blade

`empaquetar.py` ya NO genera PDFs. Escribe archivos `.txt` con bloques `[MARCADOR]` que la plantilla Blade de Laravel (`jj-import/folleto.blade.php`, `jj-import/briefing.blade.php`) convierte a PDF con Browsershot.

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
| `informe-interno.txt` | COSTE_COMPRA, COSTE_TOTAL, HONORARIOS, PRECIO_CLIENTE, COMPARABLE_AJUSTADO, MEDIANA_ES, MEDIANA_DE, HUECO, VENDIBILIDAD, VEREDICTO, RIESGOS, ALTERNATIVAS |
| `redes-sociales.txt` | GANCHO, POST_LARGO, POST_CORTO, STORIES, HASHTAGS, PIE_FOTO |
| `anuncio-portales.txt` | TITULO, DESCRIPCION, FICHA_RAPIDA, QUE_INCLUYE, AVISO_LEGAL |

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
Body: multipart/form-data con el ZIP (solo Flujo A)
       o application/json con el JSON suelto (Flujo A, B, C)
```

Comando local: `php artisan jj:importar` (lee de `export/`).

**Qué enviar según flujo:**

| Flujo | Endpoint | Estado | Formato |
|---|---|---|---|
| A | `POST /api/import-valuation` | ✅ Implementado (jul-2026) | ZIP multipart o JSON |
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
