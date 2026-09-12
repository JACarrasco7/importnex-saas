> 🔗 **Copia de distribución (12-sep-2026).** El máster de este fichero vive en el repo: `docs/claude-desktop/GUIA_INICIO_RAPIDO.md` en `C:\laragon\www\importnexcore\`. Edita ahí, no aquí — esta copia (carpeta Desktop) se actualiza a mano tras cada cambio. Detalle completo del porqué: `docs/DOCS-INDEX.md`.

---

# 🚀 GUÍA DE INICIO RÁPIDO — JJ Import Motors + Claude

> **Todo está listo para usar.** Sigue estos 3 pasos y empieza a trabajar.
>
> 📌 **Índice completo:** `README.md` (raíz) — estructura del workspace + qué hace cada cosa.

---

## ✅ Paso 1 — Abrir el proyecto en Claude

1. Abre **Claude** (web o Desktop)
2. Abre el proyecto **JJ Import Motors**
3. Verifica que el panel lateral muestre solo `CLAUDE.md` (no los 8 archivos viejos)
   - ⚠️ Si aún aparecen los `Plan_*` viejos → son caché de una versión anterior. Borra la caché/refresca.

Claude cargará automáticamente:
- `CLAUDE.md` (3KB — contexto esencial)
- `.claude/MEMORIA.md` (3KB — índice de memoria)

> **Total auto-cargado: ~6KB** → sin "Prompt length error".

---

## ✅ Paso 2 — Probar una conversación (con prompts optimizados)

> **Tip:** los prompts detallados con briefing completo ahorran tokens (Claude pregunta lo que falta solo 1 vez, no rebusca).

### 🔵 Flujo A — Evaluar un coche (URL pegada)

**Corto:**
```
evalúa este: https://www.mobile.de/fahrzeuge/details.html?id=455589559
```

**Con cliente:**
```
evalúa este GTI para un cliente, presupuesto 20k:
https://www.coches.net/segunda-mano/coches/volkswagen-golf-gti-idABC123
```

### 🟢 Flujo B — Buscar un modelo (RECOMENDADO briefing completo)

```
tengo un encargo personal de un Opel Astra J OPC genuino (2.0 Turbo 280 CV)
- matriculación: a partir de 01/2012
- km máximo: 130.000
- presupuesto: abierto pero busco buen precio
- gasolina, manual preferido
- vendedor particular preferido

este es un chollo que ya localicé (no quiero perderlo):
https://m.mobile.de/fahrzeuge/details.html?id=38347146649056
```

Claude con este prompt:
- ✅ Pregunta solo lo que falte (no todo)
- ✅ Detecta OPC = tope de gama → activa doble pasada por kW
- ✅ NO rebusca el de 8.999 € (ya le dijiste la URL)
- ✅ Ahorra ~30-40% de tokens vs. descubrimiento gradual

### 🟡 Flujo C — Escanear mercado

```
qué oportunidades hay ahora mismo para importar a España:
- presupuesto: 25-40k € puesto en Huelva
- tipo: deportivos/premium (GTI, R, M, AMG, RS, OPC)
- año 2018+, km máx 80.000
- gasolina o híbrido enchufable
```

### 🟣 Flujo D — Descubrimiento (cliente sin modelo claro)

```
un cliente quiere un coche familiar 5 puertas, gasolina, menos de 25k€
- no sabe qué modelo quiere
- uso: ciudad y autopista, con niños
```

Claude hará el embudo D1→D2→D3 (sin saltar fases):
- **D1** sondeo de modelos que caben en el presupuesto (sin abrir anuncios)
- **D2** informe de MODELOS por país × año × motorización (encaje 🟢🟡🔴 + mejor mercado)
- **D3** tú eliges 2-3 modelos → cada uno pasa a Flujo B → candidato → Flujo A

> Además, confirmará la **modalidad de honorarios M1/M2/M3** (incluidos / aparte / no se cobran) y la **tarifa ES reducida (~500 €)** si la unidad está en España.

---

## 🎯 Plantilla universal (para copiar y rellenar)

```
[QUÉ BUSCO]
  Modelo: <marca> <modelo> [versión]
  Año mín: <YYYY> | Km máx: <K>
  Presupuesto: <€> (puesto en Huelva o absoluto)
  Combustible: <gasolina/diesel/híbrido>
  Cambio: <manual/automático/indiferente>
  Origen: <Alemania / España / el que salga mejor>

[CONTEXTO]
  Finalidad: <encargo personal / revender / flota>
  Plazo: <flexible / antes de fecha>
  Equipamiento imprescindible: <techo, AWD, etc.>
  Color / puertas: <opcional>

[OPCIONAL]
  Ya he visto: <URL de candidato que te interesa>
  NO quiero: <marca, motor, color>
```

---

## ⚡ Tips cortos

| Quiero... | Escribe... |
|---|---|
| Evaluar 1 coche | `evalúa este: <URL>` |
| Buscar modelo con briefing | `encargo personal X, año Y, km Z` |
| Ver mercado completo | `qué merece la pena, presupuesto X-Yk, <resto>` |
| Refinar una búsqueda | `filtra más por <filtro>` o `amplía a <rango>` |
| Precio de un modelo | `cuánto cuesta traer un X desde Alemania?` |
| Solo ES | `en España, donde encontrar X?` |
| Ver histórico | `qué tengo medido de X?` (lee modelos-medidos.md) |
| Comparar DE vs ES | `¿dónde sale mejor este modelo, en España o Alemania?` |

---

## 🛠️ Prompt Improver — qué pasa si escribes un prompt vago

**Si tu prompt es corto (<50 chars) o vago, Claude NO navegará a ciegas. Te lo refina primero.**

**Ejemplo tuyo:**
```
busca GTI
```

**Claude responde:**
```
Casi lo tengo. Solo falta:
  • Versión (¿GTI / GTI Performance / GTI Clubsport?)
  • Presupuesto máximo
  • Finalidad (¿personal / reventa?)

