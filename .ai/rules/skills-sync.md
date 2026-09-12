---
glob: '.claude/skills/**,docs/SKILLS.md,docs/DOCS-INDEX.md,scripts/build-skill-zips.ps1'
title: 'Sincronización de skills (importacion-vehiculos, estudio-mercado)'
---

## El problema que esta regla existe para evitar (12-sep-2026)

Cada skill vive en **tres copias independientes** y editar una NO actualiza las
otras dos:

1. **Repo** — `.claude/skills/<skill>/` (la fuente real).
2. **Claude Desktop** — el `.skill.zip` importado en el gestor de skills de la app.
3. **Cowork / cuenta de Claude** — copia sincronizada de la cuenta, de solo lectura
   desde una sesión de Cowork.

El 12-sep-2026 se descubrió que llevaban **6 días divergiendo sin que nadie lo
notara**: el repo estaba en v3.9.2 / v0.4.0, Cowork seguía en v3.6.1 / v0.3.12, y
`.claude/skills/_dist/` tenía ZIPs nombrados `v3.7.1`/`v0.3.12` aunque
`build-zips.py` llevaba el nombre de fichero **hardcodeado a mano** — ni siquiera
reflejaba la versión real del `SKILL.md` que empaquetaba. Un ZIP con el nombre
equivocado es indistinguible de uno actualizado a simple vista: por eso
`docs/SKILLS.md` (que se supone que es la fuente de verdad) llevaba desde el
06-sep sin regenerarse.

## Regla dura: cada vez que se edita una skill

1. Editar los ficheros fuente en `.claude/skills/<skill>/`.
2. Correr **siempre**:
   ```powershell
   cd C:\laragon\www\importnexcore
   .\scripts\build-skill-zips.ps1
   ```
   Este script (no reinventarlo, no crear un `.bat` paralelo):
   - Regenera los ZIPs en `.claude/skills/_dist/` con el nombre correcto (versión +
     fecha leídas del `SKILL.md` real, corregido 12-sep-2026 — antes estaba
     hardcodeado).
   - Borra los ZIPs viejos de la misma skill.
   - Reescribe la tabla de `docs/SKILLS.md` con versión/tamaño/SHA256 reales.
   - Hace `git add` + commit + push de `_dist/`, `docs/SKILLS.md` y el propio
     script.
3. **Después**, y solo entonces, subir el ZIP nuevo de `_dist/` a:
   - Claude Desktop (gestor de skills de la app).
   - La cuenta de Claude (donde se gestionan las skills de Cowork).

Si un agente de Claude sin acceso a terminal en esta máquina (el puente remoto
puede estar caído, o no puede escribir bajo `.claude/`) prepara cambios de skill:
empaqueta los ficheros tocados + un `.bat` que los copie a su sitio y termine
llamando a `powershell -File scripts\build-skill-zips.ps1` — **nunca** un script
que haga su propio `git commit` de los ZIPs o reescriba `docs/SKILLS.md` a mano,
porque entonces hay dos mecanismos de la misma cosa y vuelven a divergir.

## Qué NO hacer

- No crear una carpeta de sincronización alternativa (`docs/_sync/` u otra) como
  mecanismo permanente — la única fuente de ZIPs versionados es
  `.claude/skills/_dist/`, documentada en `docs/SKILLS.md`.
- No editar `docs/SKILLS.md` a mano para poner una versión — la tabla la escribe
  el script; si se edita a mano y luego se corre el script, se sobreescribe (no
  hay pérdida de datos, pero es trabajo tirado).
- No asumir que porque el repo está actualizado, Desktop o Cowork también lo
  están. Son sincronizaciones manuales, una por una.

## Cómo comprobar rápido si las tres copias coinciden

```powershell
# Version en el repo (fuente de verdad):
Get-Content .claude\skills\importacion-vehiculos\SKILL.md | Select-String '^version:'
Get-Content .claude\skills\estudio-mercado\SKILL.md       | Select-String '^version:'

# Fecha del ultimo ZIP generado (si es de hoy, _dist esta al dia con el repo):
Get-ChildItem .claude\skills\_dist\*.zip | Select-Object Name, LastWriteTime
```

Para Desktop y Cowork no hay comando — hay que abrir el gestor de skills de cada
uno y mirar la versión a ojo, o preguntarle directamente a Claude en esa sesión
("¿qué versión de `estudio-mercado` tienes cargada?").
