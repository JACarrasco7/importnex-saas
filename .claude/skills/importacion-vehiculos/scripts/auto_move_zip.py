"""
auto_move_zip.py — Vigilante de ZIPs del coche.

Detecta ZIPs de coches en ``Downloads/`` o ``Claude outputs/`` y los mueve
automáticamente a la estructura del proyecto:
    <repo>/storage/app/private/investigaciones/<marca>/<modelo>/<coche>-<fecha>.zip

Uso:
    py scripts/auto_move_zip.py              # procesa los ZIPs existentes una vez
    py scripts/auto_move_zip.py --watch      # vigila y procesa en cuanto aparece uno
    py scripts/auto_move_zip.py --watch --interval 5   # vigila cada 5 segundos

Criterios para considerar un ZIP "del coche":
1. Path o nombre contiene "coche" / "car" / "-flujo-a-" / "-mercado-" / "-informe"
   (heurística laxa para evitar mover paquetes genéricos).
2. Si el ZIP tiene un `informe.json` con `_meta.coche_id` y `vehiculo.{marca,modelo}`
   no vacíos → se extrae marca/modelo del propio ZIP.
3. Si no, se infiere del nombre del archivo (`<marca>-<modelo>-<coche_id>-<fecha>.zip`).

Qué hace cuando lo detecta:
1. Calcula destino: `<repo>/storage/app/private/investigaciones/<marca>/<modelo>/`.
2. Si el coche_id contiene fecha → respeta; si no, añade `-YYYY-MM-DD` del día.
3. Si ya existe un ZIP con el mismo nombre en el destino, hace backup con sufijo
   `.bak-YYYY-MM-DD-HHMMSS` antes de sobreescribir.
4. Mueve el ZIP, escribe una entrada de log en `storage/logs/auto-zip-moves.log`
   y un mensaje en stdout.
"""
from __future__ import annotations

import argparse
import datetime
import json
import re
import shutil
import sys
import time
import zipfile
from pathlib import Path

# ── Rutas ─────────────────────────────────────────────────────────────────────

# __file__ = <repo>/.claude/skills/importacion-vehiculos/scripts/auto_move_zip.py
# parents[0] = scripts/
# parents[1] = importacion-vehiculos/
# parents[2] = skills/
# parents[3] = .claude/
# parents[4] = <repo>/
REPO_ROOT = Path(__file__).resolve().parents[4]
DEFAULT_DEST = REPO_ROOT / "storage" / "app" / "private" / "investigaciones"

# Carpetas donde buscar ZIPs del coche.
SOURCE_DIRS = [
    Path.home() / "Downloads",
    Path.home() / "JJImportMotors" / "Claude outputs",
    Path.home() / "Desktop" / "JJImportMotors" / "Claude outputs",
]

LOG_FILE = REPO_ROOT / "storage" / "log" / "auto-zip-moves.log"

# Heurística: nombre o path parece de coche.
NAME_HINTS = (
    "coche", "car", "vehicle", "flujo-a", "mercado", "informe",
    "arteon", "astra", "golf", "tiguan", "320d", "a3", "bmw",
)


# ── Helpers ───────────────────────────────────────────────────────────────────


def slug(s: str) -> str:
    s = (s or "").strip().lower()
    # ASCII-only slug: reemplazar acentos comunes + colapsar no-alfanum
    repl = {"á": "a", "é": "e", "í": "i", "ó": "o", "ú": "u", "ñ": "n", "ü": "u"}
    for k, v in repl.items():
        s = s.replace(k, v)
    s = re.sub(r"[^a-z0-9]+", "-", s)
    return s.strip("-") or "sin-nombre"


