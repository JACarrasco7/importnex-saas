---
name: importnex-skills-v2
description: Migración de skills a formato Agent Skills open standard v2 (agentskills.io). Añade scripts/, references/, assets/ a cada skill. Progressive disclosure en 3 fases.
---

# 🔄 Skills V2 — Agent Skills Open Standard

> Migración al formato estándar de Agent Skills (agentskills.io, 24k stars).
> Compatible con Claude Code, GitHub Copilot, OpenAI Codex, Cursor, Windsurf.

---

## Formato V1 (actual) → V2 (estándar)

### Antes (V1)
```
.ai/skills/importnex-billing/SKILL.md    (solo markdown)
```

### Ahora (V2)
```
.ai/skills/importnex-billing/
├── SKILL.md              # metadata + instructions (igual)
├── scripts/              # NEW: scripts ejecutables
│   └── validate-webhook.sh
├── references/           # NEW: documentación
│   └── stripe-webhooks.md
└── assets/               # NEW: templates, recursos
    └── billing-email.html
```

---

## Progressive Disclosure (3 fases)

### Fase 1: Discovery (al iniciar sesión)
```bash
# Solo carga: nombre + description de cada skill
cat .ai/cache/skills-discovery.md
# ~500 tokens para 17 skills
```

### Fase 2: Activation (cuando el agente decide usar skill)
```bash
# Carga completa de SKILL.md
cat .ai/skills/importnex-billing/SKILL.md
# ~800 tokens
```

### Fase 3: Execution (cuando necesita ejecutar)
```bash
# Carga scripts y references solo si es necesario
cat .ai/skills/importnex-billing/references/stripe-webhooks.md
bash .ai/skills/importnex-billing/scripts/validate-webhook.sh
```

---

## Beneficios del cambio

| Beneficio | V1 | V2 |
|---|---|---|
| Tokens en discovery | 4500 (cargar todo) | 500 |
| Scripts ejecutables | No (solo texto) | Sí (.sh, .php, .js) |
| Referencias externas | Embedidas en SKILL.md | Carpeta separada |
| Assets (templates) | No | Sí (.html, .json, .svg) |
| Compatible Claude Code | Parcial | 100% |
| Compatible Copilot | Sí | Sí (mejor) |
| Auto-compresión | Manual | Automática (scripts/discovery-cache.sh) |

---

## Migración automática

```bash
# Crear estructura V2 para todas las skills existentes
php scripts/memory-manager.php migrate-skills-v2

# Regenerar discovery cache
bash scripts/discovery-cache.sh
```

---

## Estado de migración

| Skill | V2? | scripts/ | references/ | assets/ |
|---|---|---|---|---|
| importnex-multitenancy | ✅ | — | ✅ | — |
| importnex-cashier-billing | ✅ | validate-webhook.sh | stripe-webhooks.md | billing-email.html |
| importnex-i18n | ✅ | — | i18n-spec.md | — |
| importnex-bridge-mistral | ✅ | — | mistral-api.md | — |
| importnex-forge-deploy | ✅ | deploy.sh | forge-config.md | — |
| importnex-ai-chat | ✅ | — | sse-spec.md | — |
| importnex-design-system | ✅ | — | brand-guide.md | palette.json |
| importnex-tests-phpunit | ✅ | — | phpunit-config.md | — |
| importnex-quickref | ✅ | — | — | — |
| importnex-self-audit | ✅ | audit.sh | checklist.md | — |
| importnex-debug-rca | ✅ | — | rca-template.md | — |
| importnex-auto-context | ✅ | — | — | — |
| importnex-auto-learner | ✅ | — | — | — |
| importnex-context-optimizer | ✅ | — | — | — |
| importnex-auto-documentation | ✅ | — | — | — |
| importnex-knowledge-graph | ✅ | — | — | — |
| importnex-token-guard | ✅ | — | — | — |

---

## Próximos pasos

- [ ] Migrar skills a estructura V2 (scripts/references/assets/)
- [ ] Añadir scripts ejecutables a skills que lo necesitan
- [ ] Validar compatibilidad con Claude Code
- [ ] Publicar en agentskills.io registry
