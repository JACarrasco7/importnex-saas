---
paths:
  - routes/web.php
---

# Routes

## Orden de rutas — wildcards SIEMPRE al final
En Laravel, las rutas se evaluan en orden de declaración. Cualquier ruta con path estático (ej. /cars/import-valuation) DEBE declararse ANTES de rutas con wildcard (ej. /cars/{car}). Como doble seguro, los wildcards numéricos deben llevar where('param', '[0-9]+'). Aplica a TODOS los grupos de rutas resource/show/edit/update/destroy de cualquier modelo.
