#!/usr/bin/env python3
"""sync_indice.py — regenera indice.json del Desktop desde memoria/encargos.md.

El PASO 0 del skill lee `indice.json` (Desktop) para decidir si un modelo/cliente
ya fue investigado y cuándo refrescar. Este script mantiene ese índice en sync
con el registro maestro de encargos (`memoria/encargos.md`), que es la fuente
de verdad del skill.

Qué hace:
  1. Parsea `memoria/encargos.md` buscando entradas `### ...` con campos
     `- **Tipo:**`, `- **Resultado:**`, `- **Refrescar antes de:**`.
  2. Genera `indice.json` con una entrada por encargo: modelo, tipo, fecha,
     refrescar_antes_de, estado, resultado.

Uso:
    py sync_indice.py [--desktop <ruta>] [--dry-run]

  --desktop   ruta base del Desktop (default: C:/Users/<usuario>/Desktop/JJImportMotors)
  --dry-run   imprime el JSON sin escribirlo
"""

import json
import re
import sys
import os
from pathlib import Path
from datetime import date

SKILL_DIR = Path(__file__).resolve().parent.parent
ENCARGOS_MD = SKILL_DIR / "memoria" / "encargos.md"

HEADER_RE = re.compile(r"^### (.+)$")
FIELD_RE = re.compile(r"^- \*\*(Tipo|Resultado|Refrescar antes de):\*\*\s*(.+)$")


def parse_encargos() -> list[dict]:
    entries = []
    current = None
    in_codeblock = False
    for line in ENCARGOS_MD.read_text(encoding="utf-8").splitlines():
        s = line.strip()
        if s.startswith("```"):
            in_codeblock = not in_codeblock
            continue
        if in_codeblock:
            continue
        m = HEADER_RE.match(s)
        if m:
            if current:
                entries.append(current)
            # Ignorar plantilla de ejemplo (variables <...>)
            titulo = m.group(1).strip()
            if "<" in titulo:
                current = None
            else:
                current = {"titulo": titulo, "tipo": "", "resultado": "", "refrescar": ""}
            continue
        if current:
            m = FIELD_RE.match(s)
            if m:
                current[m.group(1).lower().replace(" ", "_")] = m.group(2).strip()
    if current:
        entries.append(current)
    return entries


def build_indice(entries: list[dict]) -> dict:
    hoy = date.today().isoformat()
    items = []
    for e in entries:
        refrescar = e.get("refrescar_antes_de", "").strip()
        # Limpiar "tipo": quedarse con lo anterior al primer separador '·'
        tipo = e.get("tipo", "").split("·")[0].strip()
        items.append({
            "modelo": e["titulo"],
            "tipo": tipo,
            "resultado": e.get("resultado", "").strip(),
            "refrescar_antes_de": refrescar,
            "estado": "reinvestigar" if (refrescar and refrescar < hoy) else "cache",
        })
    return {
        "generado": hoy,
        "fuente": "memoria/encargos.md (skill importacion-vehiculos)",
        "total": len(items),
        "encargos": items,
    }


def main() -> int:
    args = sys.argv[1:]
    dry_run = "--dry-run" in args
    desktop = None
    if "--desktop" in args:
        i = args.index("--desktop") + 1
        desktop = Path(args[i]).expanduser()
    else:
        desktop = Path.home() / "Desktop" / "JJImportMotors"

    if not ENCARGOS_MD.exists():
        print(f"🔴 No encuentro {ENCARGOS_MD}")
        return 1

    entries = parse_encargos()
    indice = build_indice(entries)
    out_path = desktop / "indice.json"

    if dry_run:
        print(json.dumps(indice, ensure_ascii=False, indent=2))
        return 0

    out_path.parent.mkdir(parents=True, exist_ok=True)
    out_path.write_text(json.dumps(indice, ensure_ascii=False, indent=2), encoding="utf-8")
    print(f"✅ indice.json generado en {out_path} ({len(entries)} encargos)")
    return 0


if __name__ == "__main__":
    sys.exit(main())
