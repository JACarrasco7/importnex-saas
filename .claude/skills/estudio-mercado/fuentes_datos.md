# Fuentes de datos del estudio de mercado (ES + DE)

> Ordenadas por capa (de gratis a pago). Regla: empezar por Capa 1 + 2; dejar el esquema preparado para Capa 3.
> **Fiabilidad:** 2 fuentes de precio de referencia (mobile.de DE + Coches.net ES). El resto complementa (oferta, rotación, demanda, chollos), NUNCA sustituye a esas 2.

---

## 🇪🇸 España

### Capa 1 · Pública / gratuita
| Fuente | Dato que aporta | Acceso |
|---|---|---|
| **DGT** — estadísticas de transferencias VO | nº de transferencias mensuales por marca/modelo (demanda real de compra) | Web pública, informes PDF/microdatos |
| **GANVAM** — índice de precios VO | variación mensual de precios VO, por segmento | Informe mensual público (PDF) |
| **FACONAUTO / ANFAC** — matriculaciones VO | volumen de mercado VO (parque y transferencias) | Informes mensuales públicos |
| **Google Trends** | interés de búsqueda por modelo (demanda) | trends.google.com, navegación real |

### Capa 2 · Portales (navegación real)
| Fuente | Rol | Fiabilidad precio |
|---|---|---|
| **Coches.net** | Precio ES de REFERENCIA + oferta + rotación + tasación | 🟢 Alta |
| **AutoScout24.es** | Contar oferta ES | 🟡 Solo contar |
| **Wallapop** | Chollos particulares ES | 🟡 Negociable |
| **Milanuncios** | Chollos particulares ES | 🟡 Negociable |

### Capa 3 · Pago (futuro)
- **Eurotax / Glasses** — tasación profesional VO española.
- **GANVAM datos panel** — series históricas por modelo (suscripción).

---

## 🇩🇪 Alemania

### Capa 1 · Pública / gratuita
| Fuente | Dato que aporta | Acceso |
|---|---|---|
| **KBA (Kraftfahrt-Bundesamt)** | matriculaciones, parque, bajas por marca/modelo | Web pública, estadísticas |
| **DAT / ADAC** — informes de coste | coste de mantenimiento/reparación por modelo (fiabilidad) | Informes públicos parciales |
| **Google Trends (DE)** | interés de búsqueda por modelo en Alemania | trends.google.com |

### Capa 2 · Portales (navegación real)
| Fuente | Rol | Fiabilidad precio |
|---|---|---|
| **mobile.de** | Precio DE de REFERENCIA + oferta + días publicados + sellos "Sehr guter/Guter/Fairer Preis" | 🟢 Alta |
| **AutoScout24.de** | Contar oferta DE | 🔴 NUNCA precio (A8) |
| **kleinanzeigen.de** | Chollos particulares DE (VB = negociable) | 🟡 |
| **AutoUncle** | Rotación DE (días publicado) | 🟡 Solo contar |

### Capa 3 · Pago (futuro)
- **DAT / Schwacke** — tasación profesional VO alemana (el estándar).
- **mobile.de Market Insights / AS24 Marktreport** — precios medios, tendencias, días en stock (API/reportes).

---

## 📈 Métricas por fuente (qué se extrae de cada una)

| Métrica | Fuente primaria | Secundaria |
|---|---|---|
| **Precio de referencia ES** | Coches.net | — |
| **Precio de referencia DE** | mobile.de | — |
| **Oferta (nº anuncios)** | Coches.net + mobile.de + AS24 (contar) | resto |
| **Rotación (días en stock)** | AutoUncle (DE) + Coches.net (ES) | días publicados en listados |
| **Demanda (interés)** | Google Trends (ES + DE) | transferencias DGT, matriculaciones KBA |
| **Tamaño de mercado** | KBA (parque) + DGT (transferencias) | informes GANVAM/FACONAUTO |
| **Fiabilidad/coste mantenimiento** | ADAC / DAT informes públicos | km77 (ES) |
| **Tasación profesional (futuro)** | DAT/Schwacke (DE) + Eurotax (ES) | — |

---

## ⚠️ Trampas y cautelas (heredadas de `importacion-vehiculos/memoria/trampas-encontradas.md`)

- **AS24 NUNCA para precio** (agrega feeds sin cribar → anuncios siniestrados/fechas mal etiquetadas). Solo contar (A8).
- **Kleinanzeigen "VB"** = negociable → restar 10-15%.
- **Coches.net contador global vs por modelo**: el H1 "259.000 coches" es global; usar el nº de resultados del listado filtrado.
- **Página 1 sola sesga** hacia lo barato (A12): paginar o usar bandas de precio.
- **Topes de gama** (GTI/R/M/AMG/RS/OPC): doble pasada por kW (el filtro por variante de texto pierde coches genuinos mal etiquetados).
- **Google Trends**: comparar SIEMPRE en el mismo periodo y normalizar (es relativo, no volumen absoluto).

---

## 🔄 Cadencia de refresco por fuente

| Fuente | Cadencia | Motivo |
|---|---|---|
| Portales (precios/oferta) | 2-4 semanas según categoría (L7: showstoppers 2 · rotación 3 · gemas 4) | los anuncios rotan rápido |
| DGT / KBA (matriculaciones/transferencias) | mensual | se publican con retraso de ~1 mes |
| GANVAM / FACONAUTO | mensual | informe mensual |
| Google Trends | 3-4 semanas | tendencia estable a corto plazo |
| Tasación pro (Capa 3) | bajo demanda | caro; solo cuando se valora una unidad |
