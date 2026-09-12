# Skills — punto único de verdad

> **Documento canónico.** Si hay conflicto entre lo que dice la skill, el código, o algún README suelto: **este archivo gana**.
> _Última regeneración: 20260906 — v3.7.1 (Flujo M marketing multicanal) — 07-sep-2026 sync Desktop completo_

## 🗒 Navegación rápida

- **Índice general de docs/**: [`docs/DOCS-INDEX.md`](DOCS-INDEX.md)
- **Contexto de negocio JJ Import Motors**: [`docs/contexto-jj-import-motors/`](contexto-jj-import-motors/)
- **Mockups ficha cliente v2/v3**: [`docs/marketing/`](marketing/)
- **Informes finales ya entregados**: [`docs/informes/`](informes/)

## 📦 ZIPs de skill (builds actuales)

Generados por `scripts/build-skill-zips.ps1` (wrapper de `.claude/skills/_dist/build-zips.py`). Para instalar en Claude Desktop -> descomprimir en `%USERPROFILE%\.claude\skills\`.

| Skill | ZIP | Version | Tamano | SHA256 |
|---|---|---|---|---|
| `importacion-vehiculos` | `.claude/skills/_dist/skills-importacion-vehiculos-v3.7.1-20260906.zip` | 3.7.1 | 488 KB | `664c8a9bbae2a85d7f5541cce5e32075cdbe3b221d8a3128dac607ea1877b31e` |
| `estudio-mercado` | `.claude/skills/_dist/skills-estudio-mercado-v0.3.12-20260906.zip` | 0.3.12 | 53 KB | `894e70f8581011c8659fa683e783c86deb88d419ed3fbb75c476939a90ba2ff9` |

## 🔀 Las 3 copias que NO se sincronizan solas (12-sep-2026)

Cada skill vive en **tres sitios independientes**, y estar al día en uno no
significa estarlo en los otros dos:

| Copia | Dónde vive | Se actualiza cuando... |
|---|---|---|
| **Repo** (fuente real) | `.claude/skills/<skill>/` | editas `SKILL.md`/scripts a mano |
| **Claude Desktop** | el `.skill.zip` importado en el gestor de skills de la app | subes a mano el ZIP de `_dist/` |
| **Cowork / cuenta Claude** | copia sincronizada de la cuenta (solo lectura desde una sesión Cowork) | subes a mano el ZIP de `_dist/` al gestor de skills de tu cuenta |

Ninguna de las tres se entera sola de que otra cambió. El 12-sep-2026 se
descubrió que llevaban **6 días divergiendo sin que nadie lo notara**: el
repo iba por v3.9.2/v0.4.0, Cowork seguía en v3.6.1/v0.3.12, y encima
`.claude/skills/_dist/build-zips.py` tenía el nombre del ZIP **hardcodeado**
(`v3.7.1-20260906`), así que ni regenerando los ZIPs se habría notado el
desfase con solo mirar el nombre del fichero. Ya está corregido (el nombre
sale ahora del `version:` real de cada `SKILL.md`), pero la lección se queda:
después de tocar una skill, la tabla de arriba se regenera con el comando de
"Regenerar los ZIPs" — y aun así hay que subir el ZIP a Desktop y a Cowork **a
mano, uno por uno**, no hay atajo.

Regla completa (leer antes de tocar cualquier fichero de `.claude/skills/**`):
[`.ai/rules/skills-sync.md`](../.ai/rules/skills-sync.md).

## 🚗 ZIPs de coche (informes individuales)

**SIEMPRE** usar el flag `--auto-path`. Estructura canónica:

```
C:\Users\jacar\Desktop\JJImportMotors\investigaciones\
└── <marca>\
    └── <modelo>\
        └── <coche_id>-<YYYY-MM-DD>.zip
```

Marca y modelo se leen automáticamente del input (`vehiculo.marca` + `vehiculo.modelo`), se normalizan a slug, y el archivo lleva la fecha del día.

### Cómo usarlo

```powershell
py .claude/skills/importacion-vehiculos/scripts/empaquetar.py \
    --auto-path \
    ruta/al/flujo-a-<coche>.json
```

Resultado (ejemplo real con el fixture BMW):

```
C:\Users\jacar\Desktop\JJImportMotors\investigaciones\
└── bmw\
    └── 320d\
        └── bmw-320d-2020-test-2026-09-06.zip
```

### Por qué Desktop y no el repo

- Fuera de git → no contamina `git status`, sin commits accidentales.
- Compartible con otras herramientas (Outlook, drag&drop al panel Laravel).
- Carpeta visible siempre, fácil de localizar a ojo.

### Cómo se usa después

1. Subir al panel Laravel (`http://localhost/imports`) o por API (`POST /api/import-valuation`).
2. Laravel extrae: coche, fotos, dossier, **13 entradas de marketing** (3 redes × 3 posts + 3 stories + 4 portales).
3. Tras importar, el ZIP ya no es necesario (queda persistido en BD + `cars/{id}/contenido/*.txt` + `storage/app/public/photos/`).

### Otros flags (casos raros)

| Flag | Cuándo | Dónde va |
|---|---|---|
| `--auto-path` (recomendado) | Encargo normal, prueba, todo | `Desktop\...\investigaciones\<marca>\<modelo>\` |
| `--out <ruta>` | Si necesitas forzar otra carpeta específica | Lo que pongas |

NO recomendado: dejar que use el default `./paquetes/` (queda en el repo).

## 🛠 Regenerar los ZIPs

**Un solo comando**, automatiza ZIPs + SHA256 + commit + push:

```powershell
cd 'C:\laragon\www\importnexcore'
.\scripts\build-skill-zips.ps1
```

El script:

1. Lee la `version:` de cada `SKILL.md` fuente.
2. Genera `.claude/skills/_dist/skills-<nombre>-v<version>-<YYYYMMDD>.zip`.
3. Borra ZIPs viejos de la misma skill (mismo nombre, fecha distinta).
4. Reemplaza la tabla de ZIPs en este `docs/SKILLS.md` con SHA256/fecha/tamaño nuevos.
5. Commit + push a `master` (omite si nada cambió).

### Opciones

```powershell
.\scripts\build-skill-zips.ps1 -SkillOnly importacion-vehiculos   # solo una
.\scripts\build-skill-zips.ps1 -NoCommit                         # regenera sin commit
```

## 🗺 Dónde NO deben estar las cosas

| ❌ Prohibido | Por qué |
|---|---|
| `c:\Users\jacar\Desktop\JJImportMotors\laravel\` con `empaquetar.py`, `ImportarValoracion.php`, etc. | Es la versión VIEJA de la skill (pre-v3.0). Ya está migrada al repo. Solo guarda contexto de Claude Desktop. |
| ZIPs en la raíz del repo `c:\laragon\www\importnexcore\*.zip` | Contaminan `git status`. Deben ir en `.claude/skills/_dist/`. |
| Múltiples `.claude/MEMORIA.md` | El canónico está en `.claude/skills/*/memoria/MEMORIA.md`. El de Desktop es contexto, no skill. |
| README en la raíz del repo | Va en `.claude/skills/_dist/` o `docs/`. La raíz es código. |
| `docs/_sync/` | Carpeta ad-hoc creada el 12-sep-2026 para sincronizar ZIPs a mano; duplicaba `.claude/skills/_dist/` + este flujo. Se puede borrar. |

## 📞 Soporte

- **Repo Laravel**: <https://github.com/JACarrasco7/importnex-saas>
- **Producción**: <https://jjimportmotors.on-forge.com>
- **Plan original**: `docs/PLAN_MARKETING_ZIP_2026-09-03.md`
- **Plan marketing multicanal** (06-sep-2026, Flujo M): `docs/PLAN_MARKETING_MULTICANAL_2026-09-06.md` — documenta el módulo `07-marketing/` de la skill (3 portales + 3 redes + FB Marketplace) y el validador `scripts/check_marketing.py`.
- **Contexto Claude Desktop**: `c:\Users\jacar\Desktop\JJImportMotors\.claude\`
