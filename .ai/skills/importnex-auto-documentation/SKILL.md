---
name: importnex-auto-documentation
description: Genera documentación automáticamente de cambios. CHANGELOG entries, migration notes, API docs, README updates. Triggered tras commits que afectan archivos clave.
---

# 📝 Auto-Documentation — Docs que se mantienen

> Genera docs sin que tengas que pedirlas. Tras cada commit relevante, actualiza:
> - CHANGELOG.md
> - docs/MIGRATIONS.md (si hay migración)
> - docs/API.md (si hay endpoint nuevo)
> - README.md (si cambia setup)

---

## Cuándo se activa

- Tras cada commit (hook post-commit).
- Cuando detecta: nueva ruta, nueva migration, nuevo endpoint, cambio en package.json.

---

## Auto-detección de tipo de cambio

```bash
# Hook analiza archivos modificados:
git diff --name-only HEAD~1 HEAD | sort -u
```

| Archivos modificados | Docs a generar/actualizar |
|---|---|
| `database/migrations/*.php` | Append a `docs/MIGRATIONS.md` |
| `routes/*.php` | Append a `docs/API.md` (si API) |
| `app/Http/Controllers/*.php` | Append a `docs/API.md` |
| `resources/js/Pages/**/*.vue` | Append a `docs/UI.md` |
| `composer.json` o `package.json` | Update `README.md` setup section |
| `config/*.php` | Update `docs/CONFIG.md` |
| `.ai/skills/**/*.md` | Update `docs/IA-SYSTEM.md` |

---

## CHANGELOG auto-generado

```markdown
<!-- CHANGELOG.md (auto-generado por importnex-auto-documentation) -->

## [Unreleased]

### Added
- feat(pricing): nueva página pública PricingPublic.vue (#a436062)
- feat(ia): 11 skills casa en .ai/skills/ (#04bb984)

### Fixed
- fix(views): escapar @type en schema-org.blade.php (#ff9fbd1)
- fix(routes): wirear SitemapController + ruta /sitemap.xml (#7b1c9ad)

### Security
- chore(deps): actualizar maatwebsite/excel a 3.1.68 (9 vulns upstream)
```

**Conventional Commits:** `feat|fix|chore|docs|style|refactor|perf|test(scope): description`

---

## Generación automática

```bash
# Trigger manual
php scripts/memory-manager.php auto-changelog --since=2026-08-01

# Hook post-commit
git log --oneline --since=24h | while read hash msg; do
    TYPE=$(echo "$msg" | grep -oE '^[a-z]+(\([a-z-]+\))?:' | head -1)
    echo "- $msg (#${hash:0:7})" >> docs/CHANGELOG.draft.md
done
```

---

## Validación de docs

```php
// scripts/memory-manager.php validate-docs
// Comprueba:
//   - Cada endpoint en routes/*.php tiene doc en docs/API.md
//   - Cada migration tiene nota en docs/MIGRATIONS.md
//   - Cada skill en .ai/skills/ está en docs/IA-SYSTEM.md
```

---

## Anti-patrones

- ❌ CHANGELOG manual (se desactualiza).
- ❌ Docs duplicadas en `docs/PLAN_*.md` (consolidar).
- ❌ Commits sin conventional prefix.
- ❌ Skills sin documentar.

---

## Skills amigas

- `importnex-auto-learner` — detecta cambios
- `importnex-self-audit` — valida docs antes de commit
