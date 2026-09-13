---
paths:
  - 'app/Support/**'
---

# Support

## Patrones de URL de portales (verificados en navegador 13-sep-2026)
Para generar enlaces de búsqueda a portales usa SIEMPRE estas formas; las verifiqué abriéndolas en navegador:
- coches.net: `https://www.coches.net/<marca>/<modelo>/segunda-mano/?fi=Price&or=1&PowerHpFrom=..&PowerHpTo=..&MinYear=..` (marca con `_` si es compuesta: `alfa_romeo`). NO usar `/segunda-mano/coches/<marca>-<modelo>/` (ignora el filtro y devuelve todo el mercado). NO usar `Versions[]`. Con slug de modelo inexistente degrada a la página de marca, nunca 404; solo reconoce el PRIMER token del modelo (`Golf 7.5 TCR` → `golf`; `3 Series` → `3-series`).
- mobile.de: `https://suchen.mobile.de/fahrzeuge/search.html?dam=0&sb=p&fr=<año-1>:<año+1>&ms=<makeId>;;;&pw=<cv±10%>`. NUNCA `www.mobile.de/es/...` (modo formulario sin tarjetas).
- Orden por precio ascendente = `fi=Price&or=1` (coches.net) y `sb=p` (mobile.de).
- Los enlaces a portales son material INTERNO: solo panel admin, nunca público (`.ai/rules/mercado.md`).
Implementación de referencia: `app/Support/PortalSearchUrls.php` + accessor `Car::enlacesSuelo`.
