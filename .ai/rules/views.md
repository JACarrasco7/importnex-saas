---
paths:
  - 'resources/views/**'
---

# Views

## Critical CSS inline en app.blade.php va dentro de @layer base
El proyecto usa Tailwind v4 (utilidades en @layer utilities). El <style> inline de app.blade.php (preflight manual) NO puede ir sin capa: las reglas un-layered ganan a las utilidades y rompen mx-auto/text-center etc. en h1/p/divs. Mantener siempre envuelto en @layer base.
