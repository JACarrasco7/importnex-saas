---
description: Dashboard de métricas del sistema IA. Acceptance rate, tokens ahorrados, skills usadas, agentes invocados, fallos, aprendizaje continuo. Mantener como métrica humana.
---

# 📊 Dashboard IA — Métricas de Acceptance Rate

> Métricas auto-contabilizadas del uso de GitHub Copilot + skills + agentes en ImportnexCore.
> Patrón VoltAgent: medir para mejorar.

## Métricas globales (a actualizar tras cada sprint)

| Métrica | Valor objetivo | Última medición |
|---|---|---|
| Skills casa usadas por sprint | >= 5 | — |
| Acceptance rate (acepta sin cambios) | >= 80% | — |
| Tokens ahorrados vs baseline | >= 40% | — |
| Tests passing post-cambio | 100% | 197/210 |
| Tiempo medio por feature (h) | -30% | — |
| Fallos pre-existentes no creados | 0 | 13 (pre) |
| Builds OK | 100% | ✅ |
| Deploys OK | 100% | ✅ |

## Acceptance rate por sprint

| Sprint | Tareas | Aceptadas 1ª vez | Modificadas | Rechazadas | Tasa |
|---|---|---|---|---|---|
| Sprint A | 6 | — | — | — | — |
| Sprint B | 8 | — | — | — | — |
| Sprint C | 4 | — | — | — | — |
| Sprint D | 1 | — | — | — | — |
| Sprint E | 1 | — | — | — | — |
| Sprint F | 1 | — | — | — | — |

## Skills más activadas (telemetría estimada)

1. `.ai/skills/importnex-multitenancy` — 100% tareas backend
2. `.ai/skills/importnex-design-system` — 100% tareas frontend
3. `.ai/skills/importnex-cashier-billing` — 30% tareas totales
4. `.ai/skills/importnex-i18n` — 60% tareas frontend
5. `.ai/skills/importnex-forge-deploy` — 10% tareas totales

## Agentes más delegados

1. `importnex-frontend` — tareas UI
2. `importnex-auditor` — pre-merge
3. `importnex-billing-expert` — tareas Stripe
4. `importnex-data-migration` — migraciones

## Tokens ahorrados

Estimación por reducción de contexto:
- Skill cargada vs archivo completo: ~3000 tokens por skill.
- 8 skills × uso medio 3×/sprint × 6 sprints = 144k tokens ahorrados/mes.
- Agent delegation ahorra ~5000 tokens por delegación (subagente con scope).
- **Total estimado: ~200k tokens/mes.**

## Fallos y aprendizajes

### 2026-08-06

- **Fallo:** skills duplicadas `.github/skills/` + `.claude/skills/` consumían contexto doble. **Fix:** consolidado a `.claude/skills/`.
- **Fallo:** `laravel/boost` en `require` rompía `composer install --no-dev`. **Fix:** movido a `require-dev`.
- **Aprendizaje:** antes de cada sprint, preguntar "¿estás orgulloso del resultado?" al usuario reduce iteraciones.

## Pre-tool hook (prompt system)

Antes de cerrar cada sprint, el agente debe:

```markdown
## ¿Estás orgulloso?

- [ ] Las skills se activaron correctamente
- [ ] No se rompió nada que funcionara
- [ ] Los tests pasan
- [ ] El usuario recibió contexto mínimo
- [ ] Hay rollback plan documentado
```

Si algún item falla, NO marcar sprint como completado.

## Próximas métricas a capturar

- Latencia de respuesta del LLM por sprint.
- Número de edits de usuario sobre outputs del agente.
- Skills que se quedan obsoletas (revisar trimestralmente).
- Agentes que nunca se invocan (candidatos a eliminar).
