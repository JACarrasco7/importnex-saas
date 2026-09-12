# `_sync` — dónde están las skills actualizadas y qué hacer con ellas

> Carpeta creada el 12-sep-2026. Claude deja aquí las skills actualizadas porque
> **no puede escribir bajo `.claude\`** (regla fija del puente remoto) ni ejecutar
> `git` (el puente de terminal está roto desde la actualización de Windows del 8-sep).

## Estado de las 3 copias de cada skill (12-sep-2026 19:55 UTC+2)

Cada skill vive en TRES sitios y actualizar uno **no** actualiza los otros.
Las versiones reales ahora son: `importacion-vehiculos` **v3.9.3** y `estudio-mercado` **v0.4.0**
(verificadas con `Get-Content .claude/skills/<skill>/SKILL.md` frontmatter).

| Copia | importacion-vehiculos | estudio-mercado |
|---|---|---|
| **Repo** (`.claude\skills\`) | 3.9.3 OK | 0.4.0 OK |
| **Cowork / cuenta Claude** | 3.6.1 desactualizado | 0.3.12 desactualizado |
| **Claude Desktop** | sin confirmar | sin confirmar |

> **Antes del 12-sep:** `estudio-mercado` figuraba como `0.3.12 -> pendiente 0.4.0` porque el
> frontmatter `SKILL.md` iba retrasado respecto al CHANGELOG. Commit `c19def2` arreglo
> ademas `_dist/build-zips.py` para que el nombre del ZIP refleje la version real del
> `SKILL.md` (antes estaba hardcodeado en `v3.7.1/v0.3.12`).

## Que hacer (2 clics por skill)

### 1. Repo - ya esta al dia
Ambos SKILL.md ya dicen `version: 3.9.3` / `version: 0.4.0` y los ZIPs en
`.claude/skills/_dist/` estan regenerados con nombre correcto
(`skills-<skill>-v<VERSION>-20260912.zip`). **No hace falta correr `aplicar-estudio-mercado-v0.4.0.bat`** —
ese `.bat` se quedo obsoleto; ahora `scripts/build-skill-zips.ps1` lo hace
todo de un solo comando.

### 2. Desktop + Cowork - importar los `.skill.zip`
En el gestor de skills de Claude Desktop y en las skills de tu cuenta, sube:

- `skills-importacion-vehiculos-v3.9.3-20260912.zip`
- `skills-estudio-mercado-v0.4.0-20260912.zip`

Asi dejan de usar la 3.6.1 y la 0.3.12.

> Si prefieres automatizarlo, descarga los artefactos desde
> Actions -> build-skills (workflow de CI del repo) - cada push a master sube
> los ZIPs como artefacto descargable.

## Por que importa que las tres esten iguales

Si Cowork usa la 3.6.1 y el repo la 3.9.3, un ZIP generado desde Cowork sale con la
logica vieja (sin `[GASTO]`, sin canales de portal diferenciados) aunque el panel de
Laravel ya sepa leer el formato nuevo. La cadena solo funciona si las dos puntas van a la
misma version.

## Como detectar divergencia sin tocar nada

```powershell
cd C:\laragon\www\importnexcore
.\scripts\sync-desktop.ps1       # WhatIf: solo informa
.\scripts\sync-desktop.ps1 -Apply # replica + verifica
```

El script compara los hashes de los masters del repo contra los ficheros de
Desktop y reporta verde/amarillo/rojo. Si una skill instalada en Desktop es
vieja, lo dice claramente con su version real (sacada del frontmatter del
`SKILL.md` descomprimido).

## Cambio critico de la 0.4.0 de estudio-mercado
