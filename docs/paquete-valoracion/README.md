# Paquete de valoración — qué cambia y qué hay que construir

Documentación para quien trabaje del lado Laravel. Explica de dónde viene el
`.zip` que se sube en **Coches → Añadir coche desde informe → Subir ZIP** y qué
falta por implementar.

## Qué ha cambiado (04/08/2026)

Antes, el script externo que prepara la valoración de cada coche
(`JJImportMotors/laravel/empaquetar.py`) maquetaba **dos PDF con reportlab** y
los metía en el zip ya montados. Eso se ha quitado.

Ahora el script entrega solo el **contenido**, en dos archivos de texto plano con
bloques `[MARCADOR]`, y **la maquetación se hace en Laravel con Blade +
Browsershot**, igual que ya se hace en `jj-import/folleto.blade.php` y
`jj-import/briefing.blade.php`.

**Motivo:** el diseño pasa a vivir en un solo sitio. Un cambio de plantilla se
aplica a todos los coches, incluidos los que ya están subidos, sin regenerar
nada ni tocar Python.

## Estructura del zip

```
informe.json                        contrato de valoración (schema_version 1) → modelo Car
manifest.json                       títulos, plantilla y visibilidad de cada pieza
contenido/informe-interno.txt       contenido del informe interno (uso interno)
contenido/ficha-publicitaria.txt    contenido de la ficha del cliente
fotos/001.jpg ...                   galería, en orden
```

`manifest.json` → `contenido[]` trae, por cada archivo:

```json
{
  "archivo": "contenido/ficha-publicitaria.txt",
  "titulo": "Ficha publicitaria para el cliente",
  "plantilla": "ficha-publicitaria",
  "visibilidad": "cliente"
}
```

`plantilla` es la vista Blade que le toca y `visibilidad` es `cliente` o
`interno`. **Respeta `visibilidad`**: es lo que separa el documento que se puede
enseñar del que no.

`paquete_version` sube de 1 a 2 en este cambio. Un zip antiguo (v1) trae
`documentos/*.pdf` y `publicidad/*.pdf` en vez de `contenido/*.txt`.

## Qué hay que construir

### 1. El parser

`App\Support\Esqueleto` — código completo y probado en
[`FORMATO_ESQUELETO.md`](FORMATO_ESQUELETO.md). Es una clase sin dependencias,
unas 80 líneas. Devuelve los bloques por nombre (`uno`, `todos`), troceados por
`|` (`filas`) o agrupados (`grupos`).

### 2. Dos vistas Blade

| Vista | Fuente | Ruta |
|---|---|---|
| `jj-import/ficha-coche.blade.php` | `contenido/ficha-publicitaria.txt` | Puede colgar del expediente del coche |
| `jj-import/informe-interno.blade.php` | `contenido/informe-interno.txt` | **Protegida.** Lleva precio de compra, honorarios y margen |

Punto de partida: `resources/views/jj-import/briefing.blade.php` ya hace
exactamente esto (Browsershot A4, `showBackground`, `deviceScaleFactor(2)`,
logo en base64, QR con `SimpleSoftwareIO`). Lo único que cambia es de dónde
salen los textos.

**La interna no puede colgar de ninguna ruta pública ni del expediente que ve el
cliente.** Es el único requisito duro de esta parte.

### 3. Guardar los `.txt` al importar

El importador del zip tiene que dejar los dos archivos donde la vista pueda
leerlos después (storage del coche, o una columna de texto en `car_documents`).
Reimportar el mismo coche los **sustituye**, no los duplica — igual que las fotos.

## Qué NO va en los `.txt`

- **Logo e isotipo** → los pone el Blade en base64, como el folleto.
- **El QR renderizado** → el esqueleto trae la URL en `[QR]`; el SVG lo genera
  `SimpleSoftwareIO\QrCode` en el controlador.
- **La ficha técnica del coche** → ya está en el modelo `Car` vía `informe.json`.
  El esqueleto es para el **texto redactado**, no para duplicar datos.

## Archivos de esta carpeta

| Archivo | Qué es |
|---|---|
| [`FORMATO_ESQUELETO.md`](FORMATO_ESQUELETO.md) | Las 5 reglas del formato, la tabla completa de bloques de cada documento, el parser PHP y ejemplos de Blade. **Es el documento de referencia.** |
| [`CONTRATO_EXPORT.md`](CONTRATO_EXPORT.md) | El contrato del `informe.json` que llena el modelo `Car`. |
| `ejemplos/ficha-publicitaria.txt` | Salida real de un coche (Opel Astra OPC). |
| `ejemplos/informe-interno.txt` | Salida real del mismo coche, versión interna. |

Los ejemplos son output literal del generador: si la plantilla los pinta bien,
pinta bien cualquier coche.

## Colores de marca

| Uso | Hex |
|---|---|
| Azul corporativo (Estoril) | `#1A306D` |
| Fondo oscuro del folleto | `#0F1D42` |
| Plata / texto sobre azul | `#BEC0C3` |
| Gris asfalto (texto) | `#38393D` |
| Gris técnico (secundario) | `#5A6472` |
| Fondo claro de paneles | `#F4F6F8` |
| Naranja de acción (precio, CTA) | `#E8590C` |
| Semáforo verde / ámbar / rojo | `#10B981` / `#F59E0B` / `#EF4444` |

El informe interno trae el semáforo **ya resuelto** en el bloque `[SEMAFORO]`
(`verde` / `ambar` / `rojo` / `gris`): la plantilla solo tiene que pintar el
color, no interpretar el texto del dictamen.
