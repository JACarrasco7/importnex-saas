---
description: Plan de eficiencia de tokens 2026 — estrategias probadas y roadmap continuo.
last_updated: 2026-08-07
monthly_savings_goal: 500k tokens
---

# ⚡ Plan de Eficiencia de Tokens 2026

> Basado en tendencias 2026: Agent Skills open standard, MCP progressive disclosure, Copilot Spaces.

---

## Estrategia actual (ya implementada)

| Estrategia | Técnica | Ahorro estimado |
|---|---|---|
| Progressive Disclosure | Skills cargan solo nombre+desc (Fase 1) | -60% vs cargar body |
| Context Optimizer | 3 niveles (mínimo/estándar/completo) | -67% vs cargar todo |
| Cache entre turnos | `.ai/cache/project-context.md` | -40% en recargas |
| Reglas por glob | 9 reglas scoped, solo se carga la relevante | -60% vs todas |
| Quickref 1-página | 500 tokens vs 4500 baseline | -89% primer turno |
| Minificación | SKILL.min.md para skills >100KB | -50% por skill |
| MCP servers on-demand | Solo se activan cuando path las necesita | Variable |

## Estrategia avanzada (implementada ahora)

| Estrategia | Técnica | Ahorro adicional |
|---|---|---|
| Discovery Cache | `skills-discovery.md` con solo nombre+desc | -70% en discovery |
| Knowledge Graph | MCP Memory server para consultas relacionales | -80% en búsquedas |
| Serena MCP | Semantic code retrieval en lugar de grep manual | -50% en búsquedas |
| Token Guard | Alerta cuando >10 archivos/turno | -30% en picos |
| Sequential Thinking | Razonamiento estructurado con MCP | Mejor calidad, mismo coste |

## Objetivos mensuales

| Mes | Tokens gastados (est.) | Ahorro vs mes anterior |
|---|---|---|
| Julio 2026 | ~2M tokens (baseline, sin sistema IA) | — |
| Agosto 2026 (Sprint A-G) | ~800k tokens | -60% |
| Agosto 2026 (Sprint H-J) | ~500k tokens | -37.5% vs Sprint G |
| Agosto 2026 (Sprint K) | ~300k tokens | -40% vs Sprint J |
| **Target Sept 2026** | **~200k tokens/mes** | **-90% vs baseline** |

## Roadmap eficiencia

- [x] Quickref 1-página (Sprint G)
- [x] Reglas por glob (Sprint G)
- [x] Context 3 niveles (Sprint J)
- [x] Progressive disclosure discovery (Sprint K)
- [x] MCP Memory + Sequential Thinking (Sprint K)
- [x] Token Guard auto-monitor (Sprint K)
- [x] Serena semantic search (Sprint K)
- [ ] Skills con scripts ejecutables (Sprint L)
- [ ] Auto-compress en pre-commit hook
- [ ] Dashboard en tiempo real de consumo
- [ ] Predicción de coste por sprint
