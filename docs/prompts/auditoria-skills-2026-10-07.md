# Prompt — Auditoría PROFESIONAL integral (3 skills + panel Laravel + Drive)

> **Cuándo usar:** cuando se quiera elevar el sistema JJ Import Motors a una versión profesional madura. Audita a la vez: 3 skills de IA, el panel Laravel que las orquesta, y la sincronización con Drive de la cartera completa.
>
> **Última revisión del prompt:** 2026-10-07 (tabla de 8 flujos + D11 Drive + D12 flujos).
>
> **Auditorías previas que NO hay que re-detectar:**
> - `docs/AUDITORIA_estudio-mercado_2026-09-12.md` (3 hallazgos: IVA fantasma, dos costes en paralelo, falta de horquilla).
> - `docs/AUDITORIA_ronda4_2026-09-13.md` (4A race conditions; 4B multi-tenant/OOMs).
> - `docs/SYNC-DRIVE-PLAN.md` (contrato Drive; este prompt lo audita en D11).

---

## Configuración recomendada del modelo

> **Nota del 2026-10-07:** Claude **Opus 5.5** aún no está disponible a fecha de este prompt
> (los modelos Anthropic disponibles en Claude Desktop a día de hoy son la familia **Opus 4.1 / 4.5**
> y **Sonnet 4.5**). Si cuando leas esto ya existe Opus 5.5 o un modelo superior, aplica este
> prompt igualmente: las capacidades que aprovechamos son las del nivel Opus (razonamiento
> profundo multi-archivo, tools extendidas, contexto largo, salida estructurada), y todos los
> Opus 4.x las tienen. **Lo que NO debes hacer es degradar a Sonnet 4.5** para esta tarea:
> el razonamiento sobre 12 dimensiones + 8 flujos + 3 skills simultáneas consume
> demasiado contexto de planificación; Sonnet tiende a saltarse D11/D12.

**Cómo lanzar la auditoría en Claude Desktop:**

| Parámetro | Valor | Por qué |
|---|---|---|
| **Modelo** | Opus 4.5 (o el Opus más alto disponible) | Razonamiento sostenido sobre 12D + 8 flujos |
| **Modo** | "Pro" / "Extended thinking" si está disponible | Pensar antes de actuar reduce hallazgos fantasma |
| **Contexto** | Ventana completa (no recortes) | Debe leer SKILL.md de 3 skills + planes + reglas .ai en una sola sesión |
| **Tools habilitadas** | `Read`, `Grep`, `Glob`, `Bash` (sólo lectura: `cat`, `head`, `wc`, `git log`, `git show`) — **NO** `Write`/`Edit`/`WebFetch` agresivo | Es una auditoría de LECTURA; el bloque "LO QUE NO DEBES HACER" lo refuerza |
| **Workspace mount** | `c:\laragon\www\importnexcore` (raíz del repo) | Para que pueda leer `.claude/`, `docs/`, `.ai/` |
| **Tamaño esperado de salida** | 3-4 MDs en `docs/AUDITORIA_*.md` (5-25 KB cada uno) + bloque final en chat | Si Opus devuelve < 2 KB, ha medido mal |

**Variables de entorno opcionales** (si el cliente Claude Desktop las soporta):

```text
AUDIT_DATE=2026-10-07
AUDIT_SCOPE=3skills+laravel+drive
AUDIT_LEVEL=professional
```

---

## Prompt (pegar literal en Claude Desktop)

