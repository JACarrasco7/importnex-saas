# Memoria del skill `importacion-vehiculos`

> **Índice central de memoria del skill.** Claude debe leer este archivo al inicio de cada conversación sobre el skill. Se complementa con `.claude/memoria/` del proyecto JJImportMotors (preferencias, errores, decisiones).

---

## 📂 Estructura de la memoria del skill

| Archivo | Qué guarda | Cuándo consultar |
|---|---|---|
| **`MEMORIA.md`** (este) | Índice | SIEMPRE al inicio |
| `memoria/modelos-medidos.md` | Histórico de modelos investigados (cache) | Al retomar un modelo ya medido (PASO 0) |
| `memoria/encargos.md` | Registro central de encargos (cliente→flujo→entregables→resultado) | Al recibir cualquier encargo (PASO 0) · Auditoría de cierre |
| `memoria/filtros-portales.md` | Filtros/URLs verificados por portal (qué aplica por URL vs clic) | Al construir el plan de fase / asesor de filtros |
| `memoria/vendedores-confianza.md` | Dealers que responden bien | Antes de negociar |
| `memoria/trampas-encontradas.md` | Trampas detectadas en portales | Antes de cada navegación |
| `memoria/mejoras-aplicadas.md` | Cambios y mejoras del skill | Al planificar cambios |
| `memoria/retrospectiva.md` | Retrospectivas de sesión + plantilla de CIERRE | Al cerrar conversación / auditoría de cierre |

---

## 🚦 Protocolo de uso

### Al INICIO de cada conversación:
1. Lee este `MEMORIA.md`
2. Lee `trampas-encontradas.md` (NO repetir errores)
3. Lee `modelos-medidos.md` + `encargos.md` (saber qué ya está hecho → **PASO 0 cache**, ver `../SKILL.md` §ARRANQUE)
4. Lee `mejoras-aplicadas.md` (respetar lo que funciona)
5. **3bis:** si el encargo es de mercado → cruzar con `indice.json` (Desktop, regla <3 semanas) y `datos_mercado.json`

### Durante la conversación:
- Si descubres una trampa nueva → añádela a `trampas-encontradas.md`
- Si mides un modelo nuevo → añádelo a `modelos-medidos.md` (12 campos, con `refrescar_antes_de`)
- Si cierras/abortas un encargo → regístralo en `encargos.md`
- Si verificas un filtro/URL nuevo en un portal → añádelo a `filtros-portales.md` (fecha + parámetro)
- Si un vendedor responde bien/mal → anótalo en `vendedores-confianza.md`
- Si mejoras el skill → documéntalo en `mejoras-aplicadas.md`

### Al FINALIZAR:
- **Auditoría de cierre** (ver `../SKILL.md` §AUDITORÍA DE CIERRE): volcar las 5 dimensiones a `retrospectiva.md` (plantilla CIERRE)
- Verifica que los 8 archivos de memoria estén actualizados
- Si hay aprendizajes grandes → añádelos también a `.claude/memoria/decisiones.md` del proyecto

---

## 🧠 Tres tipos de memoria (recordatorio)

### 1. Corto plazo (sesión)
- **Qué:** Estado temporal de la conversación
- **Dónde:** `.claude/memoria/memoria-corto.md` del proyecto

### 2. Medio plazo (2-3 semanas)
- **Qué:** Datos de mercado, precios, comparables
- **Dónde:** `informes/datos/<marca>/<modelo>/mercado_<fecha>.json`

### 3. Largo plazo (permanente)
- **Qué:** Patrones, trampas, vendedores, mejoras
- **Dónde:** `memoria/*.md` (en este skill) + `.claude/memoria/*.md` (proyecto)

---

## 📊 Métricas del skill (acumuladas)

| Métrica | Valor |
|---|---|
| Versión actual | 3.1.0 |
| Fecha de release | 2026-08-16 |

---

## 📋 CASCADA DE INFORMES (12-ago-2026) — regla clave de comportamiento

Los informes NO salen todos a la vez. Es **en cascada** con checkpoint entre fases:

```
ENCARGO (Flujo B) → 📋 INFORME MODELO + top 5 con ENLACES → CP1 (¿Fase 2 o eliges?)
   │
   └─ ELIGES UNO → FLUJO A → 📋 INFORME UNIDAD (15 sec) → CP3 (veredicto)
        │
        └─ 🟢/🔵 → 📄 DOSSIER CLIENTE + 📦 ZIP Laravel
```

- **NUNCA** saltar del resumen informal al "¿evalúo el candidato X?" sin entregar INFORME MODELO + enlaces + CP1.
- El usuario decide qué candidato profundizar (Flujo A), no Claude.
- Folleto publicidad / ficha → los genera **Laravel** (no Claude), cuando el coche está en inventario.
- Plantilla INFORME MODELO en `SKILL.md` §INFORME TIPO MODELO.

## 🌍 ORIGEN DE vs ES + negocio (12-ago-2026)

- **Negocio:** NO compramos stock. Solo ofertamos el servicio (honorarios). El cliente compra el coche.
- **Ámbito:** importación desde Alemania + servicios de búsqueda y gestión dentro de España.
- **Origen:** si no se especifica → buscar en AMBOS mercados y comparar dónde sale mejor. DE = con costes de importación; ES = sin ellos. Ver `../04-negocio/costes.md` §Origen.

## 📊 Métricas del skill (detalle)

| Métrica | Valor |
|---|---|
| Fuentes integradas | 7 portales |
| Cobertura | 100% (DE + ES) |
| Trampas documentadas | 8 (4 confirmadas, 4 potenciales) |
| Modelos medidos | 4 (Astra OPC ×2, Tiguan, Golf GTI) |
| Coches evaluados | 7 |
| Encargos registrados | 9 (8 cerrados + 1 abortado) |
| Tests PHP pasando | 4/4 |

---

## 🔗 Memoria relacionada

**Memoria del proyecto JJImportMotors** (en `c:\Users\jacar\Desktop\JJImportMotors\.claude\`):
- `MEMORIA.md` — índice del proyecto
- `memoria/preferencias.md` — cómo trabaja el usuario
- `memoria/decisiones.md` — por qué se hicieron las cosas
- `memoria/errores-pasados.md` — errores NO repetir
- `memoria/memoria-larga.md` — patrones generales

---

## 🗓️ Última actualización

- **2026-08-16:** v3.1.0 — Reorganización física completa (carpetas 01-06), `encargos.md` + `filtros-portales.md` creados, `modelos-medidos.md` ampliado a 12 campos, este índice con 8 archivos + PASO 0 cache.
