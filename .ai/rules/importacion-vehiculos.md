---
paths:
  - '.claude/skills/importacion-vehiculos/**'
---

# Importacion Vehiculos

## Validador y generador v2 cambian juntos + test e2e mínimo
check_marketing.py y empaquetar.py se co-diseñan: todo bloque nuevo que emita `_bloques_v2_redes`/`_bloques_v2_portales` debe tener check que lo valide y viceversa (C17 mide POR PIEZA: agrega prefijos IG_/VT_/FB_/FBMP_/PT_ excluyendo *_FUENTES y *STORY*). Test mínimo tras tocarlos: empaquetar.py con un payload sintético y fotos SOLO en .fotos_cache debe llegar a exit 0 (cubre mkdir de fotos_dir, run_validator con resumen "🔴 0" y los FUENTES).
