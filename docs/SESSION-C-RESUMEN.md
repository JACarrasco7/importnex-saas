# Session C — Dark Mode + UX Premium (2026-08-06)

**Fecha:** 2026-08-06
**Estado:** Parcial (~2h de 20h)
**Items completados:** 0/5
**Items en progreso:** 1/5 (3.1 Dark mode - parcial)

---

## ✅ COMPLETADO

Ningún item completamente terminado.

---

## ⏳ EN PROGRESO

### 3.1 Auditar y añadir `dark:` en 30+ Pages (~8h)

**Estado:** Parcial (~2h invertidos)

**Hecho:**
- ✅ `resources/js/Pages/Dashboard.vue` — dark mode añadido a:
  - Contenedores: `bg-white` → `bg-white dark:bg-asphalt-800`
  - Bordes: `ring-gray-200` → `ring-gray-200 dark:ring-asphalt-600`
  - Texto: `text-gray-900` → `text-gray-900 dark:text-white`
  - Texto secundario: `text-gray-500` → `text-gray-500 dark:text-gray-400`
  - Headers: `text-gray-800` → `text-gray-800 dark:text-white`
  - Fondo tabla: `bg-gray-50` → `bg-gray-50 dark:bg-asphalt-700`
  - Dividers: `divide-gray-200` → `divide-gray-200 dark:divide-asphalt-700`
  - Hover: `hover:bg-gray-50` → `hover:bg-gray-50 dark:hover:bg-asphalt-700`

**Pendiente:**
- ⏳ Resto de 53 Pages en `resources/js/Pages/`:
  - `Cars/Index.vue`, `Cars/Create.vue`, `Cars/Edit.vue`, `Cars/Show.vue`, `Cars/Kanban.vue`, `Cars/Map.vue`, `Cars/Verify.vue`, `Cars/Marketing.vue`
  - `Clients/Index.vue`, `Clients/Show.vue`, `Clients/Create.vue`, `Clients/Edit.vue`
  - `Contacts/Index.vue`, `Contacts/Show.vue`, `Contacts/Create.vue`, `Contacts/Edit.vue`
  - `Alerts/Index.vue`, `Alerts/Show.vue`
  - `Subscriptions/Index.vue`, `Subscriptions/Show.vue`
  - `Billing/Index.vue`, `Billing/Show.vue`
  - `Trips/Index.vue`
  - `Marketing/Index.vue`
  - `CarRequests/Index.vue`, `CarRequests/Show.vue`
  - `Auth/Login.vue`, `Auth/Register.vue`, `Auth/ForgotPassword.vue`, `Auth/ResetPassword.vue`, `Auth/VerifyEmail.vue`
  - `Profile/Edit.vue`
  - `Organization/Edit.vue`, `Organization/Show.vue`
  - `Ai/Chat.vue`
  - `MessageTemplates/Index.vue`
  - `Guide/Index.vue`
  - `Welcome.vue`
  - `Public/MarketplaceIndex.vue`, `Public/MarketplaceShow.vue`, `Public/CarRequestForm.vue`, `Public/CarRequestSuccess.vue`, `Public/PricingPublic.vue`
  - `Onboarding/Wizard.vue`

**Patrones a aplicar:**
```vue
<!-- Contenedores principales -->
bg-white → bg-white dark:bg-asphalt-800

<!-- Bordes -->
ring-gray-200 → ring-gray-200 dark:ring-asphalt-600
border-gray-200 → border-gray-200 dark:border-asphalt-700

<!-- Texto principal -->
text-gray-900 → text-gray-900 dark:text-white
text-gray-800 → text-gray-800 dark:text-white

<!-- Texto secundario -->
text-gray-500 → text-gray-500 dark:text-gray-400
text-gray-600 → text-gray-600 dark:text-gray-300

<!-- Headers de tabla -->
bg-gray-50 → bg-gray-50 dark:bg-asphalt-700
text-gray-500 → text-gray-500 dark:text-gray-400

<!-- Dividers -->
divide-gray-200 → divide-gray-200 dark:divide-asphalt-700

<!-- Hover -->
hover:bg-gray-50 → hover:bg-gray-50 dark:hover:bg-asphalt-700
hover:bg-gray-100 → hover:bg-gray-100 dark:hover:bg-asphalt-600

<!-- Links -->
hover:text-estoril-600 → hover:text-estoril-600 dark:hover:text-estoril-400
```

---

## ⚠️ PENDIENTE (RIESGOS IDENTIFICADOS)

### 3.2 Migrar a `@vueuse/motion` (3h)

**Riesgo:** Requiere configuración Vite + plugin

**Qué hacer:**
```bash
npm install @vueuse/motion
```

**Vite config:**
```js
// vite.config.js
import { MotionPlugin } from '@vueuse/motion'

export default {
  plugins: [
    vue(),
    MotionPlugin(),
    laravelVitePlugin(),
  ]
}
```

**Luego añadir a componentes:**
```vue
<script setup>
import { useMotion } from '@vueuse/motion'
const { fadeIn, slideUp } = useMotion()
</script>

<template>
  <div v-motion-slideUp>...</div>
  <div v-motion-fade>...</div>
</template>
```

**⚠️ Riesgos:**
- Plugin puede romper build actual
- Requiere testing extenso
- No es crítico para UX

---

### 3.3 WCAG AA: contraste, focus, keyboard nav (4h)

**Estado:** No iniciado

**Qué auditar:**
- Contraste mínimo 4.5:1 para texto normal
- Contraste mínimo 3:1 para texto grande (18pt+)
- Focus visible en todos los elementos interactivos
- Navegación por teclado (Tab, Enter, Escape)
- Labels en formularios
- ARIA labels en botones sin texto

**Herramientas:**
- Lighthouse Accessibility audit
- axe DevTools extension
- WebAIM Contrast Checker

---

### 3.4 Skeleton `<Suspense>` con `WhenVisible` (3h)

**Riesgo:** Requiere cambios en backend (deferred props)

**Backend (CarsController):**
```php
return Inertia::render('Cars/Index', [
    'cars' => Inertia::defer(fn() => $cars->paginate(15)),
    'statuses' => Car::STATUSES,
    'lights' => ['green', 'amber', 'red', 'neutral'],
    'filters' => $request->only(['status', 'traffic_light', 'search']),
]);
```

**Frontend:**
```vue
<template>
  <div>
    <WhenVisible v-if="$page.props.cars" data-key="cars">
      <!-- Skeleton while loading -->
      <template #fallback>
        <SkeletonCard />
      </template>

      <!-- Cars list when loaded -->
      <CarList :cars="$page.props.cars" />
    </WhenVisible>
  </div>
</template>
```

**⚠️ Riesgos:**
- Requiere cambiar múltiples Controllers
- Performance puede empeorar si mal implementado
- Testing complejo

---

### 3.5 Eliminar `tailwind.config.js` v3 (2h)

**Riesgo:** ALTO — muchos colores prohibidos en uso

**Colores prohibidos encontrados en 30+ archivos:**
- `bg-indigo-*`, `bg-blue-*`, `bg-emerald-*`, `bg-amber-*`, `bg-rose-*`
- `text-indigo-*`, `text-blue-*`, etc.
- `ring-indigo-*`, `ring-emerald-*`, etc.

**Archivos afectados (grepped):**
- `Badge.vue` (emerald)
- `ConfirmDialog.vue` (blue)
- `FlashMessage.vue` (blue)
- `NotificationToaster.vue` (emerald)
- `PreviewCochesNet.vue` (blue)
- `StatCard.vue` (blue, emerald, amber, rose, purple, sky)
- `UpgradeBanner.vue` (rose, amber, emerald)
- `Alerts/Index.vue` (emerald)
- `Alerts/Show.vue` (emerald, amber)
- `Auth/*` (emerald)
- `CarRequests/*` (blue)
- `Cars/*` (emerald, amber, rose, blue)
- `Y más...`

**⚠️ REQUERIDO ANTES de eliminar safelist:**
1. Migrar todos los colores prohibidos a la paleta de marca:
   - `indigo` → `estoril`
   - `blue` → `estoril`
   - `emerald` → `estoril` (o mantener si es semántico)
   - `amber` → `estoril`
   - `rose` → `estoril`
   - `purple` → `estoril`
   - `sky` → `platinum`

2. Reemplazar sistemáticamente en 30+ archivos

3. Testing completo de UI

**🚫 NO hacer sin migrar colores primero.**

---

## 📊 Tiempo invertido vs estimado

| Item | Estimado | Invertido | Restante |
|---|---|---|---|
| 3.1 Dark mode en 30+ Pages | 8h | 2h | 6h |
| 3.2 @vueuse/motion | 3h | 0h | 3h |
| 3.3 WCAG AA audit | 4h | 0h | 4h |
| 3.4 Skeleton WhenVisible | 3h | 0h | 3h |
| 3.5 Eliminar safelist | 2h | 0h | 2h |
| **TOTAL** | **20h** | **2h** | **18h** |

---

## 🎯 Para otra sesión continuar

**Orden recomendado:**
1. **3.1 Dark mode** — continuar añadiendo `dark:` a las 52 Pages restantes
2. **3.3 WCAG audit** — auditar contrastes en colores de marca
3. **3.5 Eliminar saflist** — SOLO después de migrar colores (PRE-REQUISITO)
4. **3.2 @vueuse/motion** — como nice-to-have
5. **3.4 Skeleton** — como nice-to-have, requiere backend

**Archivos ya modificados:**
- `resources/js/Pages/Dashboard.vue` (dark mode parcial)

**Archivos pendientes de modificar (3.1):**
- 52 Pages restantes

---

## 🔴 NOTAS CRÍTICAS

1. **NO eliminar saflist (3.5)** sin migrar colores primero — romperá la UI
2. **3.2 y 3.4** requieren testing extenso — no son críticos para UX
3. **Priorizar 3.1** (dark mode) y **3.3** (WCAG) — mayor impacto UX
4. Dark mode en AuthenticatedLayout ya está implementado, pero Pages necesitan updates

---

## 📝 Commit pendiente

```bash
git add resources/js/Pages/Dashboard.vue docs/SESSION-C-RESUMEN.md
git commit -m "feat(Session C): dark mode parcial en Dashboard (3.1 ~2h/8h)

Añadidos dark mode variants:
- Contenedores: bg-white → bg-white dark:bg-asphalt-800
- Bordes: ring-gray-200 → ring-gray-200 dark:ring-asphalt-600
- Texto: text-gray-900 → text-gray-900 dark:text-white
- Tablas: bg-gray-50 → bg-gray-50 dark:bg-asphalt-700
- Dividers: divide-gray-200 → divide-gray-200 dark:divide-asphalt-700

Pendientes:
- 52 Pages restantes para completar 3.1
- Items 3.2, 3.3, 3.4, 3.5 (ver SESSION-C-RESUMEN.md para riesgos)"
```