> 🔗 **Fuente única (12-sep-2026).** Este fichero — y el resto de `docs/memoria-desktop/` — es el único canónico (vive en el repo Laravel: `C:\laragon\www\importnexcore\docs\memoria-desktop\`). Las copias en `Desktop\JJImportMotors\.claude\` y en el Contexto del proyecto de claude.ai son espejos que se actualizan a mano tras editar aquí. Edita SOLO aquí. Detalle: `docs/DOCS-INDEX.md`.

---

# Memoria de Claude — JJ Import Motors

> **Índice central de memoria persistente.** Claude DEBE leer este archivo al inicio de cada conversación para tener continuidad entre sesiones.
>
> ⚠️ **Memoria compacta (~11KB total).** Los docs detallados viven en `_contexto/` y solo se leen BAJO DEMANDA. Contexto auto-cargado: `CLAUDE.md` (raíz, 3KB) + `.claude/MEMORIA.md` (3KB) ≈ **6KB**.

---

## 📂 Estructura de la memoria

| Archivo | Cuándo consultar |
|---|---|
| **`MEMORIA.md`** (este) | SIEMPRE al inicio |
| `memoria/preferencias.md` | SIEMPRE (tono, formato) |
| `memoria/errores-pasados.md` | SIEMPRE antes de actuar |
| `memoria/decisiones.md` | Duda sobre "por qué" |
| `memoria/memoria-corto.md` | Sesión actual |
| `memoria/memoria-larga.md` | Cuando el patrón aplique |
| `memoria/proyectos-activos.md` | Al retomar trabajo |

**Docs bajo demanda (NO auto-cargar):** `_contexto/CONTEXTO_ACTIVO.md`, `CONTEXTO_FOTOS.md`, `Como_Funciona_JJ_Import_Motors.md`, `Flujo_Operativo_JJ_Import_Motors.md`. Leer solo si el trabajo lo requiere.

---

## 🚦 Protocolo de lectura/escritura

**Al INICIO:** 1) este MEMORIA.md · 2) `preferencias.md` · 3) `errores-pasados.md` · 4) `memoria-corto.md` (sesión previa no cerrada) · 5) `proyectos-activos.md`.

**Durante:** actualiza `memoria-corto.md`; aprendizajes → archivo correspondiente.

**Al FINALIZAR:** mueve corto → largo plazo · actualiza `proyectos-activos.md` · documenta aprendizajes.

---

## 🧠 Tres tipos de memoria

1. **Corto plazo (sesión):** estado temporal → `memoria/memoria-corto.md`.
2. **Medio plazo (2-3 semanas):** datos de mercado/precios → `informes/datos/<marca>/<modelo>/mercado_<fecha>.json` + `datos_mercado.json` (mapa, **ruta dual**: Desktop + `.claude/skills/datos_mercado.json` en el repo — la IA escribe en 1, Copilot espeja; NUNCA divergir).
3. **Largo plazo:** patrones/decisiones/errores → `memoria/*.md` + memoria del skill.

**Memoria del skill** (`importacion-vehiculos/memoria/`, 9 archivos): `modelos-medidos`, `encargos`, `filtros-portales`, `vendedores-confianza`, `trampas-encontradas`, `mejoras-aplicadas`, `retrospectiva`, `marketing-resultados`. Leer al inicio de encargos (PASO 0 cache).

---

## 🌍 Negocio (constantes)

- JJ Import Motors **NO compra stock**: solo servicio de búsqueda/importación/gestión con **honorarios fijos**. El cliente compra el coche.
- Ámbito: importación desde **Alemania** + búsqueda/gestión **dentro de España**.
- **Origen por defecto si no se especifica:** buscar en AMBOS mercados y comparar coste total puesto en Huelva. Empate (<300 €) → preferir ES (menos riesgo).

**Costes de importación DE (reales, fijados 2026-08):** **1.129 €** = transporte 900 + ausfuhr 114 + ITV import 115. **IEDMT aparte** (depende del CO₂ real de cada unidad; referencia deportivos ~1.800 € — no imputar sin dato). **IVA:** con NIF-IVA intracomunitario (empresa) se soporta en ES, no en la compra DE (ver `04-negocio/costes.md` del skill). Ejemplo tope de compra con presupuesto ~9.000 €: ES ≈ 8.550-8.850 € · DE ≈ 7.870 € (+1.129 € import).

---

## 🔄 Flujos y cascada de informes (v3.9.x)

**5 flujos:** A UNIDAD (URL concreta) · B MODELO · C MERCADO · **D DESCUBRIMIENTO** (nuevo 12-ago: cliente sin modelo → sondear modelos/motorizaciones que caben en presupuesto → embudar a B) · **M MARKETING multicanal** (nuevo 09-sep: 6 canales, ángulo/gancho por coche, resultados en `marketing-resultados.md`).

**Cascada con checkpoints (NO saltarse):** encargo B → INFORME MODELO + top 5 con enlaces → CP1 (elige el usuario) → Flujo A → INFORME UNIDAD (15 sec) → CP3 veredicto → 🟢/🔵 → DOSSIER CLIENTE + ZIP Laravel. Dossier/ficha/folleto PDF los genera **Laravel** tras subir el ZIP, no Claude.

**Rol del repo Laravel (desde 12-ago):** ImportnexCore es el repositorio único y fuente de verdad de los informes (PDFs, dossier, folleto, galería). La investigación se hace en Claude; el paquete (JSON + esqueletos + fotos) se sube a Laravel.

## 🚫 Anti-patrones críticos (A11-A14, completos en `06-reglas/anti_patrones.md`)

- **A11** — Coches.net ordena por relevancia ≠ precio: paginar TODO (`pg=` + `pf=`) o declarar cobertura parcial.
- **A12** — Página 1 orden-asc ≠ "el listado": sesgo a lo barato/viejo. Cubrir TODO el rango del presupuesto (bandas). En sondeo D no se pagina (2 lecturas suelo/techo bastan).
- **A13** — Cualquier cambio de filtros del encargo (ampliar O restringir) se declara ANTES de navegar.
- **A14** — No abandonar el camino en silencio: pregunta lateral = misión lateral con retorno ↩⃾ al waypoint 📍.

---

## 🎯 Prompt Improver + Briefing (12-ago)

Prompt vago (<50 chars o <3 parámetros) → mejorar antes de navegar: detectar faltantes, preguntar SOLO 1-3 críticos, permitir "busca tú", mostrar prompt mejorado + OK. Plantilla: [MARCA][MODELO][VERSIÓN] / [años] / [km máx] / [presupuesto puesto en provincia] / [transmisión] / [finalidad]. En tope de gama (OPC, M, AMG, RS, GTI…) confirmar versión ANTES de filtrar (anuncios mal etiquetados).

---

## 🔧 Sistema (12-sep-2026)

- **Skills:** `importacion-vehiculos` **v3.9.3** · `estudio-mercado` **v0.4.0**. Tras editar una skill SIEMPRE: `.\scripts\build-skill-zips.ps1` (repo) + reimportar ZIP en Desktop y Cowork. Regla dura: `.ai/rules/skills-sync.md`.
- **Docs:** másters de negocio/memoria en `docs/claude-desktop/` y `docs/memoria-desktop/` (repo) → réplicas en Desktop y claude.ai. Regla de qué doc tocar tras cada cambio: `.ai/rules/doc-sync.md`. Índice: `docs/DOCS-INDEX.md`.

---

## 🗓️ Última actualización

- **Fecha:** 2026-09-12 (revisión vencida del 12-ago ejecutada)
- **Cambios:** +Flujo D y M · anti-patrones A11-A14 · costes reales importación 1.129 € + IEDMT + IVA intracomunitario · rol del repo Laravel · skills v3.9.3/v0.4.0 · rutas duales y reglas sync
- **Próxima revisión:** al cerrar el próximo sprint (regla `.ai/rules/doc-sync.md`)
