# Dossier del Cliente — Informe profesional de oportunidad

> **Cargar cuando:** Flujo A cerrado con veredicto "Comprar" o "Comprar si baja de precio".
> **No cargar para:** Flujo B/C, ni informes descartados.
>
> **🌍 Origen (12-ago-2026):** el dossier se adapta al origen del coche. Si es **ES** (compra nacional), NO incluir secciones de importación (transporte DE, IEDMT, ausfuhr) — sustituirlas por el desglose ES (`costes.md` §Origen).
>
> **Filosofía:** El cliente paga honorarios. A cambio recibe un **dossier profesional** (no un simple anuncio)
> que justifica la decisión con datos verificables. Genera confianza, reduce fricción en el cierre y
> diferencia a JJ Import Motors de un concesionario cualquiera.
>
> **Regla de oro:** 🔴 **NUNCA mostrar al cliente el balance interno, el margen, los honorarios desglosados
> como "beneficio", ni la vendibilidad cuantitativa.** Eso es análisis privado. El dossier traduce ese
> análisis en argumentos comerciales honestos.

---

## 🎯 Cuándo emitir el dossier

| Veredicto Flujo A | ¿Dossier? | Variante |
|---|---|---|
| 🟢 Comprar | ✅ Sí | **Completo** (15 secciones) |
| 🔵 Comprar si baja de precio | ✅ Sí | **Completo** + sección "Negociación en curso" |
| 🟡 Dudoso | ⚠️ Solo si el cliente pidió evaluarlo | **Reducido** (sin §13 Próximos pasos) |
| 🔴 Descartar | ❌ No | Carta breve explicando por qué no |

---

## 📦 Estructura del dossier — 15 secciones

Cada sección tiene: **qué contiene · formato · fuente del dato · trampa a evitar**.

### §1 — Portada

```
[LOGO]  JJ Import Motors
        Confianza · Rapidez · Exclusividad

        DOSSER DE OPORTUNIDAD #JJM-2026-08-12-0042

        Volkswagen Golf GTI Clubsport · 2021
        28.500 € puesto en Huelva
        Fecha: 12 de agosto de 2026
```

