---
glob: '**/*.{php,vue,js,md}'
title: 'Search Hygiene: no bucles de búsqueda fallida'
---

Cuando una búsqueda (grep_search/file_search) devuelve "No matches found":

1. NO repetir el mismo patrón ni usar includeIgnoredFiles:true a ciegas.
2. Máximo 3 intentos con estrategias DISTINTAS:
   - Intento 1: búsqueda original
   - Intento 2: file_search con glob amplio (`**/nombre*`) o simplificar regex
   - Intento 3: list_dir del directorio padre
3. Si 3 intentos fallan → preguntar al usuario la ruta. STOP.
4. includeIgnoredFiles:true SOLO si sabes que el archivo está en path ignorado (vendor, node_modules, storage).
5. Preferir file_search (devuelve rutas, barato) sobre grep (devuelve contenido, caro).

Regla completa: `.ai/skills/importnex-anti-loop/SKILL.md`.
