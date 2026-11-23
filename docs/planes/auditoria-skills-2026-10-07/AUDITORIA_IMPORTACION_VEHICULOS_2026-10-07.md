# Auditoría de `importacion-vehiculos` (flujos A B C D E M) + mapa de flujos

**Fecha:** 7-oct-2026 · **Versión auditada:** importacion-vehiculos 3.10.5 (copia de la cuenta de Claude, 79 ficheros; `SKILL.md` de 96.484 bytes) · copia de tu Desktop: 3.9.10 · **Versión corregida propuesta:** 3.10.6 (ver "Pendiente de tu OK")

> **Límites de esta auditoría (léelo primero):**
> - ✅ Leído: la skill (`SKILL.md`, `03-informes/entregables.md`, `04-negocio/costes.md` y `riesgos.md`, `06-reglas/anti_patrones.md`, `scripts/empaquetar.py`, `check_marketing.py`, `check_ficha_cliente.py`, `check_avisos.py`, `fuentes.py`, `references/google_drive.md`), tu carpeta `Desktop\JJImportMotors` y el Drive.
> - ❌ **No accesible desde esta sesión:** el repo `C:\laragon\www\importnexcore` (panel Laravel, `.ai/rules/`, `docs/SYNC-DRIVE-PLAN.md`, `docs/AUDITORIA_ronda4`, `scripts/sync-desktop.ps1`, comandos artisan). Por eso **D9 (integración con el panel) queda parcial y lo marco como NO VERIFICABLE** donde dependa del repo. No lo he dado por bueno ni por malo.
> - No he tocado ningún fichero tuyo ni de las skills. No hay que instalar ni desinstalar ningún paquete para aplicar nada de esto.
> - No repito lo que ya arreglaron la auditoría del 12-sep, la ronda 4 y SYNC-DRIVE-PLAN, salvo donde he visto que **ha vuelto a aparecer** (lo digo en cada caso).
> - Cifras que no he podido contrastar con fuente: las marco **SIN FUENTE — VERIFICAR**.

---

## 🔴 Hallazgos críticos

### IV-C1 · La skill se contradice sobre si Claude entrega PDF de los informes de investigación

**En cristiano:** según la página que abra Claude, a veces "siempre PDF" y a veces "nunca PDF". El resultado depende del azar, y tú recibes unas veces un `.md` y otras un `.pdf` (o ambos).

- **Evidencia (citas literales):**
  - `03-informes/entregables.md` l.58: "**PDF del informe de búsqueda (v3.10.0)** | **misma base que el .md con extensión `.pdf`** — generado SIEMPRE con `scripts/mercado_pdf.py <informe>.md`; es el entregable que lee el operador".
  - **El mismo fichero**, l.93 y l.104-107: "**Markdown (`.md`) y UN solo fichero.** PDF, `.docx`, ZIP o cualquier otro formato **solo**…" · "PDF del informe de investigación | **nadie por defecto** | solo si el usuario lo pide" · "❌ No generar PDFs «porque queda mejor», ni una copia `.md` + otra `.pdf` del mismo trabajo".
  - `SKILL.md` l.918: "Claude genera los **PDFs de investigación** (búsqueda/unidad)" frente a `SKILL.md` l.1052: "**Claude NO genera PDFs por defecto**".
  - `CLAUDE.md` de tu Desktop y las instrucciones del proyecto de claude.ai: "Claude genera los PDFs de INVESTIGACIÓN… (plantilla `plantilla_pdf_marca.html`)".
  - `entregables.md` cabecera: "Este fichero es la ÚNICA fuente de verdad… manda esta tabla". Pero la tabla se contradice a sí misma (l.58 frente a l.104).
  - En tu disco existe `audi_compacto_deportivo_2026-10-06.pdf` junto a su `.md` (el PDF que rasterizé: p.2 con la tabla, URLs tapadas; ver `EM-I1` del doc 1).
- **Impacto:** viola la regla de "un solo fichero" cuando lo dice una parte, y produce PDFs sin enlaces clicables cuando lo dice otra. Es la decisión de formato más repetida del sistema y no tiene una respuesta única.
- **Fix concreto (pendiente de tu decisión, D-1):** elegir UNA política y propagarla a las 5 ubicaciones en el mismo commit (ver regla propuesta A37). Texto propuesto si eliges "solo `.md`, PDF únicamente si lo pides":

  ```markdown
  | PDF del informe de búsqueda / unidad / mercado | **solo si el usuario lo pide expresamente** (`scripts/mercado_pdf.py`) |
  ```
  y borrar la fila de l.58 de `entregables.md`; en `SKILL.md` l.918 cambiar "Claude genera los PDFs de investigación" por "Claude entrega `.md`; PDF solo a petición"; igual en `CLAUDE.md` y en las instrucciones del proyecto.
- **Test de regresión:** `grep -rn -i "SIEMPRE.*mercado_pdf\|genera los PDFs de investigaci" <skill> <Desktop>/CLAUDE.md` debe devolver 0 líneas tras el cambio.

---

### IV-C2 · `empaquetar.py` nunca valida `anuncio-portales.txt` (las reglas A26/A27 y los checks PT_* no se ejecutan)

**En cristiano:** el ZIP lleva tres piezas de marketing, pero el "revisor automático" solo mira dos. El anuncio para Coches.net/Milanuncios/Wallapop puede salir sin fecha de matriculación o con lenguaje de vendedor y el ZIP se empaqueta igual.

