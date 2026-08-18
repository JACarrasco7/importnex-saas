# Modelos medidos — Histórico del skill

> **Histórico de todos los modelos investigados por el skill.** Se actualiza con cada búsqueda completada. Permite detectar tendencias, repetir mediciones fácilmente y evitar perder trabajos.

---

## 📊 Formato de cada entrada (12 campos)

```markdown
### [Marca Modelo] · [fecha medición]
- **Tipo:** UNIDAD | MODELO | BUSQUEDA
- **N anuncios DE:** X
- **N anuncios ES:** X
- **Mediana DE:** X €
- **Mediana ES:** X €
- **Hueco %:** X%
- **Vendibilidad estimada:** X/100
- **Veredicto:** Comprar / Dudoso / Descartar
- **Mejor candidato:** URL mobile.de/...
- **Fuentes cubiertas:** 7/7 (o listar cuáles y cuáles bloqueadas/omitidas + motivo)
- **Peticiones usadas:** X
- **Refrescar antes de:** <fecha> (si pasada o vacía → REBUSCAR; si futura → mostrar cache, no rehacer)
- **Notas:** [lo relevante]
```

> **Reglas de cache (16-ago-2026):** al retomar un modelo, leer esta entrada ANTES de navegar (PASO 0).
> - `refrescar_antes_de` en futuro → mostrar resumen + preguntar ¿delta / refrescar / nuevo?
> - campo vacío o fecha pasada → se puede re-investigar sin preguntar (con plan de fase).
> - El resumen de fecha/tamaño vive también en `indice.json` (Desktop) — cruzarlos en el arranque.

---

## 🚗 Opel Astra OPC (280 CV)

### Opel Astra OPC 2013 · 2026-07-30
- **Tipo:** UNIDAD (id: `opel-astra-opc-2013-455420293`)
- **N anuncios DE:** 12
- **N anuncios ES:** 8
- **Mediana DE:** 12.400 €
- **Mediana ES:** 16.400 €
- **Hueco %:** 24,4%
- **Vendibilidad estimada:** 76/100
- **Veredicto:** Comprar (oferta de contenido)
- **Mejor candidato:** https://www.mobile.de/... (Astra J GTC OPC 2.0 Turbo)
- **Fuentes cubiertas:** 7/7 · **Peticiones usadas:** ~40
- **Refrescar antes de:** 2026-08-20
- **Notas:**
  - Fotos reales disponibles en `laravel/informes/opel-astra-opc-2013-455420293_fotos/`
  - 25 fotos JPG numeradas (001-025)
  - Discrepancia km: 114.615 (anuncio) vs 114.476 (odómetro en foto)
  - Equipamiento destacado: Llantas OPC 20", Asientos Performance, FlexRide

### Opel Astra OPC 2012 · 2026-08-10
- **Tipo:** MODELO
- **N anuncios DE:** ~30
- **N anuncios ES:** 12
- **Mediana DE:** 10.500 €
- **Mediana ES:** 15.200 €
- **Hueco %:** 30,9%
- **Vendibilidad estimada:** 70/100
- **Veredicto:** Comprar si baja
- **Fuentes cubiertas:** 7/7 · **Peticiones usadas:** ~50
- **Refrescar antes de:** 2026-08-31
- **Notas:**
  - Cobertura 7/7 fuentes
  - Modelo veterano (última serie 2015)
  - Posibles problemas cadena distribución pre-2014

---

## 🚙 VW Tiguan (TSI 150-180 CV)

### VW Tiguan 1.4 TSI Comfortline 2017 · 2026-08-05
- **Tipo:** UNIDAD (id: `vw-tiguan-14tsi-comfortline-2017-461371119`)
- **N anuncios DE:** ~20 · **N anuncios ES:** ~10
- **Mediana DE:** — · **Mediana ES:** — · **Hueco %:** —
- **Vendibilidad estimada:** — · **Veredicto:** oferta aceptable (margen 12%)
- **Fuentes cubiertas:** 7/7 (tanda Tiguan)
- **Refrescar antes de:** 2026-08-19
- **Notas:** Oferta aceptable. Margen 12%.

