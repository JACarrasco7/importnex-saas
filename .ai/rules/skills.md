---
paths:
  - '.claude/skills/**'
---

# Skills

## El catálogo compartido tiene 3 copias: comparar contenido, no bytes
El catálogo de IDs vive en 3 sitios que deben coincidir: `app/Support/data/mobile-de-catalogo.json`, `.claude/skills/importacion-vehiculos/references/mobile-de-ids.json` y `.claude/skills/estudio-mercado/references/mobile-de-ids.json`. Copiarlos entre sí con distintas herramientas puede dejar **CRLF vs LF**: el hash del working tree difiere (~1 byte por línea) aunque el contenido sea idéntico, y git lo normaliza a LF, por lo que el blob commiteado es el mismo y `git status` no muestra nada. Antes de "arreglar" una divergencia, comparar el JSON parseado (claves/valores), no el hash de fichero. El audit `_tmp/audit_final.py` (check C) usa hash de fichero y da falso positivo en ese caso.
