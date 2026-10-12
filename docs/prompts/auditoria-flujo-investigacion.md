# Prompt — Auditoría del flujo de investigación (reutilizable)

> Usar en Claude Desktop cuando la investigación de coches consuma demasiado
> contexto, haya que regenerar ZIPs, o el flujo no cumpla la skill a la primera.

---

## Prompt

Actúa como auditor senior del sistema JJ Import Motors (skill `importacion-vehiculos`
v3.9.13+). Haz una auditoría COMPLETA del flujo de investigación de un coche y
corrige lo que encuentres. Metas en orden de prioridad:

1. **Eficiencia de contexto (la más importante).** Cada búsqueda de un coche está
   llenando mi memoria de Claude demasiado rápido. Audita y corrige:
   - Qué archivos se cargan por sesión y su peso real en KB. El SKILL.md debe
     quedarse solo en reglas operativas; el detalle vive en compañeros que se
     leen SOLO si el flujo lo exige.
   - Presupuesto de lectura por flujo: SKILL.md + máximo 2-3 compañeros.
     Prohibido leer directorios enteros "por si acaso".
   - Los textos de anuncios (descripciones literales) van a FICHERO, nunca
     pegados al contexto: del anuncio solo suben cifras y decisiones.
   - Nada de re-leer lo ya leído en la misma sesión: una regla consultada
     queda aplicada, no se vuelve a consultar.

2. **Cero regeneraciones de ZIP.** Si alguna vez hay que lanzar `empaquetar.py`
   más de una vez por coche, es un bug. Verifica:
   - Los validadores (`check_marketing.py`, `check_ficha_cliente.py`) corren
     ANTES de comprimir: un hallazgo 🔴 aborta SIN crear ZIP.
   - Las fotos ya descargadas quedan en caché (`.fotos_cache/`): regenerar no
     vuelve a tocar la red ni re-dispara bloqueos 403 de mobile.de.
   - Los errores de validación citan el bloque y el archivo exactos a corregir.

3. **Cumplimiento de la skill a la primera.** Recorre el flujo A completo
   (briefing → navegación real → 7 fuentes → comparables con URL → veredicto →
   fotos con álbum COMPLETO → esqueletos → ZIP) y verifica que cada regla dura
   (A1-A33, 1b álbum completo, cobertura mínima ≥3 candidatos, enlaces en todo)
   tiene un mecanismo que la impone, no solo prosa. Si una regla solo existe
   como texto, propón el mecanismo (check, validador, orden de ejecución).

4. **Precisión antes que velocidad, sin re-trabajo.** Detecta dónde el flujo
   puede "hacerlo mal rápido" (improvisar estructura de informe, filtrar por
   campo de texto en Coches.net, saltarse el PASO 0 de caché) y refuerza el
   guardián correspondiente.

5. **Si algo es ambiguo o faltan datos, PREGÚNTAME antes de asumir.** Una
   pregunta cuesta menos que una investigación repetida.

Entregables de la auditoría:
- Tabla de hallazgos: nº, severidad (🔴/🟠/🟡/🟢), archivo:línea, causa, fix.
- Fixes aplicados con tests o verificación de ejecución.
- Antes de terminar: bump de versión de la skill + entrada de CHANGELOG +
  regenerar el ZIP portable (`scripts/build-skill-zips.ps1`).

Contexto de la última auditoría (23-sep-2026, ya resuelto — no repetir):
fotos duplicadas por separador de path en Windows, retry anti-bot 403,
`FC_FOTOS` ausente de LISTAS, regla 1b álbum completo, validación pre-ZIP,
caché de fotos.
