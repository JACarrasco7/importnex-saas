---
paths:
  - 'app/Support/**'
---

# Support

## Patrones de URL de portales (spec canónico 24-ago-2026 + verificado en navegador 13-sep-2026)

Fuente canónica: `.claude/skills/importacion-vehiculos/02-flujos/playbook_filtrado.md`
§"URL de resultados reales que SÍ funciona". NO reinventar estos formatos.

**mobile.de**

```
https://suchen.mobile.de/fahrzeuge/search.html?dam=0&fr=<a-1>:<a+1>
  &isSearchRequest=true&ms=<makeId>;<modelId>;;;;&od=up&s=Car&sb=p&vc=Car
  &pw=<kW-4>:<kW+4>
```

- `pw` va en **kW**, NO en CV: `kW = cv × 0,7355`, margen ±4 kW (redondear el límite
  inferior hacia abajo). Ej: 320 cv → `pw=231:239`. Mandar CV aquí filtra mal y excluye
  el propio coche.
- `ms` son **cinco campos** (`makeId;modelId;;;`). Sin `modelId` la página cae en
  **modo formulario con 0 tarjetas** → en ese caso se filtra por texto (`q=`).
- Los IDs viven en `app/Support/data/mobile-de-catalogo.json` (marcas + modelos),
  **fechado**. Orden de resolución: `busquedas_realizadas` del informe → catálogo → texto
  libre. **Nunca inventar IDs.**
- ⚠️ Los IDs **caducan**: Golf Mk7.5 = `12603` (tabla del skill, 24-ago) devolvía **0**
  anuncios el 13-sep; el vigente es `14` (**57.717**). Verificados por conteo el
  13-sep-2026: Golf=14 · Arteon=64 · Tiguan=54 · Audi A3=8 · BMW 320=10 · Ford Focus=20 ·
  Mercedes C200=18 (5.776) · Seat Leon=9 (8.670) · Cupra Formentor=5 (8.771) ·
  Skoda Octavia=10 (14.838) · Opel Astra=5 (17.992) · Volvo XC60=40 (6.373).
- makeIds vigentes (13-sep-2026): VW=25200 · Audi=1900 · BMW=3500 · Mercedes-Benz=17200 ·
  Porsche=20100 · Ford=9000 · Skoda=22900 · Opel=19000 · Toyota=24100 · Volvo=25100 ·
  Cupra=3 · Dacia=6600 · Citroën=5900. La tabla vieja de `empaquetar.py` traía valores
  inventados (Ford=24500 es en realidad **TVR**; Opel=29000 no existe) → devolvían la
  marca equivocada, que es peor que no filtrar.
- **Catálogo de modelos completo** (`app/Support/data/mobile-de-catalogo.json` →
  `modelos`) cubre 11 marcas a 13-sep-2026: VW(25200), Audi(1900), BMW(3500), Ford(9000),
  Porsche(20100), Mercedes-Benz(17200, 341 modelos), Seat(22500, 16), Cupra(3, 9),
  Skoda(22900, 24), Opel(19000, 51), Volvo(25100, 51). El anchor de extracción cambió:
  ya NO se busca `isGroup` (solo aparece para algunas marcas) — el marcador fiable es
  `"modelsCache":{"<makeId>":[...]}` en el HTML servido, con conteo de llaves `{}` para
  encontrar el cierre exacto del array (el offset fijo que usaba VW/Audi/BMW no vale para
  todas las marcas). Toyota, Dacia y Citroën tienen makeId vigente pero SIN catálogo de
  modelos todavía — caen a `q=` (texto libre) hasta que se extraigan.

**Cómo refrescar el catálogo** (los IDs cambian; ~5 min con navegador):

1. Abrir `https://suchen.mobile.de/fahrzeuge/search.html?dam=0&isSearchRequest=true&s=Car&vc=Car&ms=<makeId>;;;;`.
2. El HTML del servidor **embebe el catálogo**: buscar `isGroup` y parsear los pares
   `{\"label\":\"Golf\",\"value\":\"14\"}`. La lista de marcas está en la misma
   página tras `"Alle Marken"`.
3. Confirmar con `ms=<makeId>;<modelId>;;;;` que el contador de anuncios NO es 0.
4. Actualizar el JSON y anotar la fecha en `verificado`.

> ⚠️ Hay que **cargar la página completa** (`page.goto` con recarga real): en navegación
> cliente el payload no está en el DOM y la extracción devuelve vacío.
- NUNCA `www.mobile.de/es/...` (modo formulario sin tarjetas).

**coches.net** (verificado 13-sep-2026 → title "VOLKSWAGEN GOLF de segunda mano y ocasión")

```
https://www.coches.net/segunda-mano/?MakeIds[0]=47&Versions[0]=Golf
  &PowerHpFrom=<cv-5>&PowerHpTo=<cv+5>&fi=Price&or=1
```

- `PowerHp*` va en **CV**, margen ±5 CV.
- `Versions[0]` es texto libre: si se usa, solo el nombre limpio del modelo (`Golf 7.5 TCR` →
  `Golf`); la generación/variante rompe el filtro.
- `ModelIds[0]` es **preferible**: usarlo cuando el modelo esté mapeado, `Versions[0]`
  como fallback. Verificados 13-sep-2026: VW `MakeIds[0]`=47 · Golf `ModelIds[0]`=89.
- Los corchetes van **literales** (`MakeIds[0]=`), no `%5B`.
- VW=47 verificado. Ojo: el resto de MakeIds provienen de `empaquetar.py` sin verificar.

**Común**

- Orden por precio ascendente = `sb=p` (mobile.de) y `fi=Price&or=1` (coches.net): eso es
  lo que enseña el SUELO.
- Los enlaces a portales son material **INTERNO**: solo panel admin, nunca público
  (`.ai/rules/mercado.md`).

Implementación de referencia: `app/Support/PortalSearchUrls.php` · accessor
`Car::enlacesSuelo` · bloque "Ver suelo en portales" en
`resources/js/Pages/Cars/Partials/MarketPanel.vue` · tests
`tests/Feature/CarEnlacesSueloTest.php`.

> ⚠️ **NO usar la herramienta Boost `record-rule` en esta carpeta**: reescribe
> `.ai/rules/index.md` y borra las filas que no conoce (pasó el 13-sep-2026, se perdieron
> 7). Editar el `.md` a mano y revisar `git diff .ai/rules/index.md`.
