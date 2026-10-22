# 07 · Valoraciones, API y mercado

**Estado:** 🟢 P0 resueltos

| ID | Prio | Hallazgo | Estado |
| --- | --- | --- | --- |
| P0-3 | P0 | Token de API global con elección libre de empresa | ✅ Token por empresa en Ajustes → API |
| P0-4 | P0 | Importación desde el panel siempre a la empresa de JJ | ✅ |
| 7.3 | P1 | Ejemplos `curl` en comentarios con el dominio de JJ | P2 (solo comentarios) |
| 7.4 | P2 | El volcado del ZIP al Escritorio de Windows (`ValuationPackageIngestor`) es una utilidad local de JJ | Aparcado: no actúa en el servidor |
| 7.5 | P2 | Comandos internos (`market:import`, `import:real-cars`…) piden `--org` por nombre | Aparcado |

**Uso de la API:** cabecera `X-Import-Token: <token de la empresa>`. El token
global `IMPORTNEX_CHAT_IMPORT_TOKEN` sigue funcionando para la organización
operadora (y `?org=` para elegir otra), solo para uso interno.