- **Evidencia:**
  - `scripts/empaquetar.py` l.2440-2443:
    ```python
    check_args = [
        ("check_marketing.py", _primer_existente("redes-sociales.txt", "ficha-publicitaria.txt")),
        ("check_ficha_cliente.py", _primer_existente("ficha-cliente.txt")),
    ]
    ```
    No hay ninguna entrada para `anuncio-portales.txt`, aunque `generar_anuncio_portales()` (l.1678) lo genera y `A23` (anti_patrones l.35) lo declara requisito de entrega.
  - `scripts/check_marketing.py` l.97 define el tipo `"portal": ["PT_RESUMEN", "PT_FICHA", "PT_ESTADO", "PT_AVISO"]` y l.600 el check A27 (fecha de 1ª matriculación en `PT_FICHA`/`PT_AVISO`). El validador **sabe** revisar portales; solo que nadie se lo pasa.
- **Impacto:** el único texto que va **público** (anuncio en portales) es el que no pasa por validador. Riesgo directo de A27 (anuncio inválido) y de A32 (lenguaje de vendedor o garantía, D10).
- **Fix concreto:**
  ```python
  check_args = [
      ("check_marketing.py", _primer_existente("redes-sociales.txt", "ficha-publicitaria.txt")),
      ("check_marketing.py", _primer_existente("anuncio-portales.txt")),   # NUEVO
      ("check_ficha_cliente.py", _primer_existente("ficha-cliente.txt")),
  ]
  ```
  Antes de fusionar, comprobar a mano que `check_marketing.py` detecta el tipo por los marcadores `[PT_*]` y no por el nombre del fichero (por la l.97 parece que sí, pero no lo he ejecutado).
- **Test de regresión:** generar un ZIP de prueba con `[PT_FICHA]` sin fecha `MM/AAAA` → `empaquetar.py` debe terminar con código ≠ 0 y listar "A27". Y otro con «llave en mano» en `[PT_AVISO]` → debe fallar por A32.

---

### IV-C3 · El ejemplo oficial de IEDMT de `costes.md` no reproduce con la fórmula que él mismo declara

**En cristiano:** el "caso modelo" que Claude copia para calcular el coste puesto en Huelva tiene un IEDMT mal calculado; el precio máximo que sale de él está inflado/deflactado.

- **Evidencia:** `04-negocio/costes.md` l.183-184:
  "IEDMT (con minoración art.69):   1.850 € (PVP 38.500€ × 47% × 9,75%)" y "↳ Sin minoración: 2.100 €".
  Recalculado con la fórmula de las l.69-76 del mismo fichero (coef. 47 % para 4-5 años; tipo 9,75 %; IVA 21 %):
  | Concepto | Cálculo | Resultado |
  |---|---|---:|
  | Valor de mercado | 38.500 × 47 % | 18.095 € |
  | **Sin minoración** | 18.095 × 9,75 % | **1.764 €** (el texto dice 2.100 €) |
  | Minoración art.69 | 18.095 × 0,3075 / 1,3075 | 4.256 € |
  | **Con minoración** | (18.095 − 4.256) × 9,75 % | **1.349 €** (el texto dice 1.850 €) |

  Además la línea "con minoración" repite la cuenta **sin** minoración (38.500 × 47 % × 9,75 % = 1.764), así que el ejemplo mezcla las dos. Y l.136 repite el 1.850 en `precio_max`.
- **Impacto:** esto es exactamente lo que `costes.md` l.99 prohíbe ("NUNCA estimar el IEDMT de oído"), pero en el propio ejemplo. Un informe que copie el patrón da un `precio_max` unos 500 € más bajo de lo que toca (o, si el IEDMT real es otro, un total que no cuadra con lo que calcula Laravel; la regla de l.116 marca `avisos` si hay >10 % de diferencia).
- **Fix concreto:** sustituir el bloque de l.183-196 y el de l.130-136 por:
  ```
  IEDMT (con minoración art.69):   1.349 €  (valor 38.500 × 47 % = 18.095; minoración 4.256; base 13.839 × 9,75 %)
    ↳ Sin minoración:              1.764 €  (18.095 × 9,75 %; el gestor fiscal elige)
  TOTAL = 28.900 + 900 + 114 + 95 + 20 + 1.349 + 2.000 = 33.378 €
  AHORRO = 36.850 − 33.378 = 3.472 € (9,4 %)
  precio_max = 36.850 × 0,88 − 900 − 114 − 115 − 1.349 − 2.000 = 32.428 − 4.478 = 27.950 €
  → Ofertar máximo 27.900 € (redondeo a 100 € hacia abajo)
  ```
  Y verificar contra el `Car::calculateIEDMT()` del panel (NO VERIFICABLE desde aquí) que usa la misma minoración, porque la fórmula "(IVA + tipo)/(1 + IVA + tipo)" es la que declara la skill y **no la he contrastado con el BOE** (SIN FUENTE — VERIFICAR: art. 69 de la Ley 38/1992 y Orden HAC/1501/2025).
- **Test de regresión:** añadir a `scripts/` un `test_costes.py` de 10 líneas que recalcula el ejemplo y falla si el IEDMT del texto difiere en más de 5 €.

---

## 🟠 Hallazgos importantes

### IV-I1 · Cita legal equivocada ("Ley 11/2022") en 3 ficheros, y URL placeholder

