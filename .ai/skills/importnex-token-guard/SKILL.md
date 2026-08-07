---
name: importnex-token-guard
description: Guardián de tokens. Monitorea consumo por sesión, sugiere compresión, detecta patrones de gasto ineficiente. Auto-activado al inicio de sesiones largas.
---

# 💰 Token Guard — Eficiencia económica

> Monitorea gasto de tokens y sugiere optimizaciones.

---

## Cuándo se activa

- Auto: cada 5 turnos (si sesión larga)
- Auto: cuando la IA carga >10 archivos en un turno
- "cuántos tokens llevo gastados"

---

## Métricas en tiempo real

```bash
# Ver consumo actual de la sesión
php scripts/memory-manager.php token-usage

# Estimar ahorro vs baseline
php scripts/memory-manager.php token-savings
```

## Patrones detectables que disparan alerta

| Patrón | Alerta |
|---|---|
| Cargar >10 archivos un turno | 🔴 "¿Realmente necesitas los 10?" |
| Misma skill cargada 3 turnos seguidos | 🟡 "Cache activa, no recargues" |
| Skill > 500 líneas sin comprimir | 🟠 "Comprime con .min.md" |
| Contexto sin cambios 5 turnos | 🟢 "OK, reutilizando cache" |

## Umbrales de gasto

| Plan | Tokens/mes | Coste estimado |
|---|---|---|
| Copilot Free | 2k completions | $0 |
| Copilot Pro | ~50k tokens/turno | $10/mes |
| Copilot Pro+ | ~200k tokens/turno | $39/mes |

**ImportnexCore objetivo:** < 2k tokens/turno (nivel 1 del context-optimizer)

---

## Estrategias de ahorro automáticas

1. **Progressive disclosure** (Agent Skills standard): solo cargar nombre+descripción en discovery, cargar body en activation.
2. **Cache entre turnos**: `.ai/cache/` snapshot de contexto.
3. **Minificación**: skills >100KB → auto `.min.md`.
4. **Priorización**: P0 (quickref) > P1 (reglas dominio) > P2 (memoria).
5. **Lazy loading**: MCP servers solo cuando el path los necesita.

---

## Comando "token-audit"

```bash
php scripts/memory-manager.php token-audit
```

**Output:**
```
Token Audit — Sesión actual

Quickref:           500 tokens ✅ (cacheado)
Reglas dominio:     800 tokens ✅ (3 archivos)
Memoria reciente:   400 tokens ✅
Skills activas:     200 tokens ✅ (discovery only)
Contexto total:    1900 tokens ✅ (objetivo < 2000)
Ahorro vs baseline: 58% (4500 → 1900)
```
