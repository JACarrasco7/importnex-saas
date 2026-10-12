# Auditoría de `estudio-mercado` contra lo aprendido en `importacion-vehiculos`

**Fecha:** 12-sep-2026 · **Versión auditada:** 0.3.12 (última tocada el 24-ago) · **Versión corregida:** 0.4.0

La skill de estudio de mercado llevaba sin tocarse desde el 24-ago, así que no tenía
ninguno de los aprendizajes de la v3.7→v3.9.2 de la skill hermana. Tres hallazgos, dos
de ellos del mismo patrón que costó una semana en la otra: **una afirmación dada por
buena que nadie había verificado.**

---

## 🔴 Hallazgo 1 (crítico) — El IVA fantasma del 21 %, otra vez

`informe_mercado.md` afirmaba literalmente:

> "IVA de importación: 21 % sobre el valor en aduana (precio compra + transporte +
> seguro). Se puede deducir si el coche es para revender con margen, pero **como
> particular se paga**."

Es falso, y además está **invertido**:

| Lo que decía la skill | Lo que dice la fuente canónica (`04-negocio/costes.md` §IVA) |
|---|---|
| "valor en aduana" | No existe aduana en una compra intracomunitaria — es terminología de terceros países (EEUU, UK, Japón) |
| "como particular se paga" | El **particular sin NIF-IVA NO paga IVA español**: paga el precio alemán con el IVA alemán ya dentro |
| "se puede deducir si es para revender" | Quien liquida el 21 % (modelo 309) es la **empresa con NIF-IVA intracomunitario**, y es ella la que lo deduce — justo lo contrario |
| *(no se mencionaba)* | **Regla 6/6000**: la única excepción real — si el coche tiene <6 meses **o** <6.000 km, sí se liquida el 21 % aunque sea usado |

Es el **mismo fallo** que se eliminó de `PrecioClienteCalculator.php` el 12-sep (donde
inflaba el precio del cliente unos 6.700 € en un coche de 32.000 €). Aquí era
potencialmente peor: un 21 % fantasma metido en el coste de importación **hunde el
`hueco_neto_pct` y descarta modelos que sí son rentables** — que es exactamente la
decisión que esta skill existe para tomar. Un modelo con 15 % de hueco bruto pasa a
parecer ruinoso si le sumas un 21 % que no se paga.

Y había una vía de contagio directa a la skill hermana: `SKILL.md` instruía *"cuando hay
unidad concreta (Flujo A), se calcula IVA + IEDMT exacto"*, lo que habría reintroducido
en el flujo de valoración el bug que acabábamos de quitar del panel.

### Alcance real del daño (buena noticia)

`datos_mercado.json` está **limpio**. Su `costes_referencia` es:

```json
{
  "fijos_importacion_eur": 1129,
  "desglose": "transporte 900 + ausfuhr 114 + ITV import 115",
  ...
}
```

Sin ningún IVA. Los `hueco_neto` ya calculados usan 1.129 € + IEDMT estimado, así que
**los veredictos guardados no están contaminados** y no hay que re-medir nada. El daño
estaba solo en (a) el texto del informe que leías tú y (b) la instrucción a Flujo A.

---

## 🟠 Hallazgo 2 — Dos modelos de costes divergentes dentro de la misma skill

| Dónde | Coste fijo | Desglose |
|---|---:|---|
| `SKILL.md` §Cálculo del hueco | **1.129 €** | transporte 900 + ausfuhr 114 + ITV 115 ✅ |
| `datos_mercado.json` | **1.129 €** | idéntico ✅ |
| `SKILL.md` §Gastos fijos + todo `informe_mercado.md` | **1.500 €** | "1.000 transporte + 200 ITV + 300 gestoría" ❌ |

El total redondo de 1.500 € era una **petición explícita tuya** del 23-ago, y se
mantiene — es un colchón razonable. El problema no era la cifra, era el **desglose
inventado**: esos componentes (1.000 / 200 / 300) no existen en ninguna fuente del
sistema. Ahora se presenta honestamente como **1.129 € reales + ~371 € de colchón**,
citando `costes.md` como fuente única.

Es el anti-patrón que ya quedó registrado en la v3.9.2 de la otra skill: *"cuando la
skill y el panel mantienen dos modelos de costes en paralelo, divergen sin avisar — una
sola fuente, siempre."* Aquí divergían dentro del mismo fichero.

---

## 🟡 Hallazgo 3 — Faltaba la regla de horquilla

El informe daba cifras cerradas al euro: *"Puesto en Huelva 21.199 €"*, *"te ahorras
7.701 €"*. La v3.9.1 de la skill hermana eliminó justamente eso en la ficha del cliente
(el precio pasó a horquilla porque transporte ±15 % e IEDMT ±10/60 %). Un estudio de
mercado que promete un ahorro al euro sobre una estimación tiene la misma falsa
precisión.

Ahora: **redondeo a la centena** ("≈21.200 €", "ahorro ≈7.700 €") y la etiqueta "sin
IEDMT" pegada a la cifra cuando el impuesto no está estimado. El `hueco_neto_pct` del
JSON conserva los decimales — es cálculo interno, no una promesa que leas como firme.

---

## ✅ Revisado y correcto (no requería cambio)

- **El informe ya está marcado como interno** ("Para quién es este informe: para ti"),
  así que mostrar URLs de comparables no rompe la regla dura nº3 — esa regla prohíbe
  mostrarlas **al cliente**, no a ti.
- `coste_importacion` ya excluía honorarios explícitamente.
- Las reglas duras heredadas están presentes y coherentes con la skill hermana: A11/A12
  (paginación y rango completo), A15 (navegación real, no snippets), A17 (listado-first),
  A19 (explorar el segmento entero), la regla de caché del JSON y la regla dura de
  filtrado estructurado por URL en Coches.net/mobile.de.
- El `schema_datos_mercado.md` y `fuentes_datos.md` no tenían nada que corregir.

---

## Qué hay que hacer con esto

| # | Acción | Quién |
|---|---|---|
| 1 | Ejecutar `aplicar-estudio-mercado-v0.4.0.bat` → copia los 3 ficheros a `.claude\skills\estudio-mercado\` y hace commit+push | Tú (yo no puedo escribir bajo `.claude\` ni ejecutar git) |
| 2 | Subir `estudio-mercado-v0.4.0.skill.zip` donde gestionas las skills de tu cuenta, para que Cowork y Desktop dejen de usar la 0.3.12 | Tú |
| 3 | Re-medir modelos | **No hace falta** — el JSON está limpio |

---

## Nota de método

Este es el tercer caso en una semana del mismo patrón: un comentario o una afirmación en
el propio material ("la BD ya es utf8mb4", "los límites viven en
`config/marketing_limits.php`", "como particular se paga el 21 %") que se daba por
verificada y no lo estaba. La regla que conviene aplicar de forma sistemática: **una
afirmación escrita en el material no es evidencia — hay que abrir la fuente que dice
referenciar antes de darla por buena.** En los tres casos, abrir la fuente tardó menos
de un minuto y el error llevaba semanas en producción.
