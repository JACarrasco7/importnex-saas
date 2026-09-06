# Skills — punto único de verdad

> **Documento canónico.** Si hay conflicto entre lo que dice la skill, el código, o algún README suelto: **este archivo gana**.
> _Última regeneración: 20260906_

## 📦 ZIPs de skill (builds actuales)

Generados por `scripts/build-skill-zips.ps1`. Para instalar en Claude Desktop -> descomprimir en `%USERPROFILE%\.claude\skills\`.

| Skill | ZIP | Version | Tamano | SHA256 |
|---|---|---|---|---|
| `importacion-vehiculos` | `.claude/skills/_dist/skills-importacion-vehiculos-v3.6.1-20260906.zip` | 3.6.1 | 407 KB | `4b65cab2d3314a6c2ac5e569e7b0d56048e6ab3461e1b5b66c8baf55fe3faecb` |
| `estudio-mercado` | `.claude/skills/_dist/skills-estudio-mercado-v0.3.12-20260906.zip` | 0.3.12 | 53 KB | `84368e4888be3c4f8f9804755b171ae2b09d66f36c4059f3a24cb7c07d2dffa7` |

## 🚗 ZIPs de coche (informes individuales)

**Salida de cada encargo.** Se generan con `py empaquetar.py <input.json> --out <directorio>`.

| Caso | Dónde ponerlos | Por qué |
|---|---|---|
| Encargo real de cliente | `paquetes/<cliente>-<coche>-<fecha>.zip` (crear carpeta `paquetes/` si no existe) | Quedan versionados en el repo, fácil de localizar y resubir |
| Prueba rápida / borrador | `C:\Users\jacar\Desktop\JJImportMotors\paquetes\` (fuera del repo) | No contamina `git status`, fácil de arrastrar al panel Laravel |
| Test fixture / ejemplo | `tmp/` o bórralo tras usar | El fixture está en `.claude/skills/.../scripts/fixtures/flujo-a-bmw-320d-2020-test.json` (input), no donde va el ZIP generado |

### Cómo se usa después

Tras generar el ZIP:

1. Subirlo al panel Laravel (`http://localhost/imports`) o por la API (`POST /api/import-valuation`).
2. Laravel extrae: coche, fotos, dossier, **13 entradas de marketing** (3 redes × 3 posts + 3 stories + 4 portales).
3. El ZIP ya no es necesario, se puede borrar (queda persistido en BD + `cars/{id}/contenido/*.txt` + `storage/app/public/photos/`).

## 🛠 Regenerar los ZIPs

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
