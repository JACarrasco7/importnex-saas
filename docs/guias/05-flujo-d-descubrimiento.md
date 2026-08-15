# Guía 05 — Flujo D: descubrimiento de modelos

> Cuándo: un cliente (o tú) no tiene un modelo claro y quieres explorar qué modelos/motorizaciones encajan con sus necesidades o presupuesto.

---

## 1. Qué pedirle al skill

Dale al skill una descripción de lo que buscas, con tanto contexto como posible:

> "Quiero un coche familiar, 5 puertas, menos de 30k€, diésel, para trayectos largos. ¿Qué opciones hay?"

El skill activará Flujo D: un funnel de descubrimiento en 3 fases.

---

## 2. Cómo funciona el flujo

El Flujo D sigue este camino fijo ("El Camino"):

| Fase | Qué hace | Salida |
|------|----------|--------|
| **D1** | Sondeo de modelos (sin anuncios individuales) | Lista de modelos/motorizaciones con precio-desde |
| **D2** | Informe de MODELOS (por país × año × motor) | Encaje 🟢🟡🔴 + mejor mercado 🇩🇪/🇪🇸 |
| **D3** | Embudo: cada modelo elegido → **Flujo B** | Candidatos reales con enlaces (y luego Flujo A) |

**Importante:** NO puedes saltar fases. El skill debe completar D1 → D2 → D3 en orden. En D1 NO se abren fichas de anuncios; el informe de modelos NO lleva enlaces a unidades.

---

## 3. Modo de honorarios (M1 / M2 / M3)

Dependiendo de cómo concretes la petición, aplica una modalidad:

| Modalidad | Cuándo | Techo de búsqueda |
|-----------|--------|-------------------|
| **M1 · Incluidos** | El presupuesto paga coche + logística + honorarios | presupuesto − costes − honorarios |
| **M2 · Aparte** | Honorarios se cobran fuera del presupuesto | presupuesto − costes |
| **M3 · No se cobran** | Cliente especial / cortesía / familiar | presupuesto − costes (honorarios = 0 €) |

El skill te pedirá confirmar la modalidad si tu petición usa frases ambiguas ("todo incluido", "sin honorarios").

---

## 4. Salida típica por fase

### Fase D1 (Sweep de modelos)
- **NAVEGACIÓN REAL obligatoria** (Coches.net + mobile.de con filtros): la búsqueda web/snippets NO vale como sondeo (A15).
- Lista de **TODOS los modelos/motorizaciones que caben** con los filtros del encargo (A16) — no una selección de 3-7 ni "otros por explorar".
- Método eficiente (D1a + D1b): lectura **asc** (suelo, pág 1) + lectura **desc** (techo, pág 1) + facetas de marca con conteo + semilla `modelos-medidos.md`. El precio-desde por modelo se diferencia después (1 consulta solo si falta).
- Tipos de combustible disponibles (diésel/gasolina/híbrido)
- Quick check: ¿hay suficientes unidades en DE?

### Fase D2 (Informes por modelo)
Para cada modelo del D1:
- Costes de importación (transporte, IEDMT, tasas)
- Hueco de margen estimado
- Disponibilidad (nº de unidades en 7 fuentes)
- Recomendación rápida (Alta / Media / Baja)

### Fase D3 (Embudo a Flujo B)
- El usuario elige 2-3 modelos del informe (CP-D)
- Cada modelo pasa a **Flujo B**: 7 fuentes + candidatos reales con enlaces
- El usuario elige candidato → **Flujo A**: informe unidad + dossier + ZIP

> El deep dive (`informe_unidad_*` + ZIP) ocurre en Flujo A, no en D3.

---

## 5. Presupuesto típico

Flujo D es un embudo: cada fase gasta MÁS peticiones que la anterior.

| Fase | Peticiones |
|------|-----------:|
| **D1 · Sondeo de modelos** | 4-8 (sin abrir fichas) |
| **D2 · Informe de modelos** | 0 (sin navegar) |
| **D3 · Embudo a Flujo B/A** | B (15-50) o A (35-70) por modelo elegido |

Nunca al revés: no se navega a anuncios individuales antes de que el usuario elija modelos (CP-D).

---

## 6. Ejemplo práctico

**Tú:** "Necesito un coche para mi hermano, 5 puertas, gasolina, menos de 25k€, ciudad y autopista."

**Skill (D1):** "He barrido 12 modelos. Candidatos: Toyota Corolla, Golf VIII, Focus Mk4, Hyundai i30..."

**Skill (D2):** [Analiza cada uno] → "El Golf VIII tiene el mejor hueco (12%), pero i30 tiene más stock (45 unidades)."

**Skill (D3):** "Elige 2-3 modelos y los investigo a fondo (Flujo B: 7 fuentes + candidatos con enlaces)."

---

## 7. Diferencia con otros flujos

| Flujo | Tienes modelo | Tienes URL | Output |
|-------|---------------|------------|--------|
| **A** | - | ✅ | Veredicto de unidad |
| **B** | ✅ | - | Lista de unidades + ZIP |
| **C** | - | - | Scouting general de mercado |
| **D** | - | - | **Descubrimiento** (D1→D2→D3) |

---

## 8. Anti-patrones comunes (evita)

- **A12:** Dejarte solo con la primera página de resultados. En Flujo B (candidatos) el skill DEBE cubrir todo el rango (bandas/paginación). Matiz D1: en el sondeo de modelos NO se pagina — asc + desc + facetas bastan.
- **A13:** Cambiar filtros silenciosamente (en cualquier sentido: relajar O restringir más de lo aprobado). El skill debe declarar: "He cambiado a <filtro> porque <motivo>."
- **A15:** Sondeo D1 con búsqueda web en vez de navegación real. Cifras inconsistentes — pedir repetición con navegación real.
- **A16:** Selección manual de 3-4 modelos y "otros por explorar" sin sondear. El sondeo es por filtros y lista TODOS los que caben; la potencia es mínima (≥Xcv, versiones 125/130/150 valen igual).

Si el skill hace alguna de estas cosas → pídele que corrija.
