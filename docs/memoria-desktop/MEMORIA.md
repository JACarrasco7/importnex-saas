> 🔗 **Fuente única (12-sep-2026).** Este fichero — y el resto de `docs/memoria-desktop/` — es ahora el único canónico. La copia en `Desktop\JJImportMotors\.claude\MEMORIA.md` es un espejo que se actualiza a mano; el proyecto de claude.ai ("Claude Desktop") no lo tenía cargado como Contexto pese a que las instrucciones del proyecto lo pedían — se ha añadido hoy. Edita SOLO aquí. Detalle: `docs/DOCS-INDEX.md`.
>
> ⚠️ **Revisión vencida.** Este fichero se auto-programó para revisión el 2026-09-12 (línea final, "Próxima revisión"). Hoy es esa fecha. Sigue fechado 12-ago-2026 y no menciona nada de lo aprendido desde entonces (Flujo D, marketing multicanal, anti-patrones A11-A14, IVA en importación intracomunitaria, costes reales 1.129€, etc. — ver `.claude/skills/importacion-vehiculos/memoria/` y `.claude/skills/estudio-mercado/CHANGELOG.md` en el repo para el detalle real y actualizado). Pendiente de que alguien lo revise y lo reescriba; no lo he reescrito yo mismo porque son conclusiones de negocio que te corresponden a ti, no una sincronización mecánica.

---

# Memoria de Claude — JJ Import Motors

> **Índice central de memoria persistente.** Claude DEBE leer este archivo al inicio de cada conversación para tener continuidad entre sesiones.
>
> ⚠️ **Optimización 12-ago-2026:** la memoria es **compacta (~11KB total)**. Los docs detallados viven en `_contexto/` y solo se leen BAJO DEMANDA. El contexto auto-cargado es solo `CLAUDE.md` (raíz, 3KB) + `.claude/MEMORIA.md` (3KB) ≈ **6KB** → sin "Prompt length error".

---

## 📂 Estructura de la memoria

| Archivo | KB | Cuándo consultar |
|---|---|---|
| **`MEMORIA.md`** (este) | 3.4 | SIEMPRE al inicio |
| `memoria/memoria-corto.md` | 1.4 | Sesión actual |
| `memoria/preferencias.md` | 1.0 | SIEMPRE (tono, formato) |
| `memoria/decisiones.md` | 0.9 | Duda sobre "por qué" |
| `memoria/errores-pasados.md` | 0.8 | SIEMPRE antes de actuar |
| `memoria/memoria-larga.md` | 0.9 | Cuando el patrón aplique |
| `memoria/proyectos-activos.md` | 2.8 | Al retomar trabajo |

**TOTAL:** ~11 KB (≈2.800 tokens) — ligero.

---

## 📄 Documentación bajo demanda (NO auto-cargar)

| Archivo | KB | Cuándo leer |
|---|---|---|
| `../_contexto/CONTEXTO_ACTIVO.md` | 8.8 | Detalle de marca, fuentes, umbrales |
| `../_contexto/CONTEXTO_FOTOS.md` | 5.8 | Referencia visual de coches |
| `../_contexto/Como_Funciona_JJ_Import_Motors.md` | 6.5 | Explicación sin tecnicismos |
| `../_contexto/Flujo_Operativo_JJ_Import_Motors.md` | 8.5 | Detalle técnico del flujo |

> ⚠️ **NO leer estos a menos que el trabajo lo requiera.** La raíz solo tiene `CLAUDE.md` (3KB) que resume todo.

---

## 🚦 Protocolo de lectura/escritura

### Al INICIO de cada conversación:
1. Lee este `MEMORIA.md`
2. Lee `preferencias.md` (tono, formatos, nivel de detalle)
3. Lee `errores-pasados.md` (NO repetir)
4. Lee `memoria-corto.md` (si existe → sesión previa no cerrada)
5. Lee `proyectos-activos.md` (en qué andas)

### Durante la conversación:
- Actualiza `memoria-corto.md` con el estado actual
- Si aprendes algo nuevo → apúntalo en la sección correspondiente

### Al FINALIZAR (o cuando el usuario lo pida):
- Mueve `memoria-corto.md` → archivos de largo plazo
- Actualiza `proyectos-activos.md`
- Documenta nuevos aprendizajes

---

## 🧠 Tres tipos de memoria

### 1. Memoria de CORTO plazo (sesión)
- **Qué:** Estado temporal de la conversación actual
- **Dónde:** `memoria/memoria-corto.md`
- **Caducidad:** Cuando termina la sesión, se mueve a largo plazo o se borra

