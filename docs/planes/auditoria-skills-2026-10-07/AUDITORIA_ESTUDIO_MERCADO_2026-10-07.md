# Auditoría de `estudio-mercado` (y resumen de `ecommerce-tuning`)

**Fecha:** 7-oct-2026 · **Versión auditada:** estudio-mercado 0.5.0 (copia de la cuenta de Claude; el `SKILL.md` no lleva campo `version` y contiene reglas del 7-oct que no están en el CHANGELOG) · copia de tu Desktop: 0.4.6 · **Versión corregida propuesta:** 0.5.1 (ver §Fixes)

> **Qué he podido leer y qué no (importante):**
> - ✅ Leído: copia de las 3 skills que usa Claude (importacion-vehiculos 3.10.5, estudio-mercado 0.5.0, ecommerce-tuning 0.1.0), tu carpeta `Desktop\JJImportMotors` (informes reales, zips, `datos_mercado.json`, HANDOFF, memoria), el Drive (con el conector) y la auditoría del 12-sep.
> - ❌ No accesible desde esta sesión: el repo `C:\laragon\www\importnexcore` (panel Laravel, comandos artisan, `.ai/rules/`, `docs/SYNC-DRIVE-PLAN.md`, `docs/AUDITORIA_ronda4`, `scripts/sync-desktop.ps1`, `estudio-mercado/scripts/generar_guia_marcas.py`, `docs/ecommerce.md`). Lo que dependa de eso lo marco como **NO VERIFICABLE** y no lo he dado por bueno ni por malo.
> - No he tocado ningún archivo de tu disco ni de las skills. Todo es lectura y propuesta.

---

## 🔴 Hallazgos críticos

### EM-C1 · El IVA fantasma y el desglose inventado de los 1.500 € NO se corrigieron (o han vuelto)

- **Descripción:** la auditoría del 12-sep (🔴1 y 🟠2) dijo que se corregía en la 0.4.0: "IVA de importación del 21 %" fuera, y los 1.500 € presentados como "1.129 € reales + ~371 € de colchón". Hoy el texto sigue en las dos copias que he podido abrir.
- **Evidencia:**
  - `estudio-mercado/informe_mercado.md` (0.5.0): l.166 "1.500 € de gastos fijos (1.000 € transporte + 200 € ITV + 300 € gestoría/ausfuhr). IVA de importación aparte"; l.253-255 "**IVA de importación** (21% sobre precio + transporte + seguro) … Orden de magnitud todo incluido: **+3.500 € a +5.500 €**".
  - `estudio-mercado/SKILL.md` l.392-400: "cifra redonda de 1.500 € (1.000 + 200 + 300)… Cuando hay unidad concreta se calcula IVA + IEDMT exacto".
  - Zip de tu Desktop `estudio-mercado.skill.zip` (0.4.6): `informe_mercado.md` l.428 sigue con la frase literal que la auditoría del 12-sep marcó como falsa ("21% sobre el valor en aduana… como particular se paga"). El fix nunca llegó a un zip entregado.
  - **Los 19 informes de marca entregados entre el 30-sep y el 6-oct repiten "IVA de importación aparte" 2 veces cada uno** (conteo con grep; el VW del 7-oct, rehecho, no lo repite).
  - Fuente que manda (`importacion-vehiculos/04-negocio/costes.md` §IVA): el particular sin NIF-IVA paga el precio alemán con su IVA dentro y **no paga IVA español**; solo se liquida el 21 % si el coche tiene <6 meses **o** <6.000 km (regla 6/6000). Contrastado con una fuente externa (gestoria247.com, "al menos uno de los dos criterios") — fuente secundaria, no BOE.
  - El desglose 900 + 114 + 115 = 1.129 € es el de `costes.md` y el de `datos_mercado.json` (`costes_referencia`). Los componentes 1.000 / 200 / 300 no aparecen en ninguna otra fuente del sistema.
