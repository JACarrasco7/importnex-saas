# Retrospectiva de sesión — plantilla

> **Usar al cerrar cada conversación** (ver `SKILL.md` §Aprendizaje continuo).
> Objetivo: de cada conversación sale al menos una mejora. Si el usuario detecta un fallo, ese fallo se documenta (trampa/anti-patrón/regla) — no se queda en un "lo siento".

---

## Plantilla

```markdown
SESIÓN <fecha> — <modelo / encargo / tarea>

✅ LO QUE FUNCIONÓ
  · <qué fue bien>

❌ ERRORES COMETIDOS
  · <error> → corregido en <archivo del skill>

🧠 APRENDIZAJE NUEVO
  · <trampa detectada>
  · <preferencia del usuario>
  · <dato de mercado>
  · <motor/modelo con fiabilidad conocida>

📝 AJUSTES APLICADOS AL SKILL
  · <qué se cambió y dónde>
```

---

## Plantilla de CIERRE — al elegir candidato único (16-ago-2026)

> **Cuándo:** el usuario elige UN candidato y termina el Flujo A (ZIP), o el encargo se aborta. Ver `SKILL.md` §Auditoría de cierre. Se rellena SIN navegar, mirando hacia atrás.

```markdown
🏁 CIERRE <fecha> — <modelo elegido / abortado>

📊 EFICIENCIA
  · Peticiones reales vs presupuesto del plan: <N real> vs <N plan>
  · Desbordado en: <fuente/paso + causa>

🔻 EMBUDO
  · Niveles recorridos: <D → B → A / B → A / directo A>
  · Candidatos por nivel: <N modelos → N candidatos → 1 elegido>
  · 80% descartado en nivel: <D1 / Fase 1 / Fase 2>
  · Fase 2 gastada en candidatos que caían antes: <sí/no + cuál>

✏️ CORRECCIONES DEL USUARIO
  · <N> correcciones · causa raíz: <briefing incompleto / plan mal calibrado / fuente>
  · <corrección → convertida en: trampa/anti-patrón/regla (archivo)>

🚦 CHECKPOINTS
  · Respetados: <CP-D · CP1 · CP2 · CP3>
  · Saltados: <cuál + coste estimado>

🏆 RESULTADO
  · Candidato final: <modelo · precio · origen · score>
  · Dato de mercado aprendido: <dato>
  · ¿El embudo ahorró tokens vs búsqueda directa? <sí/no · estimación>
```

---

### 2026-08-12 — Prueba real de navegación desde VS Code (Playwright)

**🏗️ Decisión de arquitectura (resultado de la prueba):**
- **Investigación → Claude (Desktop)** — navegación real con filtros (VS Code falla en filtros).
- **Laravel (importnexcore) → repositorio único y fuente de verdad** de informes/PDF/imágenes/JSON.
- **Subir** el paquete ZIP a Laravel (`/api/import-valuation`); **ver/mostrar/gestionar/actualizar** solo desde Laravel.
- Claude **NO consulta** lo subido. Cada nuevo encargo se lanza como uno nuevo desde Claude.
- Documentado en `operaciones.md` §División de trabajo + Desktop `CLAUDE.md` + Laravel `copilot-instructions.md`.

**✅ Funciona:**
- Abrir portales (mobile.de, Coches.net) y leer el DOM visible (precios, años, km, kW).
- Clic en banners de cookies con `force:true` (mobile.de `button.mde-consent-accept-btn`).
- Coches.net: los filtros por URL (`minyear`, `maxkm`, `minpower`) SÍ aplican (contador 259k→159k).

**❌ Fricción alta (lección):**
- **mobile.de:** los filtros por URL (`ps`, `kmmax`, `ezmin`) NO aplican con `q=`. El listado sale sin filtrar (vi Astra de 2010, 2025 y siniestrados en un mismo listado). El clic en `label Benzin` NO activa el filtro; los acordeones (`Kraftstoffart Beliebig ändern`) no se expanden con selectores estándar; el overlay de consentimiento persiste (cerrar con `button.mde-consent-accept-btn` + `force` + quitar `[class*=consent]` del DOM).
- **Coches.net:** el filtro de MARCA/MODELO no aplica por URL (solo año/km/potencia vía `minyear`/`maxkm`/`minpower`). Los filtros laterales no son `<button>` estándar; el typeahead del buscador no muestra modelos con Playwright.
- Ambos portales: banners, re-renderizados y posible detección anti-bot → más tokens que en Claude Desktop.

**🧠 Conclusión:**
- VS Code SIRVE para leer datos, generar informes/JSON/ZIP y editar la skill.
- Para **búsquedas con filtros**, **Claude Desktop es mucho más robusto** (clics posicionales sobre screenshots funcionan con cualquier DOM). Recomendar Desktop para la investigación, VS Code para el resto.

---

## Registro

### 2026-08-12 — Sesión de afinado del skill (Prompt Improver, cascada, origen DE vs ES, cobertura)

**✅ Funcionó:**
- Modo automático en cascada: Fase 1 → informe MODELO → usuario elige → resto automático.
- Origen DE vs ES: comparar ambos mercados y elegir dónde sale mejor.
- Prompt Improver para prompts vagos.

**❌ Errores (todos corregidos):**
- Saltó al candidato sin informe MODELO + enlaces → corregido (cascada + CP1).
- Pidió confirmación constante ("¿qué candidato?", "¿descargo fotos?") → corregido (modo automático).
- Usó AutoScout24 como precio → corregido (A8: solo contar).
- No cubrió las 7 fuentes → corregido (regla dura #5 + A7).
- Descontó honorarios del techo de búsqueda → corregido (techo = presupuesto − solo logística).
- Estimó IEDMT de oído (700-1.200 € vs real 280 €) → corregido (calcular con coeficiente).
- Filtros de mobile.de fallaban (kW, año, puertas) → corregido (filtrado fino).

**🧠 Aprendizaje:**
- AutoScout24 agrega feeds sin cribar → nunca precio.
- En el encargo de 9.000 € el usuario pidió NO descontar honorarios del techo → decisión de ESE encargo, NO regla general. Confirmar por encargo.
- "Presupuesto sin límite pero buen precio" = prioridad precio sobre techo.
- IEDMT de coches >12 años cae a coeficiente 10% → IEDMT bajo.
- Preferencia: siempre intentar TODAS las fuentes, transparencia con las bloqueadas.

**📝 Ajustes:**
- `SKILL.md`: cascada, modo automático, origen, cobertura #5, aprendizaje continuo.
- `costes.md`: §Origen, techo de búsqueda, regla IEDMT.
- `anti_patrones.md`: A7, A8 (total 8).
- `playbook_filtrado.md`: filtrado fino (CV/kW, 5 puertas, patrocinados, engañosos).
- `riesgos.md`: motores gasolina 2016+.
- `briefing_encargo.md`: parámetro origen, presupuesto honorarios aparte.
