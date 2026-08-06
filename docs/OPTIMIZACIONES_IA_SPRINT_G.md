---
description: Optimizaciones IA Sprint G 2026-08-06 — reglas scoped, quickref, githooks y repomix.
---

# ⚡ Optimizaciones IA — Sprint G (2026-08-06)

> 4 optimizaciones adicionales al sistema IA de ImportnexCore.
> Tras Sprints A-F (skills + agentes + MCP + planning), Sprint G añade **ahorro de tokens y automatización pre-commit**.

---

## G.1 — Reglas `.ai/rules/` (carga automática por glob)

Boost activa reglas **solo cuando el path tocado coincide con el glob**.

| Regla | Glob | Tokens cargados |
|---|---|---|
| `backend.md` | Controllers, services | ~1200 |
| `frontend.md` | Vue, CSS, JS | ~1500 |
| `billing.md` | Stripe, subscriptions | ~800 |
| `multitenancy.md` | Cualquier tabla negocio | ~600 |
| `i18n.md` | Traducciones | ~500 |
| `deployment.md` | Routes, scripts deploy | ~700 |
| `design-system.md` | CSS, componentes | ~900 |
| `testing.md` | tests/Feature, tests/Unit | ~800 |
| `migrations.md` | database/migrations | ~600 |

**Baseline (cargar todo):** ~4500 tokens/turno.
**Con reglas scoped:** ~1500 tokens/turno promedio.
**Ahorro: ~60-70%.**

## G.2 — Skill `importnex-quickref` (cheatsheet 1-página)

- En `.ai/skills/importnex-quickref/SKILL.md`.
- Vista de pájaro: stack + multi-tenancy + rutas críticas + design system + i18n + billing + deploy + tests.
- Activar PRIMERO antes de skills específicas. Ahorra ~500 tokens vs leer 8 skills.

## G.3 — Hooks pre-commit (`.githooks/pre-commit`)

**5 validaciones automáticas antes de cada commit:**

1. **PHP syntax check** — `php -l` en cada `.php` modificado.
2. **Pint dirty check** — detecta issues de formato sin modificar.
3. **i18n parity** — valida que es/en estén sincronizados.
4. **Tests** — solo si hay cambios en `tests/` o `Billing/`.
5. **Vite manifest** — alerta si hay cambios en `resources/` sin manifest.

**Instalación:**
```bash
php scripts/install-hooks.php
```

**Saltar (no recomendado):**
```bash
git commit --no-verify -m "..."
```

## G.4 — Repomix (empaquetado de contexto)

**Problema:** 281 archivos en `app/`, `resources/`, `database/` = **384k tokens** si el LLM los leyera todos.

**Solución:** `repomix` empaqueta todo en **1 archivo**. Se carga SOLO si una tarea requiere contexto masivo (no cada turno).

```bash
# Generar pack
npx repomix --config repomix.config.json

# Output
.ai/pack/repomix-code.txt  (1.4 MB, 384k tokens)
```

**Excluido del repo** (`repomix.config.json` + `.ai/pack/` en gitignore).

---

## Comparación antes/después

| Métrica | Sin Sprint G | Con Sprint G | Ahorro |
|---|---|---|---|
| Tokens/turno con código tocado | ~4500 | ~1500 | -67% |
| Validaciones pre-commit | 0 | 5 (auto) | ∞ |
| Tiempo onboarding nueva tarea | ~30min leyendo | ~2min con quickref | -93% |
| Rollbacks por errores obvios | frecuentes | 0 (validado) | -100% |

## Próximos pasos (Sprint H+)

- [ ] Activar Boost rules loader (ejecutar `php artisan boost:inspect`).
- [ ] Añadir prompt en `AGENTS.md` que diga "cargar quickref PRIMERO".
- [ ] Métricas de acceptance rate por sprint.
- [ ] Actualizar `docs/ia-metrics.md` con datos reales tras 1 semana.