- **Evidencia:** `06-reglas/anti_patrones.md` l.39 (A27: "…requisito del RDL 1/2007 (art. 20) y de la Ley 11/2022") y l.42 (A30: "RDL 1/2007 modificado por Ley 11/2022 es de 3 años"); `07-marketing/fuentes_y_evidencia.md` l.53: "**Ley 11/2022** (modificaciones RDL 1/2007, garantía VO) | `https://www.boe.es/...`".
- La Ley 11/2022 es la **Ley General de Telecomunicaciones** (icab.es). La garantía de 3 años del vehículo usado sale del **RDL 7/2021** (consumo.gob.es), que modifica el texto refundido de la Ley de Consumidores (RDL 1/2007). Fuentes externas que he podido abrir; **no el BOE directo**.
- **Impacto:** una cita legal equivocada en un fichero que *manda* sobre qué escribir en los anuncios. Si alguien la copia a una ficha de cliente, queda un error jurídico con tu nombre. La URL `https://www.boe.es/...` es literalmente un placeholder.
- **Fix:** reemplazar por "RDL 1/2007 (TRLGDCU), modificado por el RDL 7/2021" y poner el enlace BOE exacto. **Enlace BOE exacto: SIN FUENTE — VERIFICAR** (no lo he encontrado confirmado). La obligación de mostrar fecha de 1ª matriculación (A27): **no he podido atribuirla a ninguna norma concreta → SIN FUENTE — VERIFICAR**; hasta entonces, A27 debería llamarse "requisito de los portales", no "requisito legal".
- **Test:** `grep -rn "Ley 11/2022" <skill>` → 0 líneas; `grep -rn "boe.es/\.\.\." <skill>` → 0.

### IV-I2 · Numeración de reglas incoherente y la regla A32 se cita con el número equivocado

- **Evidencia:** `SKILL.md` l.49 "reglas duras 32: A1-A31 + A22b"; `SKILL.md` l.1092 "A1-A33"; la tabla de `anti_patrones.md` tiene 34 entradas (A1-A33 + A22b). **A31** en la tabla es "ZIP en `Downloads\`" y **A32** es "no somos vendedores ni damos garantía", pero `scripts/check_ficha_cliente.py` l.17, 117, 131, 138 y 222 mensajes como «lenguaje de vendedor o de garantía (A31)», y `07-marketing/copy_engine.md` l.279 igual ("…y A31").
- **Impacto:** cuando salta el error, Claude abre A31 (Downloads) y no entiende el aviso. Es la regla que protege el modelo de negocio (D10) y su número está mal en el validador.
- **Fix:** en `check_ficha_cliente.py` y `copy_engine.md` sustituir "A31" por "A32" en esos contextos; en `SKILL.md` l.49 poner "34 reglas: A1-A33 + A22b".
- **Test:** `grep -n "A31" scripts/check_ficha_cliente.py` → 0 resultados; un test que compare el nº de filas de la tabla con la cifra de `SKILL.md`.

### IV-I3 · "30 checks" que en realidad son 21 funciones

- **Evidencia:** `check_marketing.py` l.6 "ejecuta 30 comprobaciones", l.224 "# 30 comprobaciones", l.1017 "(30 checks)"; `SKILL.md` l.49 y l.124 "validador 30 checks". En el fichero hay **21** `def check_*` (conteo con grep; mi conteo de códigos de error distintos es 26, **conteo mío, no verificado a mano**).
- **Impacto:** da una falsa sensación de cobertura. Nadie sabe qué cubre y qué no.
- **Fix:** que el script imprima su propia lista (`--list`) y que la cifra de `SKILL.md` salga de ahí. No inventar un número: poner el que dé `--list`.
- **Test:** CI-local: `python check_marketing.py --list | wc -l` debe coincidir con el número escrito en `SKILL.md`.

### IV-I4 · Los flujos B, C, D, E no tienen validador; `check_avisos.py` no es lo que parece

- **Evidencia:** los únicos validadores de la skill son `check_marketing.py`, `check_ficha_cliente.py` y `check_avisos.py`. Este último es una herramienta de **alertas del CRM** (seguimiento), no de informes. No hay ningún script que compruebe un `informe_busqueda_*.md`, un `informe_modelos_*.md` o un informe de unidad.
- **Impacto:** las reglas A2 (mobile.de nunca se salta), A4 (veredicto con mediana + cuartil + comparable), A5 (precio máximo), A9 (sección "Fuentes consultadas"), A33 (URLs de medición) y la regla `Versions[`/`q=` se comprueban solo con la lista de verificación mental de Claude. Es el mismo fallo de fondo que en estudio-mercado (doc 1, EM-I7).
- **Fix:** un único `scripts/check_informe.py <fichero.md> --flujo A|B|C|D|E` con comprobaciones mecánicas (sin red): secciones obligatorias, "Fuentes consultadas" presente, ninguna URL con `Versions[` o `q=`, ninguna cifra monetaria sin separador de miles, "pendiente" en lugar de vacíos, `precio_max` presente en A. Es un script nuevo → **pendiente de tu OK** (no instala paquetes: usa solo `re` y `sys`).
- **Test:** un informe de B con `Versions[0]` en una URL debe salir con código 1.

### IV-I5 · `SKILL.md` pesa 96 KB (D6, eficiencia de contexto)

