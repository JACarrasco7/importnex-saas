# Formato del esqueleto `.txt`

`empaquetar.py` ya no maqueta PDFs. Escribe el **contenido** de los dos documentos
de cada coche en texto plano con bloques `[MARCADOR]`, y la **maquetación** la hace
la plantilla Blade de Laravel (Browsershot), igual que ya se hace con
`jj-import/folleto.blade.php` y `jj-import/briefing.blade.php`.

El reparto es: **el .txt decide qué se dice, el Blade decide cómo se ve.**

Los dos archivos viajan dentro del zip:

```
contenido/ficha-publicitaria.txt   → plantilla del cliente
contenido/informe-interno.txt      → plantilla interna (coste de compra y margen)
```

Para verlos sin abrir el zip:

```bash
python3 empaquetar.py informes/<coche_id>.json --ver ficha
python3 empaquetar.py informes/<coche_id>.json --ver interno
```

---

## Las cinco reglas del formato

1. Una línea que empieza por `[NOMBRE]` **abre un bloque**.
2. Si lleva texto detrás en la misma línea, ese es su contenido.
3. Si no lleva nada, el contenido son **las líneas siguientes** hasta el próximo bloque.
4. Las líneas que empiezan por `#` son **comentarios** y se descartan.
5. Un mismo `[NOMBRE]` repetido es una **lista**, en orden de aparición.

Algunos bloques llevan varios campos separados por ` | ` (espacio-barra-espacio).
La posición de cada campo es fija y está documentada abajo; un campo vacío se
escribe vacío, nunca se salta.

El énfasis se marca con `**negrita**`, no con HTML: así el texto plano sigue
siendo legible y es la plantilla quien decide si lo convierte a `<strong>`.

Los bloques vacíos **no se escriben**. Si un dato no existe, su bloque no aparece:
la plantilla no tiene que distinguir entre "vacío" y "ausente".

---

## Parser en PHP

Devuelve dos vistas de lo mismo: `nombrados` (acceso directo por nombre) y
`orden` (la secuencia completa, por si quieres recorrerla tal cual).

```php
<?php

namespace App\Support;

class Esqueleto
{
    public array $nombrados = [];   // ['TITULO' => ['Opel Astra...'], 'SPEC' => [...], ...]
    public array $orden = [];       // [['nombre' => 'TITULO', 'texto' => '...'], ...]

    public static function desde(string $contenido): self
    {
        $e = new self();

        foreach (preg_split('/\R/', $contenido) as $linea) {
            if (str_starts_with(ltrim($linea), '#')) {
                continue;                                   // comentario
            }

            if (preg_match('/^\[([A-Z0-9_]+)\]\s?(.*)$/', $linea, $m)) {
                $e->orden[] = ['nombre' => $m[1], 'texto' => trim($m[2])];
                continue;
            }

            if ($e->orden && trim($linea) !== '') {          // continuación multilínea
                $i = count($e->orden) - 1;
                $e->orden[$i]['texto'] = trim($e->orden[$i]['texto'] . "\n" . rtrim($linea));
            }
        }

        foreach ($e->orden as $bloque) {                     // índice por nombre
            $e->nombrados[$bloque['nombre']][] = $bloque['texto'];
        }

        return $e;
    }

    /** Primer valor de un bloque, o null. */
    public function uno(string $nombre): ?string
    {
        return $this->nombrados[$nombre][0] ?? null;
    }

    /** Todos los valores de un bloque repetido. */
    public function todos(string $nombre): array
    {
        return $this->nombrados[$nombre] ?? [];
    }

    /** Bloque de campos separados por ' | ', ya troceado. */
    public function filas(string $nombre): array
    {
        return array_map(
            fn ($t) => array_map('trim', explode('|', $t)),
            $this->todos($nombre)
        );
    }

    /** Agrupa bloques repetidos: cada [ASPECTO] abre un grupo con lo que le sigue. */
    public function grupos(string $cabecera): array
    {
        $grupos = [];
        $actual = null;

        foreach ($this->orden as $bloque) {
            if ($bloque['nombre'] === $cabecera) {
                if ($actual) {
                    $grupos[] = $actual;
                }
                $actual = [$cabecera => $bloque['texto']];
            } elseif ($actual !== null && $bloque['nombre'] !== 'H2') {
                $actual[$bloque['nombre']] = $bloque['texto'];
            } elseif ($bloque['nombre'] === 'H2' && $actual) {
                $grupos[] = $actual;
                $actual = null;
            }
        }

        if ($actual) {
            $grupos[] = $actual;
        }

        return $grupos;
    }

    /** **negrita** → <strong>, escapando el resto. */
    public static function negrita(?string $texto): string
    {
        return preg_replace('/\*\*(.+?)\*\*/s', '<strong>$1</strong>', e($texto ?? ''));
    }
}
```

