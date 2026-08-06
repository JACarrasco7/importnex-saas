---
status: active
last_updated: 2026-08-06
task: {{TASK_NAME}}
agent: {{AGENT}}
priority: P0 | P1 | P2
estimated_effort: {{HOURS}}h
---

# 🎯 Plan Persistente — {{TASK_NAME}}

> Patrón Manus-style: plan completo en disco, no en contexto.
> Actualiza este archivo cada vez que avances. El agente principal lo lee antes de continuar.

---

## 1. Objetivo

Una frase describiendo QUÉ queremos conseguir y POR QUÉ importa.

## 2. Criterios de éxito (Definition of Done)

- [ ] Criterio 1: verificable, medible
- [ ] Criterio 2: verificable, medible
- [ ] Criterio 3: verificable, medible
- [ ] Test passing
- [ ] Build OK
- [ ] Deploy OK

## 3. Skills/Agentes relevantes

- `.ai/skills/importnex-X` → cuándo invocar
- `.github/agents/importnex-X` → cuándo delegar

## 4. Plan paso a paso

### Paso 1 — [descripción corta]
- **Acción concreta:** qué archivo crear/editar
- **Aceptación:** cómo sé que pasó
- **Tiempo estimado:** X min

### Paso 2 — [descripción corta]
- **Acción concreta:**
- **Aceptación:**
- **Tiempo estimado:**

### Paso 3 — [descripción corta]
- **Acción concreta:**
- **Aceptación:**
- **Tiempo estimado:**

## 5. Checkpoint actual

```
Última acción completada: Paso N
Próxima acción: Paso N+1
Bloqueos: ninguno
Notas: ...
```

## 6. Decisiones tomadas (y por qué)

- **Decisión A:** razón X. Si cambia, revisar impacto.
- **Decisión B:** razón Y.

## 7. Anti-patrones a evitar

- ❌ No X
- ❌ No Y

## 8. Riesgos identificados

| Riesgo | Probabilidad | Impacto | Mitigación |
|---|---|---|---|
| Y | Media | Alto | Backup antes |

## 9. Rollback plan

Si esto falla, ¿cómo revertir?

```bash
# comandos concretos
```

## 10. Acceptance rate

- Tests añadidos: N
- Tests passing: N (target: 100%)
- Files modificados: N
- Líneas netas: ±N
- Build: ✅
- Deploy: ⏳

## 11. Notas / Aprendizajes

- Insight 1
- Insight 2

## 12. Próximos pasos al cerrar

1. [ ] Marcar como completado
2. [ ] Commit con mensaje descriptivo
3. [ ] Push origin master
4. [ ] Deploy Forge
5. [ ] Health check
6. [ ] Cerrar este archivo (mover a `tasks/closed/`)
