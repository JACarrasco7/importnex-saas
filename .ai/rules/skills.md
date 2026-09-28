---
paths:
  - '.claude/skills/**'
---

# Skills

## El catálogo compartido tiene 3 copias: comparar contenido, no bytes
El catálogo de IDs vive en 3 sitios que deben coincidir: `app/Support/data/mobile-de-catalogo.json`, `.claude/skills/importacion-vehiculos/references/mobile-de-ids.json` y `.claude/skills/estudio-mercado/references/mobile-de-ids.json`. Copiarlos entre sí con distintas herramientas puede dejar **CRLF vs LF**: el hash del working tree difiere (~1 byte por línea) aunque el contenido sea idéntico, y git lo normaliza a LF, por lo que el blob commiteado es el mismo y `git status` no muestra nada. Antes de "arreglar" una divergencia, comparar el JSON parseado (claves/valores), no el hash de fichero. El audit `_tmp/audit_final.py` (check C) usa hash de fichero y da falso positivo en ese caso.

## Build/sync de skills: usar el wrapper, no el .py; y ojo con el ANSI en .ps1
1) `scripts/build-skill-zips.ps1` es el ÚNICO mecanismo de build (escribe la tabla de `docs/SKILLS.md`, que es el documento canónico). No invocar `.claude/skills/_dist/build-zips.py` a pelo por rutina: se salta la actualización de `docs/SKILLS.md`. Para no auto-commit: `-NoCommit`.

2) ⚠️ PowerShell 5.1 lee los `.ps1` SIN BOM como ANSI (cp1252): cualquier acento literal en un `.ps1` llega corrupto y los regex con acentos NUNCA casan (pasó con la línea "Última regeneración" de `build-skill-zips.ps1`, que llevaba meses sin actualizarse mientras la tabla sí). En `.ps1`, escribir acentos con `[char]0xFA` / `[char]0xF3` en vez de literales.

3) `docs/_sync/` está OBSOLETO (regla `skills-sync.md`): contiene copias congeladas de skills y contradice al repo. No copiar ficheros de ahí.

4) `_dist/README.md` no es fuente de versiones: la tabla vigente (ZIP/versión/SHA256) la escribe el script en `docs/SKILLS.md`.

5) Tras tocar una skill: commitear los ficheros FUENTE a mano (el script solo stagea `_dist/`, `docs/SKILLS.md` y el propio script).