Prompt mejorado que propongo:
  "VW Golf GTI 7.5 Performance 2020+, presupuesto 35k puesto en Huelva,
   km máx 80.000, automático DSG, para reventa"

¿OK o lo ajustas? (Si dices "busca tú", ejecuto con defaults razonables.)
```

**Cuándo NO te mejorará el prompt** (ya está bien):
- Tu prompt tiene 3+ parámetros del briefing
- Ya das URL en Flujo A
- Ya das briefing completo

**Reglas del Prompt Improver** (Claude las sigue siempre):
1. Detecta si el prompt es vago (<50 chars)
2. Extrae lo que ya diste (✅) y lo que falta (❓)
3. Pregunta SOLO los críticos (1-3 preguntas, máx 4)
4. SIEMPRE permite "busca tú" / "lo que puedas"
5. Muestra el prompt mejorado listo + pide confirmación
6. Detalle técnico completo en `.claude/skills/importacion-vehiculos/guia_prompts.md`

---

## 📊 Qué informes te dará Claude (y en qué orden)

> **Importante:** Claude NO te da todos los informes de golpe. Es **en cascada** — cada fase entrega su informe y espera tu OK.

```
ENCARGO (busca modelo) → 📋 INFORME MODELO + top 5 con enlaces → ¿Fase 2 o eliges?
   │
   └─ ELIGES UN CANDIDATO → 📋 INFORME UNIDAD (ficha completa + score 0-100)
        │
        └─ Si veredicto COMPRAR → 📄 DOSSIER CLIENTE + 📦 ZIP Laravel
```

| Informe | ¿Cuándo? | ¿Lo recibes en el encargo? |
|---|---|---|
| 📋 **Informe BÚSQUEDA / MODELO** | Fin de la Fase 1 del encargo | ✅ **SÍ** — top 5 con enlaces para que elijas |
| 📋 **Informe UNIDAD** | Cuando eliges un candidato | ❌ Después |
| 📄 **Dossier cliente** | Tras veredicto "Comprar" | ❌ Al final |
| 📦 **ZIP Laravel** | Tras dossier aprobado | ❌ Al final |
| 🎨 **Folleto / ficha publicitaria** | ⚠️ Los genera **Laravel** (la app), no Claude, cuando el coche ya está en inventario | No |

**⚡ Modo automático:** si tu encargo llega **completo** (modelo+versión, año, km, presupuesto, combustible, cambio, finalidad), Claude hace la Fase 1 solo y te entrega el **informe MODELO + top 5 con enlaces**. Ahí **tú eliges el candidato** ("investiga el de 8.999 €"). Desde ese momento todo es automático: fotos, informe UNIDAD, dossier y ZIP. Si le dices que investigue **varios**, primero te hace una **comparativa** y luego los informes. Solo te parará si el veredicto es dudoso o hay bandera de seguridad (ej. VIN oculto).

**Regla clave:** si Claude te da candidatos pero **no te muestra el informe con enlaces** para que elijas, pídele:
> "dame el informe MODELO completo con el top 5 y enlaces para que yo elija"

---

## ✅ Paso 3 — Verificar que la memoria funciona

Después de una conversación:
1. Pregunta a Claude: "¿Qué recuerdas de la última vez?"
2. Claude debería leer `.claude/MEMORIA.md` + memoria del skill
3. Si lo recuerda → **todo funciona**

---

## 📁 Estructura del proyecto (referencia)

```
JJImportMotors/
├── CLAUDE.md                  ← Contexto auto-cargado (no tocar)
├── importacion-vehiculos.skill ← Skill actualizado (subir a Claude)
├── .claude/                   ← Memoria del proyecto
│   ├── MEMORIA.md             ← Índice de memoria
│   └── memoria/               ← 7 archivos compactos
├── _contexto/                 ← Docs detallados (bajo demanda)
│   ├── CONTEXTO_ACTIVO.md     ← Detalle completo del negocio
│   ├── CONTEXTO_FOTOS.md      ← Galería de coches reales
│   ├── Como_Funciona...       ← Guía sin tecnicismos
│   └── Flujo_Operativo...     ← Detalle técnico
├── _archive/                  ← Lecciones históricas (no tocar)
├── laravel/                   ← Scripts Python + informes JSON
└── 07 Vehiculos.../           ← Legacy Drive (respaldo)
```

---

## 📊 Si algo falla

| Problema | Solución |
|---|---|
| "Prompt length error" | Ya resuelto (contexto 6KB). Si vuelve → borra caché de archivos viejos |
| Claude no busca en internet | Asegurar que la extensión de navegador está activa en Claude Desktop |
| Claude genera PDF mal | No genera PDFs (los hace Laravel). Solo crea esqueletos .txt |
| Falta el skill | Subir `importacion-vehiculos.skill` a Claude (Settings → Skills) |

---

## 🔄 Recordar cada vez

- **El skill genera textos/esqueletos** → Laravel los convierte en PDFs
- **Nunca generar PDF directamente** en Claude
- **7 fuentes mínimas** para veredicto
- **Citar fuente** de cada dato
- **Guardar aprendizajes** en la memoria (Claude lo hace solo con triggers)

---

*Última actualización: 2026-08-12*
