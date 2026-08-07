---
description: Checklist obligatorio de autoauditoría después de cada implementación (post-sprint, post-feature, post-fix). 10 puntos críticos verificados ANTES del commit. Patrón Manus-style + autoaudit Copilot.
---

# ✅ Autoauditoría Post-Implementación (OBLIGATORIA)

> Ejecutar **ANTES de git commit**. Si algún punto falla → NO commitear hasta arreglar.
> Patrón aprendido de Sprints A-H (2026-08-06).

---

## 🚦 10 Puntos críticos

| # | Punto | Comando / Cómo verificar | Coste oculto si se omite |
|---|---|---|---|
| 1 | **`vendor/bin/pint` después de cambios PHP** | `vendor/bin/pint --dirty --format=agent` | +20-30% diffs inútiles en PRs |
| 2 | **Tests para componentes nuevos** | `php artisan test --compact --filter=NuevoComponente` | Bug futuro invisible (sin red seguridad) |
| 3 | **Usar `codebase-memory` skill** | Activar `understand` skill antes de buscar manualmente | Pierdo contexto, repito búsquedas |
| 4 | **Escribir memoria tras hallazgos** | Actualizar `.ai/memory/findings.json` | Mismo bug en otra sesión |
| 5 | **Auditar refs cruzadas antes de refactor** | `grep_search` de props/calls consumidores | Rompo callers → runtime falla prod |
| 6 | **Verificar `i18n es.js` para keys nuevas** | `npm run i18n:check` o `node scripts/check-translations.cjs` | Strings hardcoded en plantilla |
| 7 | **`npm run build` antes de commit frontend** | `npm run build` (USER lanza, no IA) | Vite manifest desactualizado, /pricing 500 |
| 8 | **`php artisan migrate --pretend` antes de migración** | `ssh forge ... 'php artisan migrate --pretend'` | Rompe prod, hay que rollback |
| 9 | **`php artisan test --compact` global** | `php artisan test --compact` | Regresiones silenciosas |
| 10 | **Health check post-deploy** | `curl.exe` a 4+ rutas críticas | 500 silencieux en prod |

---

## 🔄 Flujo obligatorio (post-cambio)

```
[cambios en código]
       ↓
[1] vendor/bin/pint --dirty
       ↓
[2] php artisan test --filter=NewComponent (si nuevo)
       ↓
[3] grep_search consumidores del refactor
       ↓
[4] node scripts/check-translations.cjs (si strings)
       ↓
[5] npm run build (USER)
       ↓
[6] git add + commit (hook valida)
       ↓
[7] git push origin master
       ↓
[8] ssh forge deploy
       ↓
[9] curl 6 rutas prod (health check)
       ↓
[10] Update .ai/memory/findings.json (si halló algo)
```

---

## 🚨 Trampas conocidas (NO repetir)

### Trampa 1: `@type` en JSON dentro de `.blade.php`
- **Síntoma:** HTTP 500 en TODAS las páginas con Inertia.
- **Causa:** Blade interpreta `@context`/`@type` como directivas.
- **Fix:** Escapar como `@@context`, `@@type`.
- **Detectado:** 2026-08-06 (Sprint A→B).
- **Prevención:** grep `@\(type\|context\)` en `.blade.php` antes de crear.

### Trampa 2: Archivo Vue en ruta incorrecta
- **Síntoma:** `Unable to locate file in Vite manifest: ...`.
- **Causa:** Archivo en `Pages/X.vue` pero ruta apunta a `Pages/Public/X.vue`.
- **Fix:** Mover archivo o cambiar ruta.
- **Detectado:** 2026-08-06 (PricingPublic.vue).
- **Prevención:** Tras añadir nueva página, `grep manifest.json` siempre.

### Trampa 3: Migración con campos inexistentes
- **Síntoma:** SQLSTATE[42S22] Column not found.
- **Causa:** Query referencia columna que no existe en tabla.
- **Fix:** Verificar schema con `php artisan tinker`.
- **Detectado:** 2026-08-06 (SitemapController usaba `published_at`).
- **Prevención:** Lista de columnas antes de escribir query → dry-run.

### Trampa 4: No ejecutar build tras cambio en manifest
- **Síntoma:** Versión vieja servida por Vite.
- **Causa:** Commit sin regenerar `public/build/manifest.json`.
- **Fix:** `npm run build` antes de commit.
- **Detectado:** 2026-08-06.
- **Prevención:** Hook `pre-commit` valida (Sprint G.3).

### Trampa 5: Cache config cuando cambia .env
- **Síntoma:** Aplicación usa valor viejo de `.env`.
- **Causa:** `php artisan config:cache` cachea config; cambios en `.env` no se ven.
- **Fix:** `php artisan config:clear && config:cache`.
- **Detectado:** histórico.
- **Prevención:** Tras tocar `.env` → re-cache siempre.

### Trampa 6: i18n desincronizado entre es.js y en.js
- **Síntoma:** `keynotfounderror` en Vue production.
- **Causa:** Key en un idioma pero no en el otro.
- **Fix:** `node scripts/check-translations.cjs` antes de commit.
- **Detectado:** 2026-08-06 (1250 missing).
- **Prevención:** Hook pre-commit valida (Sprint G.3).

---

## 📝 Plantilla de memoria (encontrar y guardar)

Tras cada sprint, si encuentras un fallo/trampa:

```json
// .ai/memory/findings.json (append)
{
  "date": "2026-08-XX",
  "sprint": "X",
  "issue": "descripción corta del problema",
  "symptom": "qué se veía (HTTP status, error message)",
  "cause": "qué lo causaba en el código",
  "fix": "qué comando/edición lo arregló",
  "prevention_rule": "qué validar para que no vuelva a pasar",
  "affected_files": ["ruta/al/archivo.php"],
  "severity": "low|medium|high|critical"
}
```

---

## 🎯 Acceptance rate (métrica)

Medir tras cada sprint:

| Sprint | Outputs generados | Editados por humano | Acceptance rate |
|---|---|---|---|
| A | 6 | — | — |
| B | 8 | — | — |
| C | 4 | — | — |
| G | 5 | — | — |
| H | 7 | — | — |

**Target:** >= 80% (output aceptado sin modificación).

---

## 📚 Skills potenciadas por esta autoauditoría

| Skill | Cuándo activa |
|---|---|
| `importnex-self-audit` | Al cerrar cada sprint/feature |
| `importnex-debug-rca` | Cuando aparece un bug |
| `importnex-quickref` | Vista rápida de patrones |

---

## ⚡ Acción inmediata

Antes de commit hoy, ejecuta:

```bash
# 1. Formato
vendor/bin/pint --dirty

# 2. Tests
php artisan test --compact

# 3. i18n
npm run i18n:check

# 4. Build (USER)
npm run build

# 5. Stage
git add -A

# 6. Commit (hook valida)
git commit -m "..."

# 7. Push
git push origin master

# 8. Deploy + health check
ssh forge@... 'pull + build + caches'
curl.exe 6 rutas
```

Si algún paso falla → NO seguir. Arreglar primero.
