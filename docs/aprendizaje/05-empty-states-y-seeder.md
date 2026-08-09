# 05 — Empty states con doble CTA + Seeder de ejemplo

## ¿Dónde verlo?

- **Componente:** [resources/js/Components/EmptyState.vue](../../resources/js/Components/EmptyState.vue)
- **Seeder:** [database/seeders/DemoDataSeeder.php](../../database/seeders/DemoDataSeeder.php)
- **En acción:** entra con una organización nueva (sin coches) a `/cars`, `/clients` o `/contacts` → verás el empty state en lugar de una tabla vacía.

## El problema

Una tabla vacía dice *"aquí no hay nada"* y el usuario nuevo piensa *"¿y ahora qué?"*. Es el momento de mayor abandono de un SaaS: el usuario se registró, entró, vio todo vacío y se fue.

## La solución: empty state como página de ventas

El componente tiene esta anatomía:

```
        🚗 (icono grande, círculo de color)
   "No tienes vehículos todavía"        ← título
   "Importa tu primer lote o añade..."  ← descripción (el porqué)

   [ Importar CSV ]  [ Crear manual ]   ← DOBLE CTA
      (primary)        (secondary)
```

### El código ([EmptyState.vue](../../resources/js/Components/EmptyState.vue))

```vue
const props = defineProps({
    icon: String,
    title: String,          // required
    description: String,
    primaryAction: Object,  // { text, route, method, icon }
    secondaryAction: Object,
});
```

El patrón clave es el **doble CTA**:

- **Primary** (botón sólido estoril): la acción "rápida/masiva" → *Importar CSV*.
- **Secondary** (botón outline): la acción "manual/una a una" → *Crear manual*.

### ¿Por qué DOS botones y no uno?

Porque hay **dos tipos de usuario**:

| Usuario | Su situación | Su botón |
|---|---|---|
| "Ya tengo un Excel de mi concesionario" | 200 coches en CSV | Importar CSV |
| "Estoy probando la app" | 0 datos, quiere ver qué pasa | Crear manual |

Con un solo CTA, uno de los dos se frustra. Con dos, ambos avanzan en <1 minuto. Esto ataca directamente la métrica **Time-to-Value (D0 < 10 min)**.

## El seeder: el "modo demo" instantáneo

### ¿Qué es un seeder?

Un script que **rellena la BD con datos de ejemplo**. En Laravel:

```bash
php artisan db:seed --class=DemoDataSeeder
```

→ crea organización demo + coches realistas + clientes + contactos, usando las **factories** existentes.

### La parte más importante: el guard de producción

```php
// DemoDataSeeder.php — triple check antes de sembrar
if (app()->environment('production')) {
    $this->command->error('⛔ Prohibido sembrar datos demo en producción');
    return;
}
```

**Por qué triple check:** un seeder ejecutado por error en producción **ensucia datos reales de clientes**. Es de los peores accidentes posibles en un SaaS. El guard hace que sea físicamente imposible.

### ¿Por qué datos de ejemplo importan tanto?

Dos razones:

1. **Trial sin fricción:** el usuario nuevo puede cargar datos demo y ver la app "viva" (kanban con coches, financiación con números) en 30 segundos, sin tener que inventarse datos.
2. **Desarrollo y demos:** tú como programador necesitas datos realistas para desarrollar (un listado con 0 o 1 item no revela bugs de paginación, overflow, etc.).

> **Regla reutilizable:** Toda pantalla de listado necesita 3 diseños: con datos, cargando (skeleton) y vacía. El estado vacío no es un error, es tu mejor momento de venta: dile al usuario QUÉ puede hacer y dale 2 caminos (el rápido y el manual). Y si haces un seeder demo, ponle un guard de producción el día 1, no cuando tengas el susto.
