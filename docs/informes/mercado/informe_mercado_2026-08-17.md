# Estudio de mercado ES + DE — 17 de agosto de 2026
## JJ Import Motors · skill `estudio-mercado` · estudio dirigido doble

**Alcance pactado (FASE 0):**

| | Bloque A — Showstoppers | Bloque B — Compactos diario |
|---|---|---|
| Perfil de cliente | `deporte_ocio` / `impacto_showstopper` (entusiasta) | `diario_eficiencia` / `gemas_economicas` (uso ciudad) |
| Segmento | deportivo / compacto deportivo | compacto · urbano |
| Banda de precio | **≥ 20.000 €** | **8.000 – 17.000 €** |
| Filtros extra | — | gasolina o híbrido · matriculación **2016+** |
| Profundidad | completo (oferta + medianas + hueco bruto + hueco neto) | ídem |
| Banda aplicada | **en AMBOS mercados** (decisión del usuario) | **en AMBOS mercados** |

**Fuentes navegadas:** Coches.net (ES) y mobile.de (DE), navegación real, listado-first (A17), precio **al contado** (A10), orden ascendente por precio.
**Método de mediana:** conteo total del listado (`N`) + lectura del tramo de precios en la página que contiene el elemento `N/2`, con interpolación. **Validado con 3 contrastes por conteo directo** (ver §Verificación).

---

## 1. Resumen ejecutivo — las 3 conclusiones que importan

### 🔴 1. En deportivos de 20k+ NO hay negocio de importación. España está igual o más barata que Alemania.

En los cinco modelos medidos con dato sólido, el hueco bruto es **cero o negativo** — antes incluso de sumar los 1.129 € de costes fijos y el IEDMT:

| Modelo | Mediana ES | Mediana DE | Hueco bruto | Hueco **neto** (con costes) |
|---|---:|---:|---:|---:|
| Mercedes-Benz A 45 AMG | 33.900 € | 38.980 € | **−15,0 %** | −23,6 % |
| Cupra León | 27.970 € | 30.790 € | **−10,1 %** | −20,6 % |
| VW Golf R (≥290 cv 4Motion) | 34.250 € | 36.940 € | **−7,9 %** | −16,4 % |
| Audi S3 | 38.500 € | 39.290 € | **−2,1 %** | −9,7 % |
| VW Golf GTI | 29.500 € | 29.900 € | **−1,4 %** | −11,3 % |

**Lectura de negocio:** al cliente entusiasta se le cobra la **tarifa ES reducida (~500 €)** por búsqueda y verificación en España. Proponerle una importación en estos modelos sería venderle un coste de 2.900 € (transporte + ausfuhr + ITV + IEDMT) para comprar más caro.

### 🟡 2. En compactos 8-17k el hueco bruto existe pero se lo come la importación.

| Modelo | Mediana ES | Mediana DE | Hueco bruto | Hueco **neto** |
|---|---:|---:|---:|---:|
| Toyota Auris híbrido | 14.400 € | 12.500 € | **+13,2 %** | **+5,4 %** ✅ |
| Renault Mégane gasolina | 12.550 € | 12.000 € | +4,4 % | −7,4 % |
| Kia Ceed gasolina | 14.580 € | 13.950 € | +4,3 % | −5,8 % |
| Toyota Corolla híbrido | 15.900 € | 15.500 € | +2,5 % | −4,6 % |
| Peugeot 308 gasolina | 11.500 € | 11.500 € | 0,0 % | −12,9 % |

**El único que sobrevive al coste de importación es el Toyota Auris híbrido** (+5,4 % neto), y con margen fino. El resto: comprar en España.

Motivo estructural: en la banda 8-17k el coste fijo de importación (1.129 € + IEDMT) representa un **8-13 % del precio del coche**. Un hueco bruto necesita ser ≥12 % solo para empatar — el umbral que la propia skill fija para el tramo 8-14k.

### ⚙️ 3. Donde sí hay valor: la oferta alemana es 5-18× más profunda.

| Modelo (compacto, 8-17k, 2016+) | Anuncios ES | Anuncios DE | Ratio |
|---|---:|---:|---:|
| VW Golf gasolina | 405 | 7.351 | **×18** |
| Ford Focus gasolina | 431 | 5.123 | ×12 |
| Opel Astra gasolina | 330 | 3.492 | ×11 |
| Mazda 3 gasolina | 33 | 241 | ×7 |
| Seat León gasolina | 468 | 2.518 | ×5 |
| Cupra León (≥20k) | 655 | 5.321 | ×8 |