Uso en un controlador:

```php
$esqueleto = Esqueleto::desde(Storage::get("paquetes/{$cocheId}/contenido/ficha-publicitaria.txt"));

return view('jj-import.ficha-coche', ['e' => $esqueleto, 'car' => $car]);
```

Y en el Blade:

```blade
<h1>{{ $e->uno('TITULO') }}</h1>
<p class="claim">{{ $e->uno('CLAIM') }}</p>

<div class="specs">
    @foreach ($e->filas('SPEC') as [$etiqueta, $valor])
        <div class="spec"><span>{{ $valor }}</span><small>{{ $etiqueta }}</small></div>
    @endforeach
</div>

@if ($e->uno('PRECIO'))
    <div class="precio">
        {{ $e->uno('PRECIO') }}
        <small>{{ $e->uno('PRECIO_CAPTION') }}</small>
    </div>
@endif

<ul class="argumentos">
    @foreach ($e->todos('ARGUMENTO') as $punto)
        <li>{!! \App\Support\Esqueleto::negrita($punto) !!}</li>
    @endforeach
</ul>
```

---

## Vocabulario: `ficha-publicitaria.txt`

Documento **de cliente**. Aquí no entra ninguna cifra interna: `empaquetar.py`
recorre el texto antes de meterlo en el zip y **aborta** si encuentra el precio
de compra, el coste total, los honorarios, la gestoría, el IEDMT o el PVP nuevo.
El único importe permitido es el precio final.

| Bloque | Repetible | Campos | Qué es |
|---|---|---|---|
| `DOC` | no | — | Siempre `ficha-publicitaria`. Sirve para elegir plantilla. |
| `COCHE_ID` | no | — | Slug del coche, el mismo del zip. |
| `GENERADO` | no | — | Fecha de generación, `dd/mm/aaaa`. |
| `TITULO` | no | — | Titular del coche. |
| `CLAIM` | no | — | Frase de apoyo bajo el título. |
| `SPEC` | **sí** | `etiqueta \| valor` | Los cuatro datos gordos: año, km, potencia, cambio. |
| `ETIQUETA_DGT` | no | — | Letra del distintivo (`C`, `ECO`, `CERO`...). Solo si se detectó. |
| `FOTO` | **sí** | — | Ruta relativa dentro del zip (`fotos/001.jpg`). En orden. |
| `PRECIO` | no | — | Precio final, ya formateado (`Aprox. 15.920 €`). |
| `PRECIO_CAPTION` | no | — | `todo incluido y matriculado`. |
| `PRECIO_NOTA` | no | — | La letra pequeña de por qué es orientativo. |
| `PLAZO` | no | — | Plazo de entrega. Sale de `marca.json` → `plazo_entrega`. |
| `H2` | **sí** | — | Título de sección. Marca dónde empieza cada bloque temático. |
| `INCLUYE` | **sí** | — | Cada cosa que cubre el precio. |
| `ARGUMENTO` | **sí** | — | Cada "por qué este coche". Admite `**negrita**`. |
| `EQUIPAMIENTO` | **sí** | — | Cada extra de fábrica. |
| `CTA` | no | — | Llamada a la acción del cierre. |
| `CONTACTO` | no | — | Teléfonos · email · web, en una línea. |
| `QR` | no | — | URL del formulario de solicitud. Genera el QR con `SimpleSoftwareIO`. |
| `QR_TEXTO` | no | — | Pie del QR (`¿Otro coche? Escanea aquí`). |
| `LEGAL` | no | — | Texto legal de `marca.json`. |

---

## Vocabulario: `informe-interno.txt`

Documento **interno**. Lleva precio de compra, honorarios y margen: la plantilla
que lo renderice no debe ser accesible desde ninguna ruta pública ni desde el
expediente que ve el cliente.

