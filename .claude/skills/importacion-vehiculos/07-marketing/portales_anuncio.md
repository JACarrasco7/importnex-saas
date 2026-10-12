# Portales de coches — `07-marketing/portales_anuncio.md`

> **Coches.net · Milanuncios · Wallapop**: texto base único + 3 deltas.
> El comprador es el mismo — lo que cambia es el formulario de cada portal.

---

## 1 · Texto base (el mismo para los 3 portales)

Estructura en bloques escaneables:

```
[PT_RESUMEN]            2-3 frases, sin icono, sin emoji, sin enlace
[PT_FICHA]              bloque: 🗓️ fecha 1ª mat | 🛣️ km | 🐎 CV | ⚙️ cambio | ⛽ comb | 🏷️ DGT | 👤 propiet.
[PT_ESTADO]             ✅ revisión + ⚠️ pega honesta + 🔧 mantenimiento
[PT_EQUIPAMIENTO]       3-5 líneas, sin icono
[PT_QUE_INCLUYE]        bloque sin icono: gestión + transporte + trámites
[PT_COMO_FUNCIONA]      3-4 líneas: cómo es el proceso de gestión
[PT_AVISO]              aviso legal completo (A26 + A27 + A30)
[PT_TITULO_B]           segunda variante de título (A/B)
```

### 1.1 · Fórmula del título (filtros que la gente teclea)

```
<MARCA> <MODELO> <VERSIÓN> · <AÑO> · <KM> km · <1 dueño/varios>
```

Sin signos de exclamación. Sin emojis. Sin palabras vacías.
Coches.net desaconseja los signos de exclamación ("dificultan la lectura y
hacen menos atractivo tu anuncio" — fuente: ayuda.coches.net §descripción).

### 1.2 · Bloques por portal

#### Coches.net

- **Título**: máx. 80 chars, sin `!`, sin emojis.
- **Descripción**: 1.200-1.800 chars (óptimo). Techo real PRO: 3.000 chars.
- **Contacto**: solo email + teléfono del formulario del portal. **PROHIBIDO**
  meter WhatsApp, web, redes, otro teléfono en la descripción — Coches.net
  **rechaza el anuncio**.
- **Aviso legal**: obligatorio (A26 + A27 + A30).

#### Milanuncios

- **Título**: máx. 80 chars.
- **Descripción**: 800-1.500 chars.
- **Contacto**: solo formulario interno del portal.
- **Anuncios gratis**: 2 anuncios de coche incluidos por cuenta. A partir del
  tercero, Coches.net suele ser más rentable.

#### Wallapop

- **Título**: máx. 80 chars.
- **Descripción**: **600-900 chars** (recorte del base).
- **Contacto**: solo chat interno del portal. NUNCA teléfono ni email.
- **Reglas derivadas**: la app es móvil, texto corto, sin formato pesado.

### 1.3 · Trazabilidad por bloque

| Bloque | Campo del `informe.json` |
|---|---|
| `[PT_RESUMEN]` | `vehiculo.descripcion` o `valoracion` |
| `[PT_FICHA]` | `vehiculo.{year, km, cv, transmission, fuel, ...}` + `coche_id` |
| `[PT_ESTADO]` | `investigacion.aspectos[].valoracion=favorable` + `cons` filtrado |
| `[PT_EQUIPAMIENTO]` | `vehiculo.equipamiento[]` |
| `[PT_QUE_INCLUYE]` | `coche_id` (gestión + transporte + trámites) |
| `[PT_AVISO]` | plantilla fija |

El validador (`check_marketing.py`) comprueba que cada bloque pueda citar su
campo. Si no, falla.

---

## 2 · Aviso legal completo (A26 + A27 + A30)

Bloque obligatorio en cada `[PT_AVISO]`:

```
JJ Import Motors — gestoría de compra e importación de vehículos.
Identidad del empresario: [nombre empresa, NIF/CIF, dirección, email, teléfono].
El vehículo NO es propiedad del establecimiento.
Precio total cliente: <X> € (impuestos incluidos).
No incluye: transporte fuera de [provincia], matriculación en país distinto a España, ni seguros opcionales.
Garantía: el vehículo se entrega con la garantía legal que aplique según la antigüedad
y el tipo de vendedor original. No la concede JJ Import Motors.
Fecha de primera matriculación: <MM/AAAA>.
```

---

## 3 · Checklist de publicación (la usa el equipo)

- [ ] Coches.net: pegar título + descripción + seleccionar categoría + subir 5 fotos en el orden indicado.
- [ ] Milanuncios: idem + seleccionar subcategoría + marcar "se vende".
- [ ] Wallapop: idem + indicar ubicación + marcar "disponible".
- [ ] FB Marketplace: desde la ficha de FB de JJ Import Motors, pegar título + descripción.
- [ ] Validar cada anuncio a las 24 h y responder TODOS los mensajes en menos de 2 h.
