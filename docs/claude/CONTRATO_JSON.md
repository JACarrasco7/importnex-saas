# Contrato de exportación — valoraciones de coche hacia la app Laravel

Este documento define **el formato exacto** en el que el chat te entrega una valoración de coche para que tu app Laravel la importe. Es un contrato: mientras no se cambie a propósito (subiendo `schema_version`), el formato no varía, así que el código de importación que escribas una vez sigue funcionando.

## La idea

El reparto de trabajo queda así:

**Aquí (chat + IA)** se hace lo que la IA aporta de verdad: abrir el anuncio, extraer los datos, investigar el modelo a fondo (averías conocidas, recalls, homologación, etiqueta ambiental, seguro, piezas), buscar comparables de mercado reales, calcular el coste puesto en España y emitir un veredicto razonado.

**En tu app Laravel** vive la operación: inventario, clientes, documentación, pagos, trámites, estados. Todo lo que es gestión del día a día y consulta.

El puente entre las dos cosas es un archivo JSON por coche.

## Cómo llega hoy, y cómo llegará cuando tengas servidor

**Ahora (local):** el chat escribe el archivo en

```
JJImportMotors/laravel/export/coche_<id>.json
```

y tú lanzas en tu proyecto Laravel:

```bash
php artisan jj:importar
```

**Cuando tengas servidor:** el mismo JSON, sin un solo cambio, se envía por HTTP a un endpoint tuyo. Solo cambia el transporte. Por eso el archivo lleva el bloque `_meta` con `schema_version`: tu importador puede aceptar las dos vías con el mismo código de validación.

Esto es lo importante de decidirlo ahora: si el formato es estable desde el principio, migrar de fichero a API es cambiar diez líneas, no rehacer la integración.

## Estructura del JSON

Cada archivo es **un coche**. Bloques de primer nivel:

| Bloque | Qué lleva |
|---|---|
| `_meta` | Versión del esquema, fecha de generación y origen. Tu importador debe mirar esto primero. |
| `vehiculo` | Identificación y ficha técnica del coche. |
| `anuncio` | De dónde sale: portal, URL, vendedor, precio publicado, fotos. |
| `investigacion` | Los 9 aspectos investigados, cada uno con hallazgo, fuente y valoración. |
| `balance` | Puntos a favor y en contra, con peso. |
| `veredicto` | La recomendación razonada: qué hacer y por qué. |
| `costes` | Desglose del coste de puesta en España. |
| `mercado` | Comparables reales encontrados y posición del coche frente a ellos. |
| `avisos` | Cosas que quedan sin confirmar y que no deben darse por buenas. |

### `_meta`

```json
{
  "schema_version": 1,
  "generado_el": "2026-07-29T12:00:00+02:00",
  "origen": "chat-ia",
  "coche_id": "opel-astra-2019-a1b2c3",
  "client_id": null
}
```

**Nota (30/07/2026 — actualizado tras conectar el proyecto real `importnexcore`):** el importador real (`app/Services/ValuationImporter.php`) empareja por **VIN, y si no hay match, por `anuncio.url`**; si ninguno coincide, crea un coche nuevo. `coche_id` ya no es la clave de emparejamiento en la base de datos — se usa solo como nombre de archivo para la copia de respaldo local. `client_id` es opcional: si lo rellenas con el id numérico de un cliente ya existente en la app, el coche importado queda enlazado a su ficha automáticamente. El endpoint real es `POST /api/import-valuation` (token en cabecera `X-Import-Token`, ver `app/Http/Controllers/Api/ImportValuationApiController.php`) — la skill `importacion-vehiculos` ya construye este JSON y lo sube sola, este documento queda como referencia de campos, no como guía de implementación (esa ya existe y funciona).

### `vehiculo`

```json
{
  "marca": "Opel",
  "modelo": "Astra",
  "version": "1.5 CDTi Business Elegance",
  "anio": 2019,
  "km": 84500,
  "combustible": "Diésel",
  "cambio": "Manual",
  "traccion": "Delantera",
  "puertas": 5,
  "plazas": 5,
  "potencia_cv": 122,
  "co2_gkm": 109,
  "co2_confirmado": false,
  "vin": "W0LBD6EA1KG123456",
  "color_exterior": "Gris grafito",
  "color_interior": "Negro",
  "propietarios": 1,
  "equipamiento": ["Navegador", "Cámara trasera", "Faros LED", "Climatizador bizona"],
  "garantia": "Sin garantía de fábrica restante",
  "accidentes_declarados": "El vendedor declara libre de accidentes",
  "historial_mantenimiento": "Libro de revisiones completo en concesionario oficial"
}
```

