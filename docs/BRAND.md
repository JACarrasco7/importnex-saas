# 🎨 Guía de Marca — JJ Import Motors / Importnex

> **REGLA OBLIGATORIA:** Todo diseño nuevo (marketplace, folletos, PDFs, formularios, UI en general) debe usar EXCLUSIVAMENTE esta paleta. No usar verdes, cyans, turquesas ni púrpuras como color de marca.

## Paleta oficial

| Nombre | Hex | Tailwind | Uso |
|---|---|---|---|
| **Deep Estoril Blue** | `#1A306D` | `estoril-700` | Color primario de marca: botones, enlaces, highlights, logo |
| **Asphalt Grey** | `#38393D` | `asphalt-700` | Neutro oscuro: fondos oscuros, secciones CTA, footer |
| **Platinum Silver** | `#BEC0C3` | `platinum-400` | Acento claro: bordes, detalles metálicos, fondos sutiles |

## Escalas Tailwind (definidas en `tailwind.config.js`)

### Estoril (primario)
```
estoril-50  #eef1fa
estoril-100 #dce3f5
estoril-200 #b9c6ea
estoril-300 #8fa3d9
estoril-400 #5c73bd
estoril-500 #3a4f9e
estoril-600 #2a3d87
estoril-700 #1A306D  ← MARCA
estoril-800 #152756
estoril-900 #101d42
```

### Asphalt (neutro)
```
asphalt-50  #f6f6f7
asphalt-100 #e7e7e9
asphalt-200 #cfcfd2
asphalt-300 #a7a8ab
asphalt-400 #7e7f83
asphalt-500 #5d5e62
asphalt-600 #4a4b4f
asphalt-700 #38393D  ← MARCA
asphalt-800 #2a2b2e
asphalt-900 #1e1f21
```

### Platinum (acento)
```
platinum-50  #fafafa
platinum-100 #f3f3f4
platinum-200 #e6e7e8
platinum-300 #d5d6d8
platinum-400 #BEC0C3  ← MARCA
platinum-500 #a6a8ac
platinum-600 #909296
```

## Reglas de uso

- **Primario (estoril):** botones principales, CTA, enlaces, estados activos, badges de marca, gradientes de logo (`from-estoril-600 to-estoril-800`).
- **Neutro (asphalt):** secciones oscuras (contacto/footer), texto sobre fondos claros, botones secundarios oscuros (`bg-asphalt-700`).
- **Acento (platinum):** fondos de hero (`platinum-100`), bordes metálicos, detalles de tarjeta, círculos de blur decorativos.
- **Blancos y grises neutros** (`white`, `gray-*`) se permiten como base de tarjetas y separadores.
- **Colores semánticos permitidos SOLO con significado:**
  - `red-*` para puntos negativos / riesgos.
  - `amber-*` para avisos / pendiente.
  - `green-*` solo para ahorro/económico en datos (folleto) — nunca como color de marca.
- **PROHIBIDO como color de marca:** `emerald`, `teal`, `cyan`, `purple`, `indigo`, `sky`.

## Aplicación actual (verificado 03/08/2026)

| Superficie | Colores |
|---|---|
| `MarketplaceIndex.vue` | estoril/platinum/asphalt + grises |
| `MarketplaceShow.vue` | estoril/platinum, red/amber semánticos |
| `CarRequestForm.vue` / `CarRequestSuccess.vue` | estoril/platinum |
| `folleto.blade.php` / `briefing.blade.php` | estoril navy + estoril-300 + platinum |

## Gradientes recomendados

```html
<!-- Logo / icono marca -->
class="bg-gradient-to-br from-estoril-600 to-estoril-800"

<!-- Hero -->
class="bg-gradient-to-br from-estoril-50 via-white to-platinum-100"

<!-- CTA oscuro -->
class="bg-gradient-to-br from-asphalt-900 to-estoril-900"
```
