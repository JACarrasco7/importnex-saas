# Informe Técnico Interno — Estructura profesional

> **Cargar cuando:** se va a redactar el informe interno de un Flujo A (UNIDAD) o cuando
> se necesita tomar la decisión de compra.
> **No cargar para:** Flujo C (no aplica).
>
> **Filosofía:** Es el ** análisis privado** para que JJ Import Motors decida. Contiene
> TODOS los datos: margen real, honorarios, vendibilidad numérica, riesgos, URLs de comparables.
> NUNCA se entrega al cliente tal cual. El cliente recibe el `dossier_cliente.md` (versión filtrada).

---

## 🎯 Diferencia con la versión previa

| Antes (11 secciones) | Ahora (15 secciones + scoring) |
|---|---|
| Listado de cobertura | + Score de confianza por fuente |
| Comparables básicos | + Marcas visuales (🟢/🟡/🔴) por fila |
| Coste simple | + Análisis de sensibilidad IEDMT |
| Veredicto cualitativo | + Predicción días hasta venta |
| Riesgos enumerados | + Plan de mitigación por riesgo |
| Sin scoring | + Score global de oportunidad 0-100 |

---

## 📋 Estructura — 15 secciones

### §1 — Cabecera del informe

```
═══════════════════════════════════════════════════════════
INFORME TÉCNICO INTERNO — JJ IMPORT MOTORS
Flujo A · UNIDAD · 2026-08-12 14:32
───────────────────────────────────────────────────────────
Coche ID: vw-golf-gti-clubsport-2021-8b3f4a2c
Origen:  mobile.de · Múnich (DE)
Score global oportunidad: 84/100 🟢
Recomendación final: 🟢 COMPRAR PRIORITARIA
═══════════════════════════════════════════════════════════
```

- **Score global:** 0-100 (formula abajo, §15)
- **Recomendación:** 🟢 Comprar prioritaria · 🔵 Oferta de contenido · 🟡 Solo bajo pedido · 🔴 Descartar

### §2 — Cobertura de fuentes (con score de confianza)

```
COBERTURA 7 FUENTES

Fuente                Estado      Confianza   Datos clave capturados
────────────────────  ──────────  ──────────  ────────────────────────
mobile.de             OK          ⭐⭐⭐⭐⭐     mediana DE, fichas 12
Coches.net            OK          ⭐⭐⭐⭐       mediana ES, Q1, N=8
AutoScout24.de        OK          ⭐⭐⭐⭐       mediana DE cross-check
AutoUncle             OK          ⭐⭐⭐⭐⭐     días publicado, cambios%
kleinanzeigen.de      OK          ⭐⭐⭐         particulares, VB
Wallapop              OK          ⭐⭐          particulares ES
Milanuncios           OK          ⭐⭐          particulares ES, financiado
────────────────────  ──────────  ──────────

km77 PVP/CO2          bloqueada   ⭐           fallback BOE aplicado
────────────────────  ──────────  ──────────
Cobertura total: 7/7 fuentes OK · 1/1 servicio fallback
Score cobertura: 9/10
```

- **Score confianza:** 0-5 estrellas según qué datos se obtuvieron
- **Si una fuente devuelve 0 resultados:** bloqueada (no fallida). Score 0.
- **Si fallback activado (BOE para km77):** marcar ⭐ y nota

### §3 — Oferta española (comparables con análisis fila a fila)

```
OFERTA ESPAÑOLA — Golf GTI Clubsport 2021

N=8 anuncios · captura 12-08-2026

#  Título                              Precio    Km      Año   Días  Sello      URL
─  ──────────────────────────────────  ────────  ──────  ────  ────  ─────────  ──────────────────
1  Golf GTI Clubsport Perf 2021        32.400 €  45.000  2021   18   🟢 Bueno   coches.net/...
2  Golf GTI Clubsport 2021             33.100 €  52.000  2021   62   🟡 Justo   coches.net/...
3  VW Golf GTI Clubsport 2021 Sport    33.800 €  41.000  2021   9    🟢 Bueno   coches.net/...
4  Golf GTI Clubsport 2.0 TSI 2021     34.500 €  38.000  2021   72   🔴 Alto    coches.net/...
5  VW Golf GTI Clubsport 2021          34.900 €  49.000  2021   25   🟡 Justo   autouncle...
6  Golf GTI Clubsport Performance      35.800 €  35.000  2021   12   🟢 Bueno   coches.net/...
7  VW Golf GTI Clubsport 2021 Nürb.    37.200 €  29.000  2021    4    🟢 Bueno   coches.net/...
8  Golf GTI Clubsport 2021 Extras      39.900 €  28.000  2021   88   🔴 Alto    coches.net/...
─  ──────────────────────────────────  ────────  ──────  ────  ────  ─────────  ──────────────────

  Mínimo    ──────────────── 32.400 €
  Cuartil 1 (Puerta A) ───── 33.200 € ← objetivo de venta rápida
  MEDIANA   ──────────────── 34.500 €
  Cuartil 3 ─────────────── 36.100 €
  Máximo    ──────────────── 39.900 €

  Días medio publicado: 36 (mediana · vendibilidad buena)
  Rotación inferida: Q1 se vende en ~30 días · Q3 tarda 60+

  🟢 Bueno / 🟡 Justo / 🔴 Alto = priceRankIndicator del portal
```