Los campos que no se hayan podido confirmar van a `null`, **nunca inventados**. `co2_confirmado` en `false` significa que el dato viene del anuncio o de una estimación, no del COC — es relevante porque el CO2 determina el tramo del impuesto de matriculación.

### `anuncio`

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
  "fecha_captura": "2026-07-29",
  "fotos": ["https://drive.google.com/...", "..."],
  "descripcion_original": "Texto original del anuncio",
  "descripcion_traducida": "Traducción al español"
}
```

**⚠️ CRITICAL — mobile.de image URLs (bug corregido 30/07/2026):**

mobile.de usa CDN con parámetros `rule` para transformar imágenes:
- **VÁLIDOS**: `?rule=mo-1024`, `?rule=mo-1600`, `?rule=mo-640w`, `?rule=mo-360`
- **INVÁLIDO**: `?rule=mo-720x540.jpg` ← **JAMÁS inventar sufijos de resolución en el parámetro rule**

Las URLs de imágenes en el JSON **DEBEN** copiarse tal cual aparecen en el anuncio, sin modificar el parámetro `rule` existente. Si el anuncio no trae `rule`, añade `?rule=mo-1024` para buena calidad, pero NUNCA inventes formatos como `720x540`.

El fallo que causó el error del coche ID 455420293 fue inventar `?rule=mo-720x540.jpg` en vez de copiar la URL original. Las reglas reales son específicas del CDN (mo-1024, mo-1600, etc.), no resolución arbitraria.

**Respeta las URLs del anuncio. No te inventes parámetros.**
```

### `investigacion`

Las nueve claves son **siempre las mismas y siempre están presentes**, aunque algún hallazgo venga vacío. Eso permite que tu app las pinte como una tabla fija sin comprobar si existen.

```json
{
  "problemas_comunes":  {"hallazgo": "...", "fuente": "https://...", "valoracion": "desfavorable", "fecha": "29/07/2026"},
  "recalls":            {"hallazgo": "...", "fuente": "https://...", "valoracion": "favorable",    "fecha": "29/07/2026"},
  "precio_mercado":     {"hallazgo": "...", "fuente": "https://...", "valoracion": "neutro",       "fecha": "29/07/2026"},
  "fiabilidad":         {"hallazgo": "...", "fuente": "https://...", "valoracion": "favorable",    "fecha": "29/07/2026"},
  "homologacion":       {"hallazgo": "...", "fuente": "https://...", "valoracion": "favorable",    "fecha": "29/07/2026"},
  "etiqueta_ambiental": {"hallazgo": "...", "fuente": "https://...", "valoracion": "neutro",       "fecha": "29/07/2026"},
  "seguro":             {"hallazgo": "...", "fuente": "https://...", "valoracion": "neutro",       "fecha": "29/07/2026"},
  "piezas":             {"hallazgo": "...", "fuente": "https://...", "valoracion": "favorable",    "fecha": "29/07/2026"},
  "otros":              {"hallazgo": "...", "fuente": "https://...", "valoracion": "",             "fecha": "29/07/2026"}
}
```

`valoracion` solo admite: `favorable`, `neutro`, `desfavorable` o cadena vacía. Úsalo para colorear en tu interfaz.

Un `hallazgo` vacío significa "no se investigó". Si se investigó y salió limpio, el texto lo dirá explícitamente ("no se han encontrado campañas abiertas para este VIN") con valoración `favorable` — **es una distinción importante y no debe perderse al importar**.

### `balance` y `veredicto`

```json
{
  "balance": {
    "a_favor":  [{"texto": "Kilometraje bajo para el año", "peso": "alto"}],
    "en_contra": [{"texto": "Cadena de distribución sin cambiar", "peso": "alto"}]
  },
  "veredicto": {
    "recomendacion": "Comprar si baja de precio",
    "confianza": "media",
    "razonamiento": "Tres a cinco líneas ponderando lo bueno contra lo malo.",
    "que_cambiaria": "Si el COC confirma 109 g/km el impuesto baja unos 800 € y pasaría a Comprar.",
    "fecha": "29/07/2026"
  }
}
```