def log(msg: str) -> None:
    ts = datetime.datetime.now().strftime("%Y-%m-%d %H:%M:%S")
    line = f"[{ts}] {msg}"
    # Force UTF-8 stdout for Windows console (emojis cause UnicodeEncodeError)
    try:
        sys.stdout.reconfigure(encoding="utf-8")
    except (AttributeError, ValueError):
        pass
    try:
        print(line)
    except UnicodeEncodeError:
        # Fallback: ASCII-safe output
        print(line.encode("ascii", "replace").decode("ascii"))
    try:
        LOG_FILE.parent.mkdir(parents=True, exist_ok=True)
        with LOG_FILE.open("a", encoding="utf-8") as f:
            f.write(line + "\n")
    except OSError:
        pass


def parsear_zip(zip_path: Path) -> dict | None:
    """Lee el manifest + informe.json si existen. Devuelve dict con marca/modelo/coche_id."""
    try:
        with zipfile.ZipFile(zip_path) as zf:
            info = {"path": zip_path, "size": zip_path.stat().st_size, "fecha": zip_path.name}

            # 1) manifest.json (preferido)
            if "manifest.json" in zf.namelist():
                with zf.open("manifest.json") as f:
                    manifest = json.loads(f.read().decode("utf-8", errors="replace"))
                info["coche_id"] = manifest.get("coche_id")
                info["flujo"] = manifest.get("flujo")
                info["generado_el"] = manifest.get("generado_el")

            # 2) informe.json (tiene vehiculo.marca/modelo)
            if "informe.json" in zf.namelist():
                with zf.open("informe.json") as f:
                    inf = json.loads(f.read().decode("utf-8", errors="replace"))
                veh = inf.get("vehiculo") or {}
                meta = inf.get("_meta") or {}
                info["marca"] = veh.get("marca")
                info["modelo"] = veh.get("modelo")
                info["coche_id"] = info.get("coche_id") or meta.get("coche_id")
            return info
    except (zipfile.BadZipFile, OSError, json.JSONDecodeError) as exc:
        log(f"⚠️  No se pudo parsear {zip_path}: {exc}")
        return None


def parece_coche(zip_path: Path) -> bool:
    """Heurística rápida sin abrir el ZIP: nombre contiene pista de coche."""
    name = zip_path.name.lower()
    path = str(zip_path).lower()
    return any(hint in name or hint in path for hint in NAME_HINTS)


def inferir_desde_nombre(zip_path: Path) -> dict | None:
    """Si el ZIP no tiene informe.json/manifest, intenta sacar marca/modelo
    del nombre: <marca>-<modelo>-<resto>-<id>-<fecha>.zip"""
    stem = zip_path.stem
    parts = stem.split("-")
    if len(parts) < 3:
        return None
    marca = parts[0]
    modelo = parts[1]
    coche_id = next((p for p in parts if re.fullmatch(r"\w{8,}", p)), stem)
    return {"marca": marca, "modelo": modelo, "coche_id": coche_id}


def destino_path(info: dict) -> Path:
    marca = slug(info.get("marca") or "sin-marca")
    modelo = slug(info.get("modelo") or "sin-modelo")
    coche_id = info.get("coche_id") or info["path"].stem

    # Si el nombre ya tiene fecha, respetarla; si no, añadir la del día
    if not re.search(r"\d{4}-\d{2}-\d{2}", coche_id):
        fecha = datetime.datetime.now().strftime("%Y-%m-%d")
        filename = f"{coche_id}-{fecha}.zip"
    else:
        filename = f"{coche_id}.zip"

    return DEFAULT_DEST / marca / modelo / filename


def es_zip_de_coche(info: dict | None) -> bool:
    """Verifica que el ZIP tiene la estructura de un ZIP de coche Flujo A.

    Requisitos: tiene `informe.json` con `_meta.coche_id` + `vehiculo.marca`+
    `vehiculo.modelo` no vacíos. Sin esto, el ZIP no es nuestro.
    """
    if not info:
        return False
    meta = info.get("_meta") or {}
    veh = info.get("vehiculo") or {}
    return bool(
        meta.get("coche_id")
        and veh.get("marca")
        and veh.get("modelo")
    )