### VW Tiguan 1.4 TSI Highline 2018 · 2026-08-05
- **Tipo:** UNIDAD (id: `vw-tiguan-14tsi-highline-2018-462178185`)
- **N anuncios DE:** ~20 · **N anuncios ES:** ~10
- **Mediana DE:** — · **Mediana ES:** — · **Hueco %:** —
- **Vendibilidad estimada:** — · **Veredicto:** Comprar si baja
- **Fuentes cubiertas:** 7/7 (tanda Tiguan)
- **Refrescar antes de:** 2026-08-19
- **Notas:** Veredicto Comprar si baja. Km alto (85k).

### VW Tiguan 1.4 TSI Sound 2017 · 2026-08-05
- **Tipo:** UNIDAD (id: `vw-tiguan-14tsi-sound-2017-460801471`)
- **N anuncios DE:** ~20 · **N anuncios ES:** ~10
- **Mediana DE:** — · **Mediana ES:** — · **Hueco %:** —
- **Vendibilidad estimada:** — · **Veredicto:** Comprar si baja
- **Fuentes cubiertas:** 7/7 (tanda Tiguan)
- **Refrescar antes de:** 2026-08-19
- **Notas:** Veredicto Comprar si baja. Garantía restante.

### VW Tiguan 1.5 TSI R-Line 2020 · 2026-08-05
- **Tipo:** UNIDAD (id: `vw-tiguan-15tsi-rline-2020-461787152`)
- **N anuncios DE:** ~20 · **N anuncios ES:** ~10
- **Mediana DE:** — · **Mediana ES:** — · **Hueco %:** —
- **Vendibilidad estimada:** — · **Veredicto:** —
- **Fuentes cubiertas:** 7/7 (tanda Tiguan)
- **Refrescar antes de:** 2026-08-19
- **Notas:** Última generación 1.5 TSI. Buen equipamiento.

### VW Tiguan 150 CV Gasolina · 2026-08-11
- **Tipo:** MODELO
- **N anuncios DE:** — · **N anuncios ES:** —
- **Mediana DE:** — · **Mediana ES:** — · **Hueco %:** —
- **Vendibilidad estimada:** — · **Veredicto:** estudio global del segmento
- **Fuentes cubiertas:** — · **Peticiones usadas:** —
- **Refrescar antes de:** 2026-09-01
- **Notas:** Estudio global. Datos en `laravel/investigacion_modelos/volkswagen-tiguan-150cv-gasolina.json`

---

## 🚗 Seat León gasolina (2016+) — SIN BANDA medido

### Seat León gasolina 2016+ · 2026-08-18 (control de año + sin banda)
- **Tipo:** MODELO (control: 2019+ en ambos mercados, SIN banda)
- **N anuncios DE:** 753 · **N anuncios ES:** 4.727
- **Mediana DE:** 20.920 € · **Mediana ES:** 19.400 €
- **Hueco bruto:** −7,8% (ES mejor) → **Hueco neto:** −15,4%
- **Suelo verificado:** DE 8.000 € · ES 8.000 € (origen `categorias.gemas_economicas`)
- **Veredicto:** 🟡 Dudoso para importación (ES mejor) — **gema nacional** (compra cliente directo en ES)
- **Mejor candidato (si importara):** habría que esperar — la banda 8-17k con control 2019+ dio −7,7% neto; sin banda es peor
- **Fuentes cubiertas:** 2/7 (mobile.de + Coches.net) · **Peticiones usadas:** ~10
- **Refrescar antes de:** 2026-09-14 (caduca gemas +4 sem)
- **Notas:**
  - **CONFIRMA** el patrón "marca nacional": León gasolina más barato en ES (Martorell, stock abundante).
  - **NO descartar la regla** — se mantiene la corrección 18-ago de la Cupra (suelo DE 15-16k → ahí sí hay hueco real, distinto al caso León gasolina).
  - Map: `datos_mercado.json → hueco_sin_banda[1]` + `categorias.gemas_economicas[5]`.

