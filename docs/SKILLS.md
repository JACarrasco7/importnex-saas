# Skills — punto único de verdad

> **Documento canónico.** Si hay conflicto entre lo que dice la skill, el código, o algún README suelto: **este archivo gana**.

## 📐 Arquitectura

```
importnexcore/                      ← repo único
├── .claude/
│   └── skills/
│       ├── estudio-mercado/        ← FUENTE skill 1 (editable)
│       ├── importacion-vehiculos/  ← FUENTE skill 2 (editable)
│       └── _dist/                  ← BUILD ZIPs portables (regenerable)
│           ├── skills-estudio-mercado-v0.3.12-YYYYMMDD.zip
│           ├── skills-importacion-vehiculos-v3.6.1-YYYYMMDD.zip
│           ├── importacion-vehiculos-README.md
│           └── README.md
└── docs/
    └── SKILLS.md                   ← ESTE DOCUMENTO
```

**Regla:** `_dist/` se regenera, no se edita a mano. Para cambiar una skill, edita el directorio fuente y vuelve a ejecutar el build (ver "Regenerar ZIPs").

## 🎯 Las 2 skills del negocio

| Skill | Fuente | Versión | Para qué |
|---|---|---|---|
| `estudio-mercado` | `.claude/skills/estudio-mercado/` | 0.3.12 | Sondear mercado de 2ª mano (ES + DE). Alimenta a la otra con datos objetivos. |
| `importacion-vehiculos` | `.claude/skills/importacion-vehiculos/` | 3.6.1 | Encargos reales: buscar/importar un coche. Genera ZIP para Laravel. |

**Relación**: `estudio-mercado` es la **hermana previa** (input de mercado), `importacion-vehiculos` es la **ejecutora** (output de ZIP). Coexisten; la segunda parte de un mapa generado por la primera.

## 📦 ZIPs portables (builds)

Generados desde las fuentes. Para instalar en Claude Desktop → descomprimir en `%USERPROFILE%\.claude\skills\`.

| Skill | ZIP | Versión | Tamaño | SHA256 |
|---|---|---|---|---|
| `importacion-vehiculos` | `.claude/skills/_dist/skills-importacion-vehiculos-v3.6.1-20260905.zip` | 3.6.1 | 407 KB | `e3bb037e03a4c95585487c0c5cdb2c79a9c3e646182966334841ea66180ce1cf` |
| `estudio-mercado` | `.claude/skills/_dist/skills-estudio-mercado-v0.3.12-20260905.zip` | 0.3.12 | 53 KB | `84368e4888be3c4f8f9804755b171ae2b09d66f36c4059f3a24cb7c07d2dffa7` |

> Las instrucciones completas de instalación están en `.claude/skills/_dist/README.md` (consolidado).

## 🛠 Regenerar los ZIPs

Solo se hace cuando cambias algo en las fuentes. Procedimiento:

```powershell
cd 'C:\laragon\www\importnexcore'

# 1. Verificar que todo en las fuentes está commiteado (working tree limpio).
git status --short .claude/skills/

# 2. Regenerar los ZIPs (incluye la fecha actual en el nombre).
$date = Get-Date -Format 'yyyyMMdd'
Compress-Archive -Path '.claude\skills\importacion-vehiculos\*' -DestinationPath ".claude\skills\_dist\skills-importacion-vehiculos-v3.6.1-$date.zip" -CompressionLevel Optimal -Force
Compress-Archive -Path '.claude\skills\estudio-mercado\*' -DestinationPath ".claude\skills\_dist\skills-estudio-mercado-v0.3.12-$date.zip" -CompressionLevel Optimal -Force

# 3. Borrar los ZIPs viejos (los README van aparte).
Get-ChildItem .claude/skills/_dist/*.zip | Where-Object { $_.Name -notlike "*$date*" } | Remove-Item -Force

# 4. Actualizar versión y SHA256 en este docs/SKILLS.md (manual).

# 5. Commit + push.
git add .claude/skills/_dist/ docs/SKILLS.md
git commit -m "chore(skills): regenerar ZIPs vX.Y.Z"
git push origin master
```

> ⚠️ **Las versiones y hashes en este archivo se actualizan a mano** al regenerar. No automatizar (regla de oro: una sola fuente de verdad, mantenida por humanos).

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
