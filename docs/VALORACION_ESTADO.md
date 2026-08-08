# Valuación Enriquecida — Estado Actual

**Fecha:** 2026-08-08
**Estatus:** Parcialmente implementado (Fase A completa, Fase C parcial)

---

## Lo que YA existe

### Fase A: Esquema de BD ✅ COMPLETO

Todos los campos de valoración enriquecida están en la tabla `cars`:

```sql
-- Research data (9 aspects with source and value)
research              json
-- Pros with weight
pros                  json
-- Cons with weight
cons                  json
-- Structured verdict
verdict               varchar(255)
verdict_confidence    varchar(255)
verdict_reasoning     text
verdict_changes       text
verdict_at            timestamp
-- Market data
market_avg            decimal(10,2)
market_min            decimal(10,2)
market_max            decimal(10,2)
estimated_saving      decimal(10,2)
-- Meta
research_source       varchar(255)
schema_version        tinyint
```

**Migración:** `database/migrations/2026_07_29_000001_add_enriched_valuation_to_cars.php`

### Fase C: CarVerificationService actualizado ✅ PARCIAL

`app/Services/CarVerificationService.php` usa el esquema enriquecido:

- **Prompt mejorado:** Solicita JSON estructurado con todos los campos
- **Schema definido:** 12 campos incluyendo `pros`, `cons`, `verdict`, `market_*`
- **Parseo:** Implementa fallback a texto plano si JSON inválido

**Faltante:** Integración con búsqueda web para datos de mercado reales (no desde memoria de la IA).

---

## Lo que FALTA (según PLAN_VALORACION_ENRIQUECIDA.md)

### Fase B: Comando de importación ⏸️

**Archivo existente:** `app/Console/Commands/ImportValuation.php`

**Estado:** Pendiente de verificar si está implementado según el plan (importar JSON con estructura completa, emparejamiento por VIN/URL, validación separada).

### Fase C completar: Búsqueda web 🚫

**Problema identificado en plan:**
> El problema no es el formato. Es que **le pide a la IA que valore el precio de mercado sin darle ni un solo dato de mercado**. No hay búsqueda web, no se le pasan anuncios comparables, no se consulta ninguna base de recalls.

**Solución pendiente:**
- Añadir búsqueda web para precio de mercado (recalls, etc.)
- Marcar campos que requieren confirmación externa como "pending"
- Distinguir datos de memoria vs datos verificados

---

## Uso Actual

### Verificar un coche con IA

```php
$service = app(CarVerificationService::class);
$result = $service->verify($car);

// Guardar resultado
$car->update([
    'traffic_light' => $result['traffic_light'],
    'verdict' => $result['verdict'],
    'verdict_confidence' => $result['verdict_confidence'],
    'verdict_reasoning' => $result['verdict_reasoning'],
    'market_avg' => $result['market_avg'],
    'estimated_saving' => $result['estimated_saving'],
    'pros' => $result['pros'],
    'cons' => $result['cons'],
    // ...
]);
```

### Job asíncrono

```php
dispatch(new VerifyCarWithAI($car));
```

---

## Tests

**Tests existentes:**
- Buscar tests que usen `VerifyCarWithAI` job
- Verificar que el parseo de JSON funciona

**Tests faltantes:**
- Unit tests de `CarVerificationService`
- Integration tests del comando de importación (Fase B)

---

## Referencias

- **Plan completo:** `docs/planes/PLAN_VALORACION_ENRIQUECIDA.md`
- **Service:** `app/Services/CarVerificationService.php`
- **Job:** `app/Jobs/VerifyCarWithAI.php`
- **Comando:** `app/Console/Commands/ImportValuation.php`
- **Migración:** `database/migrations/2026_07_29_000001_add_enriched_valuation_to_cars.php`

---

## Próximos pasos (recomendados)

1. **Completar Fase B**: Verificar y documentar el comando `ImportValuation`
2. **Implementar Fase C web**: Añadir búsqueda web para datos de mercado
3. **Escribir tests**: Validar que el parsing y guardado funcionan correctamente
4. **Documentar**: Añadir guía de uso en `docs/VALORACION.md`

---

## Notas

- El esquema de BD es **compatible con versiones anteriores** (los campos antiguos `valuation`, `recommendation`, `red_flags`, `tips` se mantienen)
- El sistema usa `schema_version` para controlar cambios de estructura en el futuro
- Los 9 aspectos de `research` no están implementados en el prompt actual (solo `pros`/`cons` globales)