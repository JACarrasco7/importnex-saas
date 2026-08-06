<!-- filepath: docs/PLAN_IA_OPTIMIZACION_2026-08-06.md -->
# Plan de Optimización IA — ImportnexCore
**Fecha:** 2026-08-06
**Objetivo:** reducir tokens, mejorar contexto, multi-agente, MCP estratégico, skills curados.

> **Independiente del programa de producto.** Aquí solo se mejora el *sistema de IA* que programa el producto.
> Implementar después. **No es Sprint de desarrollo de producto.**

---

## 📊 Estado actual (auditoría 2026-08-06)

| Capa | Estado | Coste tokens |
|---|---|---|
| **`.github/copilot-instructions.md`** | ✅ 80 líneas | ~600 tokens / request |
| **`AGENTS.md` + `CLAUDE.md` (Boost)** | ✅ 224 líneas cada uno | ~1.7k tokens / request |
| **MCP `laravel-boost`** | ✅ activo | bajo |
| **5 skills Boost auto-instaladas** | ✅ (infer-conventions, cashier, laravel-bp, inertia-vue, tailwind) | lazy-load |
| **`.ai/rules/` (7 reglas durables)** | ✅ glob-matched | ~400 tokens / match |
| **`.github/skills/` + `.claude/skills/`** | ✅ 5 duplicadas | sin coste si no se activan |
| **`.mcp.json` + `.vscode/mcp.json`** | ✅ configurado | startup |
| **`.boost/`** | ✅ guidelines cargadas upfront | ~3k tokens / sesión |
| **Sesiones Chronicle / `memory` tool** | ❌ no estructurado | n/a |

**Coste total estimado por sesión nueva: ~6k tokens** solo en contexto fijo antes del primer mensaje del usuario.

### Problemas identificados

1. **Duplicación**: AGENTS.md + CLAUDE.md + .claude/skills + .github/skills (mismo contenido 4× en disco).
2. **Context rot**: AGENTS.md se carga completo en cada chat, aunque 80% no aplique.
3. **Sin agente dedicado**: para tareas grandes (audit, refactor, tests) usamos el mismo modo.
4. **Skills Boost limitadas**: no tenemos skills específicas para Spatie, Ziggy, Maatwebsite/Excel, Browsershot, etc.
5. **Sin plan persistente**: tareas largas (migración, billing Fase N) se pierden entre sesiones.
6. **MCP limitado**: solo `laravel-boost`. Faltan herramientas para testing, deploy, browser, GitHub.

---

## 🎯 Objetivos medibles (a 4 semanas)

| Métrica | Baseline | Target |
|---|---|---|
| Tokens de contexto fijo / sesión | ~6k | **<1.5k** (-75%) |
| Tiempo a primer mensaje útil | n/a | <3s |
| Skills específicas del proyecto | 0 | 8+ |
| Agentes custom (`.agent.md`) | 0 | 3-4 |
| MCP servers activos | 1 | 3-4 |
| Tareas largas con persistencia | 0 | 100% |
| Acceptance rate de sugerencias Copilot | n/a | >75% |

---

## 🧠 Investigación 2026: lo que funciona

### Fuentes consultadas

