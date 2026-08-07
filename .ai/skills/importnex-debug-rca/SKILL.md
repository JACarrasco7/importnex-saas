---
name: importnex-debug-rca
description: Análisis de causa raíz (RCA) post-fallo en ImportnexCore. Aplica cuando hay un bug, error 500, fallo de test, excepción en log, comportamiento inesperado, regresión. Usa metodología 5-why + timeline + verificación de fixes.
---

# 🐛 Debug RCA — ImportnexCore

> Análisis causa raíz sistemático. No parcheo hasta entender el "por qué".

---

## Cuándo se activa

- HTTP 500 inesperado
- Test fallando
- `storage/logs/laravel.log` con ERROR
- Regresión tras deploy
- Comportamiento que el usuario reporta como bug

---

## Metodología 5-Why + 3-Investigación

### Fase 1: SÍNTOMAS (qué se ve)
```bash
# 1.1 - Ver el error literal
ssh forge@168.144.6.105 'cd /home/forge/jjimportmotors.on-forge.com/current && tail -100 storage/logs/laravel.log | grep ERROR'

# 1.2 - Cuándo empezó
git log --since="3 days ago" --oneline

# 1.3 - Quién lo reportó / cómo reproducir
```

### Fase 2: TIMELINE (cuándo cambió)
```bash
# 2.1 - Últimos commits sospechosos
git log --oneline -20

# 2.2 - Diff del commit sospechoso
git show <hash> --stat

# 2.3 - Si fue deploy, qué migró
ssh forge@168.144.6.105 'cd current && git log --oneline -5'
```

### Fase 3: CAUSA (por qué pasa)

#### Técnica 5-Why
```
Why 1: ¿Por qué falla?
        → Error 500 en /pricing

Why 2: ¿Por qué 500?
        → ViewException: "Unable to locate file in Vite manifest"

Why 3: ¿Por qué no está en manifest?
        → Porque PricingPublic.vue no fue compilado por Vite

Why 4: ¿Por qué no fue compilado?
        → Porque el archivo está en Pages/ en lugar de Pages/Public/

Why 5: ¿Por qué está mal ubicado?
        → Error al crearlo en sprint anterior (Pages/ vs Public/)

→ CAUSA RAÍZ: archivo Vue en directorio incorrecto
```

#### Verificación
```bash
# Confirmar el path del archivo
ls resources/js/Pages/PricingPublic.vue
ls resources/js/Pages/Public/PricingPublic.vue

# Confirmar la ruta que el Inertia pide
grep "PricingPublic" routes/web.php

# Confirmar el manifest
cat public/build/manifest.json | grep PricingPublic
```

### Fase 4: FIX (cómo arreglar)

**Patrones comunes en ImportnexCore:**

| Síntoma | Causa probable | Fix |
|---|---|---|
| 500 "Vite manifest not found" | Archivo Vue en ruta incorrecta | `mv Pages/X.vue Pages/Public/X.vue` |
| 500 syntax error expecting endif | Blade interpreta `@type` JSON como directiva | Reemplazar `@type` por `@@type` |
| SQLSTATE Column not found | Migración no aplicada o query con campo que no existe | `php artisan migrate` o ajustar query |
| 500 "headers already sent" | Output antes de return (var_dump, echo) | Buscar echo/print antes de response |
| 500 "Class X not found" | Falta `composer dump-autoload` | SSH + dump |
| "CSRF token mismatch" en POST | Sesión caducada o middleware | Verificar token en meta + cookies |
| 404 recurso | Ruta no wireada o mal nombrada | `php artisan route:list` |

### Fase 5: PREVENCIÓN

1. **Documentar** en `.ai/AUDITORIA-POST-IMPLEMENTACION.md` (nueva trampa).
2. **Añadir regla** en `.ai/rules/{dominio}.md` (prevención).
3. **Test** que reproduzca el bug → fix verificado.
4. **Commit con detalle** del "what + why + how to avoid".

```bash
git commit -m "fix(pricing): archivo en Pages/Public/ + tests anti-regresion

Causa raíz: PricingPublic.vue creado en Pages/ (legacy) en lugar de Pages/Public/.
Síntoma: HTTP 500 Unable to locate file in Vite manifest.
Fix: mover archivo a ubicación correcta + rebuild + ruta /pricing 200.

Prevención:
- Test Feature\Routing\PricingRouteTest verifica render correcto
- Regla .ai/rules/migrations.md: 'verificar path antes de crear archivo nuevo'
- Trampa en AUDITORIA-POST-IMPLEMENTACION.md"
```

---

## Tool: investigation helpers

```bash
# Última error en log
mcp_laravel_boost_last-error

# Logs recientes
ssh forge@168.144.6.105 'cd current && tail -50 storage/logs/laravel.log'

# Database schema check
mcp_laravel_boost_database-schema --summary

# Verificar query específica
ssh forge@168.144.6.105 'cd current && php artisan tinker --no-ansi'
```

---

## Output esperado

```markdown
## RCA Report — [Sprint X]

**Síntoma:** HTTP 500 en /pricing
**Causa raíz:** Archivo `resources/js/Pages/PricingPublic.vue` en directorio incorrecto
**Fix aplicado:** `mv Pages/X.vue Pages/Public/X.vue` + `npm run build`
**Verificación:** `curl /pricing` → 200
**Prevención:**
- Test añadido: `tests/Feature/Routing/PricingRouteTest.php`
- Trampa documentada: AUDITORIA-POST-IMPLEMENTACION.md
- Regla actualizada: `.ai/rules/frontend.md`
```

---

## Anti-patrones

- ❌ Parchear sin entender (sólo añadir try/catch).
- ❌ Reiniciar servidor (no soluciona causa).
- ❌ Decir "es un problema de cache" sin más.
- ❌ Culpar al usuario o al entorno.
- ❌ Skip tests por prisa.

---

## Skills amigas

- `importnex-self-audit` — ejecutar **después** del fix.
- `importnex-multitenancy` — si el bug involucra org_id.
- `importnex-cashier-billing` — si el bug es billing.
