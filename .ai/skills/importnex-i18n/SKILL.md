---
name: importnex-i18n
description: Internacionalización es/en sincronizada. Aplica cuando se habla de traducción, i18n, lang, locale, multi-idioma, clave i18n, paridad es/en, traducir al inglés, traducir al español, gettext, ICU MessageFormat, falta traducción, missing translation, lazy translate, useTranslations composable, vue-i18n, JSON de traducciones, resources/lang, recursos/js/i18n, scripts/check-translations.cjs.
---

# i18n ImportnexCore

## Stack

- **Backend:** Laravel lang files `resources/lang/es/`, `resources/lang/en/` (PHP arrays).
- **Frontend:** `resources/js/i18n/es.js`, `resources/js/i18n/en.js` (Vue 3 reactive objects).
- **Composable:** `resources/js/Composables/useTranslations.js` (lazy-loaded).
- **Helper CLI:** `node scripts/check-translations.cjs` (verifica paridad).

## Reglas inquebrantables

1. **Toda clave nueva DEBE existir en `es` y `en`** simultáneamente. El script falla en CI si hay desfase.
2. **No hardcodear strings visibles en JSX/templates**. Usar siempre `{{ t('clave.here') }}`.
3. **Estructura jerárquica por dominio**: `nav.dashboard`, `cars.create.title`, `errors.404`, `common.save`.
4. **Interpolación con `{variable}`** (no `${variable}` ni `%variable%`).
5. **Plurales con `|` pipe** en Laravel (`'cars' => '{0} Sin coches|{1} Un coche|[2,*] :count coches'`).
6. **NUNCA dejar `:placeholder` o `TODO` en una clave**.

## Patrón: Backend (Blade)

```php
// resources/lang/es/cars.php
return [
    'create' => [
        'title' => 'Crear coche',
        'save' => 'Guardar coche',
        'errors' => [
            'required' => 'El campo :attribute es obligatorio.',
        ],
    ],
];
```

```blade
{{ __('cars.create.title') }}
```

## Patrón: Frontend (Vue 3)

```js
// resources/js/i18n/es.js
export default {
    nav: {
        dashboard: 'Dashboard',
        cars: 'Coches',
    },
    cars: {
        create: {
            title: 'Crear coche',
            save: 'Guardar coche',
        },
    },
};
```

```vue
<template>
    <h1>{{ t('cars.create.title') }}</h1>
    <PrimaryButton>{{ t('cars.create.save') }}</PrimaryButton>
</template>

<script setup>
import { useTranslations } from '@/Composables/useTranslations';
const { t } = useTranslations();
</script>
```

## Estructura de namespaces

```
common.*       → Botones, acciones, errores genéricos
nav.*          → Sidebar/menú
cars.*         → Módulo coches
clients.*      → Módulo clientes
billing.*      → Suscripciones, planes
errors.*       → 401, 403, 404, 500, validation
emails.*       → Subject + body de emails
flash.*        → Toasts in-app
alerts.*       → Notificaciones/alertas
```

## Comandos útiles

```bash
node scripts/check-translations.cjs       # valida paridad es/en
node scripts/check-untranslated.cjs       # detecta strings hardcoded
```

## Anti-patrones (NUNCA)

- ❌ `'Guardar'` hardcoded en Vue.
- ❌ `{{ __('key') }}` en componente Vue (eso es Blade, en Vue usa `t()`).
- ❌ Añadir clave solo en `es.js` (rompe paridad).
- ❌ Usar `messages.en` en lugar de `lang/en/` (convención Laravel).
- ❌ Traducir fechas con `new Date().toLocaleString()` sin locale explícito.

## Estado actual (2026-08-06)

- ✅ Backend i18n 100% sincronizado (verificado en disco).
- ✅ Composables `useTranslations`, `useLocale`, `useFormat`.
- ✅ Helper `check-translations.cjs` en CI-ready.
- ⚠️ ~70% de paridad en frontend (resto hardcoded residual).
- ⚠️ ICU MessageFormat NO adoptado (usamos pipe `|` Laravel-style).

## Auditoría periódica

Antes de mergear, ejecutar:

```bash
node scripts/check-translations.cjs && \
node scripts/check-untranslated.cjs
```

Si el primero falla: hay claves en un idioma sin contraparte.
Si el segundo falla: hay strings hardcoded que deberían estar en i18n.