- **Impacto:** las cifras de ahorro de los informes no están contaminadas (usan 1.500 € planos), pero el texto que lees sugiere un coste de "+3.500 a +5.500 €" con un IVA que normalmente no se paga. Puede descartar a ojo modelos buenos. Además, "1.500 €" significa dos cosas distintas en el sistema (gastos fijos del estudio frente a honorarios 1.500-2.250 € de `costes.md`).
- **Fix concreto** (sustituir §6 de `informe_mercado.md` y la nota ¹ de la tabla; y el párrafo de `SKILL.md` l.392-400):

```markdown
## 💶 DESGLOSE DE LOS 1.500 € DE GASTOS FIJOS

| Concepto | Estimado | Fuente |
|---|---:|---|
| Transporte Alemania → Huelva | 900 € | 04-negocio/costes.md |
| Ausfuhrkennzeichen + seguro | 114 € | 04-negocio/costes.md |
| ITV importación + tasas DGT | 115 € | 04-negocio/costes.md |
| Colchón (variación de transporte e imprevistos) | 371 € | decisión tuya 23-ago |
| **TOTAL** | **1.500 €** | |

**No incluye:** honorarios, IEDMT (se calcula por unidad con el CO₂ de su ficha).
**IVA español: no se paga** al comprar un usado a un particular alemán sin NIF-IVA,
salvo que el coche tenga menos de 6 meses o menos de 6.000 km (regla 6/6000).
```

- **Test que lo cazaría:** `check_informe_mercado.py` (ver EM-I7) con el patrón `IVA de importaci|valor en aduana|1\.000 € transporte|200 € ITV|300 € gestor` → 🔴 si aparece fuera de una negación.

### EM-C2 · La regla dura del 7-oct (nada de `Versions[` ni `q=`) no está en 6 sitios y 16 de 19 informes la incumplen