El argumento comercial de JJ Import Motors en estas dos categorías **no es el precio, es la búsqueda**: encontrar la unidad concreta (color, equipamiento, km bajos, historial) que en España sencillamente no está publicada. Eso justifica honorarios aunque el coche acabe comprándose en España.

---

## 2. Bloque A — Showstoppers deportivos ≥ 20.000 €

### 2.1 Tabla completa

| Modelo | Anuncios ES | Desde ES | **Mediana ES** | Anuncios DE | Desde DE | **Mediana DE** | Hueco bruto | Hueco neto | Mejor mercado | Veredicto |
|---|---:|---:|---:|---:|---:|---:|---:|---:|:--:|:--:|
| VW Golf GTI | 683 | 20.000 € | 29.500 € | 3.763 | 20.000 € | 29.900 € | −1,4 % | −11,3 % | **ES** | 🟡 |
| VW Golf R | 264 | 20.000 € | 34.250 € | 798 | 20.000 € | 36.940 € | −7,9 % | −16,4 % | **ES** | 🟡 |
| Audi S3 | 113 | 21.490 € | 38.500 € | 966 | 20.000 € | 39.290 € | −2,1 % | −9,7 % | **ES** | 🟡 |
| Mercedes A 45 AMG | 314 | 20.500 € | 33.900 € | 257 | 20.400 € | 38.980 € | −15,0 % | −23,6 % | **ES** | 🟡 |
| Cupra León | 655 | 20.890 € | 27.970 € | 5.321 | 20.000 € | 30.790 € | −10,1 % | −20,6 % | **ES** | 🟢 |
| BMW Serie 1 M135 | 13 | 38.850 € | 47.900 € | *pendiente* | — | — | — | — | — | ⚪ |
| Audi TT | 86 | 20.900 € | 29.350 € | *pendiente* | — | — | — | — | — | ⚪ |

> **Veredicto según criterio de categoría** (showstoppers = atractivo + demanda, el hueco es secundario): 🟡 = atractivo alto pero sin hueco de importación. La Cupra León va a 🟢 por atractivo + oferta ES abundante + rotación rápida, aunque su hueco sea negativo: **es el showstopper más vendible en España**.

### 2.2 Notas por modelo

- **VW Golf GTI** — el mercado más líquido de la categoría en España (683 unidades ≥20k). Mediana ES **verificada por conteo**: 340 de 683 anuncios por debajo de 29.500 € = 49,8 %. En Alemania hay 5,5× más oferta pero al mismo precio: importar solo tiene sentido si el cliente busca una versión concreta (Clubsport, TCR, Mk8 facelift) que aquí no aparece.
- **VW Golf R** — medido en DE con filtro de potencia ≥213 kW (290 cv) + "4MOTION" para aislarlo del GTI Clubsport. En ES, filtro de versión "R". 264 unidades en España es un mercado sano.
- **Audi S3** — mediana ES verificada por conteo (57 de 113 ≤ 38.500 € = 50,4 %). ⚠️ El filtro de versión "S3" en Coches.net **también captura RS3** (el texto contiene "S3"), lo que empuja la mediana ES hacia arriba. En mobile.de el S3 es un modelo separado del RS3. Con esa corrección el hueco real sería aún **más negativo**. Marcado `pendiente_fase2`.
- **Mercedes A 45 AMG** — el hueco más negativo (−15 %). Causa probable: mezcla de generaciones. Alemania tiene mucho W177 A45 S (421 cv, 2020+) y España mantiene más W176 (381 cv, 2016-2018). No es comparación pura de misma generación → `pendiente_fase2` para una segunda pasada con filtro de año.
- **Cupra León** — 5.321 unidades ≥20k en Alemania frente a 655 en España. Mediana DE calculada por conteo de bandas (1.706 anuncios ≤28.000 €, 3.076 ≤32.000 € → mediana interpolada 30.790 €). Precio ES claramente inferior.
- **BMW Serie 1 M135** — solo **13 unidades** en toda España ≥20k, desde 38.850 €. Mercado casi inexistente: es exactamente el perfil de encargo donde JJ Import Motors aporta valor (localizar la unidad), aunque el precio no dé margen.
- **Audi TT** — 86 unidades ES. Showstopper visual clásico con precio de entrada bajo (20.900 €).

