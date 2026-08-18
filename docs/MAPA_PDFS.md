# Mapa de PDFs — JJ Import Motors / ImportnexCore

> Actualizado: 2026-08-18 · Aplica a skill `importacion-vehiculos` v2.9.9+ y Laravel.
> El **briefing PDF ya NO existe** (eliminado 15-ago-2026). El status de cliente 'Briefing' y `briefing_encargo.md` no son el PDF briefing.

## Flujo (quién decide qué)

1. **Claude genera los 2 PDFs de investigación** (búsqueda + unidad) para el equipo.
2. **Claude genera el TEXTO** de todos los demás documentos (esqueletos `.txt [MARCADOR]` del ZIP) y **decide qué se pone y qué NO** en cada uno — especialmente en el folleto del cliente.
3. **Laravel solo maqueta**: convierte los esqueletos `.txt` en PDF con Blade + Browsershot cuando el coche está en inventario. **No decide contenido.**
4. El **folleto del coche** se genera desde `ficha-publicitaria.txt` (bloque `[VALORACION]` = texto de venta presentable). **Nunca** van al folleto/cliente datos internos: margen, honorarios, negociación, `verdict_reasoning`, `recommendation` (A22).

## Resumen: 8 PDFs en dos familias

| Familia | Quién lo genera | Para quién | Contenido |
|---|---|---|---|
| **Investigación** (3) | **Claude** (Desktop) | El usuario / equipo | Búsqueda, candidatos, análisis técnico, veredicto |
| **Venta / documento** (5) | **Laravel** (Blade + Browsershot) | Cliente / público / equipo | Ficha, folleto del coche, dossier, informe interno, folleto institucional |

Regla de oro: **Claude no genera los PDFs de venta y Laravel no genera los de investigación. Claude escribe los esqueletos; Laravel solo los maqueta.**

## PDFs que genera CLAUDE (investigación)

| PDF | Flujo | Contenido mínimo | Plantilla / origen |
|---|---|---|---|
| `informe_busqueda_*.pdf` | B / C / A | Cobertura por fuente (estado, URL, nº resultados), tabla de candidatos con enlaces, qué se excluyó | `assets/plantilla_pdf_marca.html` (KPI cards, coverage grid, tabla con badges DE/ES + fila pick, verdict-card) |
| `informe_unidad_*.pdf` | A / B (candidato elegido) | 15 secciones de `informe_tecnico.md`, score 0-100 | Idem plantilla de marca |
| Informe técnico unidad | A (URL directa) | Análisis técnico de la unidad concreta | Idem plantilla de marca |

Se entregan al usuario (leerlos). NO van dentro del ZIP.

## PDFs que genera LARAVEL (venta / documento)

| PDF | Esqueleto .txt (entrada) | Controlador · ruta | Blade | Audiencia |
|---|---|---|---|---|
| Ficha del coche | `contenido/ficha-publicitaria.txt` | `PaqueteValoracionController@ficha` · `cars.ficha` | `ficha-coche.blade.php` | Cliente |
| Folleto del coche | `contenido/ficha-publicitaria.txt` (reutiliza ficha) | `PaqueteValoracionController@folleto` · `cars.folleto` | `folleto-coche.blade.php` | Cliente |
| Dossier cliente | `contenido/dossier-cliente.txt` (15 sec) | se sirve vía ficha/documento cliente | `ficha-coche.blade.php` | Cliente |
| Informe interno | `contenido/informe-interno.txt` (~60 bloques) | `PaqueteValoracionController@interno` · `cars.informe-interno` (solo owner/operator) | `informe-interno.blade.php` | Equipo |
| Folleto institucional | estático (sin esqueleto) | `JJImportFolletoController@download` · `jj-import.folleto` | `folleto.blade.php` | Público |

## Bloques que renderiza cada Blade de Laravel

### `informe-interno.blade.php` (equipo)
Cabecera: `SCORE_GLOBAL`, `RECOMENDACION`, `COBERTURA`, `CAND_*`, `MARGEN`, `SCORE_DIM`, `VENDIBILIDAD_FACTOR`, `VENTA`+`VENTA_RECOMENDADA`, `RIESGO`, `BANDERA_ROJA/AMARILLA`, `ACCION`+`ACCION_PLAZO`, `COSTE/TOTAL`, `A_FAVOR/EN_CONTRA`, `ASPECTO`, `COMPARABLE` (badges DE/ES + fila ELEGIDO), `FUENTE_LISTA`, `CHECK`, `SEMAFORO`, `DICTAMEN`, `CONFIANZA`, `RESUMEN`, `PRECIO_OBJETIVO`, `VEREDICTO`.

### `ficha-coche.blade.php` (cliente)
Cabecera: `TITULO`, `CLAIM`, `ETIQUETA_DGT`, `SPEC` (→ KPI cards de KM/Año), `PRECIO`, `AHORRO`, `PRECIO_CAPTION`, `PLAZO`, `PRECIO_NOTA`, `H2`+`INCLUYE`/`ARGUMENTO`/`EQUIPAMIENTO`, `CTA`, `CONTACTO`, `QR`, `QR_TEXTO`, `LEGAL`, `FOTOS`. Badge de origen DE/ES desde `cars.pais_origen`.

### `folleto-coche.blade.php` (cliente — folleto del coche)
Versión visual/compacta del mismo esqueleto: portada con foto grande + precio flotante, KPI grid (KM/Año/Combustible/Potencia/Cambio), banda de veredicto (semáforo), highlights (`ARGUMENTO`/`INCLUYE`/`EQUIPAMIENTO`), CTA + QR + contacto. Badge "FOLLETO DEL COCHE". Se lista en la pestaña Documentos junto a la ficha.

### `folleto.blade.php` (público)
Estático: servicio, honorarios, contacto, QR. No depende de esqueletos.

## Rutas del ZIP → Laravel

```
<coche_id>.zip
├── informe.json            → contrato (marca/modelo/costes...)
├── manifest.json
├── contenido/
│   ├── ficha-publicitaria.txt  → ficha-coche.blade.php
│   ├── informe-interno.txt     → informe-interno.blade.php
│   └── dossier-cliente.txt     → documento cliente
└── fotos/                  → galería de la ficha
```

El ZIP se sube por web (`POST /cars/import-valuation`) o comando `importnex:import-valuation`, con token `X-Import-Token`.

## Referencias en el código

- Controladores: `app/Http/Controllers/PaqueteValoracionController.php`, `app/Http/Controllers/JJImportFolletoController.php`
- Parser de esqueletos: `app/Support/Esqueleto.php`
- Importador: `app/Services/ValuationImporter.php`, `app/Http/Controllers/Api/ImportValuationApiController.php`
- Skill: `.claude/skills/importacion-vehiculos/` (`SKILL.md` §Mapa de PDFs, `contrato.md` §Mapa de PDFs, `informe_tecnico.md` §formato-txt)
- Test de render: `tests/Feature/PlantillasValoracionRenderTest.php`
