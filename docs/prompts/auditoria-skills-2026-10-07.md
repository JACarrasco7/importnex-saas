# Prompt — Auditoría PROFESIONAL de las dos skills (mercado + importación)

> **Cuándo usar:** cuando se quiera elevar el nivel de `estudio-mercado` + `importacion-vehiculos` a una versión profesional madura (precisión, consistencia entre skills, anti-patrones conocidos, deduplicación de reglas, redacción y estructura).
>
> **Última revisión del prompt:** 2026-10-07 · basado en las auditorías `AUDITORIA_estudio-mercado_2026-09-12.md` y `AUDITORIA_ronda4_2026-09-13.md`.

---

## Prompt (pegar literal en Claude Desktop)

```
═══════════════════════════════════════════════════════════════════
ROL
═══════════════════════════════════════════════════════════════════

Actúa como AUDITOR SENIOR del sistema JJ Import Motors, especializado en
calidad de skills de IA para negocio de búsqueda/importación de coches.
Vas a hacer una auditoría PROFUNDA, PROFESIONAL y CONJUNTA de las DOS skills
que forman el núcleo del sistema:

  A) .claude/skills/estudio-mercado/     (mapa de mercado persistente)
  B) .claude/skills/importacion-vehiculos/ (búsqueda + valoración + ZIP)

Ambas viven en el workspace c:\laragon\www\importnexcore y se sincronizan a
C:\Users\jacar\Desktop\JJImportMotors\ vía scripts/sync-desktop.ps1.
El panel Laravel (ImportnexCore) consume AMBAS: estudio-mercado da el
CRITERIO de selección (PASO 0), importacion-vehiculos ejecuta los FLUJOS.

═══════════════════════════════════════════════════════════════════
CONTEXTO MÍNIMO OBLIGATORIO (lee ANTES de auditar)
═══════════════════════════════════════════════════════════════════

1. NEGOCIO — lee y entiende:
   - .github/copilot-instructions.md §"Dominio del proyecto" (NO vendemos,
     prestamos servicio de búsqueda/importación con honorarios; cliente
     compra el coche).
   - .claude/skills/importacion-vehiculos/04-negocio/costes.md
     (verificar regla 6/6000 IVA intracomunitario).
   - .claude/skills/importacion-vehiculos/04-negocio/riesgos.md
     (motores problemáticos: confirmar SKYACTIV-D, 1.2 TSI, etc.).
   - .ai/rules/business-model.md (regla A32: nunca presentarnos como vendedor).

2. SKILLS — empieza por el frontal:
   - .claude/skills/estudio-mercado/SKILL.md
   - .claude/skills/importacion-vehiculos/SKILL.md
   Y después, SOLO los compañeros que cita el frontal en cada flujo (no
   leas directorios enteros).

3. AUDITORIAS ANTERIORES — para evitar re-detectar lo ya arreglado y
   aprender los anti-patrones que motivaron las reglas:
   - docs/AUDITORIA_estudio-mercado_2026-09-12.md (3 hallazgos: IVA
     fantasma, dos costes en paralelo, falta de horquilla).
   - docs/AUDITORIA_ronda4_2026-09-13.md (4A: race conditions, push
     desde prod; 4B: multi-tenant, package muerto, OOMs).
   - .claude/skills/importacion-vehiculos/06-reglas/anti_patrones.md
     (A1-A33, EL LISTADO MAESTRO de reglas duras — tu vade-mécum).

4. ESTADO ACTUAL — qué versión corre hoy:
   - estudio-mercado: 0.5.1 (incluye scripts/generar_guia_marcas.py).
   - importacion-vehiculos: 3.10.5 (validador v2, generador v2).

═══════════════════════════════════════════════════════════════════
DIMENSIONES A AUDITAR (10 — no te saltes ninguna)
═══════════════════════════════════════════════════════════════════

D1. COHERENCIA ENTRE SKILLS (la más crítica de esta auditoría).
    - ¿El modelo de costes en estudio-mercado (1.129 € reales + colchón
      371 € = 1.500 €) coincide con el de importacion-vehiculos y con
      04-negocio/costes.md? ¿Hay un IVA fantasma del 21 % reintroducido
      en alguna ruta?
    - ¿El catálogo de IDs de portal (vw=25200, golf=14, etc.) es
      IDÉNTICO entre las dos skills? ¿Hay reglas de filtrado contradictorias
      (Coches.net NUNCA `Versions[]` vs SÍ `Versions[]`)?
    - ¿El campo `cv_min`, `cv_max`, `anio_desde/hasta`, `km`, `carroceria`
      tiene la misma semántica en los dos lados?
    - ¿La ruta pactada del mapa (Desktop + workspace) se mantiene o ha
      aparecido una ruta "huérfana" nueva?
    - ¿El contrato "PASO 0 leer datos_mercado.json → importacion" se
      respeta? (que importacion NO improvise criterio si el mapa existe).

D2. PRECISIÓN NUMÉRICA Y FUENTES.
    - Cada cifra en el texto (21 % rotado, 1.500 €, 1.000 € tras cambio,
      3 años garantía, regla 6/6000) — ¿está citada? ¿La fuente sigue
      vigente? ¿Hay inflación de cifras heredadas de hace meses y ya
      obsoletas?
    - ¿Hay dobles modelos de coste en el MISMO archivo (hallazgo 12-sep)?
      - SKILL.md vs informe_mercado.md vs datos_mercado.json vs
        04-negocio/costes.md.
    - ¿Las horquillas ("~21.000 €", no "21.000 €") se aplican cuando
      toca? (vs cifras cerradas al euro).

D3. ANTI-PATRÓN "AFIRMACIÓN DADA POR BUENA".
    - Recorre TODAS las afirmaciones cuantitativas o legales del SKILL.md
      y de los 8 informes-entregables. ¿Cuántas tienen fuente citada?
      ¿Cuántas son "se sabe" / "como es sabido"? Marca 🔴 las sin
      fuente.
    - ¿Las afirmaciones legales (Ley 11/2022, RDL 1/2007, RGPD, IVA
      intracomunitario, IEDMT por CCAA) están al día con 2026? ¿Alguna
      cita a una ley derogada o un BOE desactualizado?
    - ¿Algún texto afirma "JJ Import Motors vende" / "damos garantía"
      / "stock disponible"? (regla A32 PROHIBIDO).

D4. REGLAS DURAS (anti_patrones.md A1-A33) — INTEGRIDAD.
    - ¿Las 33 reglas siguen vigentes? ¿Alguna quedó superada por un fix
      posterior y ya no aplica (marcarla para limpieza)?
    - ¿Algún patrón nuevo detectado en los flujos desde la v3.10.5 que
      NO está en anti_patrones y debería añadirse (A34+)? Sugiere los
      que encuentres con su frase exacta.
    - ¿Hay reglas contradictorias entre A1..A33? (poco probable pero
      posible tras muchas iteraciones).

D5. VALIDADORES Y SCRIPTS.
    - scripts/check_marketing.py — ¿los 30 checks siguen reflejando
      A1-A33? ¿Algún check desfasado? (ej: C17 por pieza ya arreglado).
    - scripts/check_ficha_cliente.py — ¿cubre A22b y A32?
    - scripts/check_avisos.py — ¿cubre la regla de horquilla D2?
    - scripts/empaquetar.py — ¿hay una validación previa a empaquetar
      que ejecute los 3 checks juntos y aborte si 🔴?
    - scripts/fuentes.py — ¿el catálogo de IDs es la fuente única
      (vs hardcoded en algún otro sitio)?
    - scripts/esqueleto_a_json.py — ¿pierde campos en la conversión
      txt → JSON? (auditoría de ida y vuelta).
    - scripts/verify_skill_refs.py — ¿referencias rotas entre docs?
    - estudio-mercado/scripts/generar_guia_marcas.py — ¿el dict IDENT
      cubre los 23 slugs del mapa o hay alguno sin entrada curada?

D6. EFICIENCIA DE CONTEXTO (mismo espíritu que el prompt anterior, pero
    aplicado a las DOS skills).
    - SKILL.md de cada skill: ¿< 8 KB? ¿O se ha vuelto a hinchar?
    - Compañeros citados desde SKILL.md: ¿están todos JUSTIFICADOS por
      algún flujo real? ¿Hay compañeros "históricos" que ya nadie lee?
    - ¿Cuánto pesan los 8 informes-entregables.txt? (estudio-mercado
      usa informe_mercado.md que es plantilla; importacion tiene
      03-informes/ con varios).
    - ¿Algún bloque se duplica entre archivos (mismo párrafo en SKILL.md
      y en un compañero)?

D7. CALIDAD DE REDACCIÓN Y ESTRUCTURA.
    - ¿Hay emojis decorativos prohibidos (🔥💥🚀😍 — ver A25)? Salen
      solo permitidos (⚠️✅🔥 1 vez en showstopper, pictogramas
      informativos 🗓️🛣️⚙️⛽🐎🏷️👤🔧📄📍📦💶✅⚠️🇩🇪🇪🇸 al inicio).
    - ¿Los superlativos vacíos (A24: ¡Brutal!, ¡Increíble!, Chollo…)
      están PROHIBIDOS explícitamente en TODOS los generadores, o solo
      en algunos?
    - ¿Las secciones de marketing tienen los bloques obligatorios
      ([DESCRIPCION], [POR_QUE], [PT_AVISO], [CTA], [QR], [LEGAL],
      [FC_NO_GARANTIA]…) en su generador correspondiente?
    - ¿Los informes tienen bloque de fuentes ([FUENTES] con URL +
      parámetros + conteo + fecha)?

D8. MEMORIA Y APRENDIZAJE.
    - .claude/skills/importacion-vehiculos/memoria/ y docs/memoria-desktop/
      — ¿hay entradas obsoletas o contradictorias entre sí?
    - ¿Las "lecciones aprendidas" (errores-pasados.md) están aplicadas
      en el código/reglas? ¿O están escritas pero nadie las aplica?
    - docs/HANDOFF.md y docs/COMUNICACION-CLAUDE-VSCODE.md — ¿el
      protocolo se respeta? (la última entrada tiene < 10 días,
      no hay "⚠️" viejos sin cerrar).

D9. INTEGRACIÓN CON EL PANEL LARAVEL.
    - El panel espera un ZIP con un JSON concreto
      (scripts/empaquetar.py) — ¿el contrato se mantiene? ¿Algún
      campo nuevo en el JSON que Laravel no consume?
    - Las guías de búsqueda (Desktop\JJImportMotors\guias-busqueda\) se
      generan desde estudio-mercado para el usuario — ¿el panel las
      referencia en algún sitio (menú, ayuda, dashboard)?
    - ¿Hay features del panel (paquete valoración, marketplace, B2B)
      que NO tienen reflejo en ninguna skill y por tanto la IA nunca
      las genera bien?

D10. RIESGOS DE MODELO DE NEGOCIO (lo que NO se ve en el código).
     - ¿Algún texto podría interpretarse como que JJ Import Motors
        VENDE coches o DA garantía? (A32 — riesgo legal alto).
     - ¿Alguna mención a "stock", "disponible", "última unidad" en
        sitio no permitido? (A24 + A32).
     - ¿Hay alguna afirmación sobre homologación/atún de TTFT/ITV
        que no aplique a la realidad 2026 (post-Ley 11/2022)?
     - ¿Las reglas RGPD/AVISO LEGAL son robustas? (especialmente
        en la ficha cliente que se comparte por WhatsApp).

═══════════════════════════════════════════════════════════════════
FORMATO DE SALIDA (obligatorio — así entrego yo el informe)
═══════════════════════════════════════════════════════════════════

Genera DOS documentos Markdown en c:\laragon\www\importnexcore\docs\:

  1. AUDITORIA_ESTUDIO_MERCADO_<YYYY-MM-DD>.md
  2. AUDITORIA_IMPORTACION_VEHICULOS_<YYYY-MM-DD>.md

Plantilla por documento (idéntica a las auditorías previas):

  # Título de la auditoría
  **Fecha:** · **Versión auditada:** · **Versión corregida propuesta:**

  ## 🔴 Hallazgos críticos (rompen el sistema o dan info falsa)
  Para cada uno: ID, descripción, evidencia (cita exacta + ruta),
  impacto (qué decisión contaminaría), fix concreto (con snippet
  si es código), y test/regresión que lo cazaría en el futuro.

  ## 🟠 Importantes (degrada calidad o abre camino a bugs)
  Mismo formato.

  ## 🟡 Menores (limpieza / consistencia)
  Lista corta.

  ## ✅ Verificado correcto (no tocar)
  Lista honesta de lo que YA está bien (para no re-auditar en la
  siguiente ronda y para que el usuario confíe).

  ## 📋 Pendiente de tu OK (decisiones de negocio, no técnicas)
  Cambios que requieren aprobación humana: instalar/desinstalar
  paquetes, cambiar reglas duras, tocar dinero, modificar plantilla.

  ## 🎯 Resumen ejecutivo (3-5 líneas para WhatsApp)

Y al final, una TABLA RESUMEN transversal:

  | Hallazgo | Skill | Severidad | Fix |
  |----------|-------|-----------|-----|

═══════════════════════════════════════════════════════════════════
LO QUE NO DEBES HACER
═══════════════════════════════════════════════════════════════════

- NO tocar archivos. Esta auditoría es de LECTURA y PROPUESTA.
  Tú propones el fix exacto (con snippet), yo aplico en VS Code.
- NO "arreglar" cosas de un fix obvio sin avisar — listar y esperar.
- NO proponer instalar/desinstalar paquetes sin marcarlo como
  "pendiente de tu OK".
- NO contradecir las reglas A1-A33. Si una regla te parece obsoleta,
  marcala como sugerencia A34 (nunca la reescribas en silencio).
- NO inventar números. Si una cifra no tiene fuente, di "SIN FUENTE —
  VERIFICAR"; no la corrijas a ojo.
- NO cargar la base de datos. Si necesitas ver datos_mercado.json, lelo
  del fichero; si necesitas el panel, use el navegador con capturas.
- NO leer 50 archivos por sesión. Lee el SKILL.md → los citados →
  1-2 archivos adicionales POR hallazgo confirmado. Contexto es
  dinero; sé quirúrgico.
- NO duplicar lo que la auditoría 12-sep y la ronda-4 ya
  arreglaron. Si un hallazgo está en AUDITORIA_estudio-mercado_
  2026-09-12.md §🔴1, mencionalo SOLO si ha vuelto a aparecer.

═══════════════════════════════════════════════════════════════════
ENTREGABLE FINAL
═══════════════════════════════════════════════════════════════════

Imprime al terminar:

  === AUDITORÍA COMPLETADA ===
  Documentos:
    - docs/AUDITORIA_ESTUDIO_MERCADO_<YYYY-MM-DD>.md
    - docs/AUDITORIA_IMPORTACION_VEHICULOS_<YYYY-MM-DD>.md
  Resumen: X críticos · Y importantes · Z menores
  Pendiente de tu OK: N decisiones
  === APLICA EN VS CODE CON COPILOT — yo me encargo ===

Y NUNCA escribas en C:\Users\jacar\Desktop\JJImportMotors\ ni en
.claude/skills/ (no tienes acceso a mi disco en sesión normal).
```

