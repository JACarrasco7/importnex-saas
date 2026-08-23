# 📄 Plantilla de informe de mercado — estudio-mercado (23-ago-2026)

> **Regla de formato del informe de estudio (23-ago-2026):** el informe es un **documento de decisión para el usuario**, no una bitácora de investigación. Debe poder leerse en 1 minuto y responder a: *¿este modelo/variante merece la pena importar? ¿qué candidato concreto veo?*
>
> **MD vs PDF (resuelto 23-ago-2026):**
> - **SIEMPRE Markdown (`.md`)** = la fuente. Enlaces clicables.
> - **PDF SOLO si el usuario lo pide explícitamente** (lectura fuera de pantalla). El PDF NO lleva enlaces funcionales → en su lugar, en cada candidato se imprime la URL **visible** (no "ver [enlace]"). NUNCA generar MD+PDF a la vez por defecto: se duplican.
> - El detalle metodológico/cobertura va **al final**, no al principio.
>
> Guardar en `informes\mercado\<modelo>_<fecha>.md`.

---

## 🏁 CONCLUSIÓN (1 minuto — lo primero que se lee)

> 4-6 líneas: qué se estudió, veredicto general, si merece la pena importar y cuál es el mejor hueco.

**VW Golf 7.5 (GTI · TCR · Clubsport · R)** — estudio 2026-08-22/23.

- Las 4 variantes tienen **hueco DE→ES positivo** (12-28% neto): importar desde Alemania sale mejor en todas.
- **Mejor hueco:** Golf R (+21-25% neto) y Clubsport (+21,6% neto).
- **Hueco más ajustado:** GTI estándar (+12,9% neto).
- ⚠️ **Clubsport solo existe en Mk7 pre-facelift** (2016-2017); no hay Mk7.5. Ojo al etiquetar.
- ⚠️ **Re-chipeo endémico en Golf R/Clubsport DE** (36% de las fichas con OPF quitado o potencia alterada): todo precio agresivo hay que verificar ficha a ficha.

**Tabla resumen por variante:**

| Variante | Suelo DE | Suelo ES | Hueco bruto | Hueco neto¹ | Veredicto |
|---|---|---|---|---|---|
| GTI (230/245cv) | 15.999€ | 19.690€ | +18,7% | **+12,9%** | 🟢 importar |
| GTI TCR (290cv) | 19.699€ | 23.695€² / 28.900€ | +16,9% / +31,8% | **+12,1% / +27,9%** | 🟢 importar |
| GTI Clubsport (265cv, Mk7) | 16.499€ | 22.490€ | +26,6% | **+21,6%** | 🟢 importar |
| Golf R (310cv) | 15.950€³ / 16.899€ | 22.880€ | +30,3% / +26,1% | **+25,3% / +21,2%** | 🟢 importar |

¹ Neto = bruto − 1.129€ (transporte+ausfuhr+ITV), sin IEDMT (se calcula por unidad).
² Suelo con reserva (accidente reparado en oficial) · 28.900€ = suelo limpio comparable.
³ Suelo con reservas mecánicas · 16.899€ = suelo limpio DE.

> **🏷️ Fiabilidad de cada suelo:** junto a cada precio, indicar si es **verificado en ficha** (✅) o **de listado** (👁️ sin verificar, solo precio/año/km del listado — el anti-bot cortó antes de abrir la ficha). El suelo "oficial" del estudio es siempre el ✅ más bajo; el 👁️ se anota aparte como "posible suelo inferior pendiente de verificar".

---

## 🎯 CANDIDATOS A VER (los que merecen la pena, con enlace)

> 1-2 por variante. Solo los que pasan filtro: suelo fiable + sin indicios de modificación. URL **visible completa** (funciona también en PDF).

### GTI (230/245cv)
| Precio | Año | Km | Por qué | URL |
|---|---|---|---|---|
| 15.999€ | 2017 | 106.726 | Suelo DE, 5p DSG, cuadro digital, sin accidentes | https://www.mobile.de/es/vehículos/detalles.html?id=40947884798464 |
| 19.690€ | 2017 | 155.361 | Suelo ES, 3p manual, 245cv Performance | https://www.coches.net/volkswagen-golf-gti-performance-20-tsi-245cv-5p-gasolina-2017-en-madrid-71274163-covo.aspx |