- **Número de dossier:** `JJM-AAAA-MM-DD-####` (secuencial diario, 4 dígitos)
- **Foto principal:** primera foto del anuncio (siempre con permiso del portal)
- **Marca:** estoril-700 (#1A306D), tipografía limpia
- **Trampa:** NO poner "ahorro X%" en portada. Ese dato va en §7 (estudio de mercado) con contexto.

### §2 — Carta de presentación (1 párrafo)

```
Estimado cliente,

En JJ Import Motors llevamos X años facilitando la búsqueda e importación de
vehículos seleccionados (desde Alemania y dentro de España). Este dossier
documenta por qué este coche en concreto merece su atención: datos de mercado
reales, inspección técnica exhaustiva y un coste total sin sorpresas. Si decide
avanzar, le acompañamos en cada paso hasta la entrega de las llaves en Huelva.

Atentamente,
JJ Import Motors
```

- **Tono:** cercano, profesional, sin tecnicismos
- **1 párrafo · 4-5 líneas máximo**
- **Trampa:** no hablar de "margen" ni "negocio". Hablar de "servicio" y "acompañamiento".

### §3 — Resumen ejecutivo (1 página, lenguaje llano)

```
EN 30 SEGUNDOS:

✅ Oportunidad clara: este modelo se vende en España por ~34.500 €
   y le podemos ofrecer puesto en Huelva por 28.500 €.

✅ Configuración deseada: tracción delantera, cambio manual, pack
   Performance, etiqueta C (entra en ZBE).

✅ Estado verificado: 1 propietario, 38.000 km, libro de revisiones
   sellado en concesionario oficial.

⚠️ A tener en cuenta: el CO₂ del COC puede variar ±5 g/km y eso
   mueve el IEDMT ~50-80 €. Le confirmamos el dato final antes
   de pagar.

📍 Próximo paso: reserva de 1.000 € para iniciar la compra.
```

- **Estructura:** 3-4 ✅ + 1-2 ⚠️ + 1 📍
- **Lenguaje:** 0 tecnicismos. "IEDMT" → "impuesto matriculación".
- **Trampa:** no incluir precio sin contexto. Precio SIEMPRE con "puesto en Huelva".

### §4 — Identificación del vehículo

```
┌─────────────────────────────────────────────────────┐
│ FICHA TÉCNICA                                       │
├─────────────────────────────────────────────────────┤
│ Marca / Modelo      Volkswagen Golf GTI Clubsport   │
│ Versión             2.0 TSI 300 CV Performance      │
│ Año                 2021                            │
│ Kilómetros          38.000 km                       │
│ Combustible         Gasolina                        │
│ Cambio              Manual 6 vel.                   │
│ Tracción            Delantera                       │
│ Potencia            300 CV / 221 kW                 │
│ Etiqueta DGT        C                               │
│ Color exterior      Tornado Grey                    │
│ Color interior      Alcantara negra                 │
│ Puertas / Plazas    5 / 5                           │
│ Bastidor (VIN)      WVWZZZ... (parcial por privac.) │
│ Propietarios        1                               │
│ Origen              Múnich, Alemania                │
└─────────────────────────────────────────────────────┘
```

- **VIN:** mostrar 6 primeros + 4 últimos caracteres (WVWZZZ1KZ8W123456 → `WVWZZZ············3456`)
- **Si falta un dato:** `— (no declarado)` o `— (pendiente de inspección)`. NUNCA inventar.
- **Trampa:** no mostrar URL del anuncio original. El cliente no debe contactar al vendedor alemán.

### §5 — Equipamiento destacado

```
✦ Pack Performance (frenos, dirección adaptativa)
✦ Asientos deportivos en Alcantara
✦ Techo panorámico eléctrico
✦ Navegador Discover Pro + Apple CarPlay
✦ Cámara de aparcamiento trasera
✦ Faros LED矩阵 + luz de cruce adaptativa
✦ Climatización bizona
✦ Volante deportivo multifunción
✦ Sistema de infoentretenimiento 8" pantalla digital
```

- **Formato:** viñeta con ✦ + nombre + (1 frase explicativa si no es obvio)
- **Agrupar en categorías** si hay muchos: Seguridad · Confort · Tecnología · Deportivos
- **Trampa:** NO listar TODO lo del anuncio. Filtrar los 10-15 que aportan valor percibido real.

### §6 — Estado y antecedentes

```
HISTORIAL VERIFICADO:

• 1 propietario particular (no empresa de renting)
• 38.000 km en 4 años = 9.500 km/año (uso bajo, media nacional ~15k)
• Libro de revisiones sellado en VW Múnich Norte
• Última revisión: abril 2026 · 36.200 km
• ITV alemana (TÜV/HU) vigente hasta 09/2027
• Sin accidentes declarados por el vendedor
• Sin recalls activos según kfz-rueckrufe.de (consulta 12-08-2026)

ASPECTOS A CONFIRMAR EN INSPECCIÓN PREVIA:
☐ Estado de neumáticos (¿originales o ya reemplazados?)
☐ Desgaste de frenos (pack Performance → pastillas caras)
☐ Documentación COC completa
```

- **Honestidad = confianza:** si hay cosas por confirmar, se listan como `☐`
- **NO ocultar problemas conocidos del motor** (se ve en §9)
- **Trampa:** la palabra "verificado" solo si de verdad lo verificamos con fuente citada

### §7 — Estudio de mercado español

```
PRECIO DE MERCADO EN ESPAÑA — Golf GTI Clubsport 2021

Basado en 8 anuncios comparables activos en Coches.net + AutoUncle
(actualizados a 12-08-2026):

  Mínimo    ──────────────── 32.400 €
  Cuartil 1 (25% baratos) ─── 33.200 €
  MEDIANA   ──────────────── 34.500 €  ← referencia justa
  Cuartil 3 (75% baratos) ─── 36.100 €
  Máximo    ──────────────── 39.900 €

  NUESTRA OFERTA: 28.500 €
  AHORRO VS MEDIANA: -6.000 € (-17,4%) 🟢

¿Por qué tan competitivo?
• Compra directa en Alemania (sin intermediarios españoles)
• Negociación con concesionarios con los que trabajamos habitualmente
• Cliente no asume nuestro margen: es un honorario fijo transparente
```

- **Tabla visual con barras** si la plantilla Blade lo soporta (líneas `────` como fallback)
- **Mediana SIEMPRE con etiqueta** "referencia justa" (no "precio medio")
- **Mostrar ahorro** como valor absoluto + porcentaje
- **Trampa:** NO mostrar listado completo de comparables al cliente (con URLs). Solo el resumen estadístico. Los comparables con URL van al informe técnico.

### §8 — Por qué este coche (análisis DE vs ES)

```
COMPARATIVA ALEMANIA vs ESPAÑA

Mismo modelo · Misma configuración · Misma antigüedad

        Alemania          España
Precio  26.800 €          34.500 €
Uds.    12 activos         8 activos
Días    45 publicado       72 publicado

→ Hueco DE → ES: 7.700 € (22,4%) 🟢
→ Demanda ES alta + oferta escasa = posicionamiento de venta rápido

¿ES NEGOCIO PARA USTED?
Sí: incluso si el mercado español baja 5%, su precio de compra
sigue estando por debajo del mínimo español actual.
```

- **Visual claro:** tabla comparativa Alemania vs España
- **Hueco = argumento de valor percibido** (no confundir con margen interno)
- **Trampa:** nunca decir "para usted es rentable". Decir "su precio de compra está por debajo del mínimo español".

### §9 — Análisis técnico y riesgos conocidos

```
EVALUACIÓN TÉCNICA DEL MODELO

Motor EA888 2.0 TSI 300 CV (Volkswagen Group):
• Fiabilidad: BUENA. Motor ampliamente probado en Golf R, Audi S3.
• Problemas conocidos:
  - Consumo de aceite en unidades pre-2020 (esta es 2021, descartado)
  - Bobinas de encendido: revisar a los 60.000 km (esta está al tanto)
  - Cadena de distribución: a vigilar a partir de 100.000 km
• Recalls activos: NINGUNO para este VIN consultado (12-08-2026)

TÜV (ITV alemana):
• Vigente hasta 09/2027 · sin anotaciones de defectos graves

Veredicto técnico: COMPRA RECOMENDADA ✅
```

- **Honestidad en problemas conocidos:** citar quéproblemas tiene el motor en general + por qué este no los sufre
- **Si NO hay problemas conocidos:** "sin problemas reportados significativos para este motor"
- **Trampa:** nunca decir "perfecto". Decir "en línea con su edad y kilometraje".

### §10 — Desglose de costes transparente

```
DESGLOSE DE COSTE TOTAL — 28.500 € puesto en Huelva

Compra del vehículo (DE) ............... 21.950 €
Transporte DE → ES (camión) ............    900 €
ITV importación + tasas DGT ............    115 €
Impuesto matriculación (IEDMT) .........    830 € *
Matrícula española + gestoría ..........    305 €
Honorarios JJ Import Motors ...........  4.400 € **
─────────────────────────────────────────────────
TOTAL PUESTO EN HUELVA ............... 28.500 €

* IEDMT estimado. Cifra final confirmada tras inspección y COC.
** Honorarios fijos declarados. No hay costes ocultos ni comisiones
   sobre el precio del coche. Trabajamos para usted, no para el vendedor.
```

- **Transparencia total** salvo el margen (que se camufla en "compra del vehículo")
- **Lenguaje claro:** IEDMT con "(impuesto matriculación)" al lado
- **Honorarios como línea EXPLÍCITA:** esto genera confianza brutal
- **Trampa:** el precio "Compra del vehículo" es coherente con el anuncio alemán PERO incluye margen. NUNCA mostrar el anuncio alemán original al cliente.

### §11 — Proceso de importación (timeline)

```
TIMELINE ESTIMADO — 4-6 semanas desde la reserva

Semana 0 · Reserva y encargo
└─ Firma de contrato de servicios · pago reserva 1.000 €

Semana 1 · Compra y verificación
└─ Pago al vendedor · inspección previa en DE · recogida documentación

Semana 2-3 · Transporte y trámites DE
└─ Transporte a España · Ausfuhrkennzeichen · baja exportación

Semana 3-4 · Llegada y trámites ES
└─ Llegada a Huelva · ITV importación · pago IEDMT · matriculación

Semana 5-6 · Entrega
└─ Pago restante · entrega de llaves y documentación · fin de servicio

¿Puede retrasarse?
• 80% de los casos: dentro del plazo
• 15%: 1 semana extra (trámites Hacienda)
• 5%: 2+ semanas (ITV con observaciones, pieza pendiente)
```

- **Porcentajes de cumplimiento** = credibilidad (no prometer "15 días" si no es real)
- **Timeline visual con semanas numeradas**
- **Trampa:** no prometer fechas exactas. Hablar en semanas.

### §12 — Garantías y respaldo JJ Import Motors

```
QUÉ CUBRE JJ IMPORT MOTORS

✓ Gestión integral del proceso (usted no firma nada en alemán)
✓ Verificación documental completa (COC, TÜV, historial)
✓ Inspección previa a la compra en destino (fotos + vídeo)
✓ Tramitación ITV importación y matriculación
✓ Acompañamiento hasta entrega de llaves
✓ Soporte post-venta 30 días (incidencias documentales)

NO CUBRE (porque no está en nuestra mano):
✗ Garantía mecánica del vehículo (la ofrecida por el vendedor alemán,
  si la hubiera, se traslada; en caso contrario, recomendamos asegurar
  con terceros)
✗ Problemas ocultos no detectables en inspección visual
✗ Costes de mantenimiento ordinario

IMPORTANTE: somos intermediarios de confianza, no propietarios del
vehículo. El coche se matricula a su nombre desde el primer día.
```

- **Honestidad radical sobre qué cubre y qué no** → reduce disputas posteriores
- **Recomendaciones de terceros** para lo que no cubrimos (seguro mecánico)
- **Trampa:** nunca prometer "garantía total". Es mentira y nos expone legalmente.

### §13 — Preguntas frecuentes

```
PREGUNTAS FRECUENTES

¿Puedo ver el coche antes de pagar?
No físicamente (está en Alemania). Sí mediante vídeo inspección (60 fotos
+ vídeo 5 min) que le enviamos antes de pagar el resto.

¿Y si hay un problema al llegar?
Tiene 30 días para reportar incidencias documentales. Problemas mecánicos
cubiertos por el seguro que recomendamos contratar.

¿El IEDMT es fijo?
Es una estimación basada en el CO₂ declarado. La cifra final la confirma
Hacienda. Si sube más de 200 €, le avisamos ANTES de pagar para que decida.

¿Puedo financiar?
Sí, con su banco o con financiación externa. No ofrecemos financiación
propia pero podemos acompañarle en el proceso.

¿Y si me arrepiento después de la reserva?
La reserva de 1.000 € cubre los gastos ya incurridos. Si cancela ANTES de
la compra en Alemania, se reembolsa el resto. Si cancela después, se retiene
lo invertido. Todo está en el contrato de servicios.

¿Cuánto tarda en llegar?
4-6 semanas normalmente. Ver timeline §11.
```

- **6-8 preguntas frecuentes** que el cliente realmente se hará
- **Respuestas cortas** (3-5 líneas máximo cada una)
- **Tono:** resolver objeciones antes de que se conviertan en bloqueo

### §14 — Próximos pasos

```
PRÓXIMOS PASOS

1. Reserva de 1.000 € (transferencia o bizum)
   → activa el bloqueo del vehículo en Alemania

2. Envío de vídeo inspección + fotos detalladas (48-72h)

3. Revisión y validación: confirmación o cancelación con reembolso

4. Si valida, firma de contrato de servicios y pago intermedio (40%)

5. Inicio del proceso de importación (ver §11)

CONTACTO DIRECTO:
📱 675 70 14 39 · 691 48 59 27
✉️ jjimportmotors@gmail.com
📍 Huelva capital
```

- **Acción clara y única:** "Reserva 1.000 €"
- **Reduce fricción:** explica qué pasa después de la reserva
- **Trampa:** no mencionar "honorarios" aquí. Ya están en §10.

### §15 — Pie legal y fuentes

```
─────────────────────────────────────────────
JJ IMPORT MOTORS · Huelva (España)
NIF/datos fiscales disponibles bajo solicitud
jjimportmotors@gmail.com · 675 70 14 39

Este dossier se basa en datos de mercado públicos capturados
el 12-08-2026 desde Coches.net, mobile.de, AutoScout24.de,
AutoUncle y kleinanzeigen.de. Los precios son estimaciones
basadas en anuncios activos y pueden variar.

El IEDMT es una estimación. La cifra definitiva la fija la AEAT.

Documento generado por JJ Import Motors.
No publicar ni distribuir sin autorización.
─────────────────────────────────────────────
```

- **Listado de fuentes** = transparencia metodológica
- **Disclaimer sobre IEDMT y precios** = protección legal
- **Trampa:** NO citar URLs específicas de los anuncios comparables

---

## 📐 Formato `.txt` (para Blade/Browsershot)

Archivo nuevo: `dossier-cliente.txt` (en el ZIP del Flujo A).

Bloques esperados (los que apliquen, los vacíos NO se escriben):

```
[DOSSIER_NUM]       JJM-2026-08-12-0042
[DOSSIER_FECHA]     12 de agosto de 2026
[TITULO_MODELO]     Volkswagen Golf GTI Clubsport · 2021
[PRECIO_DESTACADO]  28.500 € puesto en Huelva

[CARTA]
Estimado cliente, ...

[RESUMEN_30S]
✅ Oportunidad clara: ...
✅ Configuración deseada: ...
⚠️ A tener en cuenta: ...
📍 Próximo paso: reserva de 1.000 € para iniciar la compra.

[FICHA_TECNICA]
Marca / Modelo | Volkswagen Golf GTI Clubsport
Versión        | 2.0 TSI 300 CV Performance
Año            | 2021
...

[EQUIPAMIENTO] Pack Performance
[EQUIPAMIENTO] Asientos deportivos Alcantara
[EQUIPAMIENTO] Techo panorámico
...

[ESTADO_VERIFICADO]
• 1 propietario particular
• 38.000 km en 4 años = 9.500 km/año
• Libro revisiones sellado en VW
...

[ESTADO_PENDIENTE]
☐ Estado de neumáticos
☐ Desgaste de frenos
☐ Documentación COC

[MERCADO_ES_MIN]       32.400
[MERCADO_ES_Q1]        33.200
[MERCADO_ES_MEDIANA]   34.500
[MERCADO_ES_Q3]        36.100
[MERCADO_ES_MAX]       39.900
[MERCADO_ES_N]         8
[MERCADO_NUESTRO]      28.500
[MERCADO_AHORRO_EUR]   6.000
[MERCADO_AHORRO_PCT]   17,4
[MERCADO_SEMAFORO]     green

[DE_VS_ES] Alemania | 26.800 €
[DE_VS_ES] España | 34.500 €
[DE_VS_ES] Unidades DE | 12 activos
[DE_VS_ES] Unidades ES | 8 activos
[DE_VS_ES] Días publicado DE | 45
[DE_VS_ES] Días publicado ES | 72
[DE_VS_ES] Hueco DE → ES | 22,4% 🟢

[EVAL_TECNICA_MOTOR]
Motor EA888 2.0 TSI 300 CV: fiabilidad buena...

[EVAL_TECNICA_PROBLEMAS]
- Consumo de aceite en pre-2020 (descartado)
- Bobinas: revisar 60k (al tanto)
- Cadena distribución: vigilar 100k

[EVAL_RECALLS]        Sin recalls activos para este VIN (12-08-2026)
[EVAL_VEREDICTO]      COMPRA RECOMENDADA ✅

[COSTE_LINEA] Compra del vehículo (DE) | 21.950 €
[COSTE_LINEA] Transporte DE → ES | 900 €
[COSTE_LINEA] ITV importación + DGT | 115 €
[COSTE_LINEA] Impuesto matriculación (IEDMT) | 830 € *
[COSTE_LINEA] Matrícula + gestoría | 305 €
[COSTE_LINEA] Honorarios JJ Import Motors | 4.400 € **
[COSTE_TOTAL]                        28.500 €
[COSTE_NOTA]  * IEDMT estimado ...
[COSTE_NOTA]  ** Honorarios fijos declarados ...

[TIMELINE_SEMANA] 0 | Reserva y encargo | ...
[TIMELINE_SEMANA] 1 | Compra y verificación | ...
[TIMELINE_SEMANA] 2-3 | Transporte y trámites DE | ...
[TIMELINE_SEMANA] 3-4 | Llegada y trámites ES | ...
[TIMELINE_SEMANA] 5-6 | Entrega | ...

[GARANTIA_INCLUIDO]   Gestión integral del proceso
[GARANTIA_INCLUIDO]   Verificación documental completa
[GARANTIA_INCLUIDO]   Inspección previa con fotos + vídeo
[GARANTIA_INCLUIDO]   Tramitación ITV y matriculación
[GARANTIA_INCLUIDO]   Soporte post-venta 30 días
[GARANTIA_NO_INCLUIDO] Garantía mecánica (cubre vendedor o seguro)
[GARANTIA_NO_INCLUIDO] Problemas ocultos no detectables
[GARANTIA_NO_INCLUIDO] Mantenimiento ordinario

[FAQ_Q] ¿Puedo ver el coche antes de pagar?
[FAQ_A] No físicamente. Sí por vídeo inspección.
[FAQ_Q] ¿Y si hay un problema al llegar?
[FAQ_A] Tiene 30 días para reportar...
...

[PASOS]
1. Reserva de 1.000 € → bloqueo del vehículo
2. Envío vídeo inspección (48-72h)
3. Validación: confirmación o reembolso
4. Firma contrato + pago intermedio 40%
5. Inicio del proceso de importación

[CONTACTO_TELEFONO]  675 70 14 39 · 691 48 59 27
[CONTACTO_EMAIL]     jjimportmotors@gmail.com
[CONTACTO_DIRECCION] Huelva capital

[PIE_FUENTES] Coches.net · mobile.de · AutoScout24.de · AutoUncle · kleinanzeigen.de
[PIE_LEGAL] Este dossier se basa en datos de mercado públicos capturados el ...
```

---

## 🎨 Brand visual (referencia para plantilla Blade)

- **Paleta:** estoril-700 (#1A306D) primario · platinum-100 fondo · asphalt para texto
- **Tipografía:** sans-serif (Inter / system-ui) para cuerpo, weights 400/600/700
- **Iconografía:** ✅ ✦ ⚠️ 📍 🟢 🟡 🔴 — no usar emojis de caras/personas
- **Estructura PDF:** 8-12 páginas (1 sección = 1 página aprox.)
- **Header:** logo JJ + "Dossier de oportunidad #JJM-..." en cada página
- **Footer:** número de página + contacto + disclaimer
- **Trampa:** no usar tipografías serif ni colores cálidos (rojos/naranjas) salvo semáforo.

---

## 🔴 Reglas duras del dossier (no romper nunca)

1. **NUNCA** incluir el balance interno, margen o vendibilidad cuantitativa.
2. **NUNCA** citar URLs de anuncios comparables (sí en informe técnico).
3. **NUNCA** mostrar el anuncio alemán original al cliente.
4. **NUNCA** inventar datos. Lo desconocido se dice ("pendiente de inspección").
5. **NUNCA** prometer plazos exactos. Hablar en semanas + % de cumplimiento.
6. **NUNCA** garantizar mecánicamente. Remitir a seguro externo.
7. **SIEMPRE** citar fecha de captura de datos (antigüedad = credibilidad).
8. **SIEMPRE** explicar el IEDMT como estimación y citar AEAT.
9. **SIEMPRE** mostrar honorarios como línea explícita y única (transparencia).

---

## 🆚 Diferencia con `ficha-publicitaria.txt`

| Aspecto | Ficha publicitaria | Dossier cliente |
|---|---|---|
| **Finalidad** | Vender (anuncio en portales) | Informar (entrega al cliente) |
| **Lugar de uso** | Wallapop, Coches.net, Instagram | Email directo, reunión, PDF adjunto |
| **Tono** | Seductor, gancho emocional | Profesional, transparente, técnico |
| **Datos de coste** | Solo precio final y ahorro | Desglose completo con honorarios |
| **Mercado** | No (compite con otros anuncios) | Sí (justifica la oportunidad) |
| **Proceso** | No (interesa poco al comprador) | Sí (timeline + garantías) |
| **Longitud** | 1 página A4 | 8-12 páginas A4 |
| **Cuándo emitir** | Antes de captar cliente | Después de captar y validar interés |

> **Flujo ideal:** Captación con ficha publicitaria → cliente pide info → **envío de dossier** → cierre con reserva.