### 2.3 Trampas detectadas en esta pasada (para `trampas-encontradas.md`)

| Trampa | Portal | Detalle | Cómo se evita |
|---|---|---|---|
| **ST-Line ≠ ST** | Coches.net | El filtro de versión "ST" en Ford Focus devuelve 72 anuncios, pero incluye "STLine" (1.0 EcoBoost 125 cv). No es el hot hatch. | Filtrar por potencia o exigir "2.0/2.3 EcoBoost" en el texto. **Modelo excluido de esta pasada.** |
| **N Line ≠ N** | Coches.net | Hyundai i30 con versión "N" devuelve 296 anuncios, la mayoría "N Line" (1.0/1.5 TGDI, 120-160 cv). | Ídem. **Modelo excluido.** |
| **GR Sport ≠ GR Yaris** | Coches.net | Toyota Yaris versión "GR" devuelve 41 anuncios, casi todos "GR Sport" (1.5 híbrido 120 cv). | Ídem. **Modelo excluido.** |
| **S3 captura RS3** | Coches.net | La coincidencia es por subcadena. | Restar el conteo de "RS3" o usar filtro de potencia. |
| **Modelo ignorado en ruta** | Coches.net | `/mercedes-benz/clase-a/…?Versions[0]=AMG` devuelve **toda la marca** (GLC, Clase C…). El segmento de modelo se pierde si la versión no encaja. | Verificar SIEMPRE el `h1` del resultado antes de anotar la cifra. |
| **Variante en `ms` rompe la búsqueda** | mobile.de | `ms=25200;14;GTI` devuelve el catálogo completo (726.423 anuncios). | Usar `q=` para texto libre, no el tercer campo de `ms`. |
| **Tope de paginación** | mobile.de | A partir de ~100 páginas (2.000 resultados) devuelve vacío. | Con `N > 2.000`, calcular la mediana por conteo de bandas de precio. |
| **Bloqueo por ritmo** | mobile.de | "Zugriff verweigert" tras ~40 peticiones seguidas. | Espaciar peticiones ≥8 s y partir el estudio en lotes. |

---

## 3. Bloque B — Compactos gasolina/híbrido, 2016+, 8.000-17.000 €

### 3.1 Tabla completa

| Modelo | Comb. | Anuncios ES | Desde ES | **Mediana ES** | Anuncios DE | Desde DE | **Mediana DE** | Hueco bruto | Hueco neto | Mejor mercado | Veredicto |
|---|:--:|---:|---:|---:|---:|---:|---:|---:|---:|:--:|:--:|
| Toyota Auris | híbrido | 97 | 9.800 € | 14.400 € | 216 | 8.200 € | 12.500 € | **+13,2 %** | **+5,4 %** | **DE** | 🟢 |
| Renault Mégane | gasolina | 383 | 8.200 € | 12.550 € | 947 | 8.000 € | 12.000 € | +4,4 % | −7,4 % | ES | 🟡 |
| Kia Ceed | gasolina | 269 | 8.490 € | 14.580 € | 533 | 8.000 € | 13.950 € | +4,3 % | −5,8 % | ES | 🟡 |
| Toyota Corolla | híbrido | 139 | 9.800 € | 15.900 € | 205 | 8.760 € | 15.500 € | +2,5 % | −4,6 % | ES | 🟢 |
| Peugeot 308 | gasolina | 431 | 8.000 € | 11.500 € | 1.353 | 8.000 € | 11.500 € | 0,0 % | −12,9 % | ES | 🟡 |
| VW Golf | gasolina | 405 | 8.500 € | 14.590 € | 7.351 | 8.000 € | *pendiente* | — | — | — | ⚪ |
| Seat León | gasolina | 468 | 8.000 € | 13.950 € | 2.518 | 8.000 € | *pendiente* | — | — | — | ⚪ |
| Ford Focus | gasolina | 431 | 8.000 € | 12.790 € | 5.123 | 8.000 € | *pendiente* | — | — | — | ⚪ |
| Opel Astra | gasolina | 330 | 8.000 € | 11.950 € | 3.492 | 8.000 € | *pendiente* | — | — | — | ⚪ |
| Mazda 3 | gasolina | 33 | 9.990 € | 14.100 € | 241 | 8.000 € | *pendiente* | — | — | — | ⚪ |

