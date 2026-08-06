---
name: importnex-design-system
description: Design system JJ Import Motors. Aplica cuando se habla de BRAND, paleta estoril, asphalt, platinum, color primario, color secundario, accesibilidad WCAG, dark mode, focus visible, contraste, aria-label, card-premium, text-gradient, link-underline, animación, transición, shimmer skeleton, hover state, focus ring, scrollbar personalizada, Tailwind v4 @theme, custom variant dark, color-mix oklab.
---

# Design System JJ Import Motors

## Paleta oficial

| Token | Hex | Uso |
|---|---|---|
| `estoril-700` | `#1A306D` | Primary brand (CTAs, links, headers) |
| `estoril-600` | `#2a3d87` | Primary hover |
| `estoril-100` | `#dce3f5` | Backgrounds suaves |
| `asphalt-700` | `#38393D` | Neutro principal |
| `asphalt-900` | `#1e1f21` | Dark mode background |
| `platinum-400` | `#BEC0C3` | Acentos metálicos, bordes |
| `platinum-100` | `#f3f3f4` | Backgrounds light mode |

Definido en `resources/css/app.css` con `@theme { --color-estoril-700: #1A306D; ... }`.

## Tipografía

- **Sans-serif:** sistema (`font-sans` Tailwind default).
- **Antialiasing:** `-webkit-font-smoothing: antialiased` en `body`.
- **Tamaños:** escala Tailwind default (text-xs a text-9xl).

## Espaciado

- **Cards:** `rounded-xl` (12px).
- **Botones:** `rounded-lg` (8px) + `px-4 py-2`.
- **Inputs:** `rounded-md` (6px) + `px-3 py-2`.
- **Gaps:** `gap-2`, `gap-3`, `gap-4` (8, 12, 16px).

## Componentes premium (Tailwind utilities)

```css
.card-premium {
    @apply rounded-xl border border-asphalt-200/60 bg-white shadow-sm transition
           hover:shadow-md dark:border-asphalt-700/60 dark:bg-asphalt-800/60 dark:backdrop-blur;
}

.text-gradient {
    @apply bg-gradient-to-r from-estoril-600 to-estoril-400 bg-clip-text text-transparent;
}

.link-underline {
    @apply relative inline-block after:absolute after:bottom-0 after:left-0 after:h-px
           after:w-0 after:bg-current after:transition-all after:duration-300
           hover:after:w-full;
}
```

## Animaciones estándar

```css
/* Definidas en app.css @theme */
--animate-fade-in: fade-in 0.2s ease-out;
--animate-slide-up: slide-up 0.3s cubic-bezier(0.16, 1, 0.3, 1);
--animate-shimmer: shimmer 2s infinite linear;

/* En Tailwind */
class="animate-[fade-in_0.2s_ease-out]"
class="animate-[slide-up_0.3s_cubic-bezier(0.16,1,0.3,1)]"
```

## Dark mode (Tailwind v4)

```css
/* app.css */
@custom-variant dark (&:where(.dark, .dark *));

/* Activado por clase `.dark` en <html> */
```

```vue
<div class="bg-white text-asphalt-800 dark:bg-asphalt-900 dark:text-asphalt-100">
    <button class="bg-estoril-700 hover:bg-estoril-800 dark:bg-estoril-600">
```

## Accesibilidad WCAG AA (mínimo)

1. **Contraste 4.5:1** mínimo en texto normal (verificar estoril-700 sobre blanco = 8.4:1 ✅).
2. **Focus visible** ring de 2px en estoril-500 con offset.
3. **aria-label** en iconos sin texto.
4. **role="dialog" + aria-modal** en modales.
5. **aria-live="polite"** en toasts/notificaciones.
6. **Tab order** lógico, sin `tabindex` positivos.

## Estados de interacción

| Estado | Aplicar |
|---|---|
| Hover | `hover:bg-*`, `hover:shadow-*`, `hover:scale-105` |
| Focus | `focus:outline-none focus:ring-2 focus:ring-estoril-500` |
| Active | `active:scale-95` (presionar botones) |
| Disabled | `disabled:opacity-50 disabled:cursor-not-allowed` |
| Loading | `<Skeleton>` con shimmer |

## Iconografía

- **Heroicons 24/outline** — primario.
- **24/solid** — para badges y status.
- **20/mini** — para inline UI.
- **Micro (16x16)** — para tags y pills.

## Anti-patrones (NUNCA)

- ❌ Colores hardcoded (`#1A306D` en lugar de `bg-estoril-700`).
- ❌ Usar Tailwind v3 config cuando v4 tiene `@theme` en CSS.
- ❌ Mezclar paletas (azul de Bootstrap, gris de Material).
- ❌ Animaciones >500ms (lentas, distraen).
- ❌ Sombras dramáticas (`shadow-2xl` por defecto).
- ❌ Texto sin antialiasing.
- ❌ Iconos sin `aria-hidden` o `aria-label`.

## Documentación completa

Ver `docs/BRAND.md` para guía de marca completa y `tailwind.config.js` legacy (si existe, solo referencia histórica).

## Tokens CSS disponibles

Todos exportados como `--color-{name}-{50..900}`, `--animate-{name}`, `--font-{name}`.

Usar con `var(--color-estoril-700)` en CSS arbitrario o `@apply bg-estoril-700` en Tailwind.
