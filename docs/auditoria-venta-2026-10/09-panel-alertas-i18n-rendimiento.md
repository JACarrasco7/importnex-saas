# 09 · Panel, alertas, KPIs, idioma y rendimiento

**Estado:** 🟡

| ID | Prio | Hallazgo | Evidencia | Estado |
| --- | --- | --- | --- | --- |
| 9.1 | P1 | «Acceso rápido» del panel con los subtítulos desplazados (Kanban → «Mapa», Mapa → «Finanzas», Alertas → «Plan»…) | captura del panel, `Dashboard.vue` | ✅ |
| 9.2 | P1 | Textos técnicos para el usuario en el panel: «Suma de las visitas a cada coche en /marketplace/{id} y /c/{token}» | captura del panel | ✅ |
| 9.3 | P1 | Páginas y avisos en inglés: planes, ajustes de empresa y 16 mensajes de confirmación («Car created successfully», «Documents uploaded successfully»…) | `grep` | ✅ En español. Quedan textos sueltos en pantallas antiguas: revisar al traducir a inglés (P2) |
| 9.4 | P1 | Avisos al equipo: el webhook de Slack/Discord firmaba como «JJ Import Motors Alerts» | `AlertWebhookDispatcher` | ✅ nombre del producto |
| 9.5 | — | N+1: pantallas clave (panel, inventario, ficha, kanban, mapa, clientes, encargos, portal, marketing, finanzas, KPIs, alertas, marketplace) probadas con `preventLazyLoading`: **ninguna carga perezosa** | suite con la protección activada | ✅ sin cambios |
| 9.6 | P2 | IA «trae tu clave»: cada empresa pone su clave en Ajustes. Los planes anuncian «créditos de IA» que no existen | `AiService`, `config/subscription.php` | ⏳ D5 |
| 9.7 | P2 | Push (OneSignal) y webhooks también con claves de cada empresa: correcto, pero sin guía de configuración para el cliente | — | Guía (S) |
| 9.8 | P2 | `marketplace_visits` es un contador global (uno para toda la plataforma) | esquema | Por empresa si se hace D1 |