- **Evidencia:** `wc -c SKILL.md` → 96.484 bytes. La cabecera `description` ocupa ~620 caracteres y la l.49 es un único párrafo de ~1.600 caracteres con 20 rutas.
- **Impacto:** cada vez que se dispara la skill se carga todo; el propio `CLAUDE.md` de Desktop dice "ahorrar tokens y evitar el Prompt length error". Además los textos largos duplican a `entregables.md` y son donde aparecen las contradicciones (IV-C1).
- **Fix:** dejar en `SKILL.md` solo: flujos (tabla), reglas duras (lista corta) y "qué fichero leer cuándo". Mover el detalle a los ficheros que ya existen. Objetivo orientativo <30 KB; **SIN FUENTE — VERIFICAR** cuál es el límite real que dispara el error de longitud.
- **Test:** `wc -c SKILL.md` < 30.000 y `verify_skill_refs.py` sin enlaces rotos.

### IV-I6 · Memoria y versiones desfasadas entre copias (D8, D14)

- **Evidencia:**
  - `importacion-vehiculos/memoria/*` (`mejoras-aplicadas.md`, `trampas-encontradas.md`…): las últimas entradas son de mediados de agosto (12-16 ago; p. ej. `mejoras-aplicadas.md` termina el 12-ago); desde entonces la skill pasó de 3.x a 3.10.5 sin entradas.
  - `Desktop\JJImportMotors\HANDOFF.md`: ninguna entrada posterior al 26-sep.
  - `sync.manifest.json` (Desktop): importacion **3.9.3**, estudio-mercado **0.4.0**, sin ecommerce-tuning. Zips en Desktop: 3.9.10 / 0.4.6 / 0.1.0. Copia de cuenta: 3.10.5 / 0.5.0 / 0.1.0.
  - `Desktop\CLAUDE.md` lleva un aviso de "contenido pendiente de revisión" fechado del 12-ago.
  - No he encontrado en la memoria las lecciones de septiembre-octubre (regla `Versions[`/`q=`, vigencia del mapa, política de PDF).
- **Impacto:** si abres Claude Desktop hoy, trabaja con la skill **3.9.10**, anterior a las reglas de septiembre-octubre. No he comparado fichero a fichero qué reglas le faltan; `verify_desktop_sync.py` lo dirá.
- **Fix:** (a) actualizar `sync.manifest.json` y reinstalar los zips (pendiente de OK); (b) añadir a `memoria/trampas-encontradas.md` una entrada por cada regla nueva de agosto-octubre; (c) que cada cierre de sesión escriba su entrada en HANDOFF (la regla ya existe en `CLAUDE.md`; no se cumple).
- **Test:** `verify_desktop_sync.py` (ya existe en `scripts/`) debe dar 0 diferencias entre copia de cuenta, Desktop y repo.

### IV-I7 · Las instrucciones del proyecto de claude.ai describen otro sistema (D1, D14)

- **Evidencia:** `INSTRUCCIONES_PROYECTO.md` (en el proyecto "JJ Import Motors"), fechado 12-ago: "Los 4 flujos de trabajo", "Reglas duras (anti-patrones A1-A14)", "Claude genera los PDFs de INVESTIGACIÓN", y no menciona ni los flujos E y M, ni A15-A33, ni estudio-mercado. Sí dice "NO compramos stock — solo ofertamos el servicio", pero no recoge la parte de A32 "no hay garantía" ni la lista de palabras prohibidas.
- **Impacto:** cualquier chat web que arranque con esas instrucciones aplica un sistema de hace 8 semanas, y la mitad de la regla que protege el modelo de negocio (A32: "no hay garantía", palabras prohibidas) no está en el texto que más se lee. Es el origen probable de que Claude "olvide" cosas que la skill sí tiene.
- **Fix:** sustituir el bloque por un texto corto (≤1,5 KB) que apunte a la skill y repita solo lo irrenunciable: no vendemos y no hay garantía (A32); cobertura de fuentes; datos reales o "pendiente"; un solo `.md`; 1 flujo por encargo. **Pendiente de tu OK** (decisión D-6).
- **Test:** que el texto no contenga "4 flujos", "A1-A14" ni "genera los PDFs".

### IV-I8 · El plan B `Versions[`/`q=` sigue en la plantilla de búsqueda (reaparece)

- **Evidencia:** `03-informes/informe_busqueda.md` l.131: `https://www.coches.net/segunda-mano/?MakeIds[0]=47&Versions[0]=Tiguan&ArrBodyType=5&PowerHpFrom=314…`, l.149 y l.213 hablan de `Versions[0]`. La regla del 7-oct prohíbe `Versions[` y `q=`; además `ArrBodyType=5` es monovolumen, no SUV (SUV = 6): el ejemplo de la plantilla es un Tiguan.
- **Impacto:** la plantilla es lo que Claude copia. Mismo patrón que el IVA del 12-sep: la regla nueva se escribió en un sitio y no en todos (regla propuesta A37).
- **Fix:** en el ejemplo usar `ModelIds[0]=<id>&ArrBodyType=6`; borrar las menciones a `Versions[0]` o convertirlas en "NO usar". Mismo fix en los 6 sitios de doc 1, EM-C2.
- **Test:** `grep -rn "Versions\[\|[?&]q=" <skill>` → solo líneas que digan "prohibido".

### IV-I9 · `fuentes.py` no genera la URL con el combustible de cada versión

- **Evidencia:** `scripts/fuentes.py` l.33 `--combustible diesel|gasolina` y l.157 `combustible = str(v.get("combustible")…` (l.213 lo aplica): el combustible es un parámetro **global** de la llamada, no del dato de cada versión.
- **Impacto:** un informe con versiones de gasolina y diésel mezcladas obliga a lanzar el script varias veces y a copiar a mano; justo lo que A33 dice que "NUNCA a mano".
- **Fix:** aceptar `combustible` por versión en el JSON de entrada (el código ya lo lee en l.157; verificar que la prioridad es "versión > global").
- **Test:** entrada con dos versiones (gasolina/diésel) → las dos URLs de mobile.de llevan `ft=PETROL` y `ft=DIESEL` respectivamente.

### IV-I10 · A23 valida que la foto "existe", no que sea la correcta

- **Evidencia:** `anti_patrones.md` l.35 (A23): "Validación de fotos: HTTP 200 + `Content-Type: image/*` + >1 KB + dedup por hash".
- **Impacto:** una foto del logo del concesionario, un mapa o un banner pasa los cuatro criterios. Tampoco hay comprobación de variedad de ángulos (exterior frontal/trasera/interior/cuadro). En el informe de unidad esa foto es la que ve el cliente.
- **Fix:** añadir al ZIP un `fotos_revision.md` con miniaturas y un paso manual "¿exterior ×3, interior ×2, cuadro ×1, motor?": 1 minuto de tu revisión visual. Es un flujo que **falta** (ver "Flujos que faltan").
- **Test:** ZIP con 6 fotos iguales por hash distinto → debe avisar "ángulos no verificados".

### IV-I11 · `riesgos.md`: carácter ajeno, motores que faltan y costes sin fuente (D2)

- **Evidencia:** `04-negocio/riesgos.md` l.47: "Club奥迪" (caracteres chinos dentro de la lista de foros); la tabla de motores fiables incluye "Skyactiv-G 122" (l.38) pero no el SKYACTIV-D ni el 1.2 TSI (conocidos por problemas de cadena de distribución en las primeras series; **SIN FUENTE — VERIFICAR** antes de escribirlo como regla); los costes de reparación de la tabla no llevan fuente ni fecha.
- **Impacto:** el texto tiene un fallo evidente de calidad (D7) y las cifras de reparación entran en el veredicto del cliente (D2).
- **Fix:** corregir a "Club Audi"; añadir una columna "Fuente / fecha" a cada coste; los que no la tengan, `SIN FUENTE — VERIFICAR` hasta que se midan.
- **Test:** `grep -nP "[\x{4e00}-\x{9fff}]" 04-negocio/riesgos.md` → 0.

### IV-I12 · `references/google_drive.md` está desfasado respecto al conector real

- **Evidencia:** describe el flujo "legacy" 1/2 y, para modificar, vuelve a crear con `create_file` (no usa `update_file`, que sí existe); `operaciones.md` l.4 y l.508 dicen "Drive **ya NO** es parte del flujo operativo — solo backup" mientras tu prompt de auditoría y la estructura numerada 01-07 de Drive lo tratan como canal de salida. Ver doc 3.
- **Impacto:** tres posiciones distintas sobre el papel de Drive; Claude elige una al azar.
- **Fix:** ver `AUDITORIA_DRIVE_2026-10-07.md` (política por flujo).

### IV-I13 · Referencias a ficheros que no viajan en el ZIP de la skill

- **Evidencia:** `anti_patrones.md` l.44 (A32: "Base: `.ai/rules/business-model.md` del panel"), `fuentes_y_evidencia.md` l.4 y `copy_engine.md` l.35/144/279 citan `business-model.md`. Ese fichero vive en el repo, no en la skill.
- **Impacto:** Claude Desktop no puede leer la "regla de oro" que cita; la regla A32 en la skill debe bastarse sola. Hoy lo hace (A32 está completa en `anti_patrones.md`), pero la referencia induce a buscar un fichero inexistente.
- **Fix:** anotar "(copia local: ver A32)" o incluir `business-model.md` en `references/`. **NO VERIFICABLE** su contenido actual (repo no accesible).

### IV-I14 · Encargos de D y de C: la carpeta o el nombre real no cumplen la tabla canónica

- **Evidencia:** ver mapa de flujos (abajo). `informes\descubrimiento\hatchback-B-C_2026-09-26.md` en vez de `informe_modelos_<cliente-o-segmento>_<fecha>.md`; el flujo C no tiene carpeta definida en `entregables.md` §3; el estudio-mercado tiene carpeta `informes\<marca>\` en la tabla, pero los estudios del 27-sep están en `informes\mercado\estudio_familiares_deportivos_tanda1…` (nombre no canónico).
- **Fix:** añadir a `entregables.md` §3 la fila "C → `informes\mercado\`" y "Estudio por tandas → `informes\mercado\`", o renombrar los ficheros; **decisión D-5**.

---

## 🟡 Hallazgos menores

- **IV-M1 · "6 flujos" frente a "cinco flujos".** `SKILL.md` l.4 (description) dice "Cinco flujos + marketing", l.113 dice "LOS 6 FLUJOS" y l.155 "los 5 flujos". Unificar: "5 flujos (A-E) + M".
- **IV-M2 · El flujo se llama "STOCK" y el catálogo `Inventario_Coches_Oferta`.** Con A32 ("NO vendemos, NO hay stock propio"), el nombre invita a la deriva. `02-flujos/stock-marketing.md` ya aclara "NO es comprar stock", pero el nombre sigue. Propuesta: "CATÁLOGO BAJO PEDIDO" (decisión D-7, solo cosmética).
- **IV-M3 · "No aplica" como relleno** en secciones de informes (A9/A2: "datos reales o pendiente"). Sustituir por "pendiente" o por la razón concreta.
- **IV-M4 · Tabla "peso del coste fijo" sin fuente** (cifras sin origen): SIN FUENTE — VERIFICAR o retirar.
- **IV-M5 · Restos de versiones en tu Desktop:** `_sync-skill/SKILL.md` (3.8.0), `Claude outputs/SKILL.md` y zips antiguos. Riesgo de que alguien arranque con una copia vieja. Moverlos a `_archive/` (a mano, pendiente de tu OK).
- **IV-M6 · `test-coche-001.zip` suelto en `informes\`** (raíz de informes). Archivo de prueba; moverlo a `_archive/`.

---

## ✅ Verificado correcto

- **`run_validator` de `empaquetar.py`** ya entrega a cada validador su propio fichero (el bug del arreglo "ronda 4" no ha vuelto; `empaquetar.py` l.123-170).
- **Identificadores de cabecera idénticos** entre `SKILL.md`, `contrato.md` y `empaquetar.py` (nombres de ficheros del ZIP).
- **SUV = `ArrBodyType=6`, monovolumen = 5** correcto en `SKILL.md` y `estudio-mercado` (el error del 5 solo sigue en el ejemplo de `informe_busqueda.md`, IV-I8).
- **IVA:** la regla 6/6000 de `costes.md` ("<6 meses **o** <6.000 km → 21 %, modelo 309") es coherente con la fuente externa que pude contrastar (gestoria247.com); fuente secundaria, no BOE.
- **Aritmética del ejemplo `precio_max`** (l.130-136) es correcta *con el IEDMT que se le da* (36.850 × 0,88 − 4.979 = 27.449 €); lo erróneo es el IEDMT de partida (IV-C3).
- **Total del ejemplo §6:** 28.900 + 900 + 114 + 95 + 20 + 1.850 + 2.000 = 33.879 € y ahorro 36.850 − 33.879 = 2.971 € (8,1 %): suma bien con los datos que da.
- **A32:** las palabras «vendemos», «con garantía», «llave en mano»… aparecen en la skill **solo en negaciones o en listas de prohibidos**, no como oferta (revisado con grep en `SKILL.md`, `07-marketing/*`, `03-informes/*`).
- **`base64Content`** del conector de Drive está documentado.
- **Profundidad de encabezados ≤3** en los informes revisados (legibilidad D13).
- **Lecciones del ZIP de 4 bloques** (ronda 4) aplicadas en `empaquetar.py`.
- **Desglose de costes fijos 900 + 114 + 115 = 1.129 €** coincide entre `costes.md`, `datos_mercado.json` y las cuentas del ejemplo (95 + 20 = 115).

---

## 📊 Mapa de flujos auditado (D12)

Leyenda: ✅ correcto · ⚠️ con defecto · ❌ falta o roto · ❔ NO VERIFICABLE desde esta sesión.

| Flujo | Skill | Plantilla OK | Validador OK | Fichero OK | Carpeta OK | Drive OK | Notas |
|---|---|:---:|:---:|:---:|:---:|:---:|---|
| **A** Unidad | importacion-vehiculos | ✅ `informe_tecnico.md` | ⚠️ valida marketing y ficha, no el informe en sí; `anuncio-portales.txt` sin validar (IV-C2) | ✅ `informe_unidad_<marca>-<modelo>_<fecha>.md` | ⚠️ tabla dice `informes\<marca>\<modelo>\`; en el proyecto de claude.ai los informes aparecen planos en `audi\` | ⚠️ política contradictoria (IV-I12) | Ejemplo IEDMT erróneo (IV-C3); PDF sí/no (IV-C1) |
| **B** Modelo | importacion-vehiculos | ⚠️ `informe_busqueda.md` con `Versions[0]` y `ArrBodyType=5` (IV-I8) | ❌ ninguno (IV-I4) | ✅ `informe_busqueda_<marca>-<modelo>_<fecha>.md` | ✅ `informes\<marca>\<modelo>\` | ⚠️ idem | JSON `export/flujo-b-…json` definido, pero **no existe carpeta `export/`** en tu Desktop |
| **C** Mercado | importacion-vehiculos | ⚠️ reutiliza la de búsqueda (N modelos) | ❌ ninguno | ⚠️ nombre `informe_busqueda_<segmento>_…` definido, sin ejemplo real | ❌ sin carpeta definida (IV-I14) | ⚠️ | `scouting_mercado` (Laravel): ❔ |
| **D** Descubrimiento | importacion-vehiculos | ❌ no hay fichero de plantilla (solo `SKILL.md` §D2) | ❌ ninguno | ❌ real: `hatchback-B-C_2026-09-26.md`; canónico: `informe_modelos_…` | ✅ `informes\descubrimiento\` | ⚠️ | Única pieza real de D en disco; no sigue el nombre |
| **E** Stock / catálogo | importacion-vehiculos | ⚠️ reutiliza búsqueda; spec en `02-flujos/stock-marketing.md` | ❌ ninguno | ❌ ningún fichero real (nunca se ha ejecutado o no se guardó) | ❌ `informes\stock\` **no existe** en Desktop | ⚠️ | El prompt hablaba de `<marca>`; la skill dice `stock\`: elegir una (IV-I14) |
| **M** Marketing | importacion-vehiculos (`07-marketing`) | ✅ | ⚠️ `check_marketing` (21 funciones, no 30) y `check_ficha_cliente`; falta portales (IV-C2, IV-I3) | ✅ `redes-sociales.txt`, `anuncio-portales.txt`, `ficha-cliente.txt` | ✅ dentro del ZIP (`contenido/`) | ⚠️ | Solo tras A con 🟢 o 🔵; A32 bien cubierto en texto, mal numerado en validador (IV-I2) |
| **F-EM** Estudio de mercado | estudio-mercado | ⚠️ `informe_mercado.md` con IVA fantasma y desglose 1.000/200/300 (doc 1, EM-C1) | ❌ ninguno (doc 1, EM-I7) | ⚠️ canónico `<marca>_<segmento>_<enfoque>_<fecha>.md` (p. ej. `audi_compacto_deportivo_2026-10-06.md`); los de 27-sep (`estudio_familiares_deportivos_tanda1…`) no lo son | ⚠️ tabla: `informes\<marca>\`; los de tandas en `informes\mercado\` | ❌ sin carpeta "00 Estudios" en Drive | PDF de 4 págs. con defectos (doc 1, EM-I1) |
| **F-GM** Guía de marcas | estudio-mercado (generador en el repo) | ❔ | ❔ | ❔ | ❔ | ❔ | `estudio-mercado/scripts/generar_guia_marcas.py` y su salida viven en el repo: **no accesibles**. Dentro de la skill no hay plantilla ni validador de esta guía |

**Resumen del mapa:** 8 flujos revisados; 0 con las 6 columnas en ✅. Los más sanos: A (en nombre y carpeta) y M (en estructura). Los más débiles: D, E y C (sin validador ni carpeta/nombre fiables).

### 🆕 Flujos que faltan

Piezas de trabajo que ya haces a mano (o se repiten) y que ningún flujo recoge:

1. **Índice / ranking por segmento** (`00_indice_<segmento>_<fecha>.md`): al terminar un estudio por marcas (7 marcas × segmento) no hay un documento que las ordene; existe `informe_mercado_final_familiares_deportivos_2026-09-27.md`, que cumple ese papel una vez, sin flujo que lo repita.
2. **Importar estudio al mapa (`datos_mercado.json`)**: hoy el `.json` se edita a mano y tiene 3 copias que han divergido (doc 1, EM-I6). Falta un paso "al cierre del estudio, importar al mapa y estampar `refrescar_antes_de`".
3. **Guía de búsqueda por marca** (F-GM): existe el generador en el repo, pero no el flujo en la skill (cuándo se lanza, qué entrega, dónde va, qué validador).
4. **Calendario / plan editorial de redes** (`plan_redes_oct-dic_2026-09-25.md` existe en el proyecto): ningún flujo lo genera ni lo valida; hoy sale de una propuesta aparte.
5. **Auditoría periódica del sistema**: se hace a mano cuando alguien lo pide (12-sep, ronda 4, hoy). Un flujo "AUDITORÍA" con el prompt de hoy como plantilla, mensual.
6. **Revisión visual de fotos** antes de empaquetar (IV-I10).

---

## 🧱 Reglas nuevas propuestas (A34+; no contradicen A1-A33)

> A34 ("vigencia del dato") ya está propuesta en `propuesta_skill_marketing_editorial_2026-09-25.md`. Sigo desde A35.

| Nº | Regla | Motivo / caso |
|---|---|---|
| **A35** | Todo PDF lleva en el pie de **cada** página: marca, modelo, fecha de medición y "p. N/M". | PDF del 6-oct: pie roto y sin contexto (doc 1, EM-I1). |
| **A36** | Las cifras derivadas (ahorros, medianas, costes estimados) se redondean a la centena y llevan la fecha de medición. | Informes con cifras "al euro" sobre datos que cambian a diario (doc 1, EM-I2). |
| **A37** | **Una regla nueva se propaga a TODOS los sitios en el mismo cambio** (`grep` de la regla antigua = 0 antes de cerrar). | IVA fantasma (12-sep) y `Versions[` (7-oct) volvieron a aparecer. |
| **A38** | **Ningún informe se entrega sin pasar su validador mecánico**; la lista de verificación mental no cuenta. | IV-I4: B/C/D/E sin validador. |
| **A39** | Toda cita legal lleva norma exacta + enlace BOE comprobado; si no se ha comprobado, `SIN FUENTE — VERIFICAR`. | "Ley 11/2022" (IV-I1). |

---

## 🔗 D9 · Integración con el panel Laravel (parcial)

- **NO VERIFICABLE desde aquí:** `Car::calculateIEDMT()`, `scouting_mercado`, `CarMarketingContent`, `ingestor` de emojis, `/api/import-valuation`, `php artisan` y tests. El repo no está montado.
- **Lo que sí se ve desde la skill:** el ZIP (`informe.json`, `contenido/*.txt`, fotos) se genera con `empaquetar.py` y los nombres de fichero coinciden con los que la skill documenta; la regla de que Laravel recalcula el IEDMT y marca `avisos` si hay >10 % de diferencia (`costes.md` l.116) hace que el ejemplo erróneo de IV-C3 se descubriría aguas abajo, pero no antes.
- **Afirmación dada por buena (D3):** "la BD de Forge usa `utf8mb3`, no admite emoji de 4 bytes" (`copywriting_marketing.md` l.9, `contrato.md` l.588). Es comprobable en 1 minuto con `SHOW CREATE TABLE car_marketing_contents;`. **NO VERIFICABLE** aquí; si fuese `utf8mb4`, la regla A-COPY1 sobra o está mal justificada. Pendiente de tu OK para pedirle a Copilot que lo compruebe.

---

## 📋 Pendiente de tu OK

| # | Decisión | Mi recomendación |
|---|---|---|
| **D-1** | Política de PDF de los informes de investigación: ¿solo `.md` y PDF a petición, o `.md` + PDF siempre? | Solo `.md`, PDF a petición (es lo que dice la regla "un solo fichero" y los enlaces no funcionan en PDF). |
| **D-2** | Añadir `anuncio-portales.txt` a `check_args` de `empaquetar.py` (IV-C2). | Sí. |
| **D-3** | Corregir el ejemplo de IEDMT de `costes.md` y añadir `test_costes.py` (IV-C3). | Sí. Verificar antes en `Car::calculateIEDMT()`. |
| **D-4** | Crear `scripts/check_informe.py` (B/C/D/E/A) y las reglas A35-A39 (IV-I4). | Sí (script nuevo, sin paquetes). |
| **D-5** | Normalizar carpetas/nombres: C y estudios por tandas → `informes\mercado\`; D → renombrar al canónico; E → `informes\stock\` o `<marca>`. | `informes\mercado\` para C y tandas; mantener `stock\` para E. |
| **D-6** | Sustituir el texto del proyecto de claude.ai por uno de ≤1,5 KB (IV-I7). | Sí. |
| **D-7** | Renombrar "STOCK" → "CATÁLOGO BAJO PEDIDO" (cosmético, A32). | Sí, bajo riesgo. |
| **D-8** | Reducir `SKILL.md` de 96 KB a <30 KB moviendo detalle a ficheros existentes (IV-I5). | Sí, pero en un commit aparte y con `verify_skill_refs.py`. |
| **D-9** | Reinstalar zips 3.10.x / 0.5.x en Desktop y actualizar `sync.manifest.json`; archivar restos (IV-M5/M6). | Sí; hacerlo **después** de aplicar D-1 a D-3 para no instalar versiones con errores. |
| **D-10** | Que Copilot verifique `utf8mb3` y el `Car::calculateIEDMT()` en el repo. | Sí. |

**Paquetes a instalar o desinstalar: ninguno.**

---

## 🎯 Resumen ejecutivo (para WhatsApp)

La skill de importación está bien armada pero **se contradice en 3 puntos que ya duelen**: (1) un sitio dice "siempre PDF" y otro "nunca PDF"; (2) el anuncio de portales, lo único público, **no pasa por ningún validador** al empaquetar; (3) el ejemplo oficial de IEDMT no cuadra (1.349 € reales frente a 1.850 € escritos) y de él sale el precio máximo de compra. Además hay una cita legal equivocada (Ley 11/2022 es la de telecomunicaciones; la garantía sale del RDL 7/2021), la regla A32 está mal numerada en el validador, y tu Desktop sigue con la versión 3.9.10, anterior a la 3.10. B, C, D y E no tienen validador. Nada se ha tocado: todo son propuestas, con 10 decisiones tuyas.

---

## Tabla final

| Hallazgo | Skill / Componente | Severidad | Fix |
|---|---|:---:|---|
| IV-C1 PDF "siempre" frente a "nunca" | `entregables.md`, `SKILL.md`, `CLAUDE.md`, proyecto | 🔴 | Una política, 5 sitios, mismo commit (D-1) |
| IV-C2 `anuncio-portales.txt` sin validar | `empaquetar.py` | 🔴 | Añadir a `check_args` (D-2) |
| IV-C3 Ejemplo IEDMT erróneo | `costes.md` | 🔴 | 1.349 / 1.764 €, `precio_max` 27.950 € (D-3) |
| IV-I1 "Ley 11/2022" y URL placeholder | `anti_patrones.md`, `fuentes_y_evidencia.md` | 🟠 | RDL 1/2007 mod. por RDL 7/2021; BOE SIN FUENTE |
| IV-I2 A31/A32 mal numeradas | `check_ficha_cliente.py`, `copy_engine.md`, `SKILL.md` | 🟠 | A31→A32; "34 reglas" |
| IV-I3 "30 checks" = 21 funciones | `check_marketing.py` | 🟠 | `--list` y cifra desde ahí |
| IV-I4 B/C/D/E sin validador | skill | 🟠 | `check_informe.py` (D-4) |
| IV-I5 `SKILL.md` 96 KB | `SKILL.md` | 🟠 | <30 KB (D-8) |
| IV-I6 Memoria y versiones desfasadas | memoria, HANDOFF, manifest | 🟠 | Reinstalar y registrar (D-9) |
| IV-I7 Instrucciones de proyecto de hace 8 semanas | claude.ai | 🟠 | Texto nuevo ≤1,5 KB (D-6) |
| IV-I8 `Versions[`/`ArrBodyType=5` en plantilla | `informe_busqueda.md` | 🟠 | `ModelIds[0]` + `=6` |
| IV-I9 Combustible global en `fuentes.py` | `fuentes.py` | 🟠 | Por versión |
| IV-I10 A23 no revisa la foto | `empaquetar.py` | 🟠 | `fotos_revision.md` |
| IV-I11 `riesgos.md` ("Club奥迪", sin fuente) | `riesgos.md` | 🟠 | Corregir y poner fuente/fecha |
| IV-I12 `google_drive.md` desfasado | `google_drive.md`, `operaciones.md` | 🟠 | Ver doc Drive |
| IV-I13 `business-model.md` no viaja | A32 / marketing | 🟠 | Anotar o incluir copia |
| IV-I14 Carpeta/nombre de C, D, E, tandas | `entregables.md` | 🟠 | Normalizar (D-5) |
| IV-M1…M6 | varios | 🟡 | Ver sección menores |