`recomendacion`: `Comprar`, `Comprar si baja de precio`, `Dudoso` o `Descartar`.
`confianza` y `peso`: `alta`/`media`/`baja` y `alto`/`medio`/`bajo`.

### `costes`

```json
{
  "precio_coche": 12900,
  "pvp_nuevo": 32250,
  "transporte": 1200,
  "itv_matriculacion": 95,
  "tasa_dgt": 20.61,
  "iedmt_estimado": 0,
  "iedmt_metodologia": "Base imponible = PVP nuevo x coeficiente de antigüedad; IEDMT = base x tipo según CO2.",
  "gestoria": 0,
  "otros": 0,
  "coste_total": 14215.61,
  "honorarios": 1500,
  "precio_cliente": 15715.61,
  "iedmt_es_estimacion": true
}
```

`iedmt_es_estimacion` va casi siempre en `true`: Hacienda no calcula el impuesto sobre lo que paga el cliente, sino sobre sus tablas oficiales de valor de mercado. **Enseña ese matiz en tu app** cuando muestres el presupuesto, para no dar una cifra como cerrada.

**`pvp_nuevo` es obligatorio en la práctica (añadido el 30/07/2026).** La app **no guarda** `iedmt_estimado`: recalcula el impuesto ella misma en `Car::calculateIEDMT()` a partir de la base imponible, la antigüedad y el CO₂. Si el JSON no trae `pvp_nuevo`, la base queda a 0, el impuesto sale 0 € y el coste total que ve el usuario queda por debajo del real. Manda aquí el **PVP del coche nuevo sin depreciar** (el que aparece en la ficha técnica del modelo): el coeficiente de antigüedad lo aplica la app después, así que enviar la base ya depreciada la depreciaría dos veces.

`honorarios` y `gestoria` se suman en la misma columna de la app (`professional_fees`). Antes solo se leía `gestoria` —casi siempre 0— y los honorarios se perdían por el camino.

### `mercado`

```json
{
  "comparables": [
    {"titulo": "Opel Astra 1.5 CDTi 2019", "precio": 16400, "km": 79000, "url": "https://...", "pais": "España"}
  ],
  "precio_medio": 16250,
  "precio_min": 15400,
  "precio_max": 17200,
  "ahorro_estimado": 534.39,
  "semaforo": "green"
}
```

`semaforo`: `green` si el precio todo incluido no supera la media de comparables, `amber` hasta un 5 % por encima, `red` por encima de eso. `ahorro_estimado` es `precio_medio − precio_cliente`: es el argumento de venta del servicio.

### `avisos`

```json
["El CO2 no viene en el anuncio: la cifra del impuesto puede variar hasta confirmarlo con el COC."]
```

Lista de textos planos. Muéstralos siempre junto a la ficha; existen para que nadie dé por confirmado algo que no lo está.

## Cómo importarlo en Laravel

En `laravel/` de esta carpeta tienes dos archivos listos para copiar a tu proyecto:

- `ImportarValoracion.php` — comando de Artisan que lee los JSON de la carpeta `export/`, valida el esquema y hace `updateOrCreate` por `coche_id`. Cuando tengas servidor, el mismo código de validación te sirve para el endpoint.
- `migracion_valoraciones.php` — migración de ejemplo con las columnas mínimas. Los bloques ricos (investigación, balance, comparables) van en columnas `json`, que MySQL 5.7+ y PostgreSQL soportan de forma nativa y puedes consultar sin desnormalizar.

Sobre el diseño de la tabla: no partas los nueve aspectos en nueve columnas. Guárdalos como JSON en una sola columna `investigacion`. Son datos de lectura, casi nunca los vas a filtrar por SQL, y si algún día se añade un décimo aspecto no tendrás que migrar la tabla.

## Reglas que no conviene romper

**Reimportar debe ser seguro.** Un coche revalorado semanas después llega con el mismo `coche_id` y datos nuevos. Tu importador debe actualizar, no duplicar.

**No confundas "vacío" con "sin problema".** Ya está explicado arriba, pero es el error más fácil de cometer al pintar la ficha.

**El `schema_version` se mira antes de nada.** Si algún día sube a 2, tu importador debe avisar en vez de intentar leer campos que quizá se hayan movido.

**Los precios son números, no textos.** Sin símbolo de euro, sin separadores de miles, punto decimal. La moneda va aparte en `anuncio.moneda`.
