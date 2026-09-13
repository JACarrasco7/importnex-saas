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
  **modo formulario con 0 tarjetas** → en ese caso filtrar por texto (`q=`) en vez de `ms`.
- Los `modelId` **cambian**: no inventarlos. Preferir los que vengan en
  `busquedas_realizadas` del informe; el mapa `MODELO_ID_MOBILE_DE` solo admite IDs
  verificados con la URL canónica.
- makeIds vigentes: VW=25200 · Audi=1900 · BMW=3500 · Mercedes=17200 · Seat=22500 ·
  Cupra=3 · Opel=29000 · Ford=24500 · Hyundai=35500. La tabla vieja de
  `empaquetar.py` traía IDs duplicados o inventados (Hyundai y Nissan compartían
  `21000`, Volvo repetía `25100`) → devolvían la marca equivocada: peor que no filtrar.
- NUNCA `www.mobile.de/es/...` (modo formulario sin tarjetas).

**coches.net** (verificado 13-sep-2026 → title "VOLKSWAGEN GOLF de segunda mano y ocasión")

```
https://www.coches.net/segunda-mano/?MakeIds[0]=47&Versions[0]=Golf
  &PowerHpFrom=<cv-5>&PowerHpTo=<cv+5>&fi=Price&or=1
```

- `PowerHp*` va en **CV**, margen ±5 CV.
- `Versions[0]` es texto libre: solo el nombre limpio del modelo (`Golf 7.5 TCR` →
  `Golf`); la generación/variante rompe el filtro. `ModelIds[0]` sería preferible, pero
  no hay mapa de ModelIds de coches.net.
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