- **8 filas visibles máximo** (si hay más, agrupar por tramos)
- **Días publicado** = gem de AutoUncle/Coches.net/AutoScout24
- **Sello visual** = priceRankIndicator (visible en tarjeta)

### §4 — Oferta alemana (con portal origen y días)

Mismo formato que §3 pero con:
- Columna **Portal** (mobile.de · AutoScout24.de · kleinanzeigen.de)
- Columna **Vendedor** (Profesional · Privatanbieter)
- Columna **VB** (Verhandlungsbasis = negociable, sí/no)
- Columna **Cambio precio** (€ ó % si visible)

### §5 — Candidato seleccionado (ficha completa)

```
CANDIDATO SELECCIONADO

URL interna:     mobile.de/fahrzeuge/details.html?id=38347...
Portal:          mobile.de
Vendedor:        Autohaus München Nord GmbH (profesional, 4.7★, 230 valoraciones)
Ubicación:       Múnich, Bayern (DE)
Distancia:       2.180 km hasta Huelva

FICHA TÉCNICA
─────────────────────────────────────────────────
Marca / Modelo      Volkswagen Golf GTI Clubsport
Versión             2.0 TSI 300 CV Performance
Año                 2021 (matrícula 04/2021)
Km                  38.000 km
Combustible         Gasolina
Cambio              Manual 6 vel.
Tracción            Delantera
Potencia            300 CV (221 kW) @ 5.300 rpm
Par motor           400 Nm
Vel. máxima         267 km/h
0-100               5,9 s
Consumo WLTP        7,0 l/100km
CO₂ declarado       159 g/km ⚠️ confirmar con COC
Etiqueta DGT        C
VIN (parcial)       WVGZZZ...
Color ext/int       Tornado Grey / Alcantara negra
Puertas / Plazas    5 / 5
Propietarios        1
Garantía VW         Sí, hasta 04/2027
ITV (TÜV/HU)        Vigente hasta 04/2025

EQUIPAMIENTO DESTACADO (15 primeros items)
─────────────────────────────────────────────────
• Pack Performance (frenos racing + dirección adaptativa)
• Asientos deportivos Alcantara
• Techo panorámico
• Faros LED 矩阵 adaptativos
• Navegador Discover Pro 8"
• Apple CarPlay + Android Auto
• Cámara trasera + Park Assist
• Climatizador bizona
• Volante deportivo multifunción
• Modos de conducción (Eco/Normal/Sport/Individual)
• Diferencial autoblocante VAQ
• Escape deportivo
• Llantas 19" Pretoria
• Suspensión deportiva DCC adaptativa
• Acabado Clubsport (placas laterales, volante específico)

PRECIO
─────────────────────────────────────────────────
Precio publicado:  26.800 € (IVA incluido, no deducible)
Precio negociado:  25.950 € (estimado · ver §10)
Días publicado:    45 días (alto · сигнал de baja demanda DE)
Cambio precio:     Sí, -850 € hace 12 días (descuento reciente)

HALLAZGOS FICHA
─────────────────────────────────────────────────
• Libro de revisiones sellado en concesionario oficial
• Última revisión 04/2026 · 36.200 km
• 1 propietario particular (no renting)
• Sin defectos declarados
• Fotos muestran interior impecable
• Neumáticos originales (Pirelli P Zero 235/35 R19)

NOTAS
─────────────────────────────────────────────────
• Vendedor profesional con buena reputación
• Precio ha bajado 850€ → muestra disposición a negociar
• Km bajo para la edad (9.500 km/año vs 15k media)
• Pack Performance incluye frenos caros (presupuesto mantenimiento)
```

