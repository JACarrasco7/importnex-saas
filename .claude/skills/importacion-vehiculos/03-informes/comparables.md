# Comparables y ajuste — Flujo A

> **Cargar cuando:** Se va a hacer un informe UNIDAD (Flujo A) con comparable ajustado.
> **No cargar para:** Flujo B (solo mediana) ni Flujo C (sin comparable).

---

## 📈 VENDIBILIDAD — 5 factores, 100 puntos (movido de SKILL.md)

| # | Factor | Peso | Fuente | Estado |
|---|---:|---|---|---|
| 1 | Demanda del modelo | 30 | Coches.net `publicationDate` (mediana días) | ✅ |
| 2 | Escasez configuración | 25 | AS24.es `countryCode` + recuento | ✅ |
| 3 | Atractivo | 20 | Criterio cualitativo | Manual |
| 4 | Equipamiento sobre std ES | 15 | mobile.de `features` vs acabado ES | ✅ |
| 5 | Km e historial | 10 | mobile.de ficha: propietarios, ITV, km/año | ✅ |

**Puntuación:** Demanda: top-10=30, fuerte=22, minoritario=14, nicho=6 · Escasez: ≤20=25, 20-50=21, 50-100=16, 100-300=10, >300=4 · Atractivo: icónico=18-20, deportivo=14-17, premium=10-13, utilitario=4-8 · Equipamiento: techo=4, cuero=3, AWD=3, LED=2, audio=2, HUD=1 · Historial: libro=3, 1dueño=2, <15k/año=3, ITV=2.

### Matriz de decisión (solo Flujo A)

| | Margen ≥10% | Margen <10% |
|---|---|---|
| **Vendibilidad ≥65** | 🟢 COMPRA PRIORITARIA | 🔵 OFERTA DE CONTENIDO |
| **Vendibilidad <65** | 🟡 SOLO BAJO PEDIDO | 🔴 DESCARTAR |

> La casilla azul se ignora siempre: coche con 5% margen y vendibilidad alta **sí se oferta**. Trae los clientes de los 3 siguientes.

---

## ⚖️ 9 claves del comparable

| # | Clave | Cómo se fija |
|---|---|---|
| 1 | Modelo | Slug o `ms` validado contra `<h1>` |
| 2 | Versión/potencia | Por kW, nunca por precio |
| 3 | Carrocería | Berlina, Sportback, Variant, SUV… |
| 4 | Motorización | Gasolina, diésel, PHEV |
| 5 | Cambio | Manual descuenta 1.500-2.500 € |
| 6 | Año | ±1 año |
| 7 | Kilómetros | ±20% |
| 8 | Acabado y equipamiento | **AJUSTE** (tabla primas) |
| 9 | Historial | **AJUSTE** (libro, dueños, ITV) |

---

## 🔍 Filtro de admisión (solo Flujo A)

1. Solo ±2 años y ±40% km del objetivo
2. Ningún ajuste individual > ±25%
3. <15 comparables → **rango, no cifra**. Manda el suelo (cuartil bajo).
4. El veredicto contra mediana **Y** cuartil bajo. Si contra cuartil bajo sale negativo → NO.

---

## 📊 Comparable sin muestra (3 métodos en cascada)

**Problema:** Cuando el candidato alemán tiene 90k km y los españoles 130-160k, el filtro ±40% excluye todo.

**Solución:** Aplicar en este orden:

```
┌─────────────────────────────────────────┐
│ ¿Muestra española cubre ±40% km?         │
└────────────┬────────────────────────────┘
             │
     ┌───────┴───────┐
     │ SÍ            │ NO
     ▼               ▼
┌─────────┐  ┌───────────────────────────┐
│ MÉTODO  │  │ ¿Muestra ampliada (±3 años,│
│ NORMAL  │  │ ±60% km) tiene ≥8 uds?     │
│(9 claves│  └────────────┬──────────────┘
│+ ajuste)│               │
└─────────┘       ┌───────┴───────┐
                  │ SÍ            │ NO
                  ▼               ▼
          ┌────────────┐  ┌──────────────┐
          │ MÉTODO      │  │ MÉTODO        │
          │ AMPLIADO    │  │ CUALITATIVO   │
          │             │  │               │
          │ Regresión   │  │ "Sin comparable│
          │ lineal sobre│  │ español válido │
          │ km vs precio│  │ para este km.  │
          │ + disclaimer│  │ Evaluación     │
          │ "estimación │  │ cualitativa:   │
          │ ampliada"   │  │ atractivo,     │
          │             │  │ equipamiento,  │
          │             │  │ historial."    │
          └────────────┘  └──────────────┘
```