- **Descripción:** hoy `SKILL.md` (l.226-237, 273) prohíbe filtrar por texto libre y dice que `versions_es` y `q_de` van "SIEMPRE VACÍOS". El resto del sistema sigue enseñando lo contrario.
- **Evidencia (contradicciones):**
  - `estudio-mercado/informe_mercado.md` l.301-303: "el campo `versions_es` mete `Versions[0]`… plan B `q=`/`Versions[0]`".
  - `importacion-vehiculos/scripts/fuentes.py`: docstring (campos `versions_es`, `q_de`; opciones `--versions-es`, `--q-de`) y código ("modelo=Versions[0] (texto libre)" cuando no hay ID).
  - `importacion-vehiculos/06-reglas/anti_patrones.md` l.66 (plan B con `Versions[0]`), `02-flujos/playbook_filtrado.md` l.382-383 ("`Versions[0]` queda como fallback"), `03-informes/informe_busqueda.md` l.131 (URL de ejemplo con `Versions[0]=Tiguan`).
  - `datos_mercado.json` (Desktop): 8 URLs con `Versions[` y 6 con `q=`.
  - **Informes entregados** (conteo sobre `Desktop\JJImportMotors\informes\`): con `Versions[` o `q=` → 16 de los 19 anteriores al 7-oct (Audi ×2, BMW ×2, Cupra familiar, Ford familiar, Honda, Hyundai, Mercedes ×2, Mini, Renault, SEAT ×2, Škoda, VW familiar). Solo el VW compacto del 7-oct está limpio.
  - Ejemplo vivo: `audi_compacto_deportivo_2026-10-06.md` mide "A3 S line" con `Versions[0]=S+line` (ES, 650 anuncios) y `q=S-line` (DE, 3.976 anuncios) y calcula un "ahorro real 1.010 €" comparando un 1.0/1.6 de 116 cv alemán con un diésel español; el propio informe lo llama "orientativo".
- **Impacto:** es exactamente lo que dijiste el 7-oct (mezcla de generaciones, A1 + A19): decisiones de "buscar o no unidades" sobre comparaciones no equivalentes.
- **Fix:**
  1. `fuentes.py`: quitar `versions_es`/`q_de` de `--spec` y de las opciones; si falta el ID de modelo, abortar con error (no plan B). Añadir `combustible` por `--spec` (hoy solo existe `--combustible` global, así que un informe con versiones diésel y gasolina no puede fijarlo por versión; `SKILL.md` l.273 ya lo exige).
  2. Borrar las frases de plan B de `informe_mercado.md`, `anti_patrones.md`, `playbook_filtrado.md` e `informe_busqueda.md`; cambiar el ejemplo de `informe_busqueda.md` l.131.
  3. Regenerar los 19 informes (o marcarlos como "sustituidos por el VW del 7-oct" como ya hace ese informe).
- **Test:** el mismo `check_informe_mercado.py`: `Versions\[|[?&]q=` → 🔴; y un test que ejecute `fuentes.py --spec` con un modelo fuera de catálogo y espere código de salida ≠ 0.

---

## 🟠 Importantes

### EM-I1 · Los PDF salen con defectos de contexto y de lectura (D13/D14)
Revisé `audi_compacto_deportivo_2026-10-06.pdf` (4 páginas) página a página:
- **La tabla resumen no está en la página 1** (la portada deja media página vacía; la tabla cae en la 2).
- Título y línea de metadatos aparecen **3 veces** en la portada, y se cuela un `# Audi A3 / S3 / RS3…` en crudo.
- **Pie roto:** `mercado_pdf.py` l.159-166 usa `footer {position: fixed}` pero en el PDF sale **dentro del texto, a mitad de página**, pegado ("confidencialGenerado") y solo dice "JJ Import Motors · confidencial — Generado dd-mm-aaaa · skill importacion-vehiculos". No lleva marca/segmento, fecha de **medición**, nº de anuncios ni versión de la skill → una página impresa suelta no se entiende (regla D14).
- **URLs tapadas:** los enlaces salen como "abrir en mobile.de ↗"; impresa, la hoja pierde la URL.
- La caja del CHECKLIST lleva la etiqueta "RESUMEN PARA COPIAR" y se imprime el autochequeo interno; la sección "Archivo generado" imprime rutas del repo (jerga).
- **Fix:** (a) pasar a `mercado_pdf.py` marca/segmento/fecha-medición/nº-anuncios y rellenarlos en el pie; (b) como el pie fijo falla en Chrome headless, usar `@page { @bottom-center { content: … } }` (**VERIFICAR** versión de Chrome; si no, generar con Browsershot, que ya usa Laravel y admite `footerHtml`); (c) mostrar la URL completa en texto (el CSS ya tiene `word-break`); (d) quitar del PDF las secciones "Archivo generado" y "Checklist"; (e) cortar duplicados del H1.
- **Test:** `pdftotext` del PDF generado: el pie debe contener marca y fecha en cada página; la palabra "Resumen" no debe aparecer dentro del checklist.

### EM-I2 · Cifras derivadas al euro (regresión de la regla de horquilla del 12-sep)
La auditoría del 12-sep (🟡3) fijó redondeo a la centena. Ni `SKILL.md` ni `informe_mercado.md` contienen hoy la regla (`grep -i "centena|horquilla|redond"` → 0), y los informes dan "Puesto en Huelva 18.450 €" y "ahorro 7.050 €". Los suelos de listado pueden ir exactos (son datos); lo calculado con un coste estimado, no. **Fix:** añadir la regla a la plantilla (redondeo a 100 € en "puesto en Huelva" y "ahorro real") y al validador.

### EM-I3 · Comparabilidad: la plantilla deja pasar comparaciones no equivalentes (A1 + A19)
- Los 19 informes miden con "sin acotar km ni precio" y, según el propio Audi, "solo vendedores en Alemania" (`cn=DE`) únicamente en parte de las mediciones: dentro de un mismo informe hay mediciones con y sin ese filtro.
- No hay filtros de generación (8V/8Y), carrocería ni combustible por versión en esos informes; el VW del 7-oct sí (pre/post restyling, kilómetros homogéneos, mediana por tramo).
- **Fix:** regla de bloqueo en la plantilla: "no calcular ahorro si cv, combustible, carrocería y rango de años no coinciden entre DE y ES; poner 'no comparable' en su lugar". Añadirla al validador.

### EM-I4 · No hay modo "estándar" definido: sondeo conciso (0.5.0) frente a profundidad completa
`informe_mercado.md` 0.5.0 define 9 secciones sin rotación, demanda, hueco neto ni IEDMT, mientras que el esquema de `datos_mercado.json` (`schema_datos_mercado.md` l.122) sigue exigiendo `hueco_neto_pct`, rotación y demanda. El VW del 7-oct se entregó con "profundidad completa" (66 KB, secciones "DEMANDA Y ROTACIÓN" y "GASTOS Y TRIBUTOS" que no existen en la plantilla, sin PDF). Resultado: dos formatos en el mismo mes y ninguna regla que diga cuándo toca cada uno. Ver 📋 decisión 1.

### EM-I5 · PDF: la regla escrita dice "solo .md" y en tu disco hay PDF de casi todo
`entregables.md` §2 y `estudio-mercado/SKILL.md`: "solo `.md`; PDF únicamente si el usuario lo pide". En `Desktop\…\informes\` hay `.md` + `.pdf` en 19 de los 20 informes de marca, y el propio informe lista "…pdf (generado con mercado_pdf.py)". O la regla está obsoleta o la práctica se desvía. Ver 📋 decisión 2.

### EM-I6 · El mapa `datos_mercado.json` está caducado y en tres sitios distintos
- Desktop: 71.131 B, `actualizado` hasta el 13-sep; `refrescar_antes_de`: showstoppers 31-ago, alta_rotación 7-sep, gemas 14-sep → **todo vencido por su propia regla** (regla dura de caché de `SKILL.md` l.439).
- Drive (`Infomes/`, subido el 30-sep): 100.527 B, modificado el 28-sep → **no es la misma versión que Desktop** y esa ruta no está en el contrato "ruta dual" (`SKILL.md` l.38, 472).
- Ruta espejo del repo: NO VERIFICABLE.
- El mapa tiene 14 marcas; los informes cubren además Porsche, Mini, Honda, Škoda, Alfa Romeo, Jaguar, Land Rover, Maserati. Los estudios del 16-sep al 7-oct no se han importado (pendiente del HANDOFF 16-sep). Consecuencia: el PASO 0 de `importacion-vehiculos` lee un mapa de hace 3-4 semanas.
- **Fix:** decidir un máster único (📋 decisión 4) y añadir el paso "importar el informe al mapa" al cierre de cada estudio (ver §Flujos que faltan en el doc 2).
- **Nota menor:** `coste_importacion_estimado` toma los valores 2.929 / 1.479 / 1.129; no son tres modelos de coste: son 1.129 + IEDMT estimado (1.800 / 350 / 0). El nombre del campo engaña; documentar en el esquema.

### EM-I7 · El estudio de mercado no tiene validador y su checklist se escribe a sí mismo
La plantilla termina con "✅ Sin fichas… ✅ Ninguna URL usa Versions[": lo marca el mismo agente que escribe. Las reglas mecánicas (EM-C1, EM-C2, EM-I2, EM-I3, nombre de fichero, 9 secciones, §FUENTES con conteo y fecha) se pueden comprobar con un script de 80 líneas. Propuesta (pendiente de tu OK; no instala nada, solo stdlib):

```python
# estudio-mercado/scripts/check_informe_mercado.py  (extracto)
import re, sys, pathlib
PROHIBIDOS = [
  (r"IVA de importaci|valor en aduana",           "IVA fantasma (costes.md §IVA, regla 6/6000)"),
  (r"1\.000 € transporte|200 € ITV|300 € gestor", "desglose 1.500 inventado (usar 900+114+115+371)"),
  (r"Versions\[|[?&]q=",                          "texto libre prohibido (regla 7-oct)"),
]
NOMBRE = re.compile(r"^[a-z0-9-]+_[a-z]+_[a-z]+_\d{4}-\d{2}-\d{2}\.md$")
def main(p):
    t = pathlib.Path(p).read_text(encoding="utf-8"); fallos = []
    for pat, msg in PROHIBIDOS:
        if re.search(pat, t): fallos.append(f"🔴 {msg}")
    if not NOMBRE.match(pathlib.Path(p).name): fallos.append("🔴 nombre no canónico")
    if len(re.findall(r"^## ", t, re.M)) != 9: fallos.append("🟠 no son 9 secciones H2")
    for url in re.findall(r"→ \*\*(\d[\d.]*) anuncios\*\* \(medido", t): pass
    if not re.search(r"anuncios\*\* \(medido \d+ de \w+ de 2026\)", t): fallos.append("🔴 §FUENTES sin conteo+fecha (A33)")
    print("\n".join(fallos) or "✅ sin hallazgos"); return 1 if any("🔴" in f for f in fallos) else 0
sys.exit(main(sys.argv[1]))
```
- **Test de regresión:** correrlo sobre los 19 informes actuales: debe dar 🔴 en 19/19 (IVA) y en 16/19 (texto libre). Si da 0, el script no sirve.

### EM-I8 · Drive: la skill no sabe nada de Drive y "00 Estudios de mercado" no existe
`grep -i drive` en `estudio-mercado/` → 0 resultados. En Drive no hay carpeta "00 Estudios de mercado" (búsqueda por título sin resultados). Lo que hay es `Infomes` (con errata) con una copia masiva de tu Desktop del 30-sep. Detalle en `AUDITORIA_DRIVE_2026-10-07.md`.

---

## 🟡 Menores

- **Versionado:** `SKILL.md` sin `version:` en el frontmatter; el CHANGELOG llega a 0.5.0 (28-sep) y `SKILL.md` ya cita "reforzada 7-oct-2026" sin entrada; tu prompt habla de 0.5.1 y tu Desktop tiene 0.4.6. Hoy nadie puede decir con certeza qué versión corre en cada sitio.
- **Contradicción interna sobre el 1.500:** `SKILL.md` l.392 "usar SIEMPRE 1.500 €" frente a `informe_mercado.md` l.19 "PREGUNTAR… NO asumir 1.500 €".
- **Relleno:** la plantilla permite "Sin comparables todavía" y "Sin trampas detectadas" (l.17, 94-95, 126-127); si es el 80 % de la sección, mejor omitirla (regla D13).
- **Nombres de fichero fuera de canon:** 9 informes del 16-sep usan `<marca>_suv-deportivos_…` (canon: `<marca>_suv_deportivo_…`); `informes/mercado/00_indice_compacto_deportivo_…` no está en la tabla de nombres; `informes/descubrimiento/hatchback-B-C_2026-09-26.md` no sigue `informe_modelos_…` (ver doc 2).
- **`generar_guia_marcas.py` no está en la copia de la cuenta** (0.5.0 no trae carpeta `scripts/`). Existen 14 guías en `Desktop\…\guias-busqueda\` (14 marcas, 6-oct) frente a los "23 slugs" de tu prompt: no puedo comprobar el diccionario `IDENT`. NO VERIFICABLE.
- Precisión de la regla D2: la **Orden HAC/1501/2025** (precios medios 2026) y los **tipos del IEDMT** (0 / 4,75 / 9,75 / 14,75 %) están confirmados solo por una fuente secundaria (gestoria247.com); Canarias tiene tipos distintos y "algunas comunidades pueden subir el tipo" — Andalucía no aparece como distinta en esa fuente. Falta cita BOE en `costes.md`.

---

## ✅ Verificado correcto (no tocar)

- **Catálogo de IDs de portal:** `references/mobile-de-ids.json` es **idéntico byte a byte** en `estudio-mercado` e `importacion-vehiculos` (28.502 B).
- **Convención `<marca>_<segmento>_<enfoque>_<fecha>.md` en `informes/<marca>/`:** la cumplen los 19 informes posteriores al 16-sep.
- **§FUENTES con conteo y fecha** en todos los informes revisados; mobile.de en ruta `/es/` y `cn=DE` desde el 6-oct.
- **9 secciones H2** en los 20 informes de marca (el VW del 7-oct cambia los títulos).
- **`fuentes.py`:** SUV = `ArrBodyType=6` y monovolumen = 5 (el pendiente del HANDOFF 16-sep está resuelto).
- **`datos_mercado.json`:** sin mojibake (0 coincidencias de `Ã.`); `costes_referencia` limpio de IVA (1.129 € = 900+114+115).
- **Regla 6/6000** de `costes.md` ("<6 meses **o** <6.000 km") coincide con la fuente externa consultada.
- **Jerarquía de titulares:** máximo 3 niveles en las plantillas de informe.
- **Aviso:** el informe VW del 7-oct (rehecho con filtros de cv, años y km homogéneos por generación) es el mejor ejemplo del sistema y está limpio de `Versions[`/`q=`.

---

## 📋 Pendiente de tu OK (decisiones de negocio)

1. **¿Qué es el informe estándar?** Sondeo conciso de 9 secciones (0.5.0) o profundidad completa (bruto + neto con gastos e IEDMT + rotación + demanda, como el VW del 7-oct). Hoy conviven.
2. **¿PDF por defecto en los estudios de mercado?** La regla escrita dice no; en tu disco hay PDF de casi todos.
3. **Colchón de 371 €:** ¿se mantiene el 1.500 € redondo como cifra del estudio, ahora con desglose 900+114+115+371?
4. **Mapa único:** ¿qué copia de `datos_mercado.json` es la máster (Desktop, repo o Drive) y se descartan las otras?
5. **Validador nuevo** `check_informe_mercado.py` (solo stdlib, no es un paquete). Marcado por si prefieres revisarlo antes.
6. **Regenerar o archivar** los 19 informes con texto libre (`Versions[`/`q=`).

---

## 🧰 `ecommerce-tuning` 0.1.0 — resumen (pendiente de maduración, sin 4.º documento)

La skill es nueva (16-sep), sin scripts, con memoria vacía y sin ningún uso registrado, así que no genero documento aparte. Hallazgos ya visibles:

- **EC-1 (🟠) El Score del filtro no tiene unidades.** `Score = (Engagement Rate × Margen Neto %) / (anuncios competidores + 1)` con umbral ≥ 7,5. Si el engagement va en decimal (0,05) y el margen en % (40), el máximo posible es 2/(N+1) y nada pasa nunca; si ambos van en %, casi todo pasa. Hay que fijar unidades y mostrar un ejemplo numérico. SIN FUENTE para el umbral 7,5 (es decisión propia).
- **EC-2 (🟠) No es autosuficiente.** Delega en `docs/ecommerce.md` del repo (§4 legalidad, §9 ficha, §10 ads, §14.2 "single source of truth" del filtro). Ese archivo no viaja en el ZIP: en Cowork/Desktop la skill no puede leer su "fuente única". NO VERIFICABLE el contenido.
- **EC-3 (🟠) Cifras sin fuente:** CPA (Meta 15-25 €, TikTok 8-15 €, Google 10-18 €), comisión de pasarela (~2 % + 0,25 €), devoluciones 5 %, margen ≥ 40 %, ratio ≥ 3×. SIN FUENTE — VERIFICAR.
- **EC-4 (🟠) Reglas de copy y legales que sí existen en la skill de coches no están aquí:** no hay equivalente a A24 (superlativos), A25 (emojis), A28/A29 (pega honesta). `E12` cubre promesas falsas, nada más. En este negocio **la tienda sí es el vendedor legal** (a diferencia de JJ Import Motors y su A32): garantía legal de 3 años (RDL 7/2021, vigente desde 1-1-2022, confirmado en consumo.gob.es), desistimiento, y obligaciones de producto/aduana que conviene revisar con gestor: Reglamento de seguridad general de productos (aplicable desde dic-2024) y el régimen de IVA/aranceles de paquetes <150 € en 2026 — **VERIFICAR**, no lo he comprobado. La ficha (Flujo F) dice "envío y garantía" sin decir quién garantiza ni cuánto.
- **EC-5 (🟡) Sin protocolo de Drive** ("08 E-commerce" no existe en Drive) y la skill **no figura en `sync.manifest.json`** de tu Desktop (que solo lista importacion 3.9.3 y estudio-mercado 0.4.0), aunque `ecommerce-tuning.skill.zip` (17-sep) sí está en tu carpeta.
- ✅ Bien: E1 (sin stock), E2 (aprobación humana) y la cascada con parada entre P→F→M son coherentes con la filosofía de tramitador puro; `SKILL.md` de 10,5 KB (el objetivo era <8 KB).

---

## 🎯 Resumen ejecutivo (WhatsApp)

El IVA fantasma del 21 % y el desglose 1.000/200/300 que se "arregló" el 12-sep **sigue en la skill y en los 19 informes de la semana pasada**; el arreglo nunca llegó a un zip. La regla del 7-oct contra `Versions[`/`q=` solo está en un sitio y 16 de 19 informes la incumplen. Los PDF salen con la tabla en la página 2 y el pie roto. El mapa `datos_mercado.json` está caducado y hay una copia distinta en Drive. Propongo un validador de 80 líneas que habría cazado todo esto en segundos.

---

## 📊 Tabla resumen

| Hallazgo | Skill / Componente | Severidad | Fix |
|---|---|---|---|
| EM-C1 IVA fantasma + desglose 1.500 inventado | estudio-mercado `informe_mercado.md`, `SKILL.md`; 19 informes | 🔴 | Sustituir §6 y nota ¹; regenerar; validador |
| EM-C2 `Versions[`/`q=` en 6 sitios y 16/19 informes | estudio-mercado, `fuentes.py`, anti_patrones, playbook | 🔴 | Quitar plan B, abortar sin ID, regenerar |
| EM-I1 PDF: tabla en p.2, pie roto, URLs tapadas | `mercado_pdf.py` | 🟠 | Pie con contexto, URL visible, quitar checklist |
| EM-I2 Cifras al euro | plantilla | 🟠 | Redondeo a 100 € + validador |
| EM-I3 Comparación no equivalente | plantilla / informes | 🟠 | Regla "no comparable" |
| EM-I4 Sin modo estándar | `informe_mercado.md` vs esquema | 🟠 | Decisión 1 |
| EM-I5 PDF sí/no | entregables.md vs disco | 🟠 | Decisión 2 |
| EM-I6 Mapa caducado, 3 copias | `datos_mercado.json` | 🟠 | Máster único + importar al cierre |
| EM-I7 Sin validador | estudio-mercado | 🟠 | `check_informe_mercado.py` |
| EM-I8 Sin Drive / sin "00 Estudios" | estudio-mercado / Drive | 🟠 | Ver doc Drive |
| EC-1…EC-4 | ecommerce-tuning | 🟠 | Unidades del Score, fuentes, legal |
| Versionado, relleno, nombres, `generar_guia_marcas` | varios | 🟡 | Limpieza |
