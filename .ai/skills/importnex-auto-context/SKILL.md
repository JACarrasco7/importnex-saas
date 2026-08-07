---
name: importnex-auto-context
description: Auto-carga inteligente del contexto del proyecto. Se activa al INICIO de cada sesión sin que el usuario lo pida. Carga solo lo relevante según el path tocado: skills scoped, reglas por glob, memoria reciente, trampas conocidas, y quickref.
---

# 🧠 Auto-Context — Carga inteligente

> Esta skill es **automática**. El agente principal la invoca al arrancar sin necesidad de trigger explícito.

---

## Cuándo se activa

- Inicio de CUALQUIER sesión nueva.
- Cambio de archivo que toca otro dominio (ej: backend → frontend).
- Tras deploy / commit importante.
- Cuando el agente detecta que está "trabajando a ciegas".

---

## 3-Phase Context Loading

### Fase 1: Quickref SIEMPRE
```bash
cat .ai/skills/importnex-quickref/SKILL.md
```
**Output:** stack + multi-tenancy + design + i18n + billing + deploy (1 página, ~500 tokens).

### Fase 2: Reglas por glob (solo relevantes)
```bash
# Si tocas backend:
cat .ai/rules/backend.md
cat .ai/rules/multitenancy.md

# Si tocas frontend:
cat .ai/rules/frontend.md
cat .ai/rules/design-system.md

# Si tocas billing:
cat .ai/rules/billing.md
```
**Output:** ~1000-1500 tokens (vs ~4500 cargar todo).

### Fase 3: Memoria reciente + trampas
```bash
# Solo fallos de últimos 30 días
node -e "
const fs = require('fs');
const findings = JSON.parse(fs.readFileSync('.ai/memory/findings.json'));
const recent = findings.filter(f => new Date(f.date) > new Date(Date.now() - 30*24*60*60*1000));
console.log('Recent findings:', recent.length);
recent.forEach(f => console.log('-', f.issue, '(' + f.severity + ')'));
"

# Trampas activas
grep -E "^### Trampa" .ai/AUDITORIA-POST-IMPLEMENTACION.md
```

**Output:** ~500 tokens, solo lo aplicable.

---

## Cache de contexto (entre turnos)

Para evitar recargar todo cada turno:

```
.ai/cache/
├── project-context.md       # Stack + arquitectura + reglas globales (snapshot)
├── active-skills.md          # Skills activas por dominio
└── recent-changes.md         # Últimos 5 commits + findings relevantes
```

Regenerado solo cuando hay cambios estructurales (composer.json, package.json, .ai/rules/, .ai/skills/).

---

## Auto-update del contexto

**Triggers para regenerar `project-context.md`:**

1. Commit que modifica `composer.json` o `package.json`.
2. Commit que añade/quita skill en `.ai/skills/`.
3. Commit que añade regla en `.ai/rules/`.
4. Commit que modifica `.ai/memory/findings.json`.
5. Cada lunes a las 9am (cron semanal).

```bash
# Hook post-commit detecta cambios y regenera
if git diff --name-only HEAD~1 HEAD | grep -qE '(composer\.json|package\.json|\.ai/skills/|\.ai/rules/|\.ai/memory/)'; then
    php scripts/memory-manager.php regenerate-context
fi
```

---

## Acceptance rate tracking automático

```bash
# Cada lunes a las 9am
php scripts/memory-manager.php analyze-sprint
```

**Output:** `docs/ia-metrics.md` actualizado con:
- Acceptance rate real (outputs vs edits).
- Skills más activadas (heurística).
- Patrones de fallo recurrentes.
- Reglas obsoletas (no activadas en 90 días).

---

## Anti-patrones

- ❌ Cargar TODAS las skills cada turno (gastar tokens).
- ❌ Saltar quickref (siempre cargar).
- ❌ Ignorar findings (memoria = errores repetidos).
- ❌ Modificar `.ai/skills/` sin commit (no se persiste).

---

## Comandos relacionados

```bash
# Auto-cargar contexto (manual)
php scripts/memory-manager.php load-context

# Listar skills activas para un path
php scripts/memory-manager.php active-skills app/Http/Controllers/CarController.php

# Ver trampas recientes
php scripts/memory-manager.php recent-traps --days=30

# Acceptance rate
php scripts/memory-manager.php acceptance-rate
```

---

## Skills amigas (composables)

- `importnex-quickref` — vista rápida
- `importnex-self-audit` — auditoría tras cambio
- `importnex-debug-rca` — análisis de causa raíz
- `importnex-auto-learner` — memoria auto-actualizable
- `importnex-context-optimizer` — optimizar uso de tokens