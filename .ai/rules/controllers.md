---
paths:
  - 'docs/guias/**,app/Http/Controllers/GuideController.php'
---

# Controllers

## Guias nuevas requieren entrada en GuideController::GUIAS
Cuando se añada una nueva guía de uso en docs/guias/, registrarla en el whitelist `GUIAS` de GuideController (slug => ruta) y enlazarla en la tabla del README y en DOCS-INDEX. La ruta traduce el slug a fichero concreto, NUNCA se lee la ruta de la URL.