### §6 — Comparable ajustado (cálculo línea a línea)

Ver `comparables.md` para método completo. Formato de salida:

```
COMPARABLE AJUSTADO

Comparable base (España, fila #4):
  Golf GTI Clubsport 2021, 38.000 km, 34.500 €

Ajustes (línea por línea):
  Km:           38.000 vs 38.000 ...............   0 €
  Año:          2021 vs 2021 ..................   0 €
  Versión:      Performance vs Performance .....   0 €
  Color:        Tornado Grey vs Deep Black ..... +100 €
  Equipamiento: + Pack Performance ............. +500 €
  Estado:       1 propietario vs 1 propietario .   0 €
  Procedencia:  ES vs DE (penalización percibida) -800 €
─────────────────────────────────────────────────
Ajuste neto:                            -200 €
Precio comparable ajustado:        34.300 €

vs nuestro coste total:             28.500 €
Margen bruto sobre comparable:    +5.800 € (20,3%) 🟢
```

- **Cada ajuste justificado** con valor en € (positivo o negativo)
- **Procedencia DE:** -800€ refleja percepción de comprador ES (no es lo mismo un coche "alemán" que "español")
- **Trampa:** el comparable se ajusta, NUNCA se acepta tal cual.

### §7 — Coste puesto en Huelva (con análisis de sensibilidad)

```
COSTE TOTAL PUESTO EN HUELVA

Base cálculo:
  Precio compra (publicado) ......... 26.800 €
  Precio negociado (objetivo) ....... 25.950 € (ver §10 plan negociación)

Desglose sobre precio negociado:
  Compra vehículo .................. 25.950 €
  Transporte DE→ES (camión) ........    900 €
  ITV importación + tasas DGT ......    115 €
  IEDMT estimado ...................    830 € *
  Matrícula + gestoría .............    305 €
  Honorarios JJ Import Motors ......  4.400 € **
  Margen objetivo .................   (en compra vehículo)
─────────────────────────────────────────────────
COSTE TOTAL ..................... 32.500 € (sin IVA)
PRECIO CLIENTE .................. 28.500 €  ← SIEMPRE ≥ COSTE TOTAL

* IEDMT
  CO₂ declarado:  159 g/km
  CO₂ tramo:      9,75% (entre 150-160 g/km, art.72.1.b) Lunes 13 IEDMT)
  PVP km77:       44.500 € (con Pack Performance)
  Antigüedad:     5 años → coef 0,30 (Anexo IV)
  Base imponible: 44.500 × 0,30 = 13.350 €
  IEDMT base:     13.350 × 9,75% = 1.301,63 €
  Minoración art.69 (70%): 1.301,63 × 0,70 = 911,14 €
  IEDMT minorado: 1.301,63 × 0,30 = 390,49 € (esta es la cifra usada)
  ✓ Redondeado a 390 € (en §5 ejemplo anterior puede haber variación por CO₂)

ANÁLISIS DE SENSIBILIDAD — IEDMT
  CO₂ en COC ±5 g/km:    ±85 € (390 → 305 ó 475)
  PVP km77 ±2.000 €:     ±60 €
  Antigüedad 5 vs 4 años: +260 € (penalización 4 años)
  Sin minoración art.69:  +911 € (escenario fiscal adverso)

  Rango IEDMT razonable: 305 € – 1.301 € (intervalo ±1.000 €)
  Cifra usada en coste: 830 € (media prudente con minoración)

** Honorarios
  Segmento Nicho, target 15% margen sobre 28.500 = 4.275 €
  Redondeado a 4.400 € (cubrir imprevistos transporte/ITV)
```

- **IEDMT con metodología completa** y análisis de sensibilidad
- **Honorarios justificados** por segmento y target de margen
- **Trampa:** SIEMPRE mostrar el rango IEDMT (no solo una cifra) para no caer en optimismo fiscal

### §8 — Margen, veredicto y matriz de decisión

