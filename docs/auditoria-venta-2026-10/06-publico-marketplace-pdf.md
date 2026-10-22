# 06 · Web pública, marketplace, dossier y PDF

**Estado:** 🟡

| Página | De quién es | Estado |
| --- | --- | --- |
| `/` y `/pricing` | Del producto (SaaS) | ✅ Nombre del producto; precios reales del plan |
| `/request/{slug}` | De cada empresa | ✅ Marca de la empresa |
| `/marketplace` | De la organización operadora | ✅ Contacto de la operadora |
| `/marketplace/{car}`, `/c/{token}` (dossier), `/tracking/…` | De la empresa del coche | ✅ |
| `/legal` | Operadora; `?org=slug` para cada empresa | ✅ |

| ID | Prio | Hallazgo | Estado |
| --- | --- | --- | --- |
| 6.1 | P1 | El marketplace público es uno solo (el de la operadora). Las demás empresas solo tienen formulario de solicitud y dossier | Decisión de producto: marketplace por empresa = P2 |
| 6.2 | P1 | Folleto PDF estático de JJ | Ver 03 · 3.1 |
| 6.3 | P2 | Datos estructurados schema.org solo de la operadora | ✅ ya salen de su perfil |
