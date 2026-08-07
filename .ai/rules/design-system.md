# Design System Rules — JJ Import Motors

> Activar por glob: `resources/css/**`, `resources/js/Components/**`, `tailwind.config.js`.

---

## Paleta oficial

| Token | Hex | Uso |
|---|---|---|
| `estoril-700` | `#1A306D` | Primary brand |
| `estoril-600` | `#2a3d87` | Primary hover |
| `estoril-100` | `#dce3f5` | Backgrounds suaves |
| `asphalt-700` | `#38393D` | Neutro principal |
| `asphalt-900` | `#1e1f21` | Dark mode background |
| `platinum-400` | `#BEC0C3` | Acentos metálicos |
| `platinum-100` | `#f3f3f4` | Backgrounds light |

Definidos en `resources/css/app.css @theme`. NO `tailwind.config.js`.

## Tipografía

- **Sans-serif sistema** (`font-sans` Tailwind default).
- **Antialiasing** `-webkit-font-smoothing: antialiased` en `body`.
- **Escala** Tailwind default (`text-xs` a `text-9xl`).

## Espaciado

- **Cards** `rounded-xl` (12px).
- **Botones** `rounded-lg` + `px-4 py-2`.
- **Inputs** `rounded-md` + `px-3 py-2`.
- **Gaps** `gap-2`, `gap-3`, `gap-4` (8, 12, 16px).

## Componentes premium

- **`.card-premium`** — `rounded-xl border shadow-sm hover:shadow-md`.
- **`.text-gradient`** — `bg-linear-to-r from-estoril-600 to-estoril-400 bg-clip-text text-transparent` (migrado de `bg-gradient-to-r` a v4).
- **`.link-underline`** — `relative inline-block after:absolute ... hover:after:w-full`.

## Componentes marketplace v1 (2026-08-07)

- **`<WishlistButton :car="car" />`** — Heart icon toggle. Persiste en localStorage.
- **`<CompareBar />`** — Bottom bar flotante. Aparece cuando wishlist tiene ≥1 item. Botón "Comparar" → `/marketplace/compare?ids=`.
- **`<FinancingCalculator :price :currency :locale />`** — Sliders reactivos. Calcula cuota mensual con interés compuesto.
- **`<NewsletterPopup />`** — Modal 30s delay + 30 días localStorage cooldown. POST real a `/newsletter/subscribe`.

## Dark mode

```css
@custom-variant dark (&:where(.dark, .dark *));
```

Activado por clase `.dark` en `<html>`. Componible por `useDarkMode` (localStorage + prefers).

```vue
<div class="bg-white dark:bg-asphalt-900 text-asphalt-800 dark:text-asphalt-50">
```

## Accesibilidad WCAG AA

- **Contraste 4.5:1** mínimo.
- **Focus ring** `focus:ring-2 focus:ring-estoril-500`.
- **aria-label** en iconos sin texto.
- **aria-live="polite"** en toasts.
- **role="dialog"** + **aria-modal="true"** en modales.
- **Tab order** lógico, sin `tabindex` positivo.

## Animaciones estándar

```css
--animate-fade-in: fade-in 0.2s ease-out;
--animate-slide-up: slide-up 0.3s cubic-bezier(0.16, 1, 0.3, 1);
--animate-shimmer: shimmer 2s infinite linear;
```

En Tailwind: `animate-[fade-in_0.2s_ease-out]`.

## Estados de interacción

| Estado | Aplicar |
|---|---|
| Hover | `hover:bg-*`, `hover:shadow-*` |
| Focus | `focus:outline-none focus:ring-2 focus:ring-estoril-500` |
| Active | `active:scale-95` |
| Disabled | `disabled:opacity-50 disabled:cursor-not-allowed` |
| Loading | `<Skeleton>` con shimmer |

## Iconografía

- **Heroicons 24/outline** — primario.
- **24/solid** — badges y status.
- **20/mini** — inline UI.
- **Micro 16×16** — tags y pills.

## NO HACER

- ❌ Colores hardcoded (`#1A306D` en lugar de `bg-estoril-700`).
- ❌ Tailwind v3 `tailwind.config.js` para colores custom.
- ❌ Animaciones >500ms (lentas).
- ❌ `shadow-2xl` por defecto (demasiado dramático).
- ❌ Iconos sin `aria-hidden` o `aria-label`.
- ❌ Texto sin antialiasing.
- ❌ Mezclar paletas.