| Bloque | Repetible | Campos | Qué es |
|---|---|---|---|
| `DOC` | no | — | Siempre `informe-interno`. |
| `COCHE_ID` / `GENERADO` | no | — | Igual que en la ficha. |
| `VALIDO_HASTA` | no | — | Generado + 60 días. Los comparables caducan; pasada esa fecha la valoración hay que rehacerla. |
| `CONFIDENCIAL` | no | — | Etiqueta de confidencialidad para la cabecera. |
| `TITULO` | no | — | `Ajuste de valoración y peritaje: <coche>`. |
| `ORIGEN` | no | — | Portal · ciudad (país) · fecha de captura. |
| `URL_ANUNCIO` | no | — | Enlace al anuncio original. |
| `VIN` | no | — | El bastidor, o `PENDIENTE — pedírselo al vendedor`. |
| `DICTAMEN` | no | — | `COMPRAR`, `COMPRAR SI BAJA DE PRECIO`, `DUDOSO`, `DESCARTAR`. |
| `SEMAFORO` | no | — | `verde` / `ambar` / `rojo` / `gris`. **Ya resuelto**: la plantilla solo tiene que pintar el color. |
| `CONFIANZA` | no | — | `ALTA` / `MEDIA` / `BAJA`. |
| `RESUMEN` | no | — | Primera frase del razonamiento. Es el titular de la tarjeta ejecutiva. |
| `RAZONAMIENTO` | no | — | El razonamiento completo, multilínea. |
| `QUE_CAMBIARIA` | no | — | Qué haría falta para subir la confianza. |
| `PRECIO_OBJETIVO` | no | — | A qué precio sí compensa. Solo si el veredicto es "comprar si baja". |
| `FOTO` | **sí** | — | Hasta 6, para identificar el coche de un vistazo. |
| `H2` | **sí** | — | Título de sección. |
| `COSTE` | **sí** | `concepto \| importe` | Cada línea del desglose. |
| `TOTAL` | no | `concepto \| importe` | Coste total puesto en España. Destacar. |
| `DESTACADO` | no | `concepto \| importe` | Precio final al cliente. Destacar más. |
| `MERCADO` | no | `concepto \| importe` | Media de mercado, con el rango en el concepto. |
| `AHORRO` | no | `concepto \| importe` | Ahorro real del cliente frente al mercado. |
| `NOTA` | **sí** | `etiqueta \| texto` | Nota técnica (hoy, el desglose del cálculo del IEDMT). |
| `A_FAVOR` | **sí** | `texto \| peso` | Punto a favor. Peso: `alto` / `medio` / `bajo`. |
| `EN_CONTRA` | **sí** | `texto \| peso` | Punto a vigilar, mismo formato. |
| `ASPECTO` | **sí** | — | **Abre un grupo.** Título del aspecto investigado. |
| `VALORACION` | — | — | Del aspecto abierto: `favorable` / `neutro` / `desfavorable` / `sin valorar`. |
| `TEXTO` | — | — | Del aspecto abierto: el hallazgo, multilínea. |
| `FUENTE` | — | — | Del aspecto abierto: la URL que lo respalda. |
| `FECHA` | — | — | Del aspecto abierto: cuándo se investigó (importa si vino de caché). |
| `COMPARABLE` | **sí** | `título \| km \| precio \| url` | Cada anuncio comparable verificado. |
| `FUENTE_LISTA` | **sí** | `aspecto \| título \| url` | Índice de fuentes al final. |
| `CHECK` | **sí** | — | Cada paso pendiente del checklist técnico. |
| `PIE` | no | — | Texto de confidencialidad del pie. |

Los cuatro bloques `VALORACION` / `TEXTO` / `FUENTE` / `FECHA` **pertenecen al
`ASPECTO` que tienen encima**. Usa `$e->grupos('ASPECTO')` y te los devuelve ya
agrupados:

```blade
@foreach ($e->grupos('ASPECTO') as $a)
    <div class="aspecto aspecto--{{ $a['VALORACION'] ?? 'sin-valorar' }}">
        <h3>{{ $a['ASPECTO'] }}</h3>
        <p>{{ $a['TEXTO'] ?? '' }}</p>
        @isset($a['FUENTE'])
            <small>Fuente: {{ $a['FUENTE'] }}</small>
        @endisset
    </div>
@endforeach
```

---

## Colores de marca

Los mismos que ya usan `folleto.blade.php` y `briefing.blade.php`:

| Uso | Hex |
|---|---|
| Azul corporativo (Estoril) | `#1A306D` |
| Fondo oscuro del folleto | `#0F1D42` |
| Plata / texto sobre azul | `#BEC0C3` |
| Gris asfalto (texto) | `#38393D` |
| Gris técnico (secundario) | `#5A6472` |
| Fondo claro de paneles | `#F4F6F8` |
| Naranja de acción (precio, CTA) | `#E8590C` |
| Semáforo verde | `#10B981` |
| Semáforo ámbar | `#F59E0B` |
| Semáforo rojo | `#EF4444` |

---

## Qué NO va en el esqueleto

- **Logo e isotipo**: los pone el Blade en base64, como ya hace el folleto.
- **El QR renderizado**: el esqueleto trae la URL en `[QR]`; el SVG lo genera
  `SimpleSoftwareIO\QrCode` en el controlador.
- **Los datos que ya están en la base de datos**: el `informe.json` del mismo
  zip llena el modelo `Car`. Si un dato lo tienes en `$car`, úsalo desde ahí; el
  esqueleto es para el **texto redactado**, no para duplicar la ficha técnica.