```
MARGEN Y VEREDICTO

Contra MEDIANA española (34.500 €):
  Margen bruto:     34.500 - 28.500 = 6.000 € (21,1%) 🟢

Contra CUARTIL 1 español (33.200 € · Puerta A):
  Margen bruto:     33.200 - 28.500 = 4.700 € (16,5%) 🟢

Contra comparable AJUSTADO (34.300 €):
  Margen bruto:     34.300 - 28.500 = 5.800 € (20,3%) 🟢

Contra mínimo español actual (32.400 €):
  Margen bruto:     32.400 - 28.500 = 3.900 € (13,7%) 🟡

MATRIZ DE DECISIÓN
                  Margen ≥10%    Margen <10%
Vendibilidad ≥65  🟢 COMPRA     🔵 OFERTA CONTENIDO
Vendibilidad <65  🟡 BAJO PEDIDO 🔴 DESCARTAR

  Vendibilidad estimada: 84/100 ⭐⭐⭐⭐
  Margen mínimo cubierto: 21,1% vs 10% umbral Nicho
  → Resultado: 🟢 COMPRA PRIORITARIA
```

- **Veredicto SIEMPRE contra 4 referencias:** mediana + Q1 + comparable ajustado + mínimo
- **Matriz visual** con la casilla resultado resaltada

### §9 — Vendibilidad detallada (5 factores)

```
VENDIBILIDAD — 84/100

#  Factor                Peso  Puntos  Justificación
─  ────────────────────  ────  ──────  ──────────────────────────────────
1  Demanda del modelo      30    26    Top-10 golf GTI en coches.net
   · publicationDate mediana 36 días → rotación rápida
2  Escasez configuración   25    21    8 uds ES · pack Performance en 3/8
3  Atractivo               20    18    Icónico · GTI es subcultura propia
4  Equipamiento ES         15    12    Pack Perf + techo + LED = notable
5  Km e historial          10     7    9.5k/año · libro sellado · 1dueño
─  ────────────────────  ────  ──────
   TOTAL                  100    84    🟢 alta
```

- **Cada factor con su justificación** (no solo número)
- **Trampa:** si vendibilidad <65, automáticamente degradar veredicto aunque el margen sea ≥10%

### §10 — Plan de negociación con vendedor (mensaje sugerido)

```
PLAN NEGOCIACIÓN VENDEDOR

Vendedor:           Autohaus München Nord GmbH
Precio publicado:   26.800 €
Precio objetivo:    25.950 € (-850 € · -3,2%)

Argumentos de negociación (en alemán o traducidos):
• Anuncio activo 45 días (rotación lenta para el modelo)
• Ya hubo descuento (-850 €) → ventana de negociación abierta
• Pago inmediato (transferencia en 24h)
• Cliente final confirmado (no especulador)
• Recogida en concesionario (no requiere transporte portal)
• Inspección previa: 60 fotos + vídeo (no comprometer)

Mensaje sugerido (en alemán, plantilla):
─────────────────────────────────────────────
Sehr geehrte Damen und Herren,

ich interessiere mich für den VW Golf GTI Clubsport (Anzeige vom
[fecha]) und möchte Ihnen ein verbindliches Angebot machen:

• 25.950 € als Sofortzahlung (Überweisung innerhalb 24h nach Vertragsunterschrift)
• Abholung beim Händler, kein Transport erforderlich
• Inspektion vor Ort möglich (Fotos + kurzes Video)

Der Wagen steht seit 45 Tagen inseriert. Bitte um kurze Rückmeldung.

Mit freundlichen Grüßen,
JJ Import Motors
─────────────────────────────────────────────

Estrategia:
1. Enviar email con oferta 25.950 €
2. Esperar 48h. Si rechazan, subir a 26.200 € (límite)
3. Si aún rechazan, aceptar 26.500 € (precio tope)
4. NUNCA pagar más de 26.800 € (precio publicado) → buscar otra unidad

Backup si fracasa negociación:
• Candidato B: golf-gti-clubsport-2021-as24-28391 (34.900€ · 49k km · Brandenburgo)
• Candidato C: golf-gti-clubsport-2021-mobi-54128 (27.500€ · 41k km · Hamburgo)
```

- **Plan de negociación con límites claros** (precio tope)
- **Mensaje en alemán listo para enviar**
- **Backup de candidatos** si fracasa el principal

### §11 — Riesgos y banderas (con plan de mitigación)

