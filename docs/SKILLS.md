# Skills — punto único de verdad

> **Documento canónico.** Si hay conflicto entre lo que dice la skill, el código, o algún README suelto: **este archivo gana**.
> _Última regeneración: 20260906_

## 📦 ZIPs de skill (builds actuales)

Generados por `scripts/build-skill-zips.ps1`. Para instalar en Claude Desktop -> descomprimir en `%USERPROFILE%\.claude\skills\`. Cada ZIP contiene la carpeta raiz `<skill>/`, lista para cargar.

| Skill | ZIP | Version | Tamano | SHA256 |
|---|---|---|---|---|
| `importacion-vehiculos` | `.claude/skills/_dist/skills-importacion-vehiculos-v3.6.1-20260906.zip` | 3.6.1 | 409 KB | `78601cb7fccf5993dce044c12cd7207b12879836a05cd8d2cacc8aa89e60f3e6` |
| `estudio-mercado` | `.claude/skills/_dist/skills-estudio-mercado-v0.3.12-20260906.zip` | 0.3.12 | 53 KB | `4aa81edfdd4d18a8f2022a287b15c99bcc889b60bb61912ed32716edfe5a6917` |

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

## 📞 Soporte

- **Repo Laravel**: <https://github.com/JACarrasco7/importnex-saas>
- **Producción**: <https://jjimportmotors.on-forge.com>
- **Plan original**: `docs/PLAN_MARKETING_ZIP_2026-09-03.md`
- **Contexto Claude Desktop**: `c:\Users\jacar\Desktop\JJImportMotors\.claude\`
