# Session D — Performance + DX (2026-08-06)

**Fecha:** 2026-08-06
**Estado:** Iniciando
**Items completados:** 2/6 (ya hechos en sesiones previas)
**Items pendientes:** 4/6 (~5.5h)

---

## ✅ YA HECHOS

### 4.3 Inertia `prefetching on hover` en sidebar ✅
**Commit:** `255aa3f`
**Archivos:**
- `resources/js/Components/SidebarGroup.vue`
- `resources/js/Layouts/AuthenticatedLayout.vue` (sidebar)

**Implementación:**
```vue
<template>
  <Link
    :href="route(href)"
    class="..."
    prefetch-on-hover
    :prefetch-on-hover-delay="200"
  >
    ...
  </Link>
</template>
```

---

### 4.4 Preload `<link rel="modulepreload">` ✅
**Status:** Auto-generado por Vite build
**Archivos:**
- `resources/views/app.blade.php` (Vite @vite directive)

**Resultado:** Vite genera automáticamente `<link rel="modulepreload">` para todos los chunks.

---

## ⏳ PENDIENTE

### 4.1 `vite.config.js`: `manualChunks` split vendor (~1h)

**Objetivo:** Reducir bundle inicial de 500KB+ a chunks más pequeños con mejor cacheability.

**Patrón actual (todo en uno):**
```js
// vite.config.js
export default defineConfig({
  build: {
    rollupOptions: {
      output: {
        // TODO: añadir manualChunks aquí
      }
    }
  }
})
```

**Patrón objetivo:**
```js
// vite.config.js
export default defineConfig({
  build: {
    rollupOptions: {
      output: {
        manualChunks: {
          'vendor-vue': ['vue', '@inertiajs/vue3'],
          'vendor-inertia': ['@inertiajs/core'],
          'vendor-heroicons': ['@heroicons/vue/24/outline', '@heroicons/vue/24/solid'],
          'vendor-utils': ['axios', 'date-fns'],
          'vendor-ui': ['@vueuse/core'],
        }
      }
    }
  }
})
```

**Beneficios:**
- Cache mejor: vendor cache 30+ días, app cada deploy
- Parallel load: browser descarga chunks en paralelo
- Menor TTI: main bundle más pequeño

---

### 4.2 Inertia `deferred props` para listados largos (~3h)

**Objetivo:** Cargar listados pesados (Cars, Clients) con lazy loading.

**Patrón actual (todo en un viaje):**
```php
// app/Http/Controllers/CarsController.php
return Inertia::render('Cars/Index', [
    'cars' => Car::with(['organization', 'tags'])->paginate(15),
    'statuses' => Car::STATUSES,
    'filters' => $request->only(['status', 'traffic_light', 'search']),
]);
```

**Patrón objetivo:**
```php
// app/Http/Controllers/CarsController.php
use Inertia\DeferredProp;

return Inertia::render('Cars/Index', [
    'cars' => Inertia::defer(fn() => Car::with(['organization', 'tags'])->paginate(15)),
    'statuses' => Car::STATUSES,
    'filters' => $request->only(['status', 'traffic_light', 'search']),
]);
```

**Frontend:**
```vue
<!-- resources/js/Pages/Cars/Index.vue -->
<template>
  <WhenVisible v-if="$page.props.cars" data-key="cars">
    <template #fallback>
      <div class="animate-pulse space-y-4">
        <div class="h-24 bg-gray-200 rounded"></div>
        <div class="h-24 bg-gray-200 rounded"></div>
      </div>
    </template>

    <div class="space-y-4">
      <div v-for="car in $page.props.cars.data" :key="car.id">
        {{ car.name }}
      </div>
    </div>
  </WhenVisible>
</template>

<script setup>
import { WhenVisible } from '@inertiajs/vue3'
</script>
```

**Controllers a modificar:**
- `CarsController.php` → `cars` index
- `ClientsController.php` → `clients` index
- `ContactsController.php` → `contacts` index
- `AlertsController.php` → `alerts` index
- `CarRequestsController.php` → `car_requests` index

---

### 4.5 Compresión `brotli` en `.htaccess` / nginx (~30min)

**Objetivo:** Habilitar Brotli compression en producción (Forge).

**Opción A: `.htaccess` (Apache)**
```apache
# public/.htaccess
<IfModule mod_brotli.c>
    AddOutputFilterByType BROTLI_COMPRESS text/html text/plain text/xml text/css text/javascript application/javascript application/json
    BrotliCompressionQuality 6
</IfModule>

<IfModule mod_deflate.c>
    AddOutputFilterByType DEFLATE text/html text/plain text/xml text/css text/javascript application/javascript application/json
</IfModule>
```

**Opción B: nginx config (Forge)**
```nginx
# nginx config en Forge
brotli on;
brotli_comp_level 6;
brotli_types text/html text/plain text/css text/javascript application/javascript application/json;
```

