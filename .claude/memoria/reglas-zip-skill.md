# Regla del ZIP del skill (17-ago-2026)

> **Lección del usuario (17-ago):** el ZIP de la skill debe quedarse **SIEMPRE en la carpeta del skill**, NUNCA en Desktop.

**Ubicación fija:**
- Origen + final: `c:\laragon\www\importnexcore\.claude\skills\importacion-vehiculos\importacion-vehiculos.skill.zip`

**Método de empaquetado (17-ago, robusto):**
- **Nunca** `Compress-Archive` de PowerShell 5.1 (escribe `\` en las entradas → Claude Desktop rechaza: *"Zip file contains path with invalid characters"*).
- **Siempre** `ZipArchive` manual con `.Replace('\','/')` + `UTF8Encoding($false)`.
- **Excluir** `assets/` (plantillas HTML/xlsx) y el propio ZIP del listado de archivos.
- **Backup** previo `.bak` antes de sobrescribir.

**Verificación obligatoria tras regenerar:**
```powershell
Add-Type -AssemblyName System.IO.Compression.FileSystem
$z = [System.IO.Compression.ZipFile]::OpenRead($dest)
$z.Entries.Count               # 41 o 48 según incluya assets
@($z.Entries.FullName | Where-Object { $_ -match '\\' }).Count   # DEBE ser 0
($z.Entries | Where-Object { $_.FullName -eq 'SKILL.md' }).Count -gt 0   # SKILL.md en raíz
@($z.Entries | Where-Object { $_.Name -match '\.(zip|skill)$' }).Count   # 0 (sin zip anidado)
```

**Reglas duras (NO romper):**
1. ZIP siempre en la carpeta del skill (`\.claude\skills\importacion-vehiculos\`) — NUNCA en Desktop.
2. Tras regenerar, verificar 0 backslashes en nombres de entrada.
3. NUNCA dejar el ZIP generado dentro del directorio fuente que se empaqueta — se auto-incluye y duplica el tamaño.
4. Si el usuario lo quiere en otro sitio para subirlo, él lo copia manualmente.
