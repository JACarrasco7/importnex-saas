# Skills del negocio JJ Import Motors — Paquete v2026-09-05

> 2 ZIPs portables para cargar en Claude Desktop. Reemplazan las versiones cacheadas en `~/.claude/skills/`.

## 📦 Archivos

| Skill | ZIP | Versión | Tamaño | SHA256 |
|---|---|---|---|---|
| `importacion-vehiculos` | `skills-importacion-vehiculos-v3.6.1-20260905.zip` | 3.6.1 | 407 KB | `e3bb037e03a4c95585487c0c5cdb2c79a9c3e646182966334841ea66180ce1cf` |
| `estudio-mercado` | `skills-estudio-mercado-v0.3.12-20260905.zip` | 0.3.12 | 53 KB | `84368e4888be3c4f8f9804755b171ae2b09d66f36c4059f3a24cb7c07d2dffa7` |

## 🎯 Cuál usar

| Skill | Cuándo |
|---|---|
| **importacion-vehiculos** (3.6.1) | Un cliente tiene un encargo concreto: buscar/importar un coche específico (URL, modelo, mercado, o "qué me cabe en X presupuesto"). Genera ZIP → Laravel. |
| **estudio-mercado** (0.3.12) | Sin cliente: sondear qué modelos son buena oportunidad ahora mismo. Alimenta a `importacion-vehiculos` con datos de mercado objetivos. |

**Relación**: `estudio-mercado` es la **hermana previa** (input de mercado), `importacion-vehiculos` es la **ejecutora** (output de ZIP para Laravel). Ambas coexisten; la segunda normalmente parte de un mapa generado por la primera.

## 🚀 Instalación

### 1. Cierra Claude Desktop.

### 2. Respalda y reemplaza:

**Windows:**
```powershell
$root = "$env:USERPROFILE\.claude\skills"
foreach ($name in 'importacion-vehiculos', 'estudio-mercado') {
    if (Test-Path "$root\$name") {
        Move-Item "$root\$name" "$root\$name.bak" -Force
        Write-Host "Backup: $name.bak"
    }
}
Expand-Archive "skills-importacion-vehiculos-v3.6.1-20260905.zip" -DestinationPath "$root\importacion-vehiculos" -Force
Expand-Archive "skills-estudio-mercado-v0.3.12-20260905.zip" -DestinationPath "$root\estudio-mercado" -Force
Write-Host "Instaladas. Reinicia Claude."
```

**macOS / Linux:**
```bash
ROOT="$HOME/.claude/skills"
for name in importacion-vehiculos estudio-mercado; do
    [ -d "$ROOT/$name" ] && mv "$ROOT/$name" "$ROOT/$name.bak"
done
unzip -o skills-importacion-vehiculos-v3.6.1-20260905.zip -d "$ROOT/importacion-vehiculos"
unzip -o skills-estudio-mercado-v0.3.12-20260905.zip -d "$ROOT/estudio-mercado"
```

### 3. Reinicia Claude Desktop.

### 4. Verifica:

```
qué versiones de skills de importacion tienes?
```

Debe responder: `importacion-vehiculos 3.6.1` y `estudio-mercado 0.3.12`.

## 🆕 Qué cambia vs versiones cacheadas

### `importacion-vehiculos` v3.5.x → v3.6.1

- **`scripts/empaquetar.py`** (NUEVO · 44 KB): generador de ZIP reproducible con validación dura de fotos + 5 esqueletos + manifest v2. Antes era un script referenciado pero inexistente.
- **`scripts/fixtures/flujo-a-bmw-320d-2020-test.json`** (NUEVO): ejemplo BMW 320d 2020 para pruebas E2E.
- **§ZIP en SKILL.md** con reglas duras actualizadas: fotos obligatorias, marketing obligatorio.
- **Anti-patrón A22**: "ZIP sin fotos validadas o sin marketing = entrega INVÁLIDA".
- **`contrato.md`**: nota Laravel añadida — importa .txt → `CarMarketingContent` (status draft).
- **Esqueletos marketing**: ampliados de 1 bloque genérico a 3 redes × 3 posts + 3 stories + 4 portales.

### `estudio-mercado` v0.3.x → v0.3.12

- `SKILL.md` 37 KB, `CHANGELOG.md` 18 KB.
- `schema_datos_mercado.md` (15 KB): contrato del `datos_mercado.json` persistente.
- `fuentes_datos.md` (4.6 KB): qué se scrapea y cómo.
- `informe_mercado.md` (29 KB): plantilla del informe por categoría/modelo.
- `informes-mercado/`: 2 ejemplos reales (memoria segmentación + VW Golf 7.5 GTI TCR Clubsport R).
- Cambios menores (ver `CHANGELOG.md` para histórico).

## ✅ Compatibilidad backend Laravel

Ambas skills generan inputs que **Laravel 13.24.0 + ImportnexCore v3.6.1** en `https://jjimportmotors.on-forge.com` sabe procesar:

| Origen | Laravel hace |
|---|---|
| ZIP de `importacion-vehiculos` | Crea/actualiza coche + adjunta fotos + 5 esqueletos .txt + **13 entradas de marketing** (3 redes × 3 posts + 3 stories + 4 portales) |
| `datos_mercado.json` de `estudio-mercado` | Alimenta `market_models` y `market_leads` para el dashboard de oportunidades |

Migración relevante: `2026_09_05_150815_add_slot_and_subir_pasos_to_car_marketing_contents` (ya aplicada en prod, release `77029810`).

## 📞 Soporte

- **Repo Laravel**: <https://github.com/JACarrasco7/importnex-saas>
- **Producción**: <https://jjimportmotors.on-forge.com>
- **Plan original**: `c:\laragon\www\importnexcore\docs\PLAN_MARKETING_ZIP_2026-09-03.md`
