# Mapa de celdas de la plantilla maestra

Referencia rapida de donde vive cada dato en `assets/Plantilla_Importacion_Vehiculos_master.xlsx`.
`scripts/fill_template.py` ya conoce este mapa — usa este documento solo si necesitas
tocar algo a mano o entender la estructura sin abrir el Excel.

## Hoja "Vehiculo y resumen"

Bloque DATOS DEL VEHICULO (columna B = valor, columna C = notas), filas 6-28:

| Fila | Campo |
|---|---|
| 6 | Marca y modelo |
| 7 | Motorizacion |
| 8 | Combustible |
| 9 | Cambio |
| 10 | Traccion |
| 11 | Puertas / plazas |
| 12 | Anio / 1a matriculacion |
| 13 | Kilometraje |
| 14 | No de propietarios |
| 15 | Color exterior |
| 16 | Color / tapiceria interior |
| 17 | Equipamiento destacado |
| 18 | Precio anuncio (con IVA origen) |
| 19 | CO2 homologado (g/km NEDC) — relleno amarillo, alimenta el IEDMT en Numeros |
| 20 | Garantia ofrecida por el vendedor |
| 21 | Accidentes declarados — relleno amarillo |
| 22 | Historial de mantenimiento disponible |
| 23 | Vendedor: tipo |
| 24 | Vendedor: nombre y ubicacion |
| 25 | URL del anuncio |
| 26 | Enlace a fotos / galeria adicional |
| 27 | Persona de contacto |
| 28 | Fecha de la captura del anuncio |

Bloque INVESTIGACION DEL MODELO (columna B = hallazgo, columna C = fuente/URL), filas 33-37:

| Fila | Aspecto |
|---|---|
| 33 | Problemas / averias comunes del motor y modelo |
| 34 | Campanias de recall oficiales pendientes |
| 35 | Rango de precio de mercado real |
| 36 | Fiabilidad general / opinion media |
| 37 | Otros datos relevantes de la unidad |

Bloque RESUMEN EJECUTIVO (filas ~43-56): son formulas que leen de "Numeros" y de los
checklists, no se tocan directamente.

## Hoja "Numeros"

- Fila 6: precio de compra (formula, enlaza a `'Vehiculo y resumen'!B18`).
- Fila 19: CO2 (usado en la formula del IEDMT, enlaza a `'Vehiculo y resumen'!B19`).
- Fila 24: COSTE TOTAL PUESTO EN ESPANIA (min columna B, max columna C).
- Fila 32: Precio de venta estimado (input, relleno amarillo).
- Filas 34-36: Margen bruto minimo / maximo / porcentaje (formulas).
- Filas 42-50: tabla de comparables de mercado (Portal, URL, Motor, CV, Anio, Km,
  Precio, Ubicacion, Notas) — 9 filas disponibles.
- Fila 52: precio medio de comparables (formula `AVERAGE` sobre 42-50).

## Hoja "Contactos clave"

- Fila 5: fila del Vendedor (columna B=Nombre/Entidad, C=Telefono, D=Email, E=Notas).

## Hoja "Anuncio de venta"

Copy listo para publicar (columna C = valor; las filas de ficha tecnica y el
precio son formulas que leen de "Vehiculo y resumen", no se tocan a mano):

| Fila | Campo | Tipo |
|---|---|---|
| 5 | Titulo del anuncio | input |
| 6 | Precio de venta publicado | formula (`='Vehiculo y resumen'!B45`) |
| 10 | Descripcion corta (RRSS) | input |
| 14 | Descripcion larga (portal de venta) | input |
| 17-24 | Ficha tecnica resumida (marca/modelo, motorizacion, combustible, cambio, anio, km, color, equipamiento) | formulas, enlazan a "Vehiculo y resumen" |
| 27 | Hashtags / palabras clave | input |

`scripts/fill_template.py` rellena esta hoja a traves de `fill_anuncio()` con
el bloque `"anuncio"` del JSON de entrada (titulo, descripcion_corta,
descripcion_larga, hashtags). El resto (fotos recomendadas) es una checklist
manual, no se rellena por script.

## Hojas que NO se tocan por operacion

"Checklist inspeccion", "Checklist documentacion", "Cronograma logistico" y
"Plantillas de mensaje" son secciones genericas reutilizables para cualquier
operacion — no dependen del coche concreto y no se rellenan por `fill_template.py`.

## `assets/Ficha_Cliente_master.xlsx` (plantilla por cliente)

Hoja "Cliente y resumen" (columna B = valor):

| Fila | Campo |
|---|---|
| 6 | Nombre completo |
| 7 | Telefono |
| 8 | Email |
| 9 | Como llego |
| 10 | Fecha de alta como lead |
| 11 | Estado del proceso |
| 14 | Marca / modelo / tipo que busca |
| 15 | Presupuesto minimo |
| 16 | Presupuesto maximo |
| 17 | Uso previsto |
| 18 | Plazo deseado |
| 19 | Kilometraje maximo aceptado |
| 20 | Anio minimo |
| 21 | Combustible / cambio preferido |
| 22 | Otras preferencias |
| 25 | Coches propuestos (total) — formula `COUNTA`, no tocar |
| 26 | Coches con interes del cliente — formula `COUNTIF`, no tocar |
| 27 | Coche elegido — input |
| 28 | Enlace a la operacion una vez iniciada — input |

Hoja "Coches propuestos": tabla desde fila 5 (columnas B-G: Fecha propuesto,
Coche, Precio, Estado, Enlace a ficha completa, Notas). Hoja "Historial de
contacto": tabla desde fila 5 (columnas B-E: Fecha, Canal, Resumen, Proximos
pasos). Hoja "Plantillas de mensaje": copia identica de la misma hoja de la
plantilla de coche, reutilizable tal cual.

`scripts/fill_client_template.py` conoce este mapa (`CLIENTE_ROW_MAP`,
`BUSCA_ROW_MAP`, etc.).

## `assets/Clientes_master.xlsx` e `assets/Inventario_Coches_Oferta_master.xlsx` (listas maestras)

Mismo patron que `Registro_Operaciones_master.xlsx`: una fila por cliente o
por coche en oferta, empezando en la fila 5, con una fila de total en la
primera fila donde la columna B tiene el texto exacto de `total_label` (ver
`scripts/update_master_list.py`). Las columnas de cada una estan en
`CONFIGS` dentro de ese script — no hace falta duplicarlas aqui, pero si se
cambia el numero o el orden de columnas en el Excel hay que actualizar ese
diccionario a la vez.

## Si la plantilla maestra cambia de estructura

Si en el futuro se anade o quita una fila en el bloque DATOS DEL VEHICULO o
INVESTIGACION DEL MODELO de la hoja "Vehiculo y resumen", hay que actualizar
`VEHICLE_ROW_MAP` e `INVEST_ROW_MAP` en `scripts/fill_template.py` para que
sigan apuntando a la fila correcta. Lo mismo aplica a `PRECIO_VENTA_ROW`,
`COMPARABLES_FIRST_ROW`/`COMPARABLES_LAST_ROW`, `CONTACTOS_VENDEDOR_ROW` y a
los mapas de fila de `ANUNCIO_*` si cambia "Numeros", "Contactos clave" o
"Anuncio de venta". Lo mismo para `fill_client_template.py` si cambia
"Cliente y resumen", "Coches propuestos" o "Historial de contacto", y para
`update_master_list.py` si cambian las columnas de las listas maestras.
