# 🚀 Guía de uso — Skills `estudio-mercado` + `importacion-vehiculos`

> **JJ Import Motors · 17-ago-2026**
> Guía práctica para usar las dos skills de Claude Desktop **en el orden correcto**, con ejemplos reales de prompts, comandos y salidas. Cubre: instalación, flujo de trabajo, ejemplos por skill, integración con Laravel (`/mercado`) y resolución de dudas.

---

## 📦 0. Instalación (Claude Desktop)

Ambas skills se distribuyen como `.skill.zip`. Subirlas a Claude Desktop **por separado**:

| Skill | ZIP | Contenido |
|---|---|---|
| **`estudio-mercado`** | `estudio-mercado.skill.zip` | Estudia el mercado y genera `datos_mercado.json` |
| **`importacion-vehiculos`** | `importacion-vehiculos.skill.zip` | Busca/valora coches concretos (7 fuentes, 5 flujos) |

**Pasos:**
1. Copia ambos ZIP a tu PC (están en `c:\laragon\www\importnexcore\.claude\skills\<skill>\`).
2. En Claude Desktop: arrastra cada `.skill.zip` a la ventana de chat (o `/import`).
3. Verifica que en la configuración aparecen las dos skills activadas.

> ⚠️ **Regla del ZIP:** siempre se regeneran en su carpeta del skill (nunca en Desktop). Si tocas algún `.md`, se regenera el ZIP y se vuelve a subir.

---

## 🧭 1. Concepto clave: DOS skills, UN flujo en cascada

```
┌─────────────────────────────────────────────────────────┐
│  PASO 1 · estudio-mercado                                │
│  "Estudia el mercado" → genera datos_mercado.json        │
│  (mapa persistente: oferta, precios, hueco, veredicto)   │
└──────────────────────┬──────────────────────────────────┘
                       │ market:import (Laravel)
                       ▼
┌─────────────────────────────────────────────────────────┐
│  PASO 2 · SaaS Laravel                                   │
│  php artisan market:import → market_models → /mercado    │
│  (catálogo público + panel admin + leads)               │
└──────────────────────┬──────────────────────────────────┘
                       │ PASO 0 / FIJAR MODELOS
                       ▼
┌─────────────────────────────────────────────────────────┐
│  PASO 3 · importacion-vehiculos                          │
│  "Busca Golf GTI" → informe + candidatos con enlaces     │
│  Al cerrar: vuelca la medición real AL MAPA (feedback)   │
└─────────────────────────────────────────────────────────┘
```

**En una frase:** `estudio-mercado` decide **QUÉ** merece la pena; `importacion-vehiculos` busca **DÓNDE** está y lo valora. El mapa se re-alimenta con cada búsqueda.

---

## 🎯 2. Skill `estudio-mercado` — el mapa de mercado

### 2.1 Qué hace

Estudia el mercado de coches de 2ª mano (ES + DE) con **datos reales**: oferta, precios, rotación, demanda, hueco y veredicto por categoría/modelo. Salida: **`datos_mercado.json`** en la ruta pactada `C:/Users/jacar/Desktop/JJImportMotors/datos_mercado.json`.

### 2.2 Cómo pedirle un estudio (ejemplos)

**Estudio completo por categorías:**
```
estudia el mercado de coches
```

**Estudios dirigidos (recomendado para empezar — construye la BBDD poco a poco):**
```
estudia la marca Audi
estudia el Golf 8
estudia berlinas de 25k+
estudia coches para primer coche
estudia todos los Mercedes del segmento premium
```

**Con acotaciones:**
```
estudia compactos diésel de 2016+, 8-14k, para uso diario (muchos km)
estudia showstoppers deportivos de 25k+ para un cliente entusiasta
```

**Refresco (delta — solo lo caducado):**
```
actualiza el estudio de mercado
refresca solo la categoría showstoppers (que caduca antes)
```

### 2.3 Qué te pregunta antes de navegar (FASE 0)

- ¿Estudio **completo por categorías** o **dirigido**? (marca / modelo / segmento / rango / tipo_cliente)
- ¿Qué categorías? (showstoppers / alta rotación / gemas)
- ¿Rango de año/km/precio base? (si no, el mercado manda)
- ¿Profundidad? (rápido: solo hueco · completo: hueco + rotación + demanda)
- **ACK de 1 línea** + tu OK antes de navegar.

### 2.4 Salidas

| Archivo | Dónde |
|---|---|
| `datos_mercado.json` | `C:/Users/jacar/Desktop/JJImportMotors/datos_mercado.json` (ruta pactada L2) |
| `informe_mercado_<fecha>.md` | `informes\mercado\` |

**Ejemplo de entrada del mapa (lo que verás):**
```json
{
  "slug": "golf-vii-gti",
  "alias": ["golf gti", "golf 7 gti"],
  "categoria": "showstoppers",
  "segmento": "compacto",
  "rango_precio": "14-25k",
  "tipo_cliente": "deporte_ocio",
  "mediana_de": 18500,
  "mediana_es": 20850,
  "hueco_pct": 11.3,
  "hueco_neto_pct": 5.0,
  "veredicto": "amarillo",
  "mejor_mercado": "DE",
  "fuente_medicion": "estudio",
  "confianza_precio": 4,
  "refrescar_antes_de_categoria": "2026-08-31"
}
```

---

## 🖥️ 3. Integración con Laravel — de `datos_mercado.json` a `/mercado`

### 3.1 Importar el mapa (rellenar la web)

```powershell
# En la raíz del proyecto (c:\laragon\www\importnexcore)
php artisan market:import --file="C:\Users\jacar\Desktop\JJImportMotors\datos_mercado.json"
php artisan market:import --file="..." --org="JJ Import Motors"     # org concreta
php artisan market:import --file="..." --dry-run                     # validar sin guardar
```

**Qué hace:** upsert por `slug`, respeta `veredicto_fuente=humano`, crea histórico de precios, calcula vendibilidad fallback, valida categoría/segmento/tipo_cliente/veredicto/mejor_mercado/rango.

### 3.2 Otros comandos

```powershell
php artisan market:export                       # JSON actualizado (con correcciones humanas) al Desktop
php artisan market:alerts                       # outliers + chollos (crea Alert + push)
php artisan market:freshness                    # reporte de modelos caducados
```

### 3.3 Rutas de la web

| Ruta | Acceso | Qué es |
|---|---|---|
| `/mercado` | Público | Catálogo "bajo pedido" (filtros, comparar, "Me interesa", coste en Huelva) |
| `/mercado/admin` | Autenticado | Panel admin (KPIs, tabla, editar veredicto/nota/oportunidad) |
| `/mercado/admin/leads` | Autenticado | Pipeline de leads (estado/nota) |
| `/mercado/admin/reportes` | Autenticado | Reportes por categoría/segmento/top oportunidades/evolución |
| `GET /api/market` | Token | Puente chat→SaaS (listado del mapa) |
| `GET /api/market/stats` | Token | Estadísticas agregadas |
| `GET /api/public/market` | Público | Catálogo público API |

---

## 🔍 4. Skill `importacion-vehiculos` — buscar y valorar

### 4.1 Los 5 flujos (cómo decide)

| Flujo | Disparador | Ejemplo de prompt | Output |
|---|---|---|---|
| **A: UNIDAD** | Pegas una URL | "evalúa este https://www.mobile.de/..." | Informe UNIDAD + dossier + ZIP |
| **B: MODELO** | Modelo concreto | "busca Golf GTI 2018+" | Informe MODELO + top 5 con enlaces |
| **C: MERCADO** | Escaneo de oportunidades | "qué merece la pena / escanea el mercado" | Informe BÚSQUEDA (N modelos) |
| **D: DESCUBRIMIENTO** | Cliente sin modelo | "cliente con 9.000 €, 2016+, gasolina, ¿qué cabe?" | Informe de MODELOS por país |
| **E: STOCK** | Catálogo bajo pedido | "stock recurrente de publicaciones" | Informe STOCK (Markdown+PDF+JSON) |

### 4.2 Ejemplos reales de prompts

**Flujo A — evaluar una unidad concreta:**
```
evalúa este https://www.mobile.de/fahrzeuge/details.html?id=123456789
```
```
mira este anuncio de un Astra OPC y dime si merece la pena para un cliente
```

**Flujo B — buscar un modelo:**
```
busca Golf GTI 2016+ para reventa, presupuesto 20k
```
```
busca Audi A3 8V 2017+ diesel para un cliente, ≤120.000 km
```

**Flujo C — oportunidades:**
```
qué merece la pena ahora mismo en el mercado
```
```
dime 10 modelos rentables para importar de Alemania
```

**Flujo D — cliente sin modelo:**
```
cliente quiere un coche con 9.000 € todo incluido, 2016+, gasolina, +120cv, 5 puertas.
¿qué modelos caben y en qué mercado sale mejor?
```

**Flujo E — stock/catálogo:**
```
stock recurrente de publicaciones por categorías (showstoppers, alta rotación, gemas)
```

**Consultas puntuales:**
```
calcular coste importacion de un Golf 7 a 15.000 €
cuánto es el IEDMT de un coche con 150 g/km de 2018
precio maximo de compra para un Serie 3 2018 que sale a 16.000 en DE
```

### 4.3 Qué recibes como respuesta

- **ACK de 1 línea** primero (📥 ENTENDIDO — qué · para qué · entregable · flujo).
- **Plan de fase** de 3-5 líneas (fuentes, filtros, presupuesto de tokens) → **tú das OK**.
- **Informe** con tablas de candidatos **con enlaces a los anuncios** (regla A21) + fuentes consultadas.
- Al elegir candidato: informe UNIDAD + dossier + ZIP (Flujo A).

---

## 🔁 5. Usar AMBAS a la vez (el bucle completo)

### 5.1 Escenario 1 — El mapa ya tiene datos (recomendado)

```
1. (Ya tienes datos_mercado.json del estudio + market:import hecho)
2. Abre Claude con la skill importacion-vehiculos
3. "busca Golf GTI 2018+ para reventa"
   → La skill lee datos_mercado.json en PASO 0:
     "El mapa ya tiene el Golf VII GTI: hueco 11,3% (neto ~5%), veredicto amarillo.
      El hueco neto es justo — ¿quieres que busque igualmente o miro otra alternativa?"
4. Tú decides → ejecuta la búsqueda con criterio
5. Al cerrar, vuelca la medición real AL MAPA (feedback)
```

### 5.2 Escenario 2 — Empezar de cero (rellenar datos)

```
1. Abre Claude con la skill estudio-mercado
2. "estudia la marca Audi" (o la categoría que quieras)
   → FASE 0 te pregunta alcance → navega ES+DE → escribe datos_mercado.json
3. Ejecuta en PowerShell: php artisan market:import --file="C:\...\datos_mercado.json"
4. Abre /mercado y /mercado/admin → ya ves los modelos con datos
5. Ahora usa importacion-vehiculos para buscar clientes reales
```

### 5.3 Cuándo usar cada una

| Situación | Skill a usar |
|---|---|
| "¿Qué coches son rentables/entran en presupuesto?" | **estudio-mercado** (o importacion Flujo C/D/E) |
| "¿Este anuncio concreto vale la pena?" | **importacion-vehiculos** (Flujo A) |
| "Busca un modelo concreto" | **importacion-vehiculos** (Flujo B) |
| "El mapa está vacío / quiero rellenarlo" | **estudio-mercado** |
| "Necesito stock para el catálogo" | **importacion-vehiculos** (Flujo E) → sale del mapa |

---

## 📋 6. Reglas de oro (no romper)

1. **A21 — Enlaces SIEMPRE:** todo candidato/dato lleva el enlace al anuncio (ficha) + fuentes con URL. Sin enlaces la entrega no vale. *(La regla que más repite el usuario.)*
2. **FASE 0 ENTENDER:** ACK de 1 línea antes de navegar (qué → para qué → entregable).
3. **Protocolo de Mando:** el usuario aprueba cada fase; dentro de la fase la IA ejecuta sin preguntar.
4. **PASO 0 cache:** antes de navegar, comprobar si el modelo ya está medido (<3 semanas) → no re-buscar.
5. **Navegación real SIEMPRE** (A15) — no usar snippets de búsqueda web como sondeo.
6. **mobile.de NUNCA saltar** (A2); AS24 solo para contar (A8).
7. **El mapa asesora, el usuario decide** (L6): si el veredicto es 🔴 pero el cliente lo quiere, se avisa y se ejecuta.
8. **Veredicto humano > IA:** lo corregido en `/mercado/admin` no se sobrescribe en la próxima importación.
9. **Mapa en ruta fija:** `Desktop\JJImportMotors\datos_mercado.json` — si falta, la skill avisa (no fallback silencioso).
10. **ZIPs en su carpeta** (no Desktop) · regenerar si se tocan los `.md` · 0 backslashes.

---

## 🛠️ 7. Troubleshooting rápido

| Problema | Solución |
|---|---|
| `/mercado` vacío | Ejecuta `market:import` con un `datos_mercado.json` que tenga modelos con `publicar_en_catalogo: true` |
| El mapa no se encuentra | Asegúrate de que `datos_mercado.json` está en `Desktop\JJImportMotors\` (ruta pactada L2) |
| Un modelo no sale en la web | Marca `publicar_en_catalogo: true` en el JSON o en `/mercado/admin` |
| El import "salta" un slug | Su `categoria`/`segmento`/`tipo_cliente`/`veredicto`/`mejor_mercado`/`rango_precio` no es válido, o pertenece a otra org |
| Los stats no se actualizan | El cache se invalida al importar; si no, espera 30 min o `php artisan cache:clear` |
| Claude no usa el mapa | Ejecuta primero `estudio-mercado` (o dale el JSON) para que `datos_mercado.json` exista |
| Skills no se cargan en Claude | Re-importa los `.skill.zip`; verifica 0 backslashes en los nombres de entrada |

---

## 📁 8. Dónde está todo

```
.claude/skills/
├── estudio-mercado/
│   ├── SKILL.md                 ← skill de estudio (qué hace, flujo, ejemplos)
│   ├── schema_datos_mercado.md  ← esquema del datos_mercado.json
│   ├── fuentes_datos.md         ← capas de datos (pública/portales/pago)
│   └── estudio-mercado.skill.zip
└── importacion-vehiculos/
    ├── SKILL.md                 ← skill de búsqueda/valoración (5 flujos)
    ├── 01-arranque/ … 06-reglas/ ← briefing, planificador, anti-patrones…
    ├── memoria/                 ← modelos medidos, trampas, vendedores…
    └── importacion-vehiculos.skill.zip

Desktop/JJImportMotors/
└── datos_mercado.json           ← MAPA DE MERCADO (ruta pactada, fuente de verdad)

# Laravel (comandos)
php artisan market:import / export / alerts / freshness
```

---

## ✅ Checklist de arranque rápido

- [ ] Subí los 2 `.skill.zip` a Claude Desktop
- [ ] Generé `datos_mercado.json` con `estudio-mercado` (o tengo uno previo)
- [ ] `php artisan market:import --file="..."` → `/mercado` con datos
- [ ] Probé una búsqueda con `importacion-vehiculos` (Flujo A o B)
- [ ] Verifiqué que las respuestas traen enlaces a anuncios (A21) y fuentes