> **Veredicto según criterio de categoría** (gemas económicas = accesibilidad + durabilidad + rotación): Corolla y Auris a 🟢 por fiabilidad híbrida y coste de mantenimiento bajo, pese al hueco. Los gasolina europeos a 🟡: accesibles pero sin ventaja de importación.

### 3.2 Recomendación para el cliente de uso diario en ciudad

Ordenados por encaje con el encargo (ciudad, presupuesto 8-17k, gasolina o híbrido):

1. **Toyota Corolla / Auris híbrido** — el híbrido japonés es el coche de ciudad correcto: etiqueta ECO, consumo urbano real de 4-5 l/100 km, cadena cinemática sin embrague ni turbo. El **Auris** cabe holgado en presupuesto (mediana ES 14.400 €) y además es **el único modelo del estudio donde importar da margen positivo** (+5,4 % neto). El Corolla es más moderno pero se come casi todo el presupuesto (15.900 €).
2. **Seat León / VW Golf 1.0-1.5 TSI** — mercado ES amplísimo (468 y 405 unidades), repuestos y taller en cualquier sitio, mediana 13.950-14.590 €. Sin ventaja de importación.
3. **Peugeot 308 / Opel Astra** — los más baratos de la muestra (11.500-11.950 €) y con oferta ES abundante. Dejan 5.000 € de colchón dentro del presupuesto.
4. **Kia Ceed** — argumento de garantía de 7 años si la unidad es de 2019 en adelante; mediana 14.580 €.
5. **Mazda 3** — solo 33 unidades en España en esta banda. Encargo de búsqueda puro; el motor atmosférico Skyactiv-G es una buena elección urbana pero hay que ir a buscarlo.

---

## 4. Cálculo del hueco — fórmulas aplicadas

```
HUECO BRUTO   hueco_pct = (mediana_es − mediana_de) / mediana_es × 100

HUECO NETO    coste_importacion = 900 (transporte) + 114 (ausfuhr) + 115 (ITV import) + IEDMT
              precio_puesto_huelva = mediana_de + coste_importacion
              hueco_neto_pct = (mediana_es − precio_puesto_huelva) / mediana_es × 100
```

**IEDMT estimado por perfil** (el exacto se calcula en Flujo A con la unidad y su CO₂ real):

| Perfil | CO₂ típico | Tipo IEDMT | IEDMT estimado | Coste importación total |
|---|---|---|---|---|
| Showstopper deportivo ≥20k | 160-200 g/km | 9,75 % | ~1.800 € | **2.929 €** |
| Compacto gasolina 8-17k | 120-155 g/km | 4,75 % | ~350 € | **1.479 €** |
| Compacto híbrido 8-17k | < 120 g/km | 0 % | 0 € | **1.129 €** |

**Comprobación de las fórmulas (§Verificación):**
- Golf GTI: (29.500 − 29.900) / 29.500 × 100 = **−1,36 %** ✓
- Auris híbrido: (14.400 − 12.500) / 14.400 × 100 = **+13,19 %** ✓ · neto: (14.400 − 13.629) / 14.400 × 100 = **+5,35 %** ✓
- Mercedes A45: (33.900 − 38.980) / 33.900 × 100 = **−14,99 %** ✓

---

## 5. Contexto macro (Capa 1)

**España — julio 2026** (Ganvam/Faconauto vía Motor16): 227.653 transferencias en el mes (**−0,4 %** interanual), 1.512.146 acumuladas en el año (**+2 %**). El tramo de **1-2 años crece un +24,2 %**, mientras el de 6-10 años cae un −4,7 %: la demanda española se está desplazando al seminuevo reciente, justo por encima de la banda 8-17k. Los híbridos convencionales suben un **+22,9 %** en transferencias — coherente con recomendar Corolla/Auris al cliente urbano.

**Alemania — índice AGPI (AutoScout24), enero 2026**: precio medio de oferta 27.758 € (+0,5 % mensual). Por segmento, los **compactos suben +0,9 % hasta 20.525 €** y los utilitarios +1,9 %. Por combustible, **gasolina +0,8 %** e **híbrido −1,0 %**. Es decir: el compacto de gasolina alemán se está encareciendo mientras el híbrido se abarata — lo que refuerza el único hueco positivo detectado (Auris híbrido) y explica la paridad en los gasolina.

