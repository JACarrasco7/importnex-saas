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