```
═══════════════════════════════════════════════════════════════════
ROL
═══════════════════════════════════════════════════════════════════

Actúa como AUDITOR SENIOR del sistema JJ Import Motors, especializado
en calidad de skills de IA para negocio de búsqueda/importación de
coches. Vas a hacer una auditoría PROFUNDA, PROFESIONAL y CONJUNTA de
las TRES skills + el panel Laravel que las orquesta + la integración
con Drive que ya está parcialmente en producción:

  A) .claude/skills/estudio-mercado/       (mapa de mercado persistente)
  B) .claude/skills/importacion-vehiculos/ (búsqueda + valoración + ZIP)
  C) .claude/skills/ecommerce-tuning/      (e-commerce accesorios/tuning;
     modelo tramitador puro S/P/F/M, sin almacén; misma filosofía de
     negocio que A y B: NO comprar stock, solo ofertar servicio).

  D) Panel Laravel (ImportnexCore)
     - Stack: Laravel 11.55 + PHP 8.5 + Inertia 2 + Vue 3 + Tailwind 3.4
     - Rutas: /billing/* /vehicles/* /imports/* /valuations/* /marketplace/*
       /public/*
     - Multi-tenant via organization_id (NO confundir con plan/subscription)
     - Comandos artisan: app/Console/Commands/ (MarketImport, MarketAlerts,
       MarketExport, ImportValuation, MarketFreshness...)
     - Composer: maatwebsite/excel (xlsx local), Stripe (cashier), NO
       hay cliente Google todavía (ni google/apiclient, ni flysystem-
       google-drive, ni league/oauth2-client) — es un hueco conocido
       que D11 evaluará.

Las tres skills viven en el workspace c:\laragon\www\importnexcore y se
sincronizan a C:\Users\jacar\Desktop\JJImportMotors\ vía
scripts/sync-desktop.ps1. La skill A da el CRITERIO de selección que
la B consume en su PASO 0; la C opera en otro vertical (accesorios) y
comparte con A/B el principio de "tramitador puro, sin stock". El
panel Laravel es el consumidor final de A y B (la C aún no genera
ZIP para Laravel).

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
   - .ai/rules/business-model.md (regla A32: nunca presentarnos como
     vendedor ni dar garantía).

2. SKILLS — empieza por el frontal:
   - .claude/skills/estudio-mercado/SKILL.md
   - .claude/skills/importacion-vehiculos/SKILL.md (frontmatter
     describe los 5 flujos A/B/C/D/E + M)
   - .claude/skills/ecommerce-tuning/SKILL.md
   Y después, SOLO los compañeros que cita el frontal en cada flujo
   (no leas directorios enteros).

3. AUDITORÍAS ANTERIORES + PLANES — para no re-detectar lo arreglado:
   - docs/AUDITORIA_estudio-mercado_2026-09-12.md
   - docs/AUDITORIA_ronda4_2026-09-13.md
   - docs/SYNC-DRIVE-PLAN.md (D11 lo audita; este es el CONTRATO)
   - docs/HANDOFF.md (la última entrada indica qué sesión tocar)

4. ESTADO ACTUAL — qué versión corre hoy:
   - estudio-mercado: 0.5.1 (incluye scripts/generar_guia_marcas.py)
   - importacion-vehiculos: 3.10.5 (validador v2, generador v2)
   - ecommerce-tuning: leer frontmatter (es la más nueva)

5. TABLA DE FLUJOS Y ENTREGABLES CANÓNICOS — la verdad única:
   - .claude/skills/importacion-vehiculos/03-informes/entregables.md
     (este fichero MANDA sobre cualquier otra mención de nombres/
     carpetas; si SKILL.md dice otra cosa, este gana y hay que
     corregir SKILL.md).

═══════════════════════════════════════════════════════════════════
LOS 5 (+1) FLUJOS DE TRABAJO Y SUS ENTREGABLES (MAPA MAESTRO)
═══════════════════════════════════════════════════════════════════

Estos son los flujos que el sistema tiene hoy. Cualquier auditoría
que NO los respete está midiendo mal. Audita que cada flujo:

  (1) tiene plantilla en el sitio correcto
  (2) el generador la usa sin saltarse secciones
  (3) el validador la verifica
  (4) el nombre de fichero coincide con la tabla de entregables
  (5) la carpeta de salida es la pactada
  (6) si toca Drive, usa la carpeta canónica de SYNC-DRIVE-PLAN.md

A) FLUJO UNIDAD — "evalúa este: <URL>" / "mira este coche"
   - Skill dueña: importacion-vehiculos
   - Disparo:  el usuario pasa una URL concreta (mobile.de / coches.net /
     autoscout24 / milanuncios / wallapop).
   - Entregable: INFORME DE UNIDAD (15 secciones) + DOSSIER cliente
     + ZIP para Laravel.
   - Plantilla:  03-informes/informe_tecnico.md
   - Fichero:    informe_unidad_<marca>-<modelo>_<YYYY-MM-DD>.md
   - Carpeta:    informes\<marca>\<modelo>\
   - Valid.:     check_marketing + check_ficha_cliente + check_avisos
   - Drive:      NUNCA (es por encargo privado, va al cliente).
   - Riesgo crítico: A22b (ficha pública), A32 (no vendemos).

B) FLUJO BÚSQUEDA POR MODELO — "busca un Golf 8 GTI" / "encargo X"
   - Skill dueña: importacion-vehiculos
   - Disparo:  marca + modelo claros.
   - Entregable: INFORME DE BÚSQUEDA + Top 5 candidatos + ZIP.
   - Plantilla:  03-informes/informe_busqueda.md
   - Fichero:    informe_busqueda_<marca>-<modelo>_<YYYY-MM-DD>.md
                 + .pdf (SIEMPRE con mercado_pdf.py)
   - Carpeta:    informes\<marca>\<modelo>\
   - Valid.:     check_marketing + check_avisos
   - Drive:      07 Vehículos (operaciones) → Registro_Operaciones_Importacion.xlsx
   - Riesgo crítico: Top 5 sin coherencia con mapa (A1).

C) FLUJO BÚSQUEDA POR CATEGORÍA/MERCADO — "qué merece la pena" /
   "escanea el mercado" / "revisa este segmento"
   - Skill dueña: importacion-vehiculos
   - Disparo:  segmento / familia / categoría (ej. "SUV premium 25-40k",
     "compactos deportivos", "berlinas").
   - Entregable: INFORME DE BÚSQUEDA (N modelos) → tabla scouting_mercado.
   - Plantilla:  03-informes/informe_busqueda.md
   - Fichero:    informe_busqueda_<segmento-o-categoria>_<YYYY-MM-DD>.md
   - JSON:       export/flujo-c-<YYYY-MM-DD>.json
   - Carpeta:    informes\<marca>\ o informes\<segmento>\
   - Valid.:     check_marketing
   - Drive:      07 Vehículos → coches.json (actualizar maestro)
   - Riesgo crítico: comparar peras con manzanas (A1 + A19).

D) FLUJO DESCUBRIMIENTO — "cliente sin modelo, tiene X €"
   - Skill dueña: importacion-vehiculos
   - Disparo:  presupuesto + (opcional) preferencias.
   - Entregable: INFORME DE MODELOS (país × año × motor) — SIN
     anuncios, SIN enlaces. Embudo → acaba en flujo B.
   - Plantilla:  SKILL.md §D2
   - Fichero:    informe_modelos_<cliente-o-segmento>_<YYYY-MM-DD>.md
   - Carpeta:    informes\descubrimiento\
   - Valid.:     (no tiene propio; reusa check_avisos si se imprime)
   - Drive:      NUNCA (es prospecto, no operación).
   - Riesgo crítico: si sesga el veredicto del mapa, A1 falla.

E) FLUJO STOCK — "catálogo bajo pedido" / "stock recurrente"
   - Skill dueña: importacion-vehiculos
   - Disparo:  operación periódica de oferta (catálogo por categoría).
   - Entregable: INFORME DE BÚSQUEDA (por categorías) + JSON stock.
   - Plantilla:  03-informes/informe_busqueda.md
   - Fichero:    informe_busqueda_<segmento-o-categoria>_<YYYY-MM-DD>.md
   - JSON:       export/stock-<YYYY-MM-DD>.json
   - Carpeta:    informes\<marca>\ (uno por marca)
   - Valid.:     check_marketing + check_avisos
   - Drive:      07 Vehículos → Inventario_Coches_Oferta.xlsx
   - Riesgo crítico: A32 (nunca "vendemos" / "stock propio"); A1.

M) FLUJO MARKETING — "dame los anuncios" / "copy para redes" /
   "ficha de publicación"
   - Skill dueña: importacion-vehiculos
   - Disparo:  tras A, B, C, D o E; o petición explícita de marketing.
   - Entregable: redes-sociales.txt (IG/FB/FBMP/Story) +
                 anuncio-portales.txt (PT) + ficha_cliente.txt.
   - Plantilla:  07-marketing/copy_engine.md + 07-marketing/ficha_cliente.md
   - Valid.:     check_marketing (30 checks) + check_ficha_cliente
   - Drive:      NUNCA (es contenido efímero, vive en la ficha del
                 cliente en panel; A22b y A32 obligan a no exponer).

F-EM) ESTUDIO DE MERCADO POR MARCA — "estudia Audi" / "estudia VW
   Golf 8" / "estudia berlinas 25k+"
   - Skill dueña: estudio-mercado
   - Disparo:  marca / modelo / segmento / rango / tipo_cliente
     (los "estudios dirigidos" de SKILL.md §153).
   - Entregable: INFORME DE MERCADO (sondeo conciso, 9 secciones,
     v0.5.0) + actualiza datos_mercado.json.
   - Plantilla:  estudio-mercado/informe_mercado.md (9 secciones fijas)
   - Fichero:    <marca>_<segmento>_<enfoque>_<YYYY-MM-DD>.md
                 (ej. vw_compacto_deportivo_2026-09-27.md)
   - Carpeta:    informes\<marca>\  (la MISMA que usa importacion-vehiculos
                 para sus informes de marca)
   - Valid.:     (no tiene propio; el auditor humano verifica la
                 regla de horquilla y la §FUENTES)
   - Drive:      00 Estudios de mercado → datos_mercado.json + .md
                 (PDF NO por defecto, solo si el usuario lo pide).
   - Riesgo crítico: introducir cifra sin fuente (D2 del prompt).

F-GM) ESTUDIO DE MERCADO POR SEGMENTO — "estudia todas las berlinas
   premium" / "estudia SUVs compactos 15-25k"
   - Skill dueña: estudio-mercado
   - Disparo:  segmento / rango / tipo de cliente (no marca).
   - Entregable: INFORMES DE MERCADO por marca DENTRO del segmento,
                 uno por marca, en carpetas separadas, ORDENADOS por
                 prioridad (modelos con más hueco, primero).
   - Plantilla:  misma que F-EM (informe_mercado.md 9 secciones).
   - Fichero:    <marca>_<segmento>_<enfoque>_<YYYY-MM-DD>.md (uno
                 por marca; cada uno se entrega en su carpeta
                 informes\<marca>\).
   - Carpeta:    informes\<marca>\ (N marcas = N carpetas; ver
                 SYNC-DRIVE-PLAN.md §1.1 → "00 Estudios de mercado /
                 <marca>/").
   - Valid.:     misma que F-EM.
   - Drive:      00 Estudios de mercado → un subdirectorio por marca,
                 con sus MDs.
   - Riesgo crítico: comparar marcas con universos distintos (A1 + A19).

NOTA — ALCANCE DEL PROMPT:
   El usuario te recordará que estos 3 últimos (F-EM, F-GM y el
   "investigar una unidad" que es A) son los flujos que más usa.
   Si descubres un flujo que NO está en esta tabla, AÑÁDELO a la
   sección "Flujos que faltan" del informe final (con su análisis
   de a qué skill debería pertenecer y qué plantilla necesitaría).

═══════════════════════════════════════════════════════════════════
DIMENSIONES A AUDITAR (12 — no te saltes ninguna)
═══════════════════════════════════════════════════════════════════

D1. COHERENCIA ENTRE SKILLS (la más crítica).
    - ¿El modelo de costes en estudio-mercado (1.129 € reales + colchón
      371 € = 1.500 €) coincide con el de importacion-vehiculos y con
      04-negocio/costes.md? ¿Hay un IVA fantasma del 21 % reintroducido
      en alguna ruta?
    - ¿El catálogo de IDs de portal (vw=25200, golf=14, etc.) es
      IDÉNTICO entre las dos skills? ¿Hay reglas de filtrado
      contradictorias (Coches.net NUNCA Versions[] vs SÍ Versions[])?
    - ¿El campo cv_min/cv_max/anio_desde/anio_hasta/km/carroceria tiene
      la misma semántica en los dos lados?
    - ¿La ruta pactada del mapa (Desktop + workspace) se mantiene o ha
      aparecido una ruta "huérfana" nueva?
    - ¿El contrato "PASO 0 leer datos_mercado.json → importacion" se
      respeta? (que importacion NO improvise criterio si el mapa
      existe).
    - ¿ecommerce-tuning comparte la regla A32 (no vendemos) o se le ha
      colado "vendemos stock"?
    - ¿Las 3 skills se sincronizan a Desktop correctamente vía
      sync-desktop.ps1 sin pisarse?

D2. PRECISIÓN NUMÉRICA Y FUENTES.
    - Cada cifra (21 % rotado, 1.500 €, 3 años garantía, regla 6/6000)
      — ¿está citada? ¿La fuente sigue vigente? ¿Hay inflación de
      cifras heredadas de hace meses?
    - ¿Hay dobles modelos de coste en el MISMO archivo (hallazgo
      12-sep)? SKILL.md vs informe_mercado.md vs datos_mercado.json
      vs 04-negocio/costes.md.
    - ¿Las horquillas ("~21.000 €") se aplican cuando toca? (vs
      cifras cerradas al euro).
    - En ecommerce-tuning: ¿las cifras de márgenes (s/P/F/M) tienen
      fuente (1688/Alibaba/CJ) o son ojo?

D3. ANTI-PATRÓN "AFIRMACIÓN DADA POR BUENA".
    - Recorre TODAS las afirmaciones cuantitativas o legales del SKILL.md
      y de las 6 plantillas de informe (informe_tecnico, informe_busqueda,
      informe_mercado, copy_engine, ficha_cliente, etc.). ¿Cuántas
      tienen fuente citada? Marca 🔴 las sin fuente.
    - ¿Las afirmaciones legales (Ley 11/2022, RDL 1/2007, RGPD, IVA
      intracomunitario, IEDMT por CCAA) están al día con 2026?
    - ¿Algún texto afirma "JJ Import Motors vende" / "damos garantía"
      / "stock disponible"? (regla A32 PROHIBIDO — en las 3 skills).

D4. REGLAS DURAS (anti_patrones.md A1-A33) — INTEGRIDAD.
    - ¿Las 33 reglas siguen vigentes? ¿Alguna quedó superada por un fix
      posterior (marcarla para limpieza)?
    - ¿Algún patrón nuevo detectado en los flujos desde la v3.10.5 que
      NO está en anti_patrones y debería añadirse (A34+)? Sugiere los
      que encuentres con su frase exacta.
    - ¿Hay reglas contradictorias entre A1..A33?
    - ¿Las reglas A24/A25/A28/A29/A32 están aplicadas también en
      ecommerce-tuning (no solo en importacion)?

D5. VALIDADORES Y SCRIPTS.
    - scripts/check_marketing.py — ¿los 30 checks siguen reflejando
      A1-A33? ¿Algún check desfasado?
    - scripts/check_ficha_cliente.py — ¿cubre A22b y A32?
    - scripts/check_avisos.py — ¿cubre la regla de horquilla D2?
    - scripts/empaquetar.py — ¿hay validación previa que ejecute los
      3 checks juntos y aborte si 🔴?
    - scripts/fuentes.py — ¿el catálogo de IDs es la fuente única
      (vs hardcoded en otro sitio)?
    - scripts/esqueleto_a_json.py — ¿pierde campos en txt→JSON?
    - scripts/verify_skill_refs.py — ¿referencias rotas entre docs?
    - scripts/sync_web_data.py (Drive B) — ¿usa base64Content o el
      corrupto `content`? (auditoría D11 en detalle).
    - estudio-mercado/scripts/generar_guia_marcas.py — ¿el dict IDENT
      cubre los 23 slugs o hay alguno sin entrada curada?
    - ecommerce-tuning/scripts/viabilidad.py o equivalente — ¿cubre
      S/P/F/M y cita fuentes (TikTok Creative Center, Meta Ads
      Library, Google Trends)?

D6. EFICIENCIA DE CONTEXTO.
    - SKILL.md de cada skill: ¿< 8 KB? ¿O se ha vuelto a hinchar?
    - Compañeros citados desde SKILL.md: ¿están todos JUSTIFICADOS
      por algún flujo real? ¿Hay compañeros "históricos" que ya nadie
      lee?
    - ¿Cuánto pesan los 6 entregables-txt?
    - ¿Algún bloque se duplica entre archivos (mismo párrafo en
      SKILL.md y en un compañero)?

D7. CALIDAD DE REDACCIÓN Y ESTRUCTURA.
    - ¿Hay emojis decorativos prohibidos (🔥💥🚀😍 — ver A25)?
      Permitidos solo: 🔥 (1 vez en showstopper), pictogramas
      informativos (🗓️🛣️⚙️⛽🐎🏷️👤🔧📄📍📦💶✅⚠️🇩🇪🇪🇸) al inicio.
    - ¿Los superlativos vacíos (A24) están PROHIBIDOS en TODOS los
      generadores?
    - ¿Las secciones de marketing tienen los bloques obligatorios
      ([DESCRIPCION], [POR_QUE], [PT_AVISO], [CTA], [QR], [LEGAL],
      [FC_NO_GARANTIA]…) en su generador?
    - ¿Los informes tienen bloque de fuentes ([FUENTES] con URL +
      parámetros + conteo + fecha)?

D8. MEMORIA Y APRENDIZAJE.
    - .claude/skills/importacion-vehiculos/memoria/ y
      docs/memoria-desktop/ — ¿hay entradas obsoletas o
      contradictorias entre sí?
    - ¿Las "lecciones aprendidas" (errores-pasados.md) están aplicadas
      en el código/reglas? ¿O están escritas pero nadie las aplica?
    - docs/HANDOFF.md y docs/COMUNICACION-CLAUDE-VSCODE.md — ¿el
      protocolo se respeta?

D9. INTEGRACIÓN CON EL PANEL LARAVEL.
    - El panel espera un ZIP con un JSON concreto (scripts/empaquetar.py)
      — ¿el contrato se mantiene? ¿Algún campo nuevo que Laravel no
      consume? ¿Algún campo que Laravel espera y no llega?
    - Las guías de búsqueda (Desktop\JJImportMotors\guias-busqueda\)
      se generan desde estudio-mercado — ¿el panel las referencia en
      algún sitio (menú, ayuda, dashboard)?
    - ¿Hay features del panel (paquete valoración, marketplace, B2B)
      que NO tienen reflejo en ninguna skill y por tanto la IA nunca
      las genera bien?
    - ¿Los comandos artisan de mercado (MarketImport, MarketAlerts,
      MarketExport, ImportValuation, MarketFreshness) tienen
      contraparte en las skills o se operan a ciegas?

D10. RIESGOS DE MODELO DE NEGOCIO (lo que NO se ve en el código).
     - ¿Algún texto podría interpretarse como que JJ Import Motors
       VENDE coches o DA garantía? (A32 — riesgo legal alto) — en
       las 3 skills.
     - ¿Alguna mención a "stock", "disponible", "última unidad" en
       sitio no permitido? (A24 + A32).
     - ¿Hay alguna afirmación sobre homologación/ITV que no aplique
       a la realidad 2026 (post-Ley 11/2022)?
     - ¿Las reglas RGPD/AVISO LEGAL son robustas? (especialmente en
       la ficha cliente que se comparte por WhatsApp).

D11. SINCRONIZACIÓN DRIVE TRANSVERSAL Y CONSISTENCIA MULTI-CANAL.
     - Lee primero docs/SYNC-DRIVE-PLAN.md (es el CONTRATO que
       fija el diseño). La auditoría verifica si la realidad
       coincide con el plan, fase por fase.
     - ¿La skill A (estudio-mercado) está subiendo a Drive carpeta
       "00 Estudios de mercado"? ¿Y sus MDs por marca?
     - ¿La skill C (ecommerce-tuning) tiene un protocolo de subida
       para "08 E-commerce" definido o sigue siendo un gap?
     - ¿El panel Laravel tiene cliente Google
       (google/apiclient / flysystem-google-drive)? ¿Hay un canal
       inverso (webhook Drive → re-leer plantilla → regenerar ZIP)?
     - Módulo Drive HOY (skill B): ¿usa base64Content (correcto) o
       content (corrupto)? ¿Maneja los duplicados en "actualizar" o
       crea copia nueva cada vez? ¿Respeta las 2 carpetas canónicas
       "07 Vehículos" y "06 CRM y clientes"? ¿Está versionado en
       .ai/rules/drive.md o vive solo dentro de la skill?
     - Métricas: ¿se mide la tasa de éxito de subidas? ¿Los
       duplicados por mes? (SYNC-DRIVE-PLAN.md §5).

D12. FLUJOS DE TRABAJO (nueva dimensión — verifica los 8 flujos
     del mapa maestro arriba).
     - ¿Cada flujo (A, B, C, D, E, M, F-EM, F-GM) tiene:
         □ Trigger/keyword reconocida en la skill correspondiente
         □ Plantilla en el sitio correcto
         □ Generador que la usa sin saltarse secciones
         □ Validador(es) asociado(s)
         □ Nombre de fichero canónico (NO inventado)
         □ Carpeta de salida pactada
         □ Política Drive (sí/no, qué carpeta)
         □ Riesgos críticos documentados
     - ¿La tabla "entregables.md" está sincronizada con SKILL.md y
       con las reglas .ai/rules? Si SKILL.md dice otro nombre de
       fichero que entregables.md, ese SKILL.md HAY que corregirlo.
     - ¿Hay un flujo huérfano (la IA hace X sin que esté en la tabla)?
       → Listarlo en "Flujos que faltan".

═══════════════════════════════════════════════════════════════════
FORMATO DE SALIDA (obligatorio — así entrego yo el informe)
═══════════════════════════════════════════════════════════════════

Genera TRES documentos Markdown en c:\laragon\www\importnexcore\docs\:

  1. AUDITORIA_ESTUDIO_MERCADO_<YYYY-MM-DD>.md
  2. AUDITORIA_IMPORTACION_VEHICULOS_<YYYY-MM-DD>.md
  3. AUDITORIA_DRIVE_<YYYY-MM-DD>.md (resultado de D11)

(Opcionalmente un cuarto: AUDITORIA_ECOMMERCE_TUNING_<YYYY-MM-DD>.md
si la skill C está lo bastante madura; si no, déjalo como
"pendiente de maduración" en el informe 1).

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
  Lista honesta de lo que YA está bien.

  ## 📋 Pendiente de tu OK (decisiones de negocio, no técnicas)
  Cambios que requieren aprobación humana: instalar/desinstalar
  paquetes, cambiar reglas duras, tocar dinero, modificar plantilla.

  ## 🎯 Resumen ejecutivo (3-5 líneas para WhatsApp)

Y al final, una TABLA RESUMEN transversal:

  | Hallazgo | Skill / Componente | Severidad | Fix |
  |----------|--------------------|-----------|-----|

Y un APÉNDICE "FLUJOS" (resultado de D12) en el documento 2:

  ## 📊 Mapa de flujos auditado (D12)
  | Flujo | Skill | Plantilla OK | Validador OK | Fichero OK | Carpeta OK | Drive OK | Notas |
  |-------|-------|--------------|--------------|------------|------------|----------|-------|

  ### 🆕 Flujos que faltan (descubiertos durante la auditoría)
  - <flujo>: <qué hace>, debería vivir en <skill>, necesita
    plantilla en <ruta>, validador <qué checks>.

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
  del fichero; si necesitas el panel, usa el navegador con capturas.
- NO leer 50 archivos por sesión. Lee el SKILL.md → los citados →
  1-2 archivos adicionales POR hallazgo confirmado. Contexto es
  dinero; sé quirúrgico.
- NO duplicar lo que la auditoría 12-sep, la ronda-4 y el SYNC-DRIVE-
  PLAN ya arreglaron o definieron. Si un hallazgo está en
  AUDITORIA_estudio-mercado_2026-09-12.md §🔴1, mencionalo SOLO si ha
  vuelto a aparecer.
- NO contradecir 03-informes/entregables.md. Si lo que dice SKILL.md
  no coincide con esa tabla, el bug está en SKILL.md (marcado como
  hallazgo 🔴 con severidad alta).
- NO escribir en C:\Users\jacar\Desktop\JJImportMotors\ ni en
  .claude/skills/ (no tienes acceso a mi disco en sesión normal).
  Drive es el único canal de salida para tus propuestas de informe.

═══════════════════════════════════════════════════════════════════
ENTREGABLE FINAL
═══════════════════════════════════════════════════════════════════

Imprime al terminar:

  === AUDITORÍA COMPLETADA ===
  Documentos:
    - docs/AUDITORIA_ESTUDIO_MERCADO_<YYYY-MM-DD>.md
    - docs/AUDITORIA_IMPORTACION_VEHICULOS_<YYYY-MM-DD>.md
    - docs/AUDITORIA_DRIVE_<YYYY-MM-DD>.md
    - (opcional) docs/AUDITORIA_ECOMMERCE_TUNING_<YYYY-MM-DD>.md
  Resumen: X críticos · Y importantes · Z menores
  Pendiente de tu OK: N decisiones
  Flujos auditados: 8/8 (A B C D E M F-EM F-GM)
  Flujos nuevos descubiertos: K (listados en doc 2)
  === APLICA EN VS CODE CON COPILOT — yo me encargo ===
```

