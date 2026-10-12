# CHANGELOG — ecommerce-tuning

## 0.1.0 — 2026-09-16
- Versión inicial. Modelo tramitador puro (sin almacén ni stock).
- 4 flujos: S SEÑALES, P PRODUCTO, F FICHA, M MARKETING.
- Filtro de viabilidad completo (Score ≥7,5 · margen ≥40% · ratio ≥3×).
- Reglas duras E1-E12 (aprobación humana, costes completos, legalidad, memoria).
- Decisión de navegador: interno por defecto, Claude para Chrome con login/JS pesado/anti-bot.
- Guía maestra del negocio: docs/ecommerce.md v2 (§14 agente IA, §15 skill).