**Reglas:**
- **Método normal:** rango del comparable en el informe
- **Método ampliado:** añadir disclaimer "estimación con muestra ampliada (±3 años, ±60% km)"
- **Método cualitativo:** NO dar cifra de ahorro. Evaluar solo vendibilidad y decir "pendiente de muestra española en este tramo de km"

---

## 🕵️ Detección de competencia en anuncios españoles

**Objetivo:** Identificar si el vendedor ES es un importador/revendedor profesional (competencia directa).

**Regex para detectar competencia:**

```python
import re

PATTERNS_COMPETENCIA = [
    r'importaci[oó]n',
    r'alemania|alem[aá]n',
    r'ue|uni[oó]n europea',
    r'sin iva|iva deducible',
    r'profesional|concesionario|taller',
    r'garant[ií]a.*meses',
    r'revisado|preparado|puesto a punto',
    r'entrega.*domicilio',
    r'financiaci[oó]n',
]

def es_competencia(descripcion: str) -> bool:
    """Devuelve True si el anuncio parece de un importador/revendedor."""
    desc_lower = descripcion.lower()
    matches = sum(1 for p in PATTERNS_COMPETENCIA if re.search(p, desc_lower))
    return matches >= 2  # Si coinciden 2+ patrones, es competencia
```

**Regla:** Si `es_competencia() == True`, excluir de la muestra española (no es comparable válido). Anotar en §8 (Riesgos): "X de Y anuncios españoles son de competencia, muestra reducida a Z"

**Cuándo aplicar:**
- **Fase 2 de Flujo A**, después de recolectar las 3 fuentes ES (Wallapop, Milanuncios, Coches.net) y antes del comparable ajustado.
- **NO aplicar en Fase 1** (no hay descripción completa, solo precio/año/km).
- **NO aplicar en Flujo B/C** (no necesitan comparables españoles).

---

## 💶 Primas de equipamiento

| Equipamiento | Prima ES | Nota |
|---|---:|---|
| Techo panorámico | +1.000-1.500 € | Muy valorado en España |
| Cuero | +800-1.200 € | Menos valor si es tela premium |
| Tracción total (AWD/4MOTION/quattro) | +1.500-2.500 € | Clave en SUV |
| Faros LED/Matrix | +500-800 € | Estándar en premium 2020+ |
| Audio premium (Burmester, B&O, Harman) | +300-600 € | Solo si es tope gama |
| Virtual cockpit / HUD | +300-600 € | Valorado en Audi/BMW |
| Cámara 360° + sensores | +400-700 € | Estándar en premium 2018+ |
| Asientos ventilados/masaje | +200-500 € | Poco común en compactos |
| Portón eléctrico | +200-400 € | Estándar en SUV |
| Enganche remolque | +150-300 € | Solo si es extra, no de serie |

**Ajuste manual:** Solo sumar primas que el candidato DE tenga y el comparable ES **no** tenga (o viceversa). No sumar todo el equipamiento.

---

## 📝 Ajuste línea a línea (formato en informe §5)

```
COMPARABLE AJUSTADO — VW Golf GTI Clubsport 2017 (62k km)

Base: mediana ES 34.500 € (18 uds, 2016-2018, 50-80k km)

Ajustes:
  Año: candidato 2017 vs mediana 2017 → ±0 €
  Km: candidato 62k vs mediana 65k → +300 € (menos km)
  Cambio: ambos DSG → ±0 €
  Techo: candidato SÍ, mediana NO → +1.200 €
  Cuero: ambos NO → ±0 €
  Audio: candidato Burmester, mediana estándar → +450 €
  Historial: candidato libro sellado, mediana mixto → +400 €

TOTAL AJUSTE: +2.350 €
PRECIO OBJETIVO: 34.500 + 2.350 = 36.850 €
```

**Regla:** Mostrar el ajuste en el informe §5. El usuario debe poder replicar el cálculo.
