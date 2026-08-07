---
name: importnex-context-optimizer
description: Optimiza uso de tokens. Decide qué cargar y qué omitir según la tarea. Comprime contexto verbose, prioriza reglas activas, descarta información obsoleta. Auto-invocado por el agente principal.
---

# ⚡ Context Optimizer — Uso eficiente de tokens

> Reduce tokens gastados en cada turno. Decide qué vale la pena leer.

---

## Cuándo se activa

- Auto-invocado al inicio de cada turno (cuando hay tokens limitados).
- Cuando el agente detecta que cargó demasiada info irrelevante.
- "optimiza contexto", "gasta menos tokens".

---

## Estrategia de 3-niveles

### Nivel 1: Mínimo viable (cuando tokens < 4k disponibles)
```bash
# Solo:
cat .ai/skills/importnex-quickref/SKILL.md  # 500 tokens
cat .ai/memory/findings.json | jq '.[] | select(.date > "2026-07-01")'  # 200 tokens
# Total: ~700 tokens
```

### Nivel 2: Estándar (cuando tokens 4k-12k)
```bash
# Quickref + reglas del dominio + findings recientes
# Total: ~1500-2000 tokens
```

### Nivel 3: Completo (cuando tokens > 12k)
```bash
# Todo + trampas + memoria + reglas cross
# Total: ~3500-4500 tokens
```

---

## Reglas de priorización

| Prioridad | Cargar siempre | Cargar condicional | Omitir si tokens bajos |
|---|---|---|---|
| **P0** | `.ai/skills/quickref` | — | — |
| **P1** | `.ai/rules/{dominio}` | — | — |
| **P2** | `.ai/memory/findings.json` (recientes) | — | antiguos (>30 días) |
| **P3** | — | `.ai/skills/{dominio}/SKILL.md` completa | versiones cortas |
| **P4** | — | trampas conocidas | solo críticas |
| **P5** | — | docs/PLAN_*.md | muy largos (>10k tokens) |
| **P6** | — | composer.json, package.json | cache de hace <24h |

---

## Compresión de contexto verbose

Si una skill es muy larga (>500 líneas), versión comprimida:

```
.ai/skills/importnex-cashier-billing/SKILL.md      # completa (1200 líneas)
.ai/skills/importnex-cashier-billing/SKILL.min.md  # comprimida (200 líneas)
```

**Trigger:** si tamaño > 100KB → crear `.min.md`.

```bash
# Auto-comprimir
php scripts/memory-manager.php compress-skill importnex-cashier-billing
```

---

## Cache de lectura (no recargar 2x misma sesión)

```
.ai/cache/
├── last-load.json            # { timestamp, files_loaded: [...] }
├── tokens-saved.log          # log de ahorro por sesión
└── session-context.md        # snapshot para siguiente turno
```

---

## Comando "modo eficiente"

```markdown
USUARIO: "modo eficiente"

RESPUESTA agente:
- Activo Nivel 1 (mínimo viable)
- Cargo solo quickref + reglas del path actual
- Omitir skills completas, usar versiones comprimidas
- Aceptar respuestas más cortas, sin detalles extras
- Cache local del contexto entre turnos
```

---

## Heurística de descarte

Si el agente detecta que el contexto tiene info no usada en últimos 3 turnos → descartar.

```php
// memory-manager.php detect-stale
foreach ($loaded_files as $file) {
    $last_used = $usage_log[$file] ?? 0;
    if (now() - $last_used > 3_turns) {
        mark_as_stale($file);
    }
}
```

---

## Output esperado

```markdown
## Context Optimization Report

- Tokens cargados: 1247 (vs baseline 4500)
- Ahorro: 72.3%
- Skills activas: 3 (multitenancy, design-system, billing)
- Skills comprimidas: 2 (cashier-billing, i18n)
- Cache hits: 5 archivos
- Stale descartado: 0
```

---

## Anti-patrones

- ❌ Cargar TODO siempre (gasto inútil).
- ❌ Recargar misma skill 5 turnos seguidos (no aporta).
- ❌ Ignorar compresión disponible.
- ❌ Priorizar info antigua sobre reciente.

---

## Skills amigas

- `importnex-auto-context` — carga inteligente
- `importnex-auto-learner` — aprende qué cargar/omitir
- `importnex-quickref` — versión comprimida canónica
