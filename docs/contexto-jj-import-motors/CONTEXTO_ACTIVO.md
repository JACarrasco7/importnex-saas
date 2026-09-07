# JJ Import Motors — Contexto activo para Claude

> ⚠️ **12-ago-2026: este archivo ya NO se carga automáticamente.** Se movió a `_contexto/` para ahorrar tokens. **El archivo de contexto principal ahora es `CLAUDE.md` (en la raíz), que es compacto.** Consulta este archivo SOLO si necesitas detalle sobre: marca, tono, fuentes, outputs, umbrales, convenciones.
>
> **Resumen en 30s (todo en CLAUDE.md):** Importación DE→ES sin stock · honorarios fijos · 4 flujos (A/B/C/D) · 7 fuentes · navegación real · 14 anti-patrones · PDFs: Claude genera investigación, Laravel marketing.

---

## 🏢 Quién es JJ Import Motors

---

## 🏢 Quién es JJ Import Motors

Empresa de **importación de vehículos desde Alemania a España**, basada en **Huelva**. Modelo de negocio: **sin stock** (no compran hasta tener cliente) cobrando **honorarios fijos transparentes** por la gestión integral del proceso.

**Lema:** Confianza · Rapidez · Exclusividad

**Contacto:**
- Teléfonos: 675 70 14 39 · 691 48 59 27
- Email: jjimportmotors@gmail.com
- Ubicación: Huelva capital

**Diferenciadores:**
- Compra directa en Alemania (sin intermediarios)
- Negociación con concesionarios de confianza
- Honorarios fijos declarados (no comisiones ocultas sobre precio)
- Acompañamiento de principio a fin (compra → transporte → ITV → entrega)

---

## 🎯 Qué hace este proyecto

### Cuatro flujos de trabajo (todos en `importacion-vehiculos` skill)

1. **A: UNIDAD** — Evaluar un coche concreto (URL pegada). Genera PDFs de investigación (búsqueda + unidad) + ZIP para Laravel.
2. **B: MODELO** — Buscar un modelo concreto (ej: "busca Golf GTI"). Genera informe MODELO + top 5 candidatos con enlaces de ficha.
3. **C: MERCADO** — Escanear mercado (ej: "qué merece la pena"). Genera informe BUSQUEDA con tabla multi-modelo.
4. **D: DESCUBRIMIENTO** — Cliente sin modelo claro (presupuesto+requisitos). D1 sondeo → D2 informe de modelos → D3 embudo a Flujo B. Nunca saltar fases.

