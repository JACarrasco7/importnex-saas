# Operaciones de cierre y seguimiento

> **Cargar cuando:** Se cierra una venta, se actualiza el registro, o se necesita medir KPIs.
> **No cargar para:** Investigación de modelos ni generación de informes.

---

## 📊 Registro de cierre (Mejora #15)

Cuando un coche investigado se vende (o no), registrar el resultado para calibrar futuras predicciones.

**Archivo:** `datos/registro_cierres.json` (en Desktop)

```json
{
  "cierres": [
    {
      "coche_id": "opel-astra-opc-2012-38347146649056",
      "fecha_investigacion": "2026-08-10",
      "veredicto": "Comprar",
      "precio_objetivo": 11800,
      "fecha_venta": "2026-08-15",
      "precio_final": 11500,
      "cliente": "Juan Pérez",
      "plataforma": "Wallapop",
      "dias_hasta_venta": 5,
      "comentario": "Cliente negoció 300€ menos. Coche en perfecto estado."
    }
  ]
}
```

**Cuándo actualizar:**
- Cuando el usuario informa que un coche se vendió
- Cuando pasan >30 días sin noticias (marcar como "no vendido")

**Para qué sirve:**
- Calibrar precisión de veredictos (KPI #16)
- Ajustar umbrales de margen si la realidad difiere de la predicción

---

## 📈 KPIs del skill (Mejora #16)

Métricas para evaluar la calidad de las investigaciones.

| KPI | Cómo se mide | Objetivo |
|-----|--------------|----------|
| **Precisión de veredictos** | (Cierres exitosos / Total veredictos "Comprar") × 100 | >80% |
| **Tiempo hasta venta** | Media de `dias_hasta_venta` en registro_cierres.json | <15 días |
| **Desviación de precio** | (precio_final - precio_objetivo) / precio_objetivo | <5% |
| **Tasa de falsos positivos** | (Veredictos "Comprar" que no se venden) / Total | <20% |

**Cuándo calcular:**
- Mensualmente (o cuando el usuario pida "estadísticas")
- Leer `datos/registro_cierres.json` y agrupar por mes

**Output ejemplo:**
```
📊 KPIs Agosto 2026:
- Precisión: 85% (17/20 coches recomendados se vendieron)
- Tiempo medio: 12 días
- Desviación media: +2.3% (precios finales ligeramente por encima del objetivo)
```

---

## � KPIs en Laravel (endpoint + dashboard) — pipeline oficial

> **Auditoría 3 (#15):** Laravel ya tiene el pipeline oficial de KPIs. El registro local en
> `datos/registro_cierres.json` sigue siendo válido para el Desktop, pero los KPIs agregados
> se consultan vía API o dashboard.

**Registro de cierres (desde el chat):**
```bash
curl -X POST https://jjimportmotors.on-forge.com/api/cierres \
  -H "X-Import-Token: <token>" -H "Content-Type: application/json" \
  --data '{"coche_id":"opel-astra-opc-2012","veredicto":"Comprar","precio_objetivo":11800,"fecha_venta":"2026-08-15","precio_final":11500,"cliente":"Juan Pérez","plataforma":"Wallapop","estado":"vendido"}'
```

**Consultar KPIs agregados (histórico N meses):**
```bash
curl "https://jjimportmotors.on-forge.com/api/kpis?periodo=2026-08&months=6" \
  -H "X-Import-Token: <token>"
```
→ `kpis_periodo` (precisión, tiempo hasta venta, desviación, falsos positivos, volumen) + `historico[]`.

**Dashboard web:** `/kpis` (autenticado, scoped por organización) — cards con semáforo,
tendencia 6 meses, tabla de cierres con filtros por marca y plataforma.

**Lógica:** `app/Services/KpiCalculator.php` (fuente única web+API). Tabla `cierres`
con `brand`/`model` denormalizados para filtros por marca.

---

## �📝 Changelog del skill (Mejora #17)

Versionado formal de cambios en los archivos del skill.

**Archivo:** `CHANGELOG.md` (en `.claude/skills/importacion-vehiculos/`)

**Formato:** [Keep a Changelog](https://keepachangelog.com/es-ES/1.0.0/) + [Semantic Versioning](https://semver.org/lang/es/)

**Cuándo actualizar:**
- Cada vez que se modifica SKILL.md o archivos modulares
- Cambios en `../03-informes/contrato.md` (estructura JSON)
- Nuevos endpoints o migraciones en Laravel

**Ejemplo de entrada:**
```markdown
## [1.8.0] - 2026-08-11

### Añadido
- **Mejora #15:** Registro de cierre en `datos/registro_cierres.json`
- **Mejora #16:** KPIs del skill (precisión, tiempo, desviación, falsos positivos)
- **Mejora #17:** Changelog formal con versionado semántico
- **Mejora #20:** Script `verify_desktop_sync.py` para sincronización Desktop ↔ Skill

### Cambiado
- SKILL.md: 485 → 526 líneas (+41)
- Sección "Registro de cierre" movida a `operaciones_cierre.md`
```

---

## 🔄 Sincronización Desktop ↔ Skill (Mejora #20)

Verificar que los scripts referenciados en el skill existen en Desktop.

**Script:** `scripts/verify_desktop_sync.py`

**Ejecución:**
```bash
py .claude/skills/importacion-vehiculos/scripts/verify_desktop_sync.py
```

**Qué verifica:**
- Scripts Python en `Desktop/JJImportMotors/laravel/` (12 archivos)
- Datos requeridos: `marca.json`, `datos_mercado.json`

**Output ejemplo:**
```
🔍 Verificando sincronización Desktop ↔ Skill

📁 Ruta base: C:\Users\jacar\Desktop\JJImportMotors\laravel

============================================================
SCRIPTS REQUERIDOS
============================================================
✅ Script: franja.py (20.2 KB)
✅ Script: comparativa_cliente.py (22.0 KB)
❌ Script: fill_template.py FALTANTE
...

📊 Scripts: 4/12 presentes

============================================================
RESUMEN
============================================================
❌ FALTAN 8 archivos:
   - fill_template.py
   - fill_client_template.py
   ...

💡 SOLUCIÓN:
   1. Verifica que la carpeta Desktop/JJImportMotors/laravel/ esté completa
   2. Si falta algo, copia desde .claude/skills/importacion-vehiculos/scripts/
   3. Re-ejecuta este script para confirmar
```

**Cuándo ejecutar:**
- Al inicio de cada sesión de investigación (automático si se configura)
- Manualmente: `py scripts/verify_desktop_sync.py`

**Si faltan scripts:** Avisar al usuario y ofrecer copiar desde `references/scripts/` (si están versionados).

---

## 📋 Encargo permanente (Gap G10)

**Definición:** Modelos que SIEMPRE están en búsqueda activa (Flujo C mensual automático).

### Segmento Nicho (alto margen, baja oferta)

| Criterio | Valor |
|---|---|
| **Precio venta ES** | ≥20.000 € |
| **Oferta España** | <15 unidades comparables |
| **Margen mínimo** | ≥15% |
| **Vendibilidad** | ≥70/100 |
| **Ejemplos** | Porsche Cayman, BMW M2/M3, Audi RS3/RS4, Mercedes AMG, VW Golf R |

**Equipamiento mínimo:** Manual/DSG + Techo + LED + Cámara (o equivalente premium)

**Regla:** Si un modelo Nicho baja de 15% margen durante 2 meses consecutivos, degradar a Rotación.

### Segmento Rotación (margen medio, alta demanda)

| Criterio | Valor |
|---|---|
| **Precio venta ES** | 8.000-20.000 € |
| **Oferta España** | 15-100 unidades comparables |
| **Margen mínimo** | ≥10% |
| **Vendibilidad** | ≥60/100 |
| **Ejemplos** | VW Golf GTI, Audi A3/S3, BMW Serie 1/3, Mercedes Clase A, Seat León FR |

**Equipamiento mínimo:** Automático + Techo o LED (al menos 2 extras premium)

**Regla:** Si un modelo Rotación sube a ≥15% margen durante 3 meses consecutivos, promover a Nicho.

### Lista de encargo permanente (actualizar mensualmente)

```
NICHO (revisar 1ª semana del mes):
- Porsche Cayman (718/981)
- BMW M2 Competition
- Audi RS3 (8V/8Y)
- VW Golf R (Mk7/Mk8)
- Mercedes A45 AMG

ROTACIÓN (revisar 2ª semana del mes):
- VW Golf GTI (Mk7/Mk8)
- Audi S3 (8V/8Y)
- BMW 128ti (F40)
- Seat León FR 2.0 TSI
- Mercedes A250e (PHEV)
```

**Regla:** Si el usuario menciona un modelo que NO está en la lista, evaluar si cumple criterios de Nicho/Rotación y añadirlo si aplica.
