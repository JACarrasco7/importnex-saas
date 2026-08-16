# Mejoras aplicadas al skill — Histórico

> Cambios y mejoras que se han aplicado al skill con su justificación. Cuando algo funciona bien, se documenta aquí para no romperlo en futuras ediciones.

---

## 🎯 Mejoras de funcionalidad

### Mejora #1: Sistema de memoria persistente (2026-08-12)
**Problema:** Claude Desktop olvida todo entre conversaciones.
**Solución:** Carpeta `.claude/skills/importacion-vehiculos/memoria/` + `.claude/memoria/` en el proyecto
**Beneficio:** Continuidad entre sesiones. Trampas, vendedores y modelos se recuerdan.
**Validación:** Claude lee `MEMORIA.md` al iniciar → lee archivos específicos → escribe nuevos aprendizajes.

### Mejora #2: Navegación real estilo humano (2026-08-12)
**Problema:** Los extractores técnicos (fetch, JS inject) no funcionan en Claude Desktop.
**Solución:** Método basado en screenshot + clic + scroll (únicas herramientas disponibles)
**Beneficio:** Funciona en todos los portales. 5-7 capturas por búsqueda.
**Validación:** `../02-flujos/navegacion_real.md` y `../02-flujos/playbook_filtrado.md` documentan el método.

### Mejora #3: Documentación real de portales (2026-08-12)
**Problema:** Las URLs y selectores cambiaban entre versiones.
**Solución:** `../02-flujos/paginas_reales.md` con screenshots y selectores verificados el 2026-08-12.
**Beneficio:** Claude sabe qué esperar en cada portal (URLs reales, no inventadas).
**Validación:** 7 portales documentados con URL + cookie modal + filtros + sort options.

### Mejora #4: Briefing eliminado, dossier creado (2026-08-12)
**Problema:** Teníamos 2 PDFs casi idénticos (briefing + ficha).
**Solución:** Briefing deprecado (devuelve 410 Gone). Dossier profesional creado (15 sec).
**Beneficio:** Cliente solo recibe dossier + ficha + folleto (3 PDFs claros).
**Validación:** Briefing API devuelve 410 con mensaje. Dossier renderiza con todos los bloques.

### Mejora #5: Informe técnico 15 secciones + score 0-100 (2026-08-12)
**Problema:** Veredicto cualitativo era subjetivo.
**Solución:** Score 0-100 con 6 dimensiones (margen, vendibilidad, cobertura, calidad, riesgo, confianza).
**Beneficio:** Comparación objetiva entre coches. Score visible en cabecera del informe.
**Validación:** `../03-informes/informe_tecnico.md` documenta la fórmula. Vista Blade renderiza el score con barras.

### Mejora #6: Dossier del cliente 15 secciones (2026-08-12)
**Problema:** Cliente recibía informe técnico con margen (información privada).
**Solución:** Dossier separado que filtra: sin margen, sin URLs comparables, sin mensaje vendedor.
**Beneficio:** Genera confianza. Honorarios como línea explícita.
**Validación:** `../03-informes/dossier_cliente.md` + `dossier.blade.php` con 9 reglas duras.

### Mejora #7: Cascada de informes + plantilla INFORME MODELO (2026-08-12)
**Problema:** Claude saltaba del resumen informal al "¿evalúo el candidato X?" sin entregar el informe MODELO con enlaces (caso real Astra OPC). El usuario no podía revisar los candidatos.
**Solución:** Regla dura: INFORME MODELO + top 5 con ENLACES + CP1 SIEMPRE antes de Fase 2. Plantilla completa en `SKILL.md`. Cascada: B→MODELO→CP1→elegir→A→UNIDAD→CP3→dossier→ZIP.
**Beneficio:** El usuario revisa candidatos con enlaces antes de decidir. Los informes salen en orden.
**Validación:** SKILL.md §CASCADA + §INFORME TIPO MODELO. `../05-operaciones/operaciones.md` Flujo B con CP1.

### Mejora #8: Modo automático en cascada (2026-08-12)
**Problema:** El usuario tenía que pedir cada informe y responder "¿continúo?", "¿descargo las fotos?" — el flujo no era automático.
**Solución:** Fase 1 automática → INFORME MODELO + top 5 → **el USUARIO elige candidato** (decisión de negocio) → resto automático: fotos + informe UNIDAD + dossier + ZIP. Si son varios candidatos → comparativa antes de los informes.
**Beneficio:** El usuario solo decide el candidato. Todo lo demás (descargas, informes, paquete) es automático.
**Validación:** SKILL.md §MODO AUTOMÁTICO + `../01-arranque/briefing_encargo.md` Paso 3 + `../05-operaciones/operaciones.md` Flujo B.