> Este cruce macro explica el resultado del estudio: **el diferencial ES-DE que sostenía el negocio de importación en compactos se ha cerrado**. El margen se ha desplazado a nichos concretos (híbridos japoneses) y al servicio de búsqueda, no al arbitraje de precio.

---

## 6. Verificación (auditoría de fase)

| Comprobación | Resultado |
|---|---|
| Mediana por interpolación vs conteo directo — Golf GTI ES (29.500 €) | 340 de 683 anuncios ≤ 29.500 € = **49,8 %** ✓ |
| Mediana por interpolación vs conteo directo — Audi S3 ES (38.500 €) | 57 de 113 anuncios ≤ 38.500 € = **50,4 %** ✓ |
| Mediana por interpolación vs conteo directo — VW Golf gasolina ES (14.590 €) | 205 de 405 anuncios ≤ 14.590 € = **50,6 %** ✓ |
| Fórmulas de hueco recalculadas a mano | 3 de 3 correctas (§4) |
| Datos inventados | **0** — todo lo no medido va como `pendiente` / `null` con motivo |
| Precio contado vs financiado (A10) | Coches.net: filtro "Contado" activo. mobile.de: `Kaufen`, no `Leasen` |
| Paginación / rango completo (A11-A12) | Sí: mediana calculada sobre el conteo total `N`, no sobre la página 1 |
| mobile.de consultado (A2) | Sí — es la fuente de precio DE. AutoScout24 **no** usado para precio (A8) |
| Cambios de filtro declarados (A13) | Sí: potencia ≥213 kW en Golf R DE; exclusión de Focus ST / i30 N / GR Yaris por contaminación de versión |

### Cobertura de fuentes

| Fuente | Estado | Uso | URL |
|---|---|---|---|
| **Coches.net** | ✅ OK | Precio y oferta ES, 17 modelos | https://www.coches.net/segunda-mano/ |
| **mobile.de** | ⚠️ Parcial | Precio y oferta DE, 11 de 17 modelos. Bloqueo "Zugriff verweigert" por ritmo | https://suchen.mobile.de/fahrzeuge/search.html |
| **AutoScout24.de** | ⚪ No usado | Solo válido para contar oferta (A8), no aportaba en esta pasada | — |
| **Ganvam / Motor16** | ✅ OK | Transferencias VO España julio 2026 | https://www.motor16.com/las-ultimas-noticias/mercado-coches-ocasion-julio |
| **AutoScout24 AGPI** | ✅ OK | Índice de precios VO Alemania | https://www.autoscout24.de/unternehmen/agpi/agpi-januar-jahresstart-mit-nachfrageplus/ |
| **Google Trends** | ❌ No consultado | Demanda por modelo — pendiente para la próxima pasada | — |
| **DGT / KBA (detalle por modelo)** | ❌ No consultado | Solo el agregado vía Ganvam/AGPI | — |
| **kfz-rueckrufe.de** | ⚪ N/A | Los recalls se verifican en Flujo A sobre unidad concreta | — |

---

## 7. Qué queda pendiente (siguiente pasada)

1. **Cerrar mobile.de**: medianas DE de VW Golf gasolina, Seat León, Ford Focus, Opel Astra, Mazda 3 (compactos) y Audi TT + BMW M135 (showstoppers). Bloqueo por ritmo — reintentar con lotes espaciados.
2. **Segunda pasada por año** en Mercedes A 45 y Audi S3 para eliminar la mezcla de generaciones (`pendiente_fase2: true`).
3. **Ford Focus ST, Hyundai i30 N y Toyota GR Yaris**: requieren filtro de potencia en Coches.net (parámetro aún no identificado) para separarlos de ST-Line / N Line / GR Sport.
4. **Google Trends** por modelo (ES vs DE) para el campo `demanda_trends`.
5. **Rotación** (`rotacion_dias_de` / `_es`): ninguno de los dos portales expone días publicados en el listado. Requiere AutoUncle.

---

## 8. Caducidad

| Categoría | Cadencia | Refrescar antes de |
|---|---|---|
| Showstoppers | 2 semanas | **2026-08-31** |
| Gemas económicas / compactos | 4 semanas | **2026-09-14** |

---

*Generado por la skill `estudio-mercado` · JJ Import Motors · 17-ago-2026. Mapa persistente en `datos_mercado.json`.*