```
RIESGOS Y BANDERAS

#  Riesgo                                Probabilidad  Impacto  Mitigación
─  ────────────────────────────────────  ────────────  ───────  ──────────────────
1  CO₂ en COC distinto a declarado       Media         Alto     Solicitar COC antes pago
   (159 vs 165 → +85 € IEDMT)
2  Concesionario no acepta negociación   Baja          Medio    Backup B/C preparados
3  Daños en transporte                   Baja          Alto     Seguro todo riesgo (camión)
4  ITV con observaciones                 Media         Medio    +1-2 semanas
5  Recall pendiente al llegar            Muy baja      Bajo     Verificar kfz-rueckrufe
6  Problemas mecánicos post-entrega      Baja          Alto     Recomendar seguro mecánico
7  Tipo cambio EUR/DE fluctúa            N/A           N/A      Misma moneda (€)
─  ────────────────────────────────────  ────────────  ───────

BANDERAS ROJAS (detectadas):
  ❌ Ninguna

BANDERAS AMARILLAS:
  ⚠️ 45 días publicado → posible sobreprecio inicial
  ⚠️ Vendedor profesional puede poner pegas a particular extranjero
  ⚠️ Pack Performance = neumativos y pastillas caros

Veredicto riesgos: ACEPTABLE 🟢
```

- **Tabla con plan de mitigación por cada riesgo** (no solo listar)
- **Banderas rojas = cancelar** · **amarillas = vigilar**
- **Trampa:** nunca decir "sin riesgos". Siempre hay algo.

### §12 — Alternativas

```
ALTERNATIVAS SI ESTA UNIDAD NO PROSPERA

Candidato B (DE cross-backup):
  AS24 golf-gti-clubsport-2021 · 34.900 € ES · 41.000 km · Hamburgo
  Margen objetivo: 19% · Vendibilidad estimada: 82/100
  Diferencia vs candidato A: +500 € precio, +3k km

Candidato C (DE cross-backup):
  mobile.de golf-gti-clubsport-2021 · 35.800 € ES · 35.000 km · Colonia
  Margen objetivo: 17% · Vendibilidad estimada: 80/100
  Diferencia vs candidato A: +1.300 € precio, -3k km

Modelo alternativo (si el segmento GTI se cierra):
  VW Golf R 2021 · 320 CV · 4Motion
  Margen objetivo: 15% · Vendibilidad estimada: 85/100
  Diferencia: segmento superior, más exclusivo, menos uds.
```

- **2-3 alternativas reales** con URLs
- **Comparativa rápida** con el candidato principal

### §13 — Predicción de venta (tiempo hasta cierre)

```
PREDICCIÓN VENTA (días hasta cierre)

Basado en historial (registro_cierres.json) + rotación actual ES:

Escenario optimista (precio agresivo):
  Precio venta: 31.500 € (-9% mediana)
  Días hasta venta: 18-25 días
  Margen neto: 3.000 € (10,5%)

Escenario base (precio mercado Q1):
  Precio venta: 33.200 €
  Días hasta venta: 30-45 días
  Margen neto: 4.700 € (16,5%)

Escenario conservador (precio mediana):
  Precio venta: 34.500 €
  Días hasta venta: 50-70 días
  Margen neto: 6.000 € (21,1%)

Escenario pesimista (precio Q3):
  Precio venta: 36.100 €
  Días hasta venta: 75-90 días
  Margen neto: 7.600 € (26,7%) · pero más riesgo de markt turn

Recomendación: ESCENARIO BASE (33.200 € · 30-45 días)
  · Mejor balance tiempo/margen
  · Reduce exposición a bajadas de mercado
```

- **4 escenarios** con precio · días · margen
- **Recomendación con justificación**

### §14 — Acción inmediata (pasos numerados)

```
ACCIÓN INMEDIATA

☐ 1. Enviar email negociación a vendedor (precio 25.950 €)
☐ 2. Preparar contrato de servicios para cliente
☐ 3. Solicitar COC al vendedor en paralelo (condición de compra)
☐ 4. Bloquear unidad: pedir reserva de 1.000 € al cliente
☐ 5. Confirmar transporte (camión desde Múnich, ~2 semanas)
☐ 6. Preparar dossier del cliente (dossier_cliente.md)
☐ 7. Enviar dossier + vídeo inspección al cliente

Plazo objetivo: cerrar antes del 19-08-2026 (7 días)
```

### §15 — Score global de oportunidad (0-100)