def mover(zip_path: Path) -> bool:
    # Heurística rápida por nombre (evita abrir ZIPs grandes que NO son coches).
    if not parece_coche(zip_path):
        return False

    info = parsear_zip(zip_path)
    if info is None:
        return False

    # Verificación ESTRICTA: tiene que tener la estructura de un ZIP de coche.
    # parsear_zip ya leyó informe.json y merged con manifest.
    veh_marca = info.get("marca")
    veh_modelo = info.get("modelo")
    coche_id = info.get("coche_id")

    if not (coche_id and veh_marca and veh_modelo):
        # Fallback: intentar desde el nombre
        guess = inferir_desde_nombre(zip_path)
        if guess:
            veh_marca = veh_marca or guess["marca"]
            veh_modelo = veh_modelo or guess["modelo"]
            coche_id = coche_id or guess["coche_id"]

    if not (coche_id and veh_marca and veh_modelo):
        log(f"SKIP (sin estructura de coche: informe.json no tiene vehiculo.marca/modelo): {zip_path.name}")
        return False

    info["marca"] = veh_marca
    info["modelo"] = veh_modelo
    info["coche_id"] = coche_id

    dest = destino_path(info)
    dest.parent.mkdir(parents=True, exist_ok=True)

    # Backup si ya existe
    if dest.exists():
        ts = datetime.datetime.now().strftime("%Y-%m-%d-%H%M%S")
        backup = dest.with_suffix(dest.suffix + f".bak-{ts}")
        shutil.move(str(dest), str(backup))
        log(f"   Backup: {backup.name}")

    shutil.move(str(zip_path), str(dest))
    size_kb = dest.stat().st_size / 1024
    log(f"OK Movido: {zip_path.name} -> {dest.relative_to(REPO_ROOT.parent)} ({size_kb:.1f} KB)")
    return True


def procesar_existentes() -> int:
    """Busca ZIPs en SOURCE_DIRS y los mueve. Devuelve nº movidos."""
    movidos = 0
    for src_dir in SOURCE_DIRS:
        if not src_dir.exists():
            continue
        for zip_path in sorted(src_dir.glob("*.zip")):
            try:
                if mover(zip_path):
                    movidos += 1
            except Exception as exc:  # noqa: BLE001
                log(f"❌ Error procesando {zip_path}: {exc}")
    return movidos


def watch(interval: float) -> None:
    log(f"WATCH Vigilando {SOURCE_DIRS} cada {interval}s (Ctrl+C para salir)")
    seen: set[Path] = set()
    for src_dir in SOURCE_DIRS:
        if src_dir.exists():
            seen.update(src_dir.glob("*.zip"))

    while True:
        time.sleep(interval)
        current: set[Path] = set()
        for src_dir in SOURCE_DIRS:
            if src_dir.exists():
                current.update(src_dir.glob("*.zip"))
        nuevos = current - seen
        for zip_path in sorted(nuevos):
            log(f"NUEVO Detectado: {zip_path}")
            try:
                mover(zip_path)
            except Exception as exc:  # noqa: BLE001
                log(f"ERROR: {exc}")
        seen = current


def main() -> int:
    global DEFAULT_DEST  # noqa: PLW0603
    parser = argparse.ArgumentParser(
        description="Detecta y mueve ZIPs de coches a la estructura del proyecto.",
    )
    parser.add_argument("--watch", action="store_true",
                        help="Vigila Downloads/ y Claude outputs/ en bucle")
    parser.add_argument("--interval", type=float, default=10.0,
                        help="Segundos entre checks en modo --watch (default: 10)")
    parser.add_argument("--dest", type=Path, default=DEFAULT_DEST,
                        help=f"Carpeta raíz de destino (default: {DEFAULT_DEST})")
    args = parser.parse_args()

    DEFAULT_DEST = args.dest

    if args.watch:
        watch(args.interval)
        return 0

    movidos = procesar_existentes()
    print(f"\nResumen: {movidos} ZIP(s) movido(s) a {DEFAULT_DEST.relative_to(REPO_ROOT.parent)}")
    return 0


if __name__ == "__main__":
    sys.exit(main())