---

## Notas para el usuario (Jacar)

- **Por qué este prompt es "profesional"** vs. uno de "audita esto":
  - Define ROL explícito (auditor senior, no asistente genérico).
  - Obliga a leer el contexto MÍNIMO antes de auditar (sin contexto
    cualquier auditoría es opinión).
  - **Mapa maestro de 8 flujos** (A, B, C, D, E, M, F-EM, F-GM) con
    skill dueña, plantilla, fichero, carpeta, validador, Drive y
    riesgo crítico POR FLUJO. Si el auditor mide algo que no está
    en el mapa, está midiendo mal.
  - 12 DIMENSIONES concretas y disjuntas (no "audita todo"), incluida
    D11 (Drive) y D12 (flujos) que faltaban en rondas previas.
  - FORMATO de salida idéntico a las 2 auditorías previas → 3-4 MDs
    integrables con `AUDITORIA_*.md` ya existentes y comparables.
  - Lista LO QUE NO DEBE HACER (anti-patrones del auditor), incluida
    la regla "NO contradecir 03-informes/entregables.md".
  - Pide ENTREGABLE explícito (Claude Desktop te dice dónde dejó
    los MDs).

- **Sobre los 3 flujos que más usas** (los del usuario):
  1. **Investigar una unidad** = Flujo A (UNIDAD).
  2. **Estudio de mercado por modelos** = Flujo B o C de
     importacion-vehiculos PERO el "estudio" en sí = F-EM si va por
     marca, o F-GM si va por segmento. (Tu memoria dice "normlque
     usamos es suvs de audi o familiares de vw" — eso es F-GM:
     estudio por segmento, informe por cada marca dentro del
     segmento).
  3. **Estudio de mercado por segmento** = F-GM (informes separados
     por marca, organizados por operación / modelos, como ya
     documenta SYNC-DRIVE-PLAN.md §1.1).

  **¿Falta algo?** El prompt obliga al auditor a listar "Flujos
  nuevos descubiertos" si ve un workflow que no encaje en estos 8.
  Si se te ocurre uno que YO no he incluido, dímelo y lo
  añadimos antes de pasar el prompt.

- **Cómo lo uso en Claude Desktop (Opus 4.5 / Opus 4.1):**
  1. Abrir Claude Desktop con el workspace ImportnexCore y seleccionar
     **Opus 4.5** (o el Opus más alto disponible — NO Sonnet).
  2. Activar "Pro" / "Extended thinking" si la UI lo permite.
  3. Pegar el bloque desde `## Prompt` hasta el último ``` literal.
  4. Dejarle trabajar. Tardará ~30-60 min (Opus 4.5 con thinking
     extendido es el doble de lento que Sonnet, pero el triple de
     riguroso en D11/D12).
  5. Al terminar te dice dónde están los MDs. Tú los abres en
     VS Code y aplicas los fixes con Copilot (o me los pasas).
  6. **Si en el futuro aparece Opus 5.5 o superior:** este prompt
     sigue válido tal cual. Lo único que cambia es el "Modelo" en
     la tabla de arriba.

- **Cadencia recomendada:** 1 vez al trimestre o tras 5+ cambios
  mayores en cualquier skill. No más (las auditorías tienen coste
  de contexto alto).