---

## 🚙 Cupra León — pendiente RE-MEDIR sin banda + máximo equipamiento (18-ago)

### Cupra León (veredicto NO fiable)
- **Tipo:** MODELO · **Última medición:** 2026-08-17 (mediana con banda `≥20k` en AMBOS mercados)
- **N anuncios DE:** 5.321 · **N anuncios ES:** 655 (586 son 2023+)
- **Mediana DE:** 30.790 € · **Mediana ES:** 27.970 €
- **Hueco bruto:** −10,1% (banda ≥20k) → **Hueco neto:** −20,6%
- **Suelo registrado:** DE 15.500 € · ES 19.500 €
- **Veredicto actual:** 🟢 verde (medición sesgada, NO de fiar)
- **Motivo RE-MEDIR:** la banda ≥20k recortó la cola barata DE → sesgo metodológico (mismo error que Golf v2). La regla Seat/Cupra dice **mirar el suelo sin banda**: DE 15-16k vs ES 19,5k → **hueco POSITIVO probable**.
- **Refrescar antes de:** 2026-08-31 (showstoppers caduca)
- **Plan:**
  1. ES (Coches.net): `cupra-leon?MinPrice=15500&fi=Price&or=1` SIN techo de banda → recoger mediana real, marca equipamiento (en Coches.net solo hay techo solar como proxy full).
  2. DE (mobile.de): `ms=3;6;;&sb=p&od=up` SIN `p=` mínimo + checkboxes full (cuadro digital+Head-up+calefacción+techo+LED) + `pw=:` para aislar VZ. Suelo 15.500 € abajo.
  3. Recalcular hueco bruto y neto (coste_importacion 2.929 + IEDMT 1.800).
  4. Reemplazar entrada en `datos_mercado.json` con `fuente_medicion: estudio`, `pendiente_fase2: false`, `confianza_precio: 3-4`.
- **Notas:**
  - Categoría: showstopper · segmento: deportivo · rango: 25k+ · tipo_cliente: impacto_showstopper / deporte_ocio / premium_imagen.
  - Si el nuevo hueco sale ≥10% bruto y ≥0% neto → verde real con suelo de 15.500 €.

---

## 🚗 VW Golf GTI (pendiente)

### Pendiente medir
- Golf 8 GTI Clubsport 2021+ (Fase 1)
- Golf R 2021+ (Fase 1)
- Golf GTI Performance 2021+

### Notas del modelo
- Última medición: 2026-08-12 (búsqueda inicial sin conclusión)
- 2.707 resultados en AutoScout24.de (Golf Gti)
- 8.991 resultados en Coches.net (Golf GTI)
- 4.181 resultados en kleinanzeigen.de
- **Fuentes cubiertas:** 3 (parcial) · **Peticiones usadas:** —
- **Refrescar antes de:** 2026-08-19 (re-investigar con plan de fase)

---

## 📊 Resumen acumulado

| Métrica | Valor |
|---|---|
| Coches evaluados (Flujo A) | 7 |
| Modelos estudiados (Flujo B) | 3 |
| Búsquedas mercado (Flujo C) | 1 |
| Encargos DESCUBRIMIENTO (Flujo D) | 1 (abortado) |
| Veredictos 🟢 Comprar | 1 |
| Veredictos 🔵 Comprar si baja | 4 |
| Veredictos 🟡 Dudoso | 1 |
| Veredictos 🔴 Descartar | 1 |
| Margen medio conseguido | 18% |
| Tiempo medio de medición | 12 min |

---

## 🗓️ Última actualización

- **2026-08-12:** Sistema de memoria iniciado. 7 coches + 3 modelos registrados.
