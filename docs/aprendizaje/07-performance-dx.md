# 07 — Performance + DX (Sprint 4): por qué se tomó cada decisión

> Esta es la guía que más te va a servir: cada técnica explicada con **el problema → la decisión → el razonamiento**. Son las 6 optimizaciones estándar de cualquier app moderna con Vite + SPA.

## El problema de fondo

Una SPA (Vue + Inertia) compila TODO el JavaScript en archivos que el navegador descarga antes de pintar nada. Sin optimizar:

```
app.js = 500 KB+   (Vue + Inertia + Heroicons + tus páginas, todo junto)
→ descarga lenta → pantalla en blanco → usuario se va
```

Las 2 métricas que importan:
- **LCP** (Largest Contentful Paint): cuándo aparece lo principal. Target: <2.5s.
- **TTI** (Time to Interactive): cuándo puedes hacer click. Target: <1.5s.

---

## 4.1 manualChunks — partir el bundle en trozos

**Problema:** un solo `app.js` gigante. Si cambias UNA línea de tu código, el hash del archivo cambia y el usuario tiene que descargar los 500KB enteros otra vez... incluyendo Vue, que NO cambió.

**Decisión ([vite.config.js](../../vite.config.js)):**
```js
manualChunks: {
  'vendor-vue': ['vue', '@inertiajs/vue3'],
  'vendor-heroicons': ['@heroicons/vue/...'],
  'vendor-utils': ['axios', 'date-fns'],
}
```

**Por qué funciona:** las librerías (vendor) cambian 1 vez al mes; tu código cambia cada deploy. Separados:
- `vendor-*.js` → el navegador lo cachea 30+ días.
- `app.js` → pequeño, se descarga rápido en cada cambio.
- Además el navegador descarga los chunks **en paralelo**.

> **Regla:** separa por "frecuencia de cambio", no por tipo de archivo.

## 4.2 Deferred props — no cargar lo que no se ve

**Problema:** al entrar a `/cars`, el servidor consultaba los coches, serializaba 15 registros con relaciones y los mandaba en el HTML inicial. Si la consulta tarda 800ms, la página entera espera 800ms.

**Decisión:** `Inertia::defer(fn () => Car::paginate(15))` → la página llega SIN los coches (rápida), y el navegador pide los coches en una segunda petición en paralelo. Mientras tanto, skeletons.

**Por qué funciona:** conviertes 1 petición lenta en 2 rápidas concurrentes. El usuario ve la estructura (sidebar, título, skeletons) en ~200ms en lugar de una pantalla blanca de 1s.

> **Regla:** el primer render solo necesita lo visible "above the fold". Todo lo demás, `defer`.

## 4.3 Prefetch on hover — adelantarse al click

**Problema:** click en "Clientes" → petición al servidor → 400ms de espera → navegación.

**Decisión ([SidebarGroup.vue](../../resources/js/Components/SidebarGroup.vue)):**
```vue
<Link prefetch-on-hover :prefetch-on-hover-delay="200">
```

Cuando el ratón pasa 200ms sobre el enlace, Inertia **precarga la página destino en background**. Al hacer click, ya está descargada → navegación instantánea (~0ms).

**Por qué funciona:** el usuario pasa ~1-2 segundos con el ratón sobre un link antes de decidirse. Ese tiempo "muerto" se usa para trabajar. Es gratis para el usuario que no hace click (una petición pequeña) y mágico para el que sí.

> **Regla:** prefetch solo en navegación de alta frecuencia (sidebar, tabs). No en cada link de una tabla (100 links = 100 peticiones al pasar el ratón).

## 4.4 modulepreload — decirle al navegador qué necesitará

**Decisión:** ninguna manual. Vite genera automáticamente en el HTML:
```html
<link rel="modulepreload" href="/assets/vendor-vue-abc123.js">
```

**Por qué funciona:** sin preload, el navegador descubre los archivos "en cadena" (descarga app.js → lo lee → descubre que necesita vendor.js → lo descarga...). Con preload, los pide TODOS a la vez desde el primer momento.

## 4.5 Brotli/gzip — comprimir en el servidor

**Decisión:** configuración de compresión en `.htaccess`/nginx (commit `73232b2`).

**Por qué funciona:** un `.js` de 300KB se comprime a ~80KB con brotli. Es la optimización con mejor ratio esfuerzo/resultado que existe: 30 min de config = 20-30% menos bytes. **Se hace en el servidor, no en el código.**

## 4.6 Lazy icons — importar solo lo que usas

**Problema:** `import { X } from '@heroicons/vue/24/outline'` parece inocente, pero sin tree-shaking bien hecho puedes acabar con la librería entera (24KB) en el bundle.

**Decisión ([resources/js/utils/lazyIcons.js](../../resources/js/utils/lazyIcons.js)):**
```js
defineAsyncComponent(() => import('@heroicons/vue/24/outline/ArrowUpIcon'))
```
Cada icono se convierte en un mini-chunk que se descarga solo cuando aparece en pantalla.

---

## Cómo se llegó a estas conclusiones (el proceso, no el resultado)

1. **Medir antes de tocar:** las decisiones salieron de leer el bundle (`npm run build` te dice el tamaño de cada chunk) y de las métricas target del plan (LCP <2.5s, bundle <250KB).
2. **Ordenar por esfuerzo/impacto:** brotli (30min, alto) y manualChunks (1h, alto) primero; lazy icons (2h, medio) después.
3. **Descartar lo que no aporta:** se evaluó PWA como "no core" y Reverb/WebSockets como overkill. Performance ≠ añadir cosas; a veces es NO añadir.

> **Regla reutilizable:** El flujo de performance siempre es: **medir → hipótesis → fix barato primero → medir de nuevo**. Nunca optimices "de oído". Y el orden de prioridades casi siempre es: 1) comprimir, 2) partir bundles, 3) cargar tarde (defer/lazy), 4) cargar antes (prefetch/preload).