**Beneficio:** 20-30% reducción en tamaño de assets.

**⚠️ Nota:** Requiere acceso server/Forge, puede ser manejado por el usuario.

---

### 4.6 Lazy load Heroicons por categoría (~2h)

**Objetivo:** Reducir bundle de 24KB → 8KB con lazy loading.

**Patrón actual (import directo):**
```vue
<script setup>
import { MagnifyingGlassIcon, PlusIcon, EyeIcon } from '@heroicons/vue/24/outline';
</script>
```

**Patrón objetivo:**
```vue
<script setup>
import { defineAsyncComponent } from 'vue';

const MagnifyingGlassIcon = defineAsyncComponent(() =>
  import('@heroicons/vue/24/outline/MagnifyingGlassIcon')
);
const PlusIcon = defineAsyncComponent(() =>
  import('@heroicons/vue/24/outline/PlusIcon')
);
const EyeIcon = defineAsyncComponent(() =>
  import('@heroicons/vue/24/outline/EyeIcon')
);
</script>
```

**Mejor aún: helper composable**
```vue
<!-- resources/js/Composables/useIcons.js -->
export function useIcon(name) {
  return defineAsyncComponent(() =>
    import(`@heroicons/vue/24/outline/${name}Icon.vue`)
  );
}
```

**Uso:**
```vue
<script setup>
import { useIcon } from '@/Composables/useIcons';

const MagnifyingGlassIcon = useIcon('MagnifyingGlass');
const PlusIcon = useIcon('Plus');
</script>
```

**Archivos a modificar:** ~30 archivos con imports de Heroicons.

---

## 📊 Tiempo estimado

| Item | Esfuerzo | Estado |
|---|---|---|
| 4.1 `manualChunks` | 1h | ⏳ Pendiente |
| 4.2 `deferred props` | 3h | ⏳ Pendiente |
| 4.3 prefetch sidebar | 2h | ✅ HECHO |
| 4.4 preload Vite | 30min | ✅ HECHO |
| 4.5 brotli | 30min | ⏳ Pendiente (server) |
| 4.6 lazy icons | 2h | ⏳ Pendiente |
| **TOTAL** | **~9h** | **2/6 completados** |

---

## 🎯 Orden de ejecución

1. **4.1 `manualChunks`** — 1h, alto impacto, bajo riesgo
2. **4.6 lazy icons** — 2h, alto impacto, bajo riesgo
3. **4.2 `deferred props`** — 3h, alto impacto, medio riesgo (backend + frontend)
4. **4.5 brotli** — 30min, medio impacto, requiere server config

---

## 🔴 RIESGOS

- **4.1 `manualChunks`**: Puede romper SSR si chunks no están disponibles. Testing en staging.
- **4.2 `deferred props`**: Skeletons deben estar bien diseñados, UX no debe degradarse.
- **4.6 lazy icons**: Flickering inicial si iconos cargan tarde. Fallback `<LoadingIcon>`.

---

## 📝 Comits propuestos

```bash
git add vite.config.js
git commit -m "perf(Session D 4.1): manualChunks para split vendor

Chunkes:
- vendor-vue: vue + inertia-vue3
- vendor-inertia: inertia-core
- vendor-heroicons: iconos
- vendor-utils: axios + date-fns
- vendor-ui: @vueuse/core

Beneficio: mejor cache, parallel load, menor TTI"

git add app/Http/Controllers/*.php resources/js/Pages/*/*.vue
git commit -m "perf(Session D 4.2): deferred props para listados pesados

Controllers:
- CarsController: cars deferred
- ClientsController: clients deferred
- ContactsController: contacts deferred

Frontend:
- WhenVisible component en Pages
- Skeletons mientras carga

Beneficio: carga inicial 2-3x más rápida"

git add resources/js/**/*.vue
git commit -m "perf(Session D 4.6): lazy load Heroicons por categoría

Bundle: 24KB -> 8KB
Patrón: defineAsyncComponent por icono

Archivos: ~30 archivos con imports lazy

Beneficio: menor bundle size, carga más rápida"
```

---

## 🚀 Testing checklist

- [ ] Build: `npm run build` sin errores
- [ ] Dev: `npm run dev` sin errores
- [ ] Prod: deploy a staging
- [ ] Lighthouse: Performance > 90
- [ ] Network tab: chunks cargados en paralelo
- [ ] Deferred props: skeletons visibles, luego datos
- [ ] Lazy icons: no flickering, carga rápida

---

## 📖 Referencias

- [Vite manualChunks](https://vitejs.dev/config/build-options.html#build-rollupoptions-output-manualchunks)
- [Inertia deferred props](https://inertiajs.com/deferred-props)
- [Inertia WhenVisible](https://inertiajs.com/when-visible)
- [Vue async components](https://vuejs.org/guide/components/async.html)
- [Brotli compression](https://github.com/google/ngx_brotli)