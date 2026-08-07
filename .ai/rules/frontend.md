# Frontend Rules — Vue 3 + Inertia v3 + Tailwind v4

> Activar por glob: `resources/js/**/*.{vue,js}`, `resources/css/**`, `vite.config.js`

---

## Vue 3 + Composition API

- **`<script setup>`** OBLIGATORIO (NO options API).
- **Single root element** por componente.
- **`<template>` con tipos** (`defineProps<{...}>()`).
- **`computed()`** para valores derivados (no inline en template).
- **`watch()` / `watchEffect()`** con cuidado (memoria si no cleanup).
- **Refs**: `ref()` para primitivos, `reactive()` para objetos grandes.
- **Composable > utility** cuando hay >5 utilities en una línea.

## Inertia v3

- **`Deferred` prop** para datos pesados (carga diferida).
- **`WhenVisible`** para infinite scroll.
- **`Link prefetch`** on hover (`:prefetch="true"`).
- **Para llamadas Inertia**: `useForm` o `router.post/visit/get`. **NUNCA** axios/fetch directo.
- **Excepción legítima de fetch**: endpoints no-Inertia como `/newsletter/subscribe` (rate limit + JSON response). Usar `fetch` con `headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' }`.
- **Head** para `<title>` y meta tags.

## Tailwind v4 (CSS-first) — v3 deprecations migradas 2026-08-07

- **Paleta en `app.css @theme`** — NO `tailwind.config.js`.
- **`@custom-variant dark (&:where(.dark, .dark *))`** ya configurado.
- **`dark:`** en TODO elemento visible.
- **Tokens semánticos:** `bg-estoril-700`, `text-asphalt-900`, `border-platinum-400`.
- **Custom utilities:** `card-premium`, `text-gradient`, `link-underline` (definidas en app.css).
- **Animaciones:** `animate-[fade-in_0.2s_ease-out]`, etc. (claves en `@theme`).
- **Migración v3 → v4 (NO REVERTIR)**: usar `bg-linear-to-*` (NO `bg-gradient-to-*`), `shrink-0` (NO `flex-shrink-0`), `aspect-X/Y` (NO `aspect-[X/Y]`).

## i18n

- **`t('clave')`** SIEMPRE (comp. `useTranslations`).
- **Namespace jerárquico** (`nav.dashboard`, `cars.create.title`).
- **NUNCA** string visible hardcoded.
- **Plurales** con `|` Laravel style.

## Performance

- **Lazy load** imágenes y componentes pesados (`defineAsyncComponent`).
- **Bundle principal** target < 100kB gzipped.
- **NO `console.log`** en producción.
- **NO `any`** en TS (vetar con linter).

## Accesibilidad (WCAG AA mínimo)

- **Contraste 4.5:1** en texto normal.
- **Focus visible** `focus:ring-2 focus:ring-estoril-500`.
- **aria-label** en iconos sin texto.
- **role="dialog"** + `aria-modal="true"` en modales.
- **aria-live="polite"** en toasts.

## NO HACER

- ❌ Options API en Vue nuevo código.
- ❌ Tailwind v3 utilities con `tailwind.config.js` theme.
- ❌ Strings hardcoded visibles.
- ❌ `class="bg-[#1A306D]"` — usar token.
- ❌ Componente > 200 líneas (romper en composables).
- ❌ `v-html` sin sanitización.
- ❌ `setTimeout` para UX (usar transitions).