### Mejora #9: Auditoría de consistencia + plantilla comparativa (2026-08-12)
**Problema:** Revisión proactiva detectó: encargo completo pedía confirmación (contradicción), año desactualizado en guia_prompts, typo en anti_patrones, y faltaba plantilla de comparativa para varios candidatos.
**Solución:** Plantilla COMPARATIVA nueva (tabla lado a lado + banderas 🟢🟡🔴 + recomendación ganador/alternativa). Corregidas 5 inconsistencias menores.
**Beneficio:** El skill es coherente y cubre el caso "investiga varios candidatos" que el usuario pidió.
**Validación:** SKILL.md §COMPARATIVA + correcciones en briefing/guia/anti_patrones.

### Mejora #10: Estructura de informes por fase + tarifa ES + anti-patrones A9-A11 (2026-08-15)
**Problema:** En el encargo Tiguan cliente solo se creó un `.md` de valoración al final; faltaron el informe de búsqueda (fase 1), el informe de unidad y el ZIP. Además se asumieron 1.500 € de importación cuando la unidad estaba en España, y el barrido de Coches.net fue parcial (6 páginas de muchas).
**Solución:** 
- §ESTRUCTURA DE INFORMES en SKILL.md: fase 1 = informe de búsqueda + candidatos; fase 2 = informe de unidad solo del elegido; fase 3 = ZIP. Archivos separados.
- `../04-negocio/costes.md` §Origen ES: tarifa de gestión reducida (~500 €) para unidades en España, no los 1.500 €.
- `../06-reglas/anti_patrones.md`: A9 (no afirmar sin comprobar), A10 (financiado vs contado), A11 (paginación completa Coches.net).
**Beneficio:** El usuario recibe candidatos + informe de búsqueda al terminar el barrido, y el resto solo cuando avanza con uno. Los precios y la cobertura son fiables.
**Validación:** SKILL.md §ESTRUCTURA DE INFORMES + checklist · `../04-negocio/costes.md` §Origen ES · `../06-reglas/anti_patrones.md` (8→11) · CHANGELOG 2.5.0.

