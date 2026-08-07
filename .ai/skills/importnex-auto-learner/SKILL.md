---
name: importnex-auto-learner
description: Memoria auto-actualizable. Detecta patrones, fallos, éxitos. Aprende de cada interacción. Actualiza findings.json automáticamente sin intervención humana. Detecta skills obsoletas y propone nuevas.
---

# 🎓 Auto-Learner — Memoria que aprende

> Esta skill **se invoca a sí misma**. Aprende de cada sprint, commit, fix, error.
> Genera sugerencias para el humano: "Encontré X patrón en 3 sprints, considera documentarlo".

---

## Cuándo se activa

- **Automático:** tras cada commit (hook post-commit).
- **Manual:** "aprende de este fallo", "qué patrones has visto", "actualiza memoria".
- **Cron:** semanalmente lunes 9am.

---

## 4 funciones principales

### 1. Detectar patrones recurrentes
```php
// scripts/memory-manager.php analyze
// Lee últimos 50 commits + findings.json
// Busca recurrencias:
//   - "fix(sitemap)" + "fix(pricing)" + "fix(routes)" → patrón: rutas mal wireadas
//   - "i18n missing" + "i18n missing" → patrón: clave sin contraparte
// Sugerencia: crear regla .ai/rules/{dominio}.md
```

### 2. Proponer nuevas skills
```php
// Si en 5 sprints distintos aparece patrón "multi-tenancy scoping"
// y NO existe skill específica, sugiere crearla.
```

### 3. Marcar skills obsoletas
```php
// Si una skill no se activó en 90 días, sugiere:
//   - Eliminarla (ahorra tokens en index)
//   - O re-archivarla en .ai/skills/archive/
```

### 4. Acceptance rate automático
```php
// Cada lunes:
//   - Cuenta outputs generados (commits, archivos)
//   - Cuenta edits humanos post-commit (git diff)
//   - Acceptance rate = outputs no editados / total
//   - Actualiza docs/ia-metrics.md
```

---

## Estructura de memoria

```
.ai/memory/
├── findings.json              # Hallazgos detectados (auto + manual)
├── patterns.json              # Patrones recurrentes detectados
├── skill-usage.json           # Frecuencia de uso de cada skill
├── acceptance-rate.json       # Métricas semanales
└── proposals.json             # Sugerencias para el humano (revisar lunes)
```

---

## Trigger words (auto-activación)

Si el usuario dice:
- "esto ya lo vimos antes" → buscar en findings.json
- "tengo un bug nuevo" → abrir debug-rca
- "qué sabes del proyecto" → cargar quickref + recent findings
- "aprende esto" → append a findings.json + actualizar reglas

---

## Output esperado tras auto-aprendizaje

```markdown
## Auto-Learner Report — Semana 2026-08-04

### Patrones detectados
- 🔴 (3x) Fix de rutas mal wireadas (sitemap, pricing, schema-org)
  → Sugerencia: crear regla `.ai/rules/routing.md`
- 🟠 (5x) i18n desincronizado (1250 claves en en.js sin es.js)
  → Sugerencia: implementar sync bidireccional automático
- 🟡 (2x) Backend sin tests para componente nuevo
  → Sugerencia: hook pre-commit más estricto

### Acceptance rate
- Outputs generados: 47
- Editados por humano: 9
- Acceptance rate: 80.8% ✅ (target >= 80%)

### Skills obsoletas (90 días sin uso)
- (ninguna)

### Propuestas
- [ ] Crear `.ai/rules/routing.md` (patrón recurrente)
- [ ] Implementar script `sync-i18n.cjs` bidireccional
- [ ] Endurecer hook pre-commit

### Findings nuevos esta semana
- 2 nuevos (PricingPublic bug + SitemapController query)
```

---

## Comando manual

```bash
# Análisis completo (tarda ~30s)
php scripts/memory-manager.php full-analysis

# Solo patrones recurrentes
php scripts/memory-manager.php patterns --since=30days

# Solo acceptance rate
php scripts/memory-manager.php acceptance-rate --week=2026-08-04

# Forzar regeneración de cache
php scripts/memory-manager.php regenerate-context
```

---

## Privacidad y límites

- **NO aprende** de:
  - Mensajes privados del usuario.
  - Datos personales en logs.
  - Secretos en `.env`.
- **SÍ aprende** de:
  - Mensajes de error y fixes.
  - Patrones en commits.
  - Acceptance rate objetivo.
  - Trampas conocidas.

---

## Skills amigas

- `importnex-auto-context` — carga contexto auto
- `importnex-self-audit` — auditoría al cierre
- `importnex-debug-rca` — análisis causa raíz
