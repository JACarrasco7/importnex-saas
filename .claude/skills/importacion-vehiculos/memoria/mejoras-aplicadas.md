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
**Validación:** `navegacion_real.md` y `playbook_filtrado.md` documentan el método.

### Mejora #3: Documentación real de portales (2026-08-12)
**Problema:** Las URLs y selectores cambiaban entre versiones.
**Solución:** `paginas_reales.md` con screenshots y selectores verificados el 2026-08-12.
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
**Validación:** `informe_tecnico.md` documenta la fórmula. Vista Blade renderiza el score con barras.

### Mejora #6: Dossier del cliente 15 secciones (2026-08-12)
**Problema:** Cliente recibía informe técnico con margen (información privada).
**Solución:** Dossier separado que filtra: sin margen, sin URLs comparables, sin mensaje vendedor.
**Beneficio:** Genera confianza. Honorarios como línea explícita.
**Validación:** `dossier_cliente.md` + `dossier.blade.php` con 9 reglas duras.

### Mejora #7: Cascada de informes + plantilla INFORME MODELO (2026-08-12)
**Problema:** Claude saltaba del resumen informal al "¿evalúo el candidato X?" sin entregar el informe MODELO con enlaces (caso real Astra OPC). El usuario no podía revisar los candidatos.
**Solución:** Regla dura: INFORME MODELO + top 5 con ENLACES + CP1 SIEMPRE antes de Fase 2. Plantilla completa en `SKILL.md`. Cascada: B→MODELO→CP1→elegir→A→UNIDAD→CP3→dossier→ZIP.
**Beneficio:** El usuario revisa candidatos con enlaces antes de decidir. Los informes salen en orden.
**Validación:** SKILL.md §CASCADA + §INFORME TIPO MODELO. operaciones.md Flujo B con CP1.

### Mejora #8: Modo automático en cascada (2026-08-12)
**Problema:** El usuario tenía que pedir cada informe y responder "¿continúo?", "¿descargo las fotos?" — el flujo no era automático.
**Solución:** Fase 1 automática → INFORME MODELO + top 5 → **el USUARIO elige candidato** (decisión de negocio) → resto automático: fotos + informe UNIDAD + dossier + ZIP. Si son varios candidatos → comparativa antes de los informes.
**Beneficio:** El usuario solo decide el candidato. Todo lo demás (descargas, informes, paquete) es automático.
**Validación:** SKILL.md §MODO AUTOMÁTICO + briefing_encargo.md Paso 3 + operaciones.md Flujo B.

### Mejora #9: Auditoría de consistencia + plantilla comparativa (2026-08-12)
**Problema:** Revisión proactiva detectó: encargo completo pedía confirmación (contradicción), año desactualizado en guia_prompts, typo en anti_patrones, y faltaba plantilla de comparativa para varios candidatos.
**Solución:** Plantilla COMPARATIVA nueva (tabla lado a lado + banderas 🟢🟡🔴 + recomendación ganador/alternativa). Corregidas 5 inconsistencias menores.
**Beneficio:** El skill es coherente y cubre el caso "investiga varios candidatos" que el usuario pidió.
**Validación:** SKILL.md §COMPARATIVA + correcciones en briefing/guia/anti_patrones.

---

## 🛠️ Mejoras técnicas

### Mejora #T1: Tests PHPUnit para PaqueteValoracion (2026-08-12)
**Problema:** No había tests para los nuevos endpoints.
**Solución:** `PaqueteValoracionTest` con 4 tests (404 sin dossier, dossier OK, 403 customer, 200 owner).
**Beneficio:** Detecta regresiones al tocar el controlador o las vistas.
**Validación:** 4/4 tests pasando. `Storage::fake('local')` para aislar entre tests.

### Mejora #T2: Bloques del skill alineados con la vista (2026-08-12)
**Problema:** 6 inconsistencias entre `informe_tecnico.md` y `informe-interno.blade.php`.
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