```
SCORE GLOBAL DE OPORTUNIDAD: 84/100 🟢

Composición:
  Margen vs objetivo (25%):         21/25  (21% real vs 15% objetivo = 140%)
  Vendibilidad (25%):               21/25  (84/100)
  Cobertura fuentes (15%):          14/15  (7/7 OK)
  Calidad datos vendedor (15%):     13/15  (profesional, +4.7★, libro)
  Riesgo residual (10%):             8/10  (sólo CO₂ y días publicado)
  Confianza veredicto (10%):         7/10  (alta · 1 retry IEDMT)

Traducción:
  85-100 🟢 COMPRA PRIORITARIA — cerrar ya
  70-84  🟢 COMPRAR — buenas condiciones
  55-69  🟡 OFERTA DE CONTENIDO — traer clientes futuros
  40-54  🟡 SOLO BAJO PEDIDO — necesitar bajar coste
  <40    🔴 DESCARTAR — no operativo
```

- **Score agregado de 6 dimensiones** (no solo margen)
- **Traducción a recomendación accionable**

---

## 📐 Formato `.txt` (informe-interno.txt) — bloques esperados

Archivo que `empaquetar.py` escribe dentro del ZIP. Bloques `[MARCADOR]` para Blade.

```
# Cabecera
[COCHE_ID]              vw-golf-gti-clubsport-2021-8b3f4a2c
[FECHA_INFORME]         2026-08-12 14:32
[VALIDO_HASTA]          2026-08-19
[FLUJO]                 A
[SCORE_GLOBAL]          84
[RECOMENDACION]         COMPRA PRIORITARIA
[ORIGEN]                Alemania (Múnich)
[VIN]                   WVWZZZCDZMW123456
[URL_ANUNCIO]           https://www.mobile.de/fahrzeuge/details.html?id=...
[PRECIO_OBJETIVO]       25950

# Cabecera ejecutiva (se muestra en la "Executive card" del blade)
[SEMAFORO]              verde
[DICTAMEN]              Compra recomendada
[CONFIANZA]             Alta
[RESUMEN]               **Golf GTI Clubsport Performance** con historial 1 dueño en concesionario. Mediana ES 34.500 € → margen estimado 21%.
[RAZONAMIENTO]          El coche está ~6.500 € por debajo de la mediana ES por su pack Performance y único dueño. El IEDMT es el único coste sensible (ver [IEDMT_SENSIBILIDAD]).
[QUE_CAMBIARIA]         Negociar 850 € adicionales usando el comparable ajustado (34.300 €) y el tiempo publicado (45 días).

# Balance (argumento | peso alto/medio/bajo)
[A_FAVOR] Historial completo 1 dueño | alto
[A_FAVOR] Pack Performance poco común en ES | alto
[A_FAVOR] Km contenidos (9.5k/año) | medio
[EN_CONTRA] 45 días publicado → posible sobreprecio | medio
[EN_CONTRA] Mantenimiento del pack caro | bajo

# Auditoría por aspecto (grupo: abre con [ASPECTO], cierra al repetirse o con [H2])
[ASPECTO] Mecánica
[VALORACION] Positiva
[TEXTO] Sin ruidos, arranque limpio, historial en Audi.
[FUENTE] Inspección + historial concesionario
[ASPECTO] Carrocería
[VALORACION] Positiva
[TEXTO] Sin golpes ni repintados. 2 arañazos leves.
[FUENTE] Inspección visual + fotos

# Checklist de cierre
[CHECK] Confirmado historial 1 dueño
[CHECK] COC solicitado al vendedor
[CHECK] Seguro transporte contratado

# Cobertura
[COBERTURA] mobile.de | OK | 5 | mediana DE, 12 fichas
[COBERTURA] coches.net | OK | 4 | mediana ES, Q1, N=8
...
[KM77_FALLBACK]         BOE

# Oferta ES (comparables: título | km | precio | url — mismo orden que la vista)
[MERCADO_ES_MIN]        32400
[MERCADO_ES_Q1]         33200
[MERCADO_ES_MEDIANA]    34500
[MERCADO_ES_Q3]         36100
[MERCADO_ES_MAX]        39900
[MERCADO_ES_N]          8
[MERCADO_ES_DIAS_MED]   36
[COMPARABLE] Golf GTI Clubsport Perf 2021 | 45.000 | 32.400 € | https://...
[COMPARABLE] Golf GTI Clubsport 2021 | 38.000 | 34.500 € | https://...

# Oferta DE
[MERCADO_DE_MEDIANA]    26800
[MERCADO_DE_N]          12
[COMPARABLE] Golf GTI Clubsport 2021 | 38.000 | 26.800 € | https://...

# Fuentes verificadas (aspecto | título | url)
[FUENTE_LISTA] Recalls | kfz-rueckrufe.de | https://kfz-rueckrufe.de/...
[FUENTE_LISTA] CO₂/PVP | km77 (fallback BOE) | https://www.boe.es/...

# Candidato
[CAND_URL]              https://www.mobile.de/fahrzeuge/details.html?id=...
[CAND_VENDEDOR]         Autohaus München Nord GmbH
[CAND_VENDEDOR_TIPO]    Profesional
[CAND_VENDEDOR_RATING]  4.7
[CAND_CIUDAD]           Múnich
[CAND_PRECIO]           26800
[CAND_PRECIO_OBJ]       25950
[CAND_DIAS]             45
[CAND_CAMBIO_PRECIO]    -850 € (hace 12 días)
[FICHA] Marca | Volkswagen
[FICHA] Modelo | Golf GTI Clubsport
... (resto de ficha)
[EQUIP] Pack Performance
[EQUIP] Asientos deportivos Alcantara
...

# Comparable ajustado
[COMP_BASE] Golf GTI Clubsport 2021, 38.000 km, 34.500 € | coches.net/...
[COMP_AJUSTE] Km | 0 € | 38.000 vs 38.000
[COMP_AJUSTE] Año | 0 € | 2021 vs 2021
[COMP_AJUSTE] Versión | 0 € | Performance vs Performance
[COMP_AJUSTE] Color | +100 € | Tornado Grey vs Deep Black
[COMP_AJUSTE] Equipamiento | +500 € | + Pack Performance
[COMP_AJUSTE] Estado | 0 € | 1 dueño vs 1 dueño
[COMP_AJUSTE] Procedencia | -800 € | ES vs DE (penalización percibida)
[COMP_AJUSTE_NETO]      -200
[COMP_AJUSTADO]         34300

# Costes (bloques que renderiza informe-interno.blade.php como tabla financiera)
[COSTE] Compra del vehículo (negociado) | 25.950 €
[COSTE] Transporte DE → ES (camión) | 900 €
[COSTE] ITV importación + tasas DGT | 115 €
[COSTE] Impuesto matriculación (IEDMT) | 830 €
[COSTE] Matrícula + gestoría | 305 €
[TOTAL] Precio cliente | 28.500 €
[NOTA] IEDMT estimado. Ver [IEDMT_*] abajo.
[NOTA] Honorarios fijos declarados. Margen interno camuflado en compra.
[IEDMT_CO2]             159
[IEDMT_CO2_TRAMO]       9,75%
[IEDMT_PVP]             44500
[IEDMT_ANTIGUEDAD_COEF] 0,30
[IEDMT_BASE]            13350
[IEDMT_BRUTO]           1301
[IEDMT_MINORADO]        390
[IEDMT_METODOLOGIA]     PVP km77: 44.500€. Antigüedad 5 años (30%). CO₂ 159 g/km. Minoración art.69.
[IEDMT_SENSIBILIDAD]    CO₂ +5 g/km | 475 €
[IEDMT_SENSIBILIDAD]    CO₂ -5 g/km | 305 €
[IEDMT_SENSIBILIDAD]    Sin minoración art.69 | 1.301 €
[IEDMT_SENSIBILIDAD]    Rango prudente | 305 – 1.301 €

# Margen y veredicto
[MARGEN] Mediana ES | 6000 | 21,1 | green
[MARGEN] Cuartil 1 (Puerta A) | 4700 | 16,5 | green
[MARGEN] Comparable ajustado | 5800 | 20,3 | green
[MARGEN] Mínimo español | 3900 | 13,7 | amber
[VEREDICTO]             COMPRA PRIORITARIA

# Vendibilidad
[VENDIBILIDAD_TOTAL]    84
[VENDIBILIDAD_FACTOR] Demanda | 30 | 26 | Top-10 golf GTI
[VENDIBILIDAD_FACTOR] Escasez | 25 | 21 | 8 uds ES · pack Perf 3/8
[VENDIBILIDAD_FACTOR] Atractivo | 20 | 18 | Icónico
[VENDIBILIDAD_FACTOR] Equipamiento | 15 | 12 | Pack Perf + techo + LED
[VENDIBILIDAD_FACTOR] Km e historial | 10 | 7 | 9.5k/año · libro

# Negociación
[NEG_PRECIO_PUBLICADO]  26800
[NEG_PRECIO_OBJETIVO]   25950
[NEG_PRECIO_TOPE]       26500
[NEG_MENSAJE_ALEMAN]    Sehr geehrte Damen und Herren...
[NEG_BACKUP_B]          AS24 golf-gti-clubsport-2021 · 34.900 € ES · 41.000 km
[NEG_BACKUP_C]          mobile.de golf-gti-clubsport-2021 · 35.800 € ES · 35.000 km

# Riesgos
[RIESGO] CO₂ en COC distinto | Media | Alto | Solicitar COC antes pago
[RIESGO] Concesionario no negocia | Baja | Medio | Backup B/C
[RIESGO] Daños transporte | Baja | Alto | Seguro todo riesgo
...
[BANDERA_ROJA] Recall activo sin reparar (cancelar)
[BANDERA_AMARILLA] 45 días publicado → posible sobreprecio inicial
[BANDERA_AMARILLA] Pack Performance = mantenimiento caro

# Predicción venta (escenario | precio | días | margen € | margen %)
[VENTA] optimista | 31500 | 18-25 días | 3000 € | 10,5%
[VENTA] base | 33200 | 30-45 días | 4700 € | 16,5%
[VENTA] conservador | 34500 | 50-70 días | 6000 € | 21,1%
[VENTA] pesimista | 36100 | 75-90 días | 7600 € | 26,7%
[VENTA_RECOMENDADA] BASE

# Acción
[ACCION] Enviar email negociación a vendedor (25.950 €)
[ACCION] Preparar contrato de servicios para cliente
[ACCION] Solicitar COC al vendedor en paralelo
[ACCION] Bloquear unidad: pedir reserva 1.000 € al cliente
[ACCION] Confirmar transporte (Múnich → Huelva)
[ACCION] Preparar dossier del cliente
[ACCION] Enviar dossier + vídeo inspección
[ACCION_PLAZO]  7 días (antes 19-08-2026)

# Score desglose
[SCORE_DIM] Margen vs objetivo | 25 | 21
[SCORE_DIM] Vendibilidad | 25 | 21
[SCORE_DIM] Cobertura fuentes | 15 | 14
[SCORE_DIM] Calidad datos vendedor | 15 | 13
[SCORE_DIM] Riesgo residual | 10 | 8
[SCORE_DIM] Confianza veredicto | 10 | 7

# Pie
[PIE] Coches.net · mobile.de · AutoScout24.de · AutoUncle · kleinanzeigen.de · Wallapop · Milanuncios · Análisis interno JJ Import Motors · no distribuir
```