> **Control de sesión (El Camino):** waypoint 📍 por mensaje · micro-plan aprobado antes de cada búsqueda · cuaderno en `informes\_sesion\` · auditoría de fase · desviaciones declaradas con retorno (A14). **Honorarios M1/M2/M3** (incluidos / aparte / no se cobran) — preguntar siempre; tarifa ES reducida ~500 € si la unidad está en España.

### Arquitectura (3 piezas)

```
Claude (skill) ──> investiga con navegador + genera textos/esqueletos
       │
       └─> ZIP empaquetado ──> Laravel (importnexcore) ──> Laravel (jj-panel-operaciones)
                                       │
                                       └─> Persiste en storage local (cars/{id}/contenido/*.txt)
                                              └─> Genera PDFs profesionales con marca estoril
```

**Google Drive NO es la fuente única de verdad** (esa arquitectura es legacy). Ahora:
- **Claude** = investigación + generación
- **Laravel (importnexcore)** = persistencia + generación de PDFs
- **Laravel (jj-panel)** = uso diario (consultar + mover kanban)

---

## 🎨 Marca y tono

### Voz
- **Cercano pero profesional**
- **Transparente radicalmente** (honorarios explícitos, sin comisiones ocultas)
- **Sin tecnicismos con el cliente** ("IEDMT" → "impuesto de matriculación")
- **Honesto con problemas** del coche (no esconder averías conocidas)
- **Español de España**, directo, sin anglicismos innecesarios

### Marca visual (paleta estoril)
- **Primario:** Estoril #1A306D (azul oscuro corporativo)
- **Secundario:** Asphalt (grises oscuros para texto)
- **Acentos:** Platinum (fondos suaves)
- **Semáforo:** 🟢 #10B981 · 🟡 #F59E0B · 🔴 #EF4444

### Lo que NUNCA hace Claude (reglas duras)
- ❌ Inventar datos del coche (precio, km, año, equipamiento)
- ❌ Mostrar margen, honorarios desglosados o URLs de comparables al cliente
- ❌ Prometer plazos exactos ("15 días" → "4-6 semanas con % cumplimiento")
- ❌ Garantizar mecánicamente el coche (no está en nuestra mano)
- ❌ Decir "vendo" — decir "te lo importamos" (lenguaje de servicio)
- ❌ Cambiar BD de producción sin `git commit` previo + confirmación

### Lo que SÍ hace Claude
- ✅ Citar SIEMPRE la fuente de cada dato (`https://...`)
- ✅ Reconocer cuando no sabe algo ("no verificado", "pendiente inspección")
- ✅ Mostrar honorarios como línea EXPLÍCITA (transparencia = confianza)
- ✅ Usar el semáforo visual (🟢🟡🔴) consistentemente
- ✅ Validar 7 fuentes antes de veredicto (no parar al primer candidato)

---

## 🌍 Fuentes de datos (7 portales)

| Fuente | País | Uso | Notas |
|---|---|---|---|
| mobile.de | DE | Principal para Alemania | Filtros en comboboxes del header |
| AutoScout24.de | DE | Cross-check DE | Días publicado visible |
| AutoUncle | DE | Días en venta + cambios de precio | Rotación |
| kleinanzeigen.de | DE | Particulares + VB (negociable) | Precios anterior/actual visibles |
| Coches.net | ES | Principal para España | `priceRankIndicator` visible (🟢🟡🔴) |
| Wallapop | ES | Particulares ES | Infinite scroll |
| Milanuncios | ES | Contado vs financiado | Precio contado IVA incl. |
| km77.com | ES | PVP + CO₂ del modelo | FALLBACK a BOE si 503/504 |

**Regla:** Cobertura mínima antes de veredicto = 3 fuentes Fase 1 (Coches.net, mobile.de, AutoUncle). Las 4 restantes en Fase 2.

---

## 📦 Outputs (lo que se genera)

### PDFs — quién genera cada uno (15-ago-2026)
- **Claude genera los PDFs de INVESTIGACIÓN:** `informe_busqueda_*.pdf` (Fase 1) y `informe_unidad_*.pdf` (Fase 2) — HTML de marca → Chrome headless (plantilla `plantilla_pdf_marca.html`).
- **Laravel genera los PDFs de MARKETING/VENTA** desde los `.txt` del ZIP: dossier, ficha-publicitaria, folleto. Claude NO los crea.
- **Para el equipo:** `informe-interno` (owner/operator) · informe modelo · informe búsqueda · redes sociales.

---

## 🔴 Estados CRM (Pipeline)

```
New → Briefing → Quote sent → Negotiating → Order signed → In process → Delivered
```

> ⚠️ **"Briefing" aquí es estado del pipeline (contacto con cliente nuevo), NO un PDF.** No confundir con el briefing PDF deprecado (sustituido por dossier en 12-ago-2026).

---

## 🎯 Umbrales de margen objetivo

| Segmento | Margen mínimo | Margen objetivo |
|---|---|---|
| Nicho (alto margen, baja oferta) | 8% | ≥10% |
| Rotación (margen medio, alta demanda) | 10% | ≥10% |
| Tramo 8-14k € | 12% | ≥12% |

**EXIT automático** (no ofertar):
- Hueco <8% O <3 comparables ES
- Margen < umbral mínimo
- Dificultad de homologación

---

## 💻 Convenciones técnicas

### Backend
- Laravel 11.55 + PHP 8.5
- Multi-tenant vía `organization_id` en todas las tablas
- Tests PHPUnit (no Pest) — `php artisan make:test --phpunit`
- Formateo con Pint: `vendor/bin/pint --dirty --format agent`

### Frontend
- Inertia 2 + Vue 3 + Vite + Tailwind 3.4
- Marca estoril (paleta corporativa)
- i18n: `es` (default) + `en`
- NO añadir shadcn-vue ni otro design system

### Reglas duras del proyecto (de `AGENTS.md` + `CLAUDE.md`)
- 🔴 Backup antes de cualquier INSERT/UPDATE/DELETE en producción
- 🔴 NUNCA `npm run build` ni `npx vite build` (el usuario lo lanza)
- 🔴 NUNCA deploy/scp sin `git commit` previo
- 🔴 NUNCA añadir dependencias (`composer require`/`npm install`) sin preguntar

---

## 📚 Documentos relacionados

- `Como_Funciona_JJ_Import_Motors.md` — guía sin tecnicismos (para el usuario)
- `Flujo_Operativo_JJ_Import_Motors.md` — referencia técnica detallada
- `_archive/Lecciones_*.md` — decisiones ya ejecutadas (referencia histórica)
- `CONTEXTO_FOTOS.md` — galería de referencia de coches reales fotografiados
- `importacion-vehiculos.skill/` — skill principal de Claude (16 archivos)
- `laravel/` — scripts Python legacy (Desktop) para backup/ejecución local

---

## 🧠 Sistema de memoria persistente (12-ago-2026)

Claude ahora **recuerda entre conversaciones**. Lee siempre:

### Memoria del proyecto (en `.claude/memoria/`)
- **`MEMORIA.md`** — índice de toda la memoria (léeme primero)
- `preferencias.md` — cómo trabaja el usuario (tono, formato, reglas duras)
- `errores-pasados.md` — errores NO repetir
- `decisiones.md` — por qué se hicieron las cosas
- `memoria-corto.md` — estado de la sesión actual
- `memoria-larga.md` — patrones aprendidos
- `proyectos-activos.md` — estado de proyectos en curso

### Memoria del skill (en `.claude/skills/importacion-vehiculos/memoria/`)
- **`MEMORIA.md`** — índice del skill
- `modelos-medidos.md` — histórico de modelos investigados
- `vendedores-confianza.md` — dealers que responden bien
- `trampas-encontradas.md` — trampas detectadas en portales
- `mejoras-aplicadas.md` — cambios y mejoras del skill

### Regla de oro de la memoria
> **Si aprendes algo nuevo durante una conversación, escríbelo en el archivo correspondiente ANTES de terminar.**
> La próxima conversación empezará leyéndolo y tendrás continuidad.

---

## ⚡ Inicio rápido de sesión

Al empezar una conversación de trabajo, Claude debe:

1. Leer este `CONTEXTO_ACTIVO.md`
2. Identificar el flujo (A: UNIDAD, B: MODELO, C: MERCADO) según el input
3. Si es Flujo A → cargar `importacion-vehiculos/SKILL.md`
4. Si es Flujo B/C → usar directamente los playbooks del skill
5. **Nunca** generar PDFs directamente → Laravel lo hace