### Mejora #11: Estructura de carpetas por marca/modelo en el Desktop (2026-08-15)
**Problema:** El informe del Tiguan se guardó en `AppData\Roaming\Claude\...\outputs\` (carpeta de la sesión) y el usuario no lo encontraba.
**Solución:** Ruta de guardado obligatoria `C:\Users\jacar\Desktop\JJImportMotors\informes\<marca>\<modelo>\` con subcarpeta README.md. Informes .md, JSON, fotos y ZIP por marca/modelo.
**Beneficio:** Todo centralizado en el Desktop, organizado por marca/modelo, fácil de encontrar y de mover a Laravel.
**Validación:** SKILL.md §DÓNDE SE GUARDA TODO · `../05-operaciones/operaciones.md` §Estructura de carpetas · README.md en informes/ · CHANGELOG 2.6.0.

### Mejora #12: Encargos abiertos — modalidades honorarios, plan de barrido, bandas de precio (2026-08-15)
**Problema:** En el encargo de María (9.000 €, "revisa qué mercado es mejor"): (1) "quita el coste del servicio" se interpretó como "descuenta honorarios" cuando era "no se cobran" → toda la primera tabla mal calculada; (2) se ordenó por precio y se leyó SOLO la página 1 → 8 coches de 3-4k presentados de 526 resultados, perdiendo DS4/308/Astra que también entraban; (3) año ensanchado 2016→2012 sin declarar; (4) el usuario tuvo que preguntar "¿qué vas a hacer?" porque nadie le propuso el plan.
**Solución:**
- Modalidades de honorarios M1/M2/M3 (incluidos/aparte/no se cobran) — pregunta crítica del briefing + reformulación de frases ambiguas en 1 línea.
- PLAN DE BARRIDO previo para encargos abiertos: mercados, filtros, bandas, cobertura y entregable — se muestra antes de navegar.
- A12 (página 1 ≠ listado) y A13 (filtros alterados se declaran antes). Técnica de bandas de precio en playbook.
**Beneficio:** En encargos con libertad, el usuario aprueba UN plan corto en vez de corregir a mitad; los techos salen bien a la primera; el listado cubre todo el rango de presupuesto.
**Validación:** `../01-arranque/briefing_encargo.md` §Modalidades · SKILL.md §PLAN DE BARRIDO + checklist · `../06-reglas/anti_patrones.md` A12-A13 · `../02-flujos/playbook_filtrado.md` §Bandas · `../04-negocio/costes.md` §Techo · CHANGELOG 2.7.0.

### Mejora #13: Flujo D · DESCUBRIMIENTO — embudo para clientes sin modelo (2026-08-15)
**Problema:** El cliente trae presupuesto y requisitos pero no modelo. La skill solo tenía B (modelo concreto) y C (scouting de negocio) — navegar a anuncios reales sin modelo elegido sesga y quema peticiones. El usuario tampoco sabe qué especificar.
**Solución:** Flujo D con embudo de 3 pasos: (D1) sondeo barato ES+DE de modelos/motorizaciones que caben, sin anuncios; (D2) INFORME DE MODELOS por país × año × motorización con encaje 🟢🟡🔴 y mejor mercado; (D3) el usuario elige 2-3 modelos → Flujo B cada uno → Flujo A. CP-D entre D2 y B.
**Beneficio:** La búsqueda se particiona: primero el menú de modelos que caben (8 peticiones), y las peticiones caras solo en los modelos que el usuario elige. Respuesta directa a cómo manejar encargos ambiguos.
**Validación:** SKILL.md §FLUJO D + §INFORME TIPO MODELOS (plantilla) + detección de 4 flujos + triggers · `../01-arranque/briefing_encargo.md` parám 1 · CHANGELOG 2.8.0.

### Mejora #14: Camino fijo, micro-plans, cuaderno de sesión y auditoría de fase (2026-08-15)
**Problema:** En sesiones largas Claude se desviaba del flujo sin volver, repetía errores que el usuario ya había corregido, y solo aprendía al cierre. Los informes llegaban mal porque los pasos se mezclaban.
**Solución:**
- §EL CAMINO: mapa numerado por flujo + waypoint en cada mensaje + misiones laterales con retorno (A14).
- §MICRO-PLAN antes de cada búsqueda (no solo la inicial) con OK del usuario.
- §CUADERNO DE SESIÓN: aprendizajes y correcciones en vivo (`informes\_sesion\`), releído antes de cada micro-plan, volcado a memoria al cierre.
- §AUDITORÍA DE FASE: checklist de 4 líneas al completar cada paso.
**Beneficio:** Cero ambigüedad sobre en qué fase se está y qué falta; el entendimiento Claude-usuario crece dentro de la MISMA sesión; los entregables no se saltan.
**Validación:** SKILL.md §EL CAMINO/§MICRO-PLAN/§CUADERNO/§AUDITORÍA · `../06-reglas/anti_patrones.md` A14 · `../01-arranque/briefing_encargo.md` §Arranque · CHANGELOG 2.9.0.

---

## 🛠️ Mejoras técnicas

### Mejora #T1: Tests PHPUnit para PaqueteValoracion (2026-08-12)
**Problema:** No había tests para los nuevos endpoints.
**Solución:** `PaqueteValoracionTest` con 4 tests (404 sin dossier, dossier OK, 403 customer, 200 owner).
**Beneficio:** Detecta regresiones al tocar el controlador o las vistas.
**Validación:** 4/4 tests pasando. `Storage::fake('local')` para aislar entre tests.

### Mejora #T2: Bloques del skill alineados con la vista (2026-08-12)
**Problema:** 6 inconsistencias entre `../03-informes/informe_tecnico.md` y `informe-interno.blade.php`.
**Solución:** Alineados todos los nombres de bloques (`MARGEN`, `VENTA`, `COMPARABLE`, etc.).
**Beneficio:** Skill genera esqueletos que Laravel renderiza correctamente.
**Validación:** Test renderiza PDF con bloques del skill. Sin warnings en `view:cache`.

### Mejora #T3: ZIP con separadores / (no \) (2026-08-12)
**Problema:** `Compress-Archive` de PowerShell 5.1 usa `\` → Claude rechaza el ZIP.
**Solución:** Usar `tar -a -cf archivo.zip carpeta/` que sí usa `/` estándar ZIP.
**Beneficio:** ZIP válido para subir a Claude Desktop.
**Validación:** `$zip.Entries | Where-Object {$_.FullName -match '\\'} | Count` debe ser 0.

### Mejora #T4: Pint + tests + view:cache en CI local (2026-08-12)
**Problema:** Estilo PHP inconsistente.
**Solución:** `vendor/bin/pint --dirty --format agent` + `php artisan test --compact` + `view:cache` antes de cerrar cambios PHP.
**Beneficio:** Código limpio y funcional.
**Validación:** Workflow aplicado en todos los cambios recientes.

---

## 🎨 Mejoras de UX / Marca

### Mejora #U1: Paleta estoril consistente (2026-08-12)
**Problema:** PDFs iniciales usaban rojo/naranja (no es de marca).
**Solución:** Solo estoril (#1A306D), platinum, asphalt. Semáforo solo 🟢🟡🔴.
**Beneficio:** Marca consistente en todos los PDFs.
**Validación:** `BRAND.md` define paleta. Vistas Blade usan `from-estoril-700 to-estoril-800`.

### Mejora #U2: Dossier con transparencia radical (2026-08-12)
**Problema:** Clientes no saben qué pagan realmente.
**Solución:** Dossier muestra desglose completo (incluyendo honorarios como línea explícita).
**Beneficio:** Genera confianza brutal. Diferencia vs concesionario.
**Validación:** §10 del dossier incluye honorarios como línea "JJ Import Motors | 4.400 €".

---

## 📋 Plantilla para nueva mejora

```markdown
### Mejora #[categoría][N]: [título]
**Fecha:** YYYY-MM-DD
**Problema:** [qué problema resolvía]
**Solución:** [qué hicimos]
**Beneficio:** [qué ganamos]
**Validación:** [cómo sabemos que funciona]
```

---

## 🗓️ Última actualización

- **2026-08-12:** 13 mejoras registradas (6 funcionalidad, 4 técnicas, 2 UX/marca)