### GTI TCR (290cv)
| Precio | Año | Km | Por qué | URL |
|---|---|---|---|---|
| 19.699€ | 2019 | 149.702 | Suelo DE, 5p DSG | https://www.mobile.de/es/vehículos/detalles.html?id=38717798642208 |
| 28.900€ | 2019 | 117.949 | Suelo ES limpio, full (techo+cuadro digital) | https://www.coches.net/volkswagen-golf-gti-tcr-20-tsi-213kw290cv-dsg-5p-gasolina-2019-en-madrid-71332726-covo.aspx |

### GTI Clubsport (265cv, Mk7 pre-facelift)
| Precio | Año | Km | Por qué | URL |
|---|---|---|---|---|
| 16.499€ | 2016 | 153.000 | Suelo DE genuino, sin mods | https://www.mobile.de/es/vehículos/detalles.html?id=452337727 |
| 22.490€ | 2016 | 143.000 | Suelo ES, techo panorámico, "precio justo" | https://www.coches.net/volkswagen-golf-gti-clubsport-20-tsi-265cv-bmt-dsg-5p-gasolina-2016-en-barcelona-71264521-covo.aspx |

### Golf R (310cv)
| Precio | Año | Km | Por qué | URL |
|---|---|---|---|---|
| 16.899€ | 2017 | 176.147 | Suelo DE limpio, 5p DSG | https://www.mobile.de/es/vehículos/detalles.html?id=461400725 |
| 22.880€ | 2017 | 130.000 | Suelo ES "Super precio", 3p DSG, techo | https://www.coches.net/volkswagen-golf-r-20-tsi-228kw-310cv-4motion-dsg-3p-gasolina-2017-en-sevilla-70611650-covo.aspx |

---

## 📊 POR VARIANTE (detalle breve, 3-5 líneas cada una)

> La segmentación (3p/5p, manual/DSG, techo, cuadro digital) solo se incluye si **afecta al precio**. Si no marca diferencia clara, se dice en 1 línea y se omite la tabla.

- **GTI:** cambio y puertas no marcan prima limpia; pesa km y trim (230 vs 245 Performance). Cuadro digital en 7/14 DE (sin sobreprecio aislado). Techo rarísimo (1/14).
- **TCR:** solo existe 5p+DSG de fábrica → sin segmentación posible. El precio lo domina km/estado.
- **Clubsport:** manual raro (2/12) y no penalizado. Sin cuadro digital confirmado en ninguno. Km <50k cobra prima fuerte (26-31k€). **Re-chipeo endémico** (5 casos detectados).
- **Golf R:** 5 de 14 fichas DE (36%) con OPF quitado o escape no original → todo precio <17k hay que verificar. 2 manuales raros no penalizados.

---

## ⚠️ AVISOS / TRAMPAS (solo las que cambian la decisión)

1. **Clubsport ≠ Mk7.5:** el Clubsport genuino es Mk7 pre-facelift (2016-2017). Corregir etiquetas previas.
2. **Re-chipeo silencioso:** en 2/5 casos el campo de potencia estaba alterado SIN aviso en el título. Leer descripción completa + comparar potencia vs catálogo.
3. **Precio financiado < contado (ES):** usar siempre el contado de la ficha (3 casos detectados con -2.000€).
4. **Coche en España pero físicamente en Alemania** sin matricular (TCR Tarragona 24.999€) → no es suelo ES.
5. **Techo vinilado ≠ techo solar** (Clubsport ES): verificar en foto.

---

## 📋 COBERTURA Y METODOLOGÍA (detalle al final)

- **Fuentes:** solo mobile.de (DE) + Coches.net (ES). El resto reservado para buscar unidades.
- **Filtro:** ≤180.000 km.
- **Verificación:** filtros estructurados (potencia+combustible+año+km) en Coches.net — el filtro `Version=` texto libre NO es fiable.
- **Cobertura:** GTI/TCR/Clubsport completos (10-14 c/u DE); **Golf R DE parcial** (solo página 1/6, ~95 anuncios sin revisar) → su hueco real podría ser mayor.
- **Tamaño de muestra:** 2-9 verificados por lado → aproximación suelo-a-suelo, no mediana robusta.
- **Pendiente:** cubrir páginas 2-6 de Golf R DE antes de ofertar basado en su suelo alemán.

---
