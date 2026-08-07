---
name: importnex-self-audit
description: Autoauditoría obligatoria después de cada cambio en ImportnexCore. Aplica antes de commit, antes de merge, antes de deploy, tras implementar feature, tras fix de bug, al cerrar sprint. Verifica 10 puntos críticos: Pint, tests, refs cruzadas, i18n, build, migraciones.
---

# 🔍 Self-Audit — ImportnexCore

> Autoauditoría obligatoria. Ejecutar **ANTES de hacer commit o claim "listo"**.

---

## Cuándo se activa

- "implementa [X]"
- "hemos terminado la feature"
- "haz commit"
- "vamos a deploy"
- "está listo"
- cualquier cierre de tarea que involucre cambios

---

## Los 10 puntos críticos

### 1️⃣ ¿Pint ejecutado?
```bash
vendor/bin/pint --dirty --format=agent
```
**Si falla:** formato inconsistente, commits ruidosos, +20-30% diffs inútiles en PRs.

### 2️⃣ ¿Tests para el cambio nuevo?
```bash
php artisan test --compact --filter=NombreNuevo
```
**Si falla:** bug invisible, sin red de seguridad.

### 3️⃣ ¿codebase-memory consultado?
Usar skills `understand`, `codebase-memory` antes de buscar manualmente.

**Si falla:** pierdo contexto, repito búsquedas.

### 4️⃣ ¿Memoria actualizada con hallazgos?
```bash
cat .ai/memory/findings.json  # append si hay hallazgo
```

**Si falla:** mismo bug en otra sesión, lecciones se pierden.

### 5️⃣ ¿Refs cruzadas auditadas?
```bash
grep_search --includePattern=app/Models/Car.php "method()"
# Verifica TODOS los callers antes de cambiar firma
```

**Si falla:** rompo consumidores, runtime falla en producción.

### 6️⃣ ¿i18n sincronizado es/en?
```bash
node scripts/check-translations.cjs
node scripts/check-untranslated.cjs
```

**Si falla:** strings hardcoded en plantilla, i18n coverage cae.

### 7️⃣ ¿Build frontend regenerado?
```bash
npm run build   # USER lanza, no IA
```

**Si falla:** Vite manifest desactualizado, /pricing 500, estilos rotos.

### 8️⃣ ¿Migración probada en dry-run?
```bash
ssh forge@168.144.6.105 'cd current && php artisan migrate --pretend'
```

**Si falla:** rompe prod, hay que rollback.

### 9️⃣ ¿Tests globales pasando?
```bash
php artisan test --compact
```

**Si falla:** regresiones silenciosas.

### 🔟 ¿Health check post-deploy?
```bash
curl.exe /  /admin  /marketplace  /pricing  /sitemap.xml  /robots.txt
```

**Si falla:** 500 silenciosos en producción.

---

## Output esperado (template)

```markdown
## Self-Audit Report — [Sprint X]

✅ 1. Pint: OK (0 issues, 3 archivos formateados)
✅ 2. Tests nuevos: 4 tests passing (`Feature/Billing/DunningBannerTest.php`)
⚠️ 3. codebase-memory: NO usado (justifica búsqueda tradicional)
✅ 4. Memoria actualizada: 2 findings nuevos en `.ai/memory/findings.json`
✅ 5. Refs cruzadas: 3 callers verificados
❌ 6. i18n: 12 keys nuevas en `en.js` sin contraparte en `es.js`
✅ 7. Build: USER lanzará
✅ 8. Migración: --pretend OK (0 queries)
✅ 9. Tests globales: 209/220 passing (11 pre-existentes)
⏳ 10. Health: post-deploy pendiente

**Bloqueos para commit:** 1 (i18n desincronizado)
**Acción:** Traducir 12 keys antes de commit.
```

---

## Triggers de fallo (auto-bloqueo)

Si CUALQUIER punto falla con ❌, **bloquear commit** hasta arreglar.

| Triggger | Acción |
|---|---|
| ❌ i18n desincronizado | Traducir claves faltantes |
| ❌ Tests rompiendo | Arreglar test o rollback cambio |
| ❌ Build no regenerado | Pedirle al USER que lance npm build |
| ❌ Migración rompe tabla | Rollback migration + restaurar tabla |
| ❌ Health 500 en prod | `ssh + rollback releases/<anterior>` |

---

## Memoria persistente (encontrar y guardar)

Si encuentras fallo NO listado en `.ai/AUDITORIA-POST-IMPLEMENTACION.md`:

1. **Documenta en** `.ai/memory/findings.json` (append).
2. **Añade trampa** a la lista de Trampas Conocidas.
3. **Añade regla** a `.ai/rules/` correspondiente.

---

## Skills hermanas

- `importnex-quickref` — vista de pájaro.
- `importnex-debug-rca` — análisis causa raíz post-fallo.
- `importnex-design-system` — si auditó frontend.
- `importnex-cashier-billing` — si auditó billing.

---

## Comando "estoy orgulloso"

Antes de claim "listo", decir:

```
✅ He ejecutado los 10 puntos de self-audit.
✅ 0 bloqueos pendientes.
✅ Memoria actualizada.
✅ Acceptance rate mide (output aceptado sin modificar).

LISTO PARA COMMIT.
```

Si no puedes decir esto con honestidad, **no commitear todavía**.
