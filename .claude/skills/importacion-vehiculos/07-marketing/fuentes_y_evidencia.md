# Fuentes y evidencia — `07-marketing/fuentes_y_evidencia.md`

> Cada afirmación que JJ Import Motors pone en un anuncio lleva URL de la
> fuente. La regla de oro `business-model.md` lo exige: **sin enlace = no
> cuenta**. Esta página es el catálogo de fuentes usadas por el Flujo M.

---

## 1 · Reglas duras de citación

1. **Toda cifra de mercado** lleva URL a su fuente.
2. **Todo precio de comparable** lleva URL al anuncio.
3. **Todo «mercado alemán más barato»** lleva URL al anuncio alemán y al
   anuncio español comparable.
4. **Toda etiqueta ambiental / DGT** lleva URL al documento normativo.
5. **Todo lo que diga normativa legal** lleva URL al BOE o ley aplicable.

---

## 2 · Fuentes permitidas

### 2.1 · Mercados de Alemania

| Fuente | Uso | Cómo citarla |
|---|---|---|
| **mobile.de** | Anuncios de coches (origen DE) | `https://www.mobile.de/.../inserat/<id>.html` |
| **autoscout24.de** | Anuncios + datos de mercado DE | `https://www.autoscout24.de/angebote/<id>` |
| **kleinanzeigen.de** | Anuncios de particulares DE | `https://www.kleinanzeigen.de/s-anzeige/<slug>/<id>` |

### 2.2 · Mercados de España

| Fuente | Uso | Cómo citarla |
|---|---|---|
| **autoscout24.es** | Anuncios + datos de mercado ES | `https://www.autoscout24.es/anuncios/<id>` |
| **coches.com** | Anuncios ES | `https://www.coches.com/<id>.htm` |
| **wallapop** | Anuncios + particulares | `https://es.wallapop.com/item/<slug>-<id>` |
| **milanuncios** | Anuncios ES | `https://www.milanuncios.com/<id>` |

### 2.3 · Datos de mercado

| Fuente | Uso | Cómo citarla |
|---|---|---|
| **autouncle.es** | Estadísticas de precio (ES + DE) | `https://www.autouncle.es/<categoria>.html` |
| **EUROCAREXP** | Datos comparativos UE | URL del artículo o dashboard |
| **DAT (Deutsche Automobil Treuhand)** | Valores residuales DE | `https://www.dat.de/...` |
| **DGT** | Etiqueta ambiental, datos históricos | `https://www.dgt.es/...` |

### 2.4 · Normativa

| Norma | Cómo citarla |
|---|---|
| **Real Decreto Legislativo 1/2007** (Ley General para la Defensa de los Consumidores y Usuarios) | `https://www.boe.es/buscar/act.php?id=BOE-A-2007-20555` |
| **Ley 11/2022** (modificaciones RDL 1/2007, garantía VO) | `https://www.boe.es/...` |
| **Orden ICT/1257/2019** (normas de etiquetado) | `https://www.boe.es/...` |
| **Reglamento (UE) 2018/858** (homologación de vehículos) | URL EUR-Lex |
| **Real Decreto 750/2010** (matriculación) | `https://www.boe.es/...` |

### 2.5 · Fuentes internas

El ZIP del Flujo A: contiene el `informe.json` con todos los datos del
coche. La ruta canónica se cita como `informe.json:{campo}`:

- `informe.json:vehiculo.precio_cliente`
- `informe.json:vehiculo.km`
- `informe.json:comparables[3].precio_mediana`
- `informe.json:investigacion.aspectos[0].descripcion`

---

## 3 · Plantilla de cita (en el copy)

Cuando la cifra venga de fuente externa, el campo `fuentes[]` del JSON del
copy lleva:

```json
{
  "afirmacion": "Este modelo se matriculó en Alemania por debajo de la mediana",
  "fuentes": [
    {
      "tipo": "anuncio_de",
      "url": "https://www.mobile.de/.../inserat/abc123.html",
      "titulo": "BMW 320d Touring 2018 — 84.000 km",
      "consulta": "2026-09-06",
      "snapshot": "screenshots/anuncio_de_abc.png"
    },
    {
      "tipo": "anuncio_es",
      "url": "https://www.autoscout24.es/anuncios/xyz",
      "titulo": "BMW 320d Touring 2018 — 89.000 km",
      "consulta": "2026-09-06"
    }
  ]
}
```

El validador (`check_marketing.py`) exige al menos una fuente por cifra
de mercado. Si la cifra no tiene fuente, se considera interna (procedente
del `informe.json`) y debe referenciar al campo concreto.

---

## 4 · Comportamiento cuando no hay fuente

Si JJ Import Motors NO tiene dato del mercado para un argumento:

1. **No se usa el argumento.** Se sustituye por otro de los 8.
2. **No se inventa un número.** Jamás.
3. **No se omite la fuente.** Si la cifra es interna, se cita como
   `informe.json:{campo}`.

---

## 5 · Auditoría periódica (sprint mensual)

Una vez al mes (recomendado: día 1), el equipo hace:

1. Sacar las 10 fuentes más citadas del mes (`grep '"url":' contenido/*.json
   | sort | uniq -c | sort -nr | head -10`).
2. Verificar que cada URL sigue accesible (`curl -I --max-time 5`).
3. Si alguna da 404 o redirect permanente, reemplazar y anotar el cambio.
4. Renovar capturas de pantalla (snapshots pueden quedar obsoletas).

El validador detecta citas con `consulta > 30 días` y avisa en el siguiente
ciclo QA.
