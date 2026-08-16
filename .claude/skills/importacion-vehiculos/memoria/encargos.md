# Encargos — Registro central

> **Registro maestro de encargos.** Cada fila = un encargo cerrado (o abortado). Enlaza cliente → briefing (modalidad M1/M2/M3) → flujo → entregables → ruta ZIP → resultado → fecha.
>
> **Para qué sirve:**
> - **PASO 0 cache** (ver `../SKILL.md` §ARRANQUE): al recibir un encargo, comprobar aquí si el cliente ya tuvo uno o el modelo ya se investigó → NO re-buscar.
> - **Auditoría de cierre**: alimenta la dimensión "resultado" y el historial de qué funcionó.
> - **Continuidad**: si el cliente vuelve, retomar contexto (presupuesto, modalidad, preferencias) sin volver a preguntar.
>
> **Formato de cada entrada (una `###` por encargo):**

```markdown
### <cliente o anónimo> · <modelo> · <fecha>
- **Tipo:** UNIDAD | MODELO | BUSQUEDA | DESCUBRIMIENTO
- **Modalidad:** M1 (incluidos) | M2 (aparte) | M3 (sin honorarios)
- **Origen:** DE | ES | ambos
- **Presupuesto:** X € | techo DE: X € · techo ES: X €
- **Requisitos:** año ≥ · km ≤ · cv ≥ · combustible · cambio
- **Flujo:** D → B → A | B → A | A directo | C
- **Estado:** cerrado 🟢 | abortado 🔴 | en curso
- **Entregables:** informe_modelo_<fecha>.md · informe_unidad_<fecha>.md · dossier · ZIP (coche_id)
- **Resultado:** veredicto + precio + score + candidato (URL)
- **Refrescar antes de:** <fecha> (si es cache reutilizable)
- **Notas:** [lecciones, preferencias del cliente, por qué cerró/abortó]
```

---

## 📋 Registro

### Opel Astra OPC 2013 · 2026-07-30
- **Tipo:** UNIDAD · **Modalidad:** — · **Origen:** DE
- **Presupuesto:** — · **Requisitos:** OPC 280 CV 2013
- **Flujo:** A directo · **Estado:** cerrado 🟢
- **Entregables:** informe_unidad · fotos `laravel/informes/opel-astra-opc-2013-455420293_fotos/` (25 JPG) · ZIP
- **Resultado:** Comprar · mediana DE 12.400 € / ES 16.400 € · hueco 24,4% · score 76/100 · candidato Astra J GTC OPC 2.0 Turbo
- **Refrescar antes de:** 2026-08-20
- **Notas:** discrepancia km anuncio (114.615) vs odómetro foto (114.476). Equipamiento: llantas OPC 20", asientos Performance, FlexRide.

### VW Tiguan 1.4 TSI Comfortline 2017 · 2026-08-05
- **Tipo:** UNIDAD · **Modalidad:** — · **Origen:** DE
- **Flujo:** A directo · **Estado:** cerrado 🟢
- **Entregables:** informe_unidad (id `vw-tiguan-14tsi-comfortline-2017-461371119`)
- **Resultado:** oferta aceptable · margen 12%
- **Notas:** parte de la tanda Tiguan del 5-ago (4 unidades).

### VW Tiguan 1.4 TSI Highline 2018 · 2026-08-05
- **Tipo:** UNIDAD · **Origen:** DE · **Flujo:** A directo · **Estado:** cerrado 🟢
- **Entregables:** informe_unidad (id `vw-tiguan-14tsi-highline-2018-462178185`)
- **Resultado:** Comprar si baja · km alto (85k)
- **Notas:** tanda Tiguan 5-ago.

### VW Tiguan 1.4 TSI Sound 2017 · 2026-08-05
- **Tipo:** UNIDAD · **Origen:** DE · **Flujo:** A directo · **Estado:** cerrado 🟢
- **Entregables:** informe_unidad (id `vw-tiguan-14tsi-sound-2017-460801471`)
- **Resultado:** Comprar si baja · garantía restante
- **Notas:** tanda Tiguan 5-ago.

### VW Tiguan 1.5 TSI R-Line 2020 · 2026-08-05
- **Tipo:** UNIDAD · **Origen:** DE · **Flujo:** A directo · **Estado:** cerrado 🟢
- **Entregables:** informe_unidad (id `vw-tiguan-15tsi-rline-2020-461787152`)
- **Resultado:** última gen 1.5 TSI · buen equipamiento
- **Notas:** tanda Tiguan 5-ago.

### Opel Astra OPC 2012 (modelo) · 2026-08-10
- **Tipo:** MODELO · **Origen:** ambos · **Flujo:** B → A
- **Estado:** cerrado 🟢 · **Entregables:** informe_modelo + informe_unidad
- **Resultado:** Comprar si baja · mediana DE 10.500 € / ES 15.200 € · hueco 30,9% · score 70/100 · cobertura 7/7
- **Refrescar antes de:** 2026-08-31
- **Notas:** modelo veterano (última serie 2015). Posibles problemas cadena distribución pre-2014.

### VW Tiguan 150 CV gasolina (modelo) · 2026-08-11
- **Tipo:** MODELO · **Origen:** ambos · **Flujo:** B
- **Estado:** cerrado 🟢 · **Entregables:** informe_modelo + JSON `laravel/investigacion_modelos/volkswagen-tiguan-150cv-gasolina.json`
- **Resultado:** estudio global del segmento
- **Refrescar antes de:** 2026-09-01

### Golf GTI (modelo) · 2026-08-12
- **Tipo:** MODELO · **Origen:** ambos · **Flujo:** B
- **Estado:** en curso (sin conclusión)
- **Entregables:** — · **Resultado:** pendiente
- **Datos:** AS24 2.707 · Coches.net 8.991 · kleinanzeigen 4.181
- **Notas:** pendientes: Golf 8 GTI Clubsport 2021+, Golf R 2021+, GTI Performance 2021+.

### María · 9.000 € · compacto · 2026-08-15
- **Tipo:** DESCUBRIMIENTO (Flujo D) · **Modalidad:** — · **Origen:** ambos
- **Presupuesto:** 9.000 € todo incluido · **Requisitos:** 2016+, gasolina, +120cv, 5p
- **Flujo:** D → (abortado antes de B) · **Estado:** abortado 🔴
- **Entregables:** informe_modelos parcial
- **Notas:** se navegó a anuncios reales sin modelo elegido → informe PARCIAL. Lección: D1 con navegación real y filtros, nunca snippets (A15). Foco ES dio Focus "~9.900 €" falso vs navegación real 3.000-6.990 €.