### 2. Memoria de MEDIO plazo (2-3 semanas)
- **Qué:** Datos de mercado, precios, comparables que cambian con el tiempo
- **Dónde:** `informes/datos/<marca>/<modelo>/mercado_<fecha>.json` (ya existe)
- **Caducidad:** 2-3 semanas, luego se renueva con medición fresca

### 3. Memoria de LARGO plazo (permanente)
- **Qué:** Patrones, decisiones, errores, preferencias del usuario
- **Dónde:** Archivos en `memoria/*.md`
- **Caducidad:** NUNCA se borra automáticamente. Solo manualmente.

---

## 🔗 Memoria relacionada (skill)

El skill `importacion-vehiculos` tiene su propia memoria en `.claude/skills/importacion-vehiculos/memoria/`:
- `modelos-medidos.md` — histórico de modelos investigados
- `vendedores-confianza.md` — dealers que responden bien
- `trampas-encontradas.md` — nuevas trampas detectadas en portales
- `mejoras-aplicadas.md` — qué ha mejorado el skill con el uso

---

## 📊 Reglas de oro

1. **Una memoria útil > mil memorias inútiles.** Solo escribe lo que vas a necesitar recordar.
2. **Citado es mejor que opinado.** Siempre: "el usuario prefiere X porque dijo 'Y'"
3. **Borra memorias obsoletas.** Si una preferencia cambió, actualiza el archivo.
4. **No confundir memoria con documentación.** La documentación es para Claude nuevo. La memoria es para "recordar entre sesiones".

---

## �️ Prompt Improver (12-ago-2026)

**Comportamiento obligatorio** cuando el usuario da un prompt vago:

```
¿El prompt tiene <50 chars o <3 parámetros del briefing?
├── SÍ → MEJORAR antes de navegar
│   1. Detectar qué falta (tabla de parámetros)
│   2. Preguntar SOLO críticos (1-3, nunca más de 4)
│   3. SIEMPRE permitir "busca tú" / "lo que puedas"
│   4. Mostrar prompt mejorado listo + pedir OK
└── NO → ejecutar directamente
```

**Plantilla universal (mínimo viable):**
```
[MARCA] [MODELO] [VERSIÓN si tope de gama]
[año mín]-[año máx]
[km máx]
[presupuesto] puesto en [provincia]
[transmisión] (auto/manual/indistinto)
[finalidad] (personal / reventa / cliente)
```

**Triggers exactos del Prompt Improver (skill):**
- `<50 chars` → probablemente vago
- `50-200 chars` → revisar si tiene 3+ parámetros
- `>200 chars` → ya está completo

**Detalle técnico:** `importacion-vehiculos/guia_prompts.md` (dentro del skill).

---

## 🎯 Briefing de Encargo (12-ago-2026)

**Comportamiento obligatorio** en flujos A/B antes de navegar:

```
Parámetros clave (ordenar por importancia):
1. Modelo + versión (CRÍTICO en tope de gama)
2. Año mín / km máx
3. Presupuesto (puesto en provincia, no en DE)
4. Finalidad (personal / reventa / cliente)
5. Transmisión (auto/manual)
6. Color / extras / preferencias

Si el usuario da URL sin contexto → preguntar versión (sobre todo OPC, M, AMG, RS, GTI, GTD).
```

**Trampa típica:** coches de tope de gama mal etiquetados (ej. OPC con texto genérico). Por eso se confirma versión ANTES de filtrar.

**Detalle técnico:** `importacion-vehiculos/briefing_encargo.md` (dentro del skill).

---

## 🌍 Negocio y Origen (12-ago-2026)

**Modelo de negocio:**
- JJ Import Motors **NO compra** coches ni mantiene stock.
- Solo **oferta el servicio** de búsqueda, importación y gestión, cobrando honorarios fijos.
- El cliente es quien compra el coche.

**Ámbito:** importación desde **Alemania** + servicios de búsqueda y gestión **dentro de España**.

**Origen DE vs ES:**
- Si el encargo NO especifica origen → buscar el modelo en **AMBOS mercados** y comparar dónde sale mejor (coste total puesto en Huelva).
- DE = transporte 900 + ausfuhr 114 + ITV import 115 + IEDMT + honorarios.
- ES = traslado + gestoría + honorarios (SIN costes de importación).
- Si empatan (<300 €), preferir ES (menos riesgo).

---

## 🗓️ Última actualización

- **Fecha:** 2026-08-12
- **Cambios:** Prompt Improver + Briefing de Encargo + Negocio/Origen DE vs ES
- **Próxima revisión:** 2026-09-12 (1 mes)