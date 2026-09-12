# Índice de documentación — `docs/`

> **Punto único de entrada** a toda la documentación de ImportnexCore. Si
> no sabes dónde buscar algo del proyecto, **empieza aquí**.
>
> Estados: **Activo** (se consulta y actualiza) · **Histórico** (archivo,
> no actualizar) · **Referencia** (snapshot congelado).

---

## Raíz de `docs/`

| Doc | Qué | Estado |
|---|---|---|
| [`COMUNICACION-CLAUDE-VSCODE.md`](COMUNICACION-CLAUDE-VSCODE.md) | **Guía operativa** de comunicación Claude Desktop ↔ VS Code: rutina diaria, quién toca qué, divergencia | Activo |
| [`HANDOFF.md`](HANDOFF.md) | **Cuaderno de turno entre agentes** — leer al empezar sesión, escribir al terminar | Activo |
| [`ARQUITECTURA_VISTAS.md`](ARQUITECTURA_VISTAS.md) | Arquitectura frontend (Inertia/Vue, páginas, layouts) | Activo |
| [`BRAND.md`](BRAND.md) | Manual de marca JJ Import Motors (estoril/asphalt/platinum) | Activo |
| [`SKILLS.md`](SKILLS.md) | Índice canónico de los ZIPs de skill, build, SHA, deployments — incluye el problema de las 3 copias (repo/Desktop/Cowork) | Activo |
| [`REGLAS-IA.md`](REGLAS-IA.md) | Reglas durables para coding agents + git hooks pre-commit (`php scripts/install-hooks.php`) | Activo |
| [`PLAN_MARKETPLACE.md`](PLAN_MARKETPLACE.md) | Roadmap del marketplace B2B | Activo |
| [`PLAN_MARKETING_ZIP_2026-09-03.md`](PLAN_MARKETING_ZIP_2026-09-03.md) | Plan original del pipeline ZIP de skill (lo que disparó todo) | Histórico |
| [`PLAN_MARKETING_MULTICANAL_2026-09-06.md`](PLAN_MARKETING_MULTICANAL_2026-09-06.md) | Plan del Flujo M (marketing multicanal 6 canales) | Activo |
| [`AUDITORIA_estudio-mercado_2026-09-12.md`](AUDITORIA_estudio-mercado_2026-09-12.md) | Auditoría de la skill estudio-mercado (v0.4.0) | Activo |
| [`PENDIENTES_v3.9.2_2026-09-12.md`](PENDIENTES_v3.9.2_2026-09-12.md) | Pendientes abiertos de la 3.9.2 | Activo |

Regla dura de sincronización de skills: [`../.ai/rules/skills-sync.md`](../.ai/rules/skills-sync.md)
(qué comando correr tras editar una skill, qué NO hacer).

## 📐 Planes — [`planes/`](planes/README.md)

Índice en [`planes/README.md`](planes/README.md). Activo:
`STRIPE_LOOKUP_KEYS.md` (mapeo planes ↔ Stripe). Los planes de julio-2026
(implementación completa, valoración enriquecida, marketing IA, launch,
deploy Docker, PROGRESO) están archivados en [`planes/archivo/`](planes/archivo/).

## 🚀 Deploy — [`deploy/`](deploy/README.md)

| Doc | Qué |
|---|---|
| [`deploy/README.md`](deploy/README.md) | Forge, cómo desplegar, túnel MySQL, scripts de subida |
| [`deploy/HEIDISQL_FORGE.md`](deploy/HEIDISQL_FORGE.md) | Conexión HeidiSQL vía SSH tunnel (3307) |
| [`deploy/DEPLOY_AI_MULTIPROVIDER.md`](deploy/DEPLOY_AI_MULTIPROVIDER.md) | Deploy de IA multi-proveedor + chat flotante (31-jul-2026) |

## 📄 Informes (outputs de flujos A/B/C/D) — [`informes/`](informes/)

| Ruta | Qué |
|---|---|
| [`informes/README.md`](informes/README.md) | Convenciones de nombres y rutas de informes |
| [`informes/datos/`](informes/datos/) | Datos crudos de encargos: `astra_j_opc.json`, `encargo_maria.txt`, `encargo_tiguan.txt` (encargos originales de clientes) |
| [`informes/mercado/`](informes/mercado/) | Informes de mercado 2026-08-17 (v1 + v2) y modelos medidos |
| `informes/vw/tiguan/` | Informe de unidad Tiguan 2026-08-23 (md + pdf) |
| [`informes/busqueda-bmw-320d-alemania-2026-09-09.md`](informes/busqueda-bmw-320d-alemania-2026-09-09.md) | Listado crudo de 11 candidatos mobile.de BMW 320d |
| [`informes/JJ_Import_Motors_AstraJ_OPC_09ago2026.html`](informes/JJ_Import_Motors_AstraJ_OPC_09ago2026.html) | Entregable Astra J OPC |

## 📦 Paquete valoración — [`paquete-valoracion/`](paquete-valoracion/)

Contrato de export (`CONTRATO_EXPORT.md`), formato del esqueleto
(`FORMATO_ESQUELETO.md`), ejemplo Astra J OPC y ZIP subido a Laravel.

## 🧠 Negocio JJ Import Motors — [`contexto-jj-import-motors/`](contexto-jj-import-motors/)

Cómo funciona la empresa, flujo operativo, contexto de fotos. **Toda IA
nueva debe leer estos docs antes de tocar nada del negocio.**

## 🔗 Fuente única entre sistemas (12-sep-2026)

El mismo contenido de negocio existía en **tres sitios que no se sincronizaban
solos**: la carpeta `Desktop\JJImportMotors\` (donde trabaja el proyecto
"Claude Desktop"), el propio **Contexto del proyecto de claude.ai** ("JJ
Import Motors", lo que ese proyecto realmente lee) y este repo. Al auditarlo
se encontró que no eran copias sincronizadas sino **divergentes de forma
distinta cada una**:

- El Contexto del proyecto de claude.ai tenía **duplicados** `Como_Funciona_JJ_Import_Motors.md`
  y `Flujo_Operativo_JJ_Import_Motors.md` — las instrucciones del propio
  proyecto pedían quitarlos desde el 12-ago-2026 ("ya consolidado en
  CLAUDE.md") y nunca se hizo. Eliminados el 12-sep-2026.
- Ese mismo proyecto de claude.ai **nunca tuvo cargado `.claude/MEMORIA.md`**
  ni el resto de `memoria/*.md`, pese a que sus propias instrucciones dicen
  "Lee .claude/MEMORIA.md al inicio". Añadidos el 12-sep-2026.
- `memoria-desktop/MEMORIA.md` llevaba una nota interna "Próxima revisión:
  2026-09-12" — vencida y **ejecutada el mismo 12-sep-2026**: el máster ya
  incorpora Flujo D, Flujo M, anti-patrones A11-A14, costes reales de
  importación (1.129 € + IEDMT + IVA intracomunitario) y versiones de skills.

**Regla desde hoy:** los másters son [`claude-desktop/`](claude-desktop/) y
[`memoria-desktop/`](memoria-desktop/) en este repo. `Desktop\JJImportMotors\`
y el Contexto del proyecto de claude.ai son **copias de distribución** — se
actualizan a mano después de editar el máster, nunca al revés. Ídem skills:
[`.ai/rules/skills-sync.md`](../.ai/rules/skills-sync.md).

| Carpeta | Qué | Estado |
|---|---|---|
| [`claude-desktop/`](claude-desktop/) | **Máster (12-sep-2026)** de los 4 ficheros de entrada del proyecto Claude Desktop: `CLAUDE.md`, `README.md`, `GUIA_INICIO_RAPIDO.md`, `INSTRUCCIONES_PROYECTO.md` | Activo |
| [`memoria-desktop/`](memoria-desktop/) | **Máster (12-sep-2026)** de la memoria transversal (MEMORIA.md + decisiones/errores/corto/larga/preferencias/proyectos), revisada 12-sep-2026. La memoria operativa de cada skill vive en `.claude/skills/<skill>/memoria/` | Activo |

## 📚 Guías de flujos — [`guias/`](guias/README.md)

01-primeros-pasos → 08-solucion-problemas: Flujos A (unidad), B (modelo),
C (mercado), D (descubrimiento), informes, cierre de venta, troubleshooting.
Con [`guias/ejemplos/`](guias/ejemplos/) reales.

## 🤖 Puente con Claude — [`claude/`](claude/README.md)

`CONTRATO_JSON.md`: contrato de formato del JSON que Claude entrega a Laravel.

## 📣 Marketing — [`marketing/`](marketing/)

Mockups de ficha cliente v2 + v3 (HTML).

## 🧪 Aprendizaje — [`aprendizaje/`](aprendizaje/00-INDICE.md)

13 lecciones numeradas (SaaS, SEO, PWA, i18n, performance…) con índice propio.

## 🔧 Tools — [`tools/`](tools/)

Scripts de extracción/parsing: `au_parse.py`, `extractores.js`,
`informe_importacion.py` + notas de extractores.

## 🔄 Sync de skills — [`_sync/`](_sync/LEEME.md)

ZIPs de skills actualizados que Claude deja aquí (no puede escribir bajo
`.claude/`) + instrucciones de aplicación (`.bat`).

---

## Reglas de la casa

- **Fuente de verdad:** los `*.md` de este `docs/` son canónicos y versionados.
- Los docs traídos de Desktop son **snapshot**; no se regeneran solos.
- Nada de archivos personales o autogenerados aquí dentro sin decisión consciente.
- Plan terminado o sustituido → `planes/archivo/`, nunca borrar.
- Edits de negocio (CLAUDE.md/MEMORIA.md) → siempre al máster del repo
  (`claude-desktop/`, `memoria-desktop/`), luego se replica a las copias.
