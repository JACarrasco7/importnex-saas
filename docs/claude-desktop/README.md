> 🔗 **Copia de distribución (12-sep-2026).** El máster de este fichero vive en el repo: `docs/claude-desktop/README.md` en `C:\laragon\www\importnexcore\`. Edita ahí, no aquí — esta copia (carpeta Desktop) se actualiza a mano tras cada cambio. Detalle completo del porqué: `docs/DOCS-INDEX.md`.
>
> ⚠️ Fechado 12-ago-2026 — menciona skill v2.4.1/v2.9.5 y solo 3 flujos; la skill real ya va por v3.9.2 con 4 flujos (A/B/C/D) y marketing multicanal. Pendiente de actualizar, no de eliminar.

---

# JJ Import Motors — Workspace

> **Espacio de trabajo de Claude para JJ Import Motors.** Punto de entrada único: todo arranca desde aquí.

---

## 📁 Estructura

```
JJImportMotors/
├── 📄 CLAUDE.md                      ← CONTEXTO AUTO-CARGADO (3KB)
├── 📄 GUIA_INICIO_RAPIDO.md          ← Cómo usar Claude (3 pasos)
├── 📄 README.md                      ← Este archivo (índice)
├── 📦 importacion-vehiculos.skill.zip ← ⚡ IMPORTAR ESTE en Claude Desktop (293KB, 42 entries · v2.9.5)
│
├── 🧠 .claude/
│   ├── MEMORIA.md                    ← Índice de memoria (3KB, auto-cargado)
│   └── memoria/                      ← Corto/medio/largo plazo + preferencias
│       ├── preferencias.md
│       ├── errores-pasados.md
│       ├── decisiones.md
│       ├── proyectos-activos.md
│       ├── memoria-corto.md
│       └── memoria-larga.md
│
├── 📚 _contexto/                     ← Docs detalladas (BAJO DEMANDA, NO auto-cargar)
│   ├── CONTEXTO_ACTIVO.md           ← Marca, fuentes, umbrales
│   ├── CONTEXTO_FOTOS.md            ← Referencia visual de coches
│   ├── Como_Funciona_JJ_Import_Motors.md
│   └── Flujo_Operativo_JJ_Import_Motors.md
│
├── 🗄️ _archive/                     ← Lecciones históricas (NO auto-cargar)
│   └── Lecciones_*.md (6 archivos)
│
└── 🐍 laravel/                       ← Scripts Python + informes legacy (NO auto-cargar)
    ├── *.py                          ← Scripts de caché, comparativa, empaquetar
    ├── informes/                     ← JSONs + fotos de coches
    ├── paquetes/                     ← ZIPs para Laravel
    └── ...
```

---

## 🚦 Cómo se carga el contexto

| Auto-cargado (Claude lee siempre) | Carga manual (bajo demanda) |
|---|---|
| `CLAUDE.md` (3 KB) | `.claude/MEMORIA.md` (3 KB) — al inicio |
| `.claude/MEMORIA.md` (3 KB) — al inicio | `_contexto/*.md` — solo si el trabajo lo pide |
| | `_archive/*.md` — solo si hay duda histórica |
| | `laravel/*.py` — solo si el usuario lo pide |

**Total auto-cargado:** ~6 KB → sin "Prompt length error".

---

## 🎯 Qué hace el skill

El archivo `importacion-vehiculos.skill` se **importa en Claude Desktop** y contiene:

- **3 flujos** (A: UNIDAD, B: MODELO, C: MERCADO)
- **7 fuentes** (mobile.de, AutoScout24.de, AutoUncle, kleinanzeigen.de, Coches.net, Wallapop, Milanuncios)
- **Briefing de Encargo** (preguntar críticos antes de navegar)
- **Doble pasada** (tope de gama filtrado por kW)
- **Prompt Improver** (refinar prompts vagos)
- **6 reglas duras** (countryCode, navegación real, etc.)
- **Memoria propia** (`modelos-medidos`, `vendedores-confianza`, `trampas-encontradas`)

**Para actualizar el skill:**
1. Edita `.claude\skills\importacion-vehiculos\` (en importnexcore)
2. Regenera ZIP: `Compress-Archive` → `.zip` → rename a `importacion-vehiculos.skill.zip`
3. Copia a `Desktop\JJImportMotors\importacion-vehiculos.skill.zip`
4. Reimporta en Claude Desktop (el `.skill.zip`)

**Lección de empaquetado (PowerShell 5.1):** `Compress-Archive` solo acepta `.zip` → `Move-Item .zip .skill`. Detalle en `importnexcore\.claude\MEMORIA.md` sesión.

---

## 📞 Contacto del negocio

- **Empresa:** JJ Import Motors
- **Ubicación:** Huelva capital
- **Teléfonos:** 675 70 14 39 · 691 48 59 27
- **Email:** jjimportmotors@gmail.com
- **Lema:** Confianza · Rapidez · Exclusividad
- **Modelo:** Servicio de búsqueda e importación de coches (desde Alemania y dentro de España). NO compramos stock — solo honorarios por el servicio; el cliente compra el coche.

---

## 🔗 Proyectos relacionados

| Proyecto | Ruta | Función |
|---|---|---|
| **Claude Desktop** | `Desktop\JJImportMotors\` | Investigación + generación de textos/JSON |
| **ImportnexCore** | `laragon\www\importnexcore\` | Backend Laravel + persistencia + PDFs |
| **jj-panel** | (otro repo) | UI diaria (kanban, consultar) |

**Google Drive NO es la fuente única de verdad** — esa arquitectura es legacy. Ahora:
- **Claude** = investigación + textos
- **ImportnexCore** = persistencia + PDFs
- **jj-panel** = uso diario

---

*Última actualización: 2026-08-12 — Skill v2.4.1 con Prompt Improver + Briefing de Encargo.*
