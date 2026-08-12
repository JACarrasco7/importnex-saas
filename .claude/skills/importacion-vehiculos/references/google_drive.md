# Subida a Google Drive

Cada plantilla generada por esta skill se sube automaticamente a Google
Drive (requiere que el conector de Google Drive este conectado en su cuenta
de Claude/Cowork), en una de dos carpetas ya existentes en la estructura
numerada 01-07 del Drive compartido con el companero, segun el tipo de
archivo:

- **"07 Vehiculos (operaciones)"**: plantillas de coche (Flujo 1 y Flujo 2),
  sus PDF resumen, `Inventario_Coches_Oferta.xlsx` y
  `Registro_Operaciones_Importacion.xlsx`.
- **"06 CRM y clientes"**: `Clientes.xlsx` y cada `Ficha_Cliente_NOMBRE.xlsx`.

No crees estas carpetas — ya existen en el Drive del usuario. Si por algun
motivo no aparecen, busca antes de asumir que faltan (puede que el nombre
tenga variacion de mayusculas/acentos); solo si de verdad no existen,
pregunta al usuario antes de crear una nueva, porque probablemente el
problema es que estas buscando en la carpeta equivocada.

El procedimiento de localizar carpeta y subir archivo (pasos 1-2 de abajo)
es identico para las dos, solo cambia el `title` de la carpeta y el
`parentId` resultante.

## Localizar las herramientas del conector

Las herramientas de Drive aparecen con nombres del tipo
`mcp__<uuid-del-conector>__create_file`, `..._search_files`, etc. El UUID es
especifico de cada conexion y puede cambiar entre sesiones o cuentas, asi que
nunca lo asumas fijo. Si las herramientas no estan cargadas en el contexto,
busca por palabra clave (por ejemplo `ToolSearch` con query
`"google drive create_file search_files"`) para encontrarlas antes de
llamarlas.

Si no hay ningun conector de Google Drive conectado, no falles en silencio:
avisa al usuario de que la plantilla se genero pero no se pudo subir porque
Drive no esta conectado, y entrega el archivo local igualmente.

## Paso 1 — Localizar la carpeta destino

Llama a `search_files` con esta query (ajustando el titulo segun el tipo de
archivo que vayas a subir):

```
title = '07 Vehiculos (operaciones)' and mimeType = 'application/vnd.google-apps.folder'
```

o

```
title = '06 CRM y clientes' and mimeType = 'application/vnd.google-apps.folder'
```

Usa el `id` del resultado como `parentId` en el paso 2.

## Paso 2 — Subir el archivo .xlsx ya recalculado

Lee el archivo generado (el que ya paso por `recalc.py` con `total_errors: 0`)
y codificalo en base64. Luego llama a `create_file` con:

```json
{
  "title": "Plantilla_Importacion_MARCA_MODELO_AAAA-MM.xlsx",
  "parentId": "<id de la carpeta destino>",
  "contentMimeType": "application/vnd.openxmlformats-officedocument.spreadsheetml.sheet",
  "base64Content": "<contenido del archivo en base64>",
  "disableConversionToGoogleType": true
}
```

**IMPORTANTE — usa siempre el parametro `base64Content`, nunca el parametro
`content` (deprecado).** Se confirmo en esta skill que subir el mismo
archivo con `content` en vez de `base64Content` produce, para archivos de
varios KB con multiples hojas/formulas, un archivo corrupto en Drive con
menos bytes que el original — sin que la llamada de `create_file` devuelva
ningun error. `base64Content` sube el archivo byte a byte correctamente
(verificado re-descargando y comparando tamano). Si alguna vez un archivo
subido con `content` parece corrupto o mas pequeno de lo esperado, ese es
el motivo mas probable.

Sobre `disableConversionToGoogleType`: mantenlo en `true` para los tres
archivos maestros (Registro, Inventario, Clientes) y para las plantillas de
coche/cliente que los scripts (`fill_template.py`,
`fill_client_template.py`, `update_registro.py`, `update_master_list.py`)
necesiten poder reabrir mas tarde con openpyxl — la conversion a Google
Sheets nativo puede reordenar formulas. Si el usuario pide explicitamente
un Google Sheet nativo para trabajar directamente en el navegador, omite el
flag (o ponlo a `false`) sabiendo que a partir de ahi ese archivo concreto
ya no se puede reabrir con los scripts de esta skill.