---

## 🔴 Reglas duras (no romper)

1. **NUNCA** emitir informe sin `precio_objetivo` si veredicto es "Comprar si baja de precio".
2. **NUNCA** mostrar margen al cliente (va al `dossier_cliente.md`, no al dossier cliente).
3. **SIEMPRE** incluir análisis de sensibilidad del IEDMT.
4. **SIEMPRE** incluir plan de negociación con mensaje en alemán.
5. **SIEMPRE** incluir predicción de venta (4 escenarios).
6. **SIEMPRE** verificar recalls activos antes de cerrar veredicto.
7. **SIEMPRE** citar URLs de comparables (con inventario interno, nunca al cliente).

---

## 🆚 Diferencia con `dossier_cliente.md`

| Aspecto | Informe técnico interno | Dossier cliente |
|---|---|---|
| **Audiencia** | JJ Import Motors | Cliente final |
| **Margen real** | ✅ Sí, explícito | ❌ Nunca |
| **Honorarios desglosados** | Sí (con margen objetivo) | Sí (sin margen) |
| **URLs comparables** | ✅ Con URL | ❌ Sin URL |
| **Mensaje vendedor alemán** | ✅ Plantilla incluida | ❌ No |
| **Vendibilidad numérica** | ✅ Score | ❌ Cualitativo |
| **Predicción días venta** | ✅ 4 escenarios | ❌ Solo "4-6 sem entrega" |
| **Análisis sensibilidad** | ✅ Detallado | ❌ Resumido |
| **Cuándo emitir** | Tras Fase 2 (decisión) | Tras interés del cliente |
