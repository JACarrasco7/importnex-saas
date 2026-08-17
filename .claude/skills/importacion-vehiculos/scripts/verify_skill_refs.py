#!/usr/bin/env python3
"""verify_skill_refs.py — valida referencias cruzadas del skill importacion-vehiculos.

Recorre todos los .md del skill, extrae las referencias entre backticks que
contienen `.md`, resuelve la ruta relativa al archivo que la contiene y verifica
que el archivo destino EXISTE. Reporta referencias rotas (huérfanas).

Uso:
    py verify_skill_refs.py                # desde la carpeta del skill
    py verify_skill_refs.py --base <ruta>  # base explícita

Salida: lista de OK/ROTA + resumen. Exit code 0 si no hay rotas, 1 si las hay.
"""

import re
import sys
import os
from pathlib import Path

# Patrón: `... .md` o `... .md §seccion` o `[texto](... .md ...)`
BACKTICK_REF = re.compile(r"`([^`]*?\.md)`")
LINK_REF = re.compile(r"\]\(([^)]*?\.md)(?:#[^)]*)?\)")

# Extensiones de archivo que se consideran "destino" de una referencia
VALID_EXTS = {".md"}

# Prefijos/nombres EXTERNOS al skill (proyecto Desktop, repo Laravel) — se ignoran.
# El skill convive con otros archivos fuera de su carpeta; no son "rotas".
EXTERNAL_PREFIXES = (
    ".claude/",          # memoria del proyecto Desktop
    "docs/",             # documentación del repo Laravel
    "assets/",           # plantillas HTML + xlsx (excluidas del ZIP)
    "INSTRUCCIONES_PROYECTO.md",
    "copilot-instructions.md",
    "CLAUDE.md",
    "README.md",
    "preferencias.md",
    "BRAND.md",
)

# Nombres de guías del Desktop (01-... a 07-...) que viven fuera del skill
EXTERNAL_GUIDE_PREFIXES = tuple(f"0{i}-" for i in range(1, 8))


def is_external(ref: str) -> bool:
    """True si la referencia apunta fuera del skill (no debe validarse)."""
    r = ref.strip().replace("\\", "/")
    if r == ".md" or not r:
        return True
    if r.startswith(EXTERNAL_PREFIXES):
        return True
    if r.startswith(EXTERNAL_GUIDE_PREFIXES):
        return True
    # Autoreferencias absolutas .claude/skills/... (rutas de instalación)
    if r.startswith(".claude/skills/"):
        return True
    # Memoria del proyecto Desktop (no del skill), escrita sin prefijo .claude/
    if r.startswith("memoria/preferencias.md") or r.startswith("memoria/decisiones.md") \
       or r.startswith("memoria/errores-pasados.md") or r.startswith("memoria/memoria-larga.md"):
        return True
    # Wildcards y variables
    if "*" in r or "<" in r or ">" in r:
        return True
    return False


def find_references(text: str) -> list[str]:
    """Extrae referencias a .md de un texto (backticks y links markdown)."""
    refs = []
    for m in BACKTICK_REF.finditer(text):
        refs.append(m.group(1).strip())
    for m in LINK_REF.finditer(text):
        refs.append(m.group(1).strip())
    return refs


def resolve_ref(base_dir: Path, ref: str) -> Path | None:
    """Resuelve una referencia a una ruta absoluta, si es local al skill."""
    # Descarta URLs externas
    if "://" in ref or ref.startswith("http"):
        return None
    if is_external(ref):
        return None
    # Quita secciones/anclas
    ref = ref.split("#")[0].split("§")[0].strip()
    # Descarta variables tipo <...>
    if "<" in ref or ">" in ref:
        return None
    # Normaliza backslashes
    ref = ref.replace("\\", "/")
    candidate = (base_dir / ref).resolve()
    return candidate


def main() -> int:
    base = Path(sys.argv[1]).resolve() if len(sys.argv) > 1 else Path(__file__).resolve().parent.parent

    md_files = sorted(base.rglob("*.md"))
    # Excluir assets (plantillas HTML) y el propio ZIP si estuviera
    md_files = [f for f in md_files if "assets" not in str(f)]

    broken = []
    checked = 0

    for f in md_files:
        text = f.read_text(encoding="utf-8", errors="replace")
        refs = find_references(text)
        for ref in refs:
            target = resolve_ref(f.parent, ref)
            if target is None:
                continue
            checked += 1
            if not target.exists():
                broken.append((str(f.relative_to(base)), ref))

    print(f"Archivos .md escaneados: {len(md_files)}")
    print(f"Referencias locales comprobadas: {checked}")

    if broken:
        print(f"\n🔴 {len(broken)} REFERENCIAS ROTAS:")
        for src, ref in broken:
            print(f"  - {src}  →  `{ref}`  (NO existe)")
        return 1

    print("\n✅ Todas las referencias cruzadas apuntan a archivos existentes.")
    return 0


if __name__ == "__main__":
    sys.exit(main())