La respuesta de `create_file` trae `id`, `fileSize` y normalmente un
`viewUrl` — comprueba que `fileSize` coincide con el tamano real del archivo
local antes de dar la subida por buena, y pasale el `viewUrl` al usuario.

## Ejemplo de comando para codificar en base64 (si trabajas por shell)

```bash
python3 -c "import base64,sys; print(base64.b64encode(open(sys.argv[1],'rb').read()).decode())" /ruta/Plantilla_XXX.xlsx
```

Ten cuidado con el tamano del resultado en contextos con limite de tokens —
para un workbook de este tipo (~10-40 KB) no deberia ser un problema.

**Cuidado al retranscribir base64 a mano**: si el string de base64 no viene
directo de un `cat`/`read` justo antes de la llamada a `create_file` (por
ejemplo, si se reconstruye de memoria o de un mensaje anterior), el archivo
subido puede quedar corrupto o con contenido antiguo sin que la llamada de
error. Comprueba siempre el `fileSize` que devuelve la respuesta contra el
tamano real del archivo local (`ls -la` o `len(data)` en Python) antes de dar
la subida por buena. Si hay dudas, descarga el archivo recien subido con
`download_file_content`, decodifica el base64 y valida con
`zipfile.ZipFile(...).testzip()` / `openpyxl.load_workbook(...)` — un
`BadZipFile` confirma corrupcion real (no un falso positivo).

## Caso especial — las tres listas maestras (archivos compartidos, no plantillas individuales)

