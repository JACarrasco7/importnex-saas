---
paths:
  - 'resources/js/Pages/**'
---

# Pages

## Relaciones camelCase llegan snake_case en props de coche
Eloquent serializa las relaciones con $snakeAttributes=true: una relación `marketingContents()` llega al frontend como `car.marketing_contents`, NO `car.marketingContents`. Bug real 03-sep-2026: los chips del overview /marketing leían `car.marketingContents` y salían vacíos desde el día uno. Regla: al consumir una relación camelCase en una página Inertia, usar la forma snake_case y cubrirlo con test (`->has('car.marketing_contents', N)`); las props dedicadas (p. ej. `contents` en Cars/Marketing) no se ven afectadas.
