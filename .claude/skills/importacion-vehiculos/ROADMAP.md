# Roadmap — Importación de Vehículos

> Última revisión: 12/08/2026
> Fuente: skill original + `ROADMAP.md` del escritorio

---

## Lado Laravel (completado 12 ago 2026)

- [x] Endpoints API: `/api/import-valuation` (A), `/api/import-modelo` (B), `/api/import-mercado` (C)
- [x] Cache de investigación multi-tenant: `investigation_cache` + `organization_id` + soft deletes
- [x] Registro de cierres: `/api/cierres` (POST/GET) + modelo `Cierre`
- [x] KPIs agregados: `/api/kpis` (histórico mensual) + dashboard web `/kpis`
- [x] Filtros de marca/plataforma en dashboard KPIs (brand denormalizado en cierres)
- [x] Backfill command: `skill:backfill-investigation-cache` (dry-run por defecto)
- [x] Briefing PDF: `/api/cars/{car}/briefing-pdf`
- [x] IEDMT en `config/iedmt.php` (single source of truth, Anexo IV corregido)
- [x] `KpiCalculator` service (lógica KPIs unificada web/API)
- [x] Test suite: 130+ tests, 500+ aserciones

## Hecho (8-10 ago 2026)

- [x] Wallapop y Milanuncios arreglados
- [x] Coches.net reescrito sobre `__INITIAL_PROPS__` (35/pág + tasación + fecha)
- [x] km77 como fuente PVP, CO₂ y tipo IEDMT
- [x] Método IEDMT correcto con minoración art.69
- [x] Auditoría completa 6 portales
- [x] Librería única extractores
- [x] Corrección sesgo exclusión por falta datos → sello `man`
- [x] Corrección equipamiento corto en topes de gama
- [x] 4 fuentes alemanas directas (no solo AutoUncle)
- [x] `hadAccident` AS24 no fiable vs texto libre
- [x] Plantilla PDF con membrete + tarjetas resumen
- [x] 3 componentes unificados en un solo flujo
- [x] Estructura carpetas marca/modelo + `indice.json`
- [x] Tabla cobertura 7 fuentes (arranque obligatorio)
- [x] `www.mobile.de` como alternativa a `suchen.`/`m.`
- [x] `franja.py`: franja precio + desgloses + lotes
- [x] Matrículas temporales como líneas propias
- [x] Desglose obligatorio del precio de cada coche
- [x] 5 reglas del comparable honesto
- [x] Recalls: KBA alemán, no NHTSA
- [x] PDFs los monta Laravel (Blade + Browsershot), no Python

---

## Urgente (del rastreador)

| # | Tarea | Impacto |
|---|---|---|
| 1 | **Calibrar descuento por días publicado** con cierres reales | 3-8 puntos margen inflado |
| 2 | **Construir índice de rotación** con `publicationDate` y `publishDate` | Desbloquea factor 1 (peso 30) |
| 3 | **Deduplicar entre fuentes** por `(año, km ±2%, CV, precio ±3%)` | Coches contados 2 veces |
| 4 | Varias consultas por modelo y unir | Anuncios perdidos por texto |
| 5 | Paginación completa Coches.net con `pg`, verificando `initialSearch` | Muestras incompletas |
| 6 | **Extractor propio kleinanzeigen.de** | Fuente 7 sin cobertura real |
| 7 | **Caché fichas km77** | Tokens malgastados |
| 8 | Ampliar muestra española en tramos km bajos (<130.000 km) | Candidatos sin comparable |
| 9 | **Definir esquema `datos_mercado.json`** para Laravel | Bloquea integración |
| 10 | **Registro coches descartados** por VIN/URL | No repetir descartes |

---

## Sin medir

- BMW M240i
- Volvo V90 y XC60 T8
- Mercedes Clase A (sumando versiones)
- Toyota GR Yaris
- Golf 8 GTI Clubsport
- Audi RS4/RS6
- Mercedes C43

---

## Rehacer con método nuevo

- 4 Golf R y lista larga del 6-ago (arrastran fallos `countryCode` y `powertype`)

---

## Lado Laravel (usuario)

- [ ] Plantillas Blade para ficha publicitaria y briefing
- [ ] Los dos esqueletos en `empaquetar.py`
- [ ] Campos `iva_deducible` y `ahorro_si_alta`
- [ ] Validador regla 6/6.000
- [ ] Tarifas reales de transporte
- [ ] Registro de demanda (quién busca qué, presupuesto, plazo)
- [ ] Importar `datos_mercado.json`
- [ ] Tabla de primas de equipamiento
- [ ] Índice de vendibilidad automatizado