`Registro_Operaciones_Importacion.xlsx`, `Inventario_Coches_Oferta.xlsx` y
`Clientes.xlsx` son distintos del resto: cada uno es un unico archivo que se
va actualizando fila a fila, y ademas son compartidos con el companero del
usuario. Como el conector de Drive no permite sobrescribir un archivo
existente, subirlos con `create_file` siempre crea una copia nueva en vez de
actualizar la anterior. El protocolo (identico para los tres, cambiando el
`title` y la carpeta — Inventario y Registro en "07 Vehiculos
(operaciones)", Clientes en "06 CRM y clientes") para minimizar duplicados y
no perder cambios del companero:

1. Antes de anadir una fila nueva, busca en la carpeta correspondiente si ya
   hay un archivo con ese nombre (`search_files` con
   `title = 'NOMBRE_ARCHIVO.xlsx' and parentId = '<id carpeta>'`). Si hay
   varios (duplicados de sesiones anteriores), usa el de `modifiedTime` mas
   reciente.
2. Descargalo con `download_file_content` (no lo reconstruyas de memoria) y
   fusiona la fila nueva sobre esa copia con `update_registro.py` o
   `update_master_list.py` segun corresponda, para respetar cualquier edicion
   manual que haya hecho el companero directamente en Drive.
3. Recalcula y vuelve a subir con `create_file` usando el mismo `title`,
   `base64Content` (nunca `content`) y `disableConversionToGoogleType: true`
   (a diferencia de las plantillas de coche/cliente, estos tres se mantienen
   como .xlsx real para que los scripts los puedan reabrir con openpyxl la
   proxima vez sin que la conversion a Google Sheets reordene las formulas).
4. No hay forma de borrar la version vieja por API — queda como duplicado en
   la carpeta hasta que alguien la mueva a la papelera manualmente desde
   Drive. Avisa de esto al usuario la primera vez que ocurra en una sesion.

## Caso especial — coches.json y clientes.json (fuente compartida con el panel web)

Mismo protocolo descargar-fusionar-subir que las tres listas maestras, pero
con `scripts/sync_web_data.py` en vez de `update_registro.py`/
`update_master_list.py`, y `contentMimeType: application/json` en vez de
xlsx. Estos dos archivos son los que lee y escribe en vivo el panel web
(artifact de Cowork) — cualquier coche/cliente procesado por el chat que no
pase por `sync_web_data.py` no aparecera ahi.

1. Busca el archivo mas reciente (`search_files` con
   `title = 'coches.json' and parentId = '<id de 07 Vehiculos (operaciones)>'`,
   o `clientes.json` en "06 CRM y clientes").
2. Descargalo con `download_file_content`, decodifica el base64 a texto
   UTF-8 y guardalo como `.json` local.
3. Ejecuta `python scripts/sync_web_data.py coche datos.json /ruta/coches.json`
   (o `cliente`) — fusiona por `"id"`, no duplica.
4. Sube el resultado con `create_file`, `base64Content`,
   `disableConversionToGoogleType: true`, mismo `title`.

El panel web hace exactamente este mismo protocolo desde JavaScript
(`window.cowork.callMcpTool`) cada vez que alguien cambia un estado ahi, asi
que un archivo con `modifiedTime` reciente puede venir tanto del chat como
de la web — no asumas que la version mas nueva es siempre la tuya.

## Caso especial — JJ_Centro_Operaciones.html (panel de navegador)

Este archivo es distinto a los dos anteriores: no lo lee ni escribe nadie
directamente, es una FOTO generada a partir de `coches.json`/`clientes.json`
que el usuario abre suelto en su navegador (no es un artifact de Cowork, no
tiene `window.cowork` disponible). Se regenera con
`scripts/generate_browser_dashboard.py` cada vez que cualquiera de los dos
JSON cambia (mismo momento que el paso anterior).

1. Genera el HTML localmente:
   `python scripts/generate_browser_dashboard.py coches.json clientes.json /ruta/JJ_Centro_Operaciones.html`
   (usa las mismas copias locales de `coches.json`/`clientes.json` que
   acabas de sincronizar, no hace falta volver a descargarlas).
2. Sube el resultado a "07 Vehiculos (operaciones)" con `create_file`. A
   diferencia de los xlsx, este archivo es texto plano UTF-8 (no un zip
   binario), asi que **`textContent` funciona bien aqui** y es mas simple
   que `base64Content` — pasale el HTML tal cual, con
   `contentMimeType: "text/html"` y `disableConversionToGoogleType: true`,
   mismo `title: "JJ_Centro_Operaciones.html"`.
3. Si el usuario tiene una carpeta local conectada (fuera de Drive), deja
   tambien una copia ahi para que lo abra con doble clic sin pasar por
   Drive — es opcional pero mas comodo para uso diario.

Los CDNs que carga este HTML (Leaflet, OpenStreetMap tiles, Nominatim para
geocodificar, OSRM para rutas reales) no funcionan dentro del sandbox de
esta skill ni dentro del artifact de Cowork (ambos bloquean red arbitraria),
pero si funcionan en el navegador real del usuario cuando abre el archivo —
por eso la geocodificacion y las rutas se calculan ahi, no al generar el
archivo.

## Carpetas obsoletas (limpieza pendiente, julio 2026)

Antes de unificar la estructura, esta skill subio archivos a dos ubicaciones
que ya no se usan:

- Carpeta **"Vehiculos exportacion"** (nombre antiguo, sin los numeros
  01-07): contenia copias antiguas de `Registro_Operaciones_Importacion.xlsx`
  con el esquema de margen de reventa (obsoleto, sustituido por el esquema de
  honorarios de servicio en "07 Vehiculos (operaciones)").
- Varios intentos fallidos de subir `Clientes.xlsx` en "06 CRM y clientes"
  quedaron como duplicados corruptos (subidos con el parametro `content` en
  vez de `base64Content`, ver arriba) antes de encontrar la version buena.
- Hay dos copias de `coches.json` en "07 Vehiculos (operaciones)" (de antes
  de que esta skill se conectara al panel). `sync_web_data.py`/el panel
  siempre usan la de `modifiedTime` mas reciente, asi que no bloquea nada,
  pero conviene que el usuario borre la mas antigua a mano cuando pueda.

Estos archivos duplicados/obsoletos no se pueden borrar por API — hay que
avisar al usuario para que los mueva a la papelera manualmente desde Drive.