- [GitHub Next: "Can agents be proud of their work?"](https://githubnext.com/posts/can-agents-be-proud-of-the-work/)
- [VS Code Copilot MCP docs (2026)](https://code.visualstudio.com/docs/copilot/chat/mcp-servers)
- [Agent Skills open standard](https://agentskills.io/home) (Anthropic + comunidad)
- [GitHub: `awesome-copilot`](https://github.com/github/awesome-copilot)
- [VoltAgent: `awesome-agent-skills`](https://github.com/VoltAgent/awesome-agent-skills)
- [wshobson/agents](https://github.com/wshobson/agents) — multi-harness marketplace
- [addyosmani/agent-skills](https://github.com/addyosmani/agent-skills) — production-grade
- [OthmanAdi/planning-with-files](https://github.com/OthmanAdi/planning-with-files) — Manus-style plans
- [anthropics/skills](https://github.com/anthropics/skills) — reference impl
- [cherryhq/cherry-studio](https://github.com/CherryHQ/cherry-studio) — multi-agent
- [Laravel Boost v2.5](https://laravel.com/docs/13.x/ai)

### Patrones 2026 que vamos a aplicar

1. **Progressive disclosure de skills** — `name` + `description` en startup, `SKILL.md` solo cuando aplica.
2. **Planning persistente** — plan en markdown + checkpoint por turno (estilo Manus/OthmanAdi).
3. **Agentes custom scoped** — un `.agent.md` por dominio (billing, frontend, data) en vez de uno mega.
4. **MCP orquestado** — cada MCP server tiene rol claro: `laravel-boost` (introspección), `playwright` (E2E), `github` (issues), `browsershot` (PDF).
5. **Memorias locales** — `memory` tool por scope (user / repo / session) en vez de pegar todo en cada prompt.
6. **Acceptance rate via edits pequeños** — preferir `edit_file` a `create_file` cuando exista el archivo.
7. **Be proud of your work** — pedir al agente "¿estás orgulloso?" antes de cerrar tarea.

---

## 🏗️ Plan de implementación

### Sprint A — Reducir tokens (1 día, alto impacto)

| # | Acción | Ahorro |
|---|---|---|
| A.1 | Mover `.claude/skills/` y `.github/skills/` Boost a **un único** `.claude/skills/`. Symlink desde `.github/`. | ~600KB disco, 0 tokens |
| A.2 | Partir `AGENTS.md` en **módulos `@include` Boost** (uno por paquete: inertia, tailwind, pint, etc.). | ~1.2k tokens / sesión |
| A.3 | Convertir `.ai/rules/index.md` en **TOCs** + reglas cargadas solo cuando el glob matchea. | ~400 tokens / match |
| A.4 | Añadir `boost.json` exclude: skills Boost no usadas (livewire, filament, pest). | ~800 tokens / sesión |
| A.5 | `composer require laravel/boost --dev` (mover a dev, no a require). | n/a |
| A.6 | `.gitignore` los `.mcp.json` workspace-specific + `.boost/` generated. | hygiene |

**Total ahorrado: ~2.4k tokens / sesión (-40%).**

### Sprint B — Skills específicas del proyecto (1-2 días)

Custom skills (carpeta `resources/.ai/skills/` o `.claude/skills/`) con SKILL.md + opcional `references/`:

| Skill | Trigger | Contenido |
|---|---|---|
| `importnex-multitenancy` | "tabla organization_id", "tenant" | Patrón `organization_id`, middleware `EnsureOrganization`, query scoping, tests multi-tenant. |
| `importnex-cashier-billing` | "subscription", "stripe", "webhook" | Idempotencia webhooks, grace period, `is_owner`, dunning en español, idempotency_key. |
| `importnex-i18n` | "traducción", "i18n", "lang" | Sync `es/en` + `resources/js/i18n/`, paridad claves, `node scripts/check-translations.cjs`. |
| `importnex-bridge-mistral` | "Mistral", "scraping", "puente" | Flujo Mistral API → bridge → Cars, model choices, fallback, prompt cache. |
| `importnex-forge-deploy` | "deploy", "forge", "ssh" | `forge-mysql-tunnel.bat`, `subir-informe.ps1`, cache clear, npm build, deploy checklist. |
| `importnex-ai-chat` | "AiChat", "ai.chat", "prompts" | Floating widget, streaming, history, métricas, guardrails. |
| `importnex-design-system` | "estoril", "asphalt", "platinum", "BRAND" | Paleta, accesibilidad WCAG AA, dark mode variants, `card-premium`, `text-gradient`. |
| `importnex-tests-phpunit` | "test", "phpunit", "Feature test" | SQLite in-memory, factories, RefreshDatabase, `actingAs($user)`, datos multi-tenant. |

> **Origen**: estos skills son home-grown. Investigamos `wshobson/agents` (3k+ stars) y `addyosmani/agent-skills` (production-grade) como referencia para SKILL.md format, pero **no copiamos skills ajenos** porque son muy genéricos.

### Sprint C — Agentes custom (`.github/agents/*.agent.md`) (1 día)

| Agente | Modelo sugerido | Tools |
|---|---|---|
| `importnex-auditor.agent.md` | Sonnet | Read, Grep, Glob, Bash, MCP `laravel-boost` |
| `importnex-frontend.agent.md` | Sonnet/Haiku | Read, Edit, Bash, MCP `laravel-boost` |
| `importnex-data-migration.agent.md` | Sonnet | Read, Bash (artisan, mysql), MCP `laravel-boost` |
| `importnex-billing-expert.agent.md` | Sonnet | Read, Edit, Grep, MCP `laravel-boost` |

> Formato: `.github/agents/<name>.agent.md` con frontmatter `description`, `tools`, `model`, `infer: true`. Detectados automáticamente por Copilot.

**Beneficio**: tareas complejas se delegan a agente especializado que mantiene su propio contexto sin contaminar el chat principal.

### Sprint D — MCP servers adicionales (½ día)

Investigamos estos candidatos (los 3 más útiles):

| MCP | URL / comando | Rol | Cuesta |
|---|---|---|---|
| **`@microsoft/mcp-server-playwright`** | `npx -y @microsoft/mcp-server-playwright` | E2E browser testing, screenshots, click flows | medio |
| **`@modelcontextprotocol/server-github`** | Docker o stdio | Issues, PRs, code review, search repo | bajo |
| **`@upstash/mcp-server-browsershot`** | stdio | Reemplazar nuestro Spatie Browsershot con MCP dedicado | alto (eval) |
| **`@stripe/mcp-server-stripe`** | stdio (oficial Stripe) | Refunds, disputes, customers, charges sin curl | medio |

**Decisión**: añadir primero Playwright (testing E2E real del marketplace). Después GitHub. Stripe lo evaluamos cuando activemos Stripe real.

### Sprint E — Planning persistente (1 día)

Adoptar patrón **planning-with-files** de OthmanAdi (3k+ stars):

- `tasks/active/<id>.md` con: objetivo, criterios de éxito, plan paso a paso, checkpoint actual.
- Pre-tool hook inyecta el plan activo en cada turno.
- Crash recovery: si la sesión muere, leemos el .md y retomamos.
- Aplicar a migraciones largas, fases de billing, refactors multi-archivo.

**Beneficio**: tareas largas ya no se pierden entre sesiones. Es el patrón más popular de agent-skills 2026.

### Sprint F — MCP orquestación + acceptance rate (½ día)

- Pre-tool hook que pregunte "¿estás orgulloso de esto?" antes de cerrar (patrón GitHub Next).
- Post-edit hook que mida diff vs request (rechaza edits no relacionados al objetivo).
- Acceptance rate dashboard en `docs/ia-metrics.md` semanal.

---

## 📚 Skills de terceros a evaluar (estrellas + comunidad, NO copy-paste)

Investigamos pero **no instalamos** directamente porque la mayoría son muy genéricos. Mejor inspiración que dependencia:

| Repo | Estrellas | Lo que aporta | ¿Vale instalar? |
|---|---|---|---|
| [anthropics/skills](https://github.com/anthropics/skills) | n/a (oficial) | Spec + skills de referencia (PDF, DOCX) | ❌ usar spec |
| [addyosmani/agent-skills](https://github.com/addyosmani/agent-skills) | alta | Engineering best practices para AI agents | ❌ inspiración |
| [wshobson/agents](https://github.com/wshobson/agents) | ~3k | Multi-harness marketplace (Claude/Codex/Copilot/Gemini) | ⚠️ evaluar CLI |
| [ComposioHQ/awesome-claude-skills](https://github.com/ComposioHQ/awesome-claude-skills) | media | Curated list, integración con Rube (1k+ apps) | ❌ ver lista |
| [OthmanAdi/planning-with-files](https://github.com/OthmanAdi/planning-with-files) | ~3k | Manus-style plans persistentes | ✅ **adoptar patrón** |
| [topoteretes/cognee](https://github.com/topoteretes/cognee) | alta | Memory engine para agents | ❌ overkill |
| [VoltAgent/awesome-agent-skills](https://github.com/VoltAgent/awesome-agent-skills) | alta | 1000+ skills filtrados | ❌ navegar |
| [DietrichGebert/ponytail](https://github.com/DietrichGebert/ponytail) | media | "Laziest senior dev" mindset | ⚠️ leer |
| [sickn33/agentic-awesome-skills](https://github.com/sickn33/agentic-awesome-skills) | alta | Local control plane para skills | ❌ overkill |
| [github/awesome-copilot](https://github.com/github/awesome-copilot) | oficial | Prompts, agents, skills community | ✅ **explorar** |
| [googleworkspace/cli](https://github.com/googleworkspace/cli) | oficial | Google Workspace CLI + AI skills | ❌ no aplica |
| [K-Dense-AI/scientific-agent-skills](https://github.com/K-Dense-AI/scientific-agent-skills) | alta | 158 skills científicas | ❌ no aplica |
| [phuryn/pm-skills](https://github.com/phuryn/pm-skills) | media | Product management skills | ❌ no aplica |

> **Regla**: si una skill de terceros no es 1:1 con un problema nuestro, NO la instalamos. Mejor skills caseras scoped que se cargan solo cuando aplica.

---

## 🗂 Cambios concretos en repo

```
.github/
├── copilot-instructions.md         # queda, ligero
├── agents/                          # NUEVO
│   ├── importnex-auditor.agent.md
│   ├── importnex-frontend.agent.md
│   ├── importnex-data-migration.agent.md
│   └── importnex-billing-expert.agent.md
└── skills/                          # simplificado
    ├── cashier-stripe-development/  # ya
    ├── inertia-vue-development/     # ya
    ├── laravel-best-practices/      # ya
    ├── tailwindcss-development/     # ya
    └── infer-conventions/           # ya
                                    # + skills casa (Sprint B)
.claude/skills/ → symlink a .github/skills/  # evita duplicar

.ai/
├── rules/
│   ├── index.md                     # TOC lazy-load
│   ├── db-backup.md
│   ├── ...
└── skills/                          # NUEVO, solo las 8 de casa
    ├── importnex-multitenancy/SKILL.md
    ├── importnex-cashier-billing/SKILL.md
    ├── importnex-i18n/SKILL.md
    ├── importnex-bridge-mistral/SKILL.md
    ├── importnex-forge-deploy/SKILL.md
    ├── importnex-ai-chat/SKILL.md
    ├── importnex-design-system/SKILL.md
    └── importnex-tests-phpunit/SKILL.md

tasks/
└── active/
    └── T-2026-08-XX-billing-fase-4.md  # plan persistente

docs/
├── IA_OPTIMIZACION_2026-08-06.md   # este doc
└── ia-metrics.md                    # NUEVO, semanal

.mcp.json                            # NUEVO, raíz (no workspace)
.vscode/mcp.json                     # mantener (dev-specific)
boost.json                           # excluir skills no usadas
```

---

## 🚫 Lo que NO haremos

- ❌ Instalar skills genéricas masivas (ruido > valor).
- ❌ Cognee / memoria externa (sin datos suficientes aún).
- ❌ Multi-agente cloud (Cara/Copilot Cloud) — Copilot Pro actual basta.
- ❌ Cambiar de Copilot a Cursor/Claude (sunk cost + curva).
- ❌ Stripe MCP antes de activar Stripe real.

---

## 📈 Métricas de éxito (4 semanas)

| Métrica | Baseline | Target |
|---|---|---|
| Tokens contexto fijo / sesión | ~6k | <1.5k |
| Acceptance rate Copilot | n/a | >75% |
| Tiempo a "Listo para programar" | n/a | <3s |
| Skills casa activadas / semana | 0 | >5 |
| Agentes custom usados / semana | 0 | >10 |
| Tareas largas completadas en 1 sesión | n/a | >80% |
| MCP servers activos | 1 | 3-4 |

---

## 🧭 Próximo paso

**Empezar Sprint A** (1 día, alto impacto). Sin dependencias, ahorra tokens desde día 1.

Después Sprint B (skills casa) en paralelo a features de producto.

Sprint E (planning persistente) es lo más caro pero más impacto a largo plazo en tareas multi-sesión.

---

## 📌 Decisión

Esto **no es Sprint de producto**, es infraestructura de IA. Recomiendo aprobar Sprint A + B primero (3 días) y medir acceptance rate antes de comprometerse con C, D, E.
