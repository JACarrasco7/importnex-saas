---
paths:
  - '.claude/skills/estudio-mercado/**'
---

# Estudio Mercado

## Estudio de mercado: plantilla única (9 secciones), ruta fija y §FUENTES obligatoria
Desde v0.5.0 (28-sep-2026) TODO informe de marca/segmento usa la MISMA plantilla `informe_mercado.md`, sin variantes por segmento (SUV/hatchback/familiar...): 9 secciones (conclusión → versión a versión → comparables → trampas → resumen → gastos → cobertura → fuentes → checklist). Es un SONDEO, no un dossier: NO se incluyen fichas de "2 mejores anuncios", desglose por variables, ni verificación de fichas. El detalle de anuncios lo aporta el usuario abriendo los listados.

Ruta/nombre canónicos (únicos): `informes/<marca>/<marca>_<segmento>_<enfoque>_<YYYY-MM-DD>.md` — marca corta en minúsculas (`vw`), `<segmento>` del schema (compacto|suv|berlina|deportivo|familiar|urbano), `<enfoque>` (deportivo|generalista|accesible|premium|mixto). Estudio de un modelo: `<marca>-<modelo>_...`.

§FUENTES (sección 8) es la única imprescindible y se genera con `importacion-vehiculos/scripts/fuentes.py`, nunca a mano: URL + parámetros al lado + **conteo** + **fecha**, todos los `--spec` en UNA llamada. El conteo va POR VERSIÓN dentro de su `--spec` (campos 10-11); `versions_es` (campo 12) mete `Versions[0]` en Coches.net — sin eso, GTI y R generan la MISMA URL.

## Guías de búsqueda manual: artefacto separado del sondeo (generar_guia_marcas.py)
Las guías por marca (guia-&lt;marca&gt;_&lt;fecha&gt;.md|pdf + 00-INDICE.md) NO son sondeos de mercado: son documentación de usuario generada por scripts/generar_guia_marcas.py desde datos_mercado.json + dict curado IDENT. Al entrar un modelo nuevo en el mapa, añadir su entrada IDENT (generación/años, motores CV, trampa) o la guía sale con huecos. Salida dual: Desktop JJImportMotors/guias-busqueda/ + espejo informes-mercado/. PDF vía Chrome headless: rutas ABSOLUTAS + --headless=new (relativas fallan en silencio).