---

## Notas para el usuario (Jacar)

- **Por qué este prompt es "profesional"** vs. uno de "audita esto":
  - Define ROL explícito (auditor senior, no asistente genérico).
  - Obliga a leer el contexto MÍNIMO antes de auditar (sin contexto
    cualquier auditoría es opinión).
  - 10 DIMENSIONES concretas y disjuntas (no "audita todo").
  - FORMATO de salida idéntico a las 2 auditorías previas → resultado
    integrable con `AUDITORIA_*.md` ya existentes y comparables.
  - Lista LO QUE NO DEBE HACER (anti-patrones del auditor).
  - Pide ENTREGABLE explícito (Claude Desktop te dice dónde dejó
    los MDs).

- **Cómo lo uso en Claude Desktop:**
  1. Abrir Claude Desktop con el workspace ImportnexCore.
  2. Pegar el bloque desde `## Prompt` hasta el último ```.
  3. Dejarle trabajar. Tardará ~15-30 min (depende de cuánto contexto
     cargue; él mismo debe aplicar las reglas de D6).
  4. Al terminar te dice dónde están los MDs. Tú los abres en VS Code
     y aplicas los fixes con Copilot (o me los pasas).

- **Cadencia recomendada:** 1 vez al trimestre o tras 5+ cambios
  mayores en cualquier skill. No más (las auditorías tienen coste de
  contexto alto).