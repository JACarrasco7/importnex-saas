#!/usr/bin/env python3
# -*- coding: utf-8 -*-
"""
empaquetar.py — Genera el ZIP para Laravel desde un JSON de contrato (Flujo A).

Uso:
    python empaquetar.py export/flujo-a-<coche_id>.json
    python empaquetar.py export/flujo-a-<coche_id>.json --out paquetes/
    python empaquetar.py export/flujo-a-<coche_id>.json --strict
    python empaquetar.py export/flujo-a-<coche_id>.json --no-photos  # sólo esqueletos

Reglas duras (15-ago-2026 + 03-sep-2026):
- FOTOS: SIEMPRE descargadas de la URL del anuncio (vehiculo.fotos[]),
  con UA navegador + Referer del anuncio. NUNCA capturas de pantalla.
- MARKETING: SIEMPRE se generan contenido/redes-sociales.txt y
  contenido/anuncio-portales.txt (Laravel los importa a CarMarketingContent).
- paquete_version: 2 (contenido/*.txt en vez de documentos/*.pdf + publicidad/*.pdf).
- Validación dura de fotos en modo --strict: falla si 0 fotos válidas.
- Validación mínima en modo normal: warning si <3 fotos.

Estructura del ZIP:

  [coche_id].zip
  ├── informe.json
  ├── manifest.json
  ├── contenido/
  │   ├── ficha-publicitaria.txt
  │   ├── informe-interno.txt
  │   ├── dossier-cliente.txt       (solo si veredicto Comprar*)
  │   ├── redes-sociales.txt        (NUEVO 03-sep-2026)
  │   └── anuncio-portales.txt      (NUEVO 03-sep-2026)
  └── fotos/
      ├── 001.jpg
      ├── 002.jpg
      └── ...

Sin dependencias externas: solo stdlib (urllib, json, zipfile, hashlib, argparse).
"""
from __future__ import annotations

import argparse
import datetime
import hashlib
import json
import os
import re
import sys
import time
import unicodedata
import urllib.error
import urllib.request
import zipfile
from pathlib import Path
from urllib.parse import quote_plus

# ── Constantes ────────────────────────────────────────────────────────────────

SCHEMA_VERSION = 1
PACKAGE_VERSION = 2
PAQUETES_DIRNAME = "paquetes"
USER_AGENT = (
    "Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 "
    "(KHTML, like Gecko) Chrome/127.0.0.0 Safari/537.36"
)
TIMEOUT_S = 25
MAX_PHOTO_BYTES = 12 * 1024 * 1024  # 12 MB tope por foto
MIN_PHOTO_BYTES = 1024              # 1 KB mínimo
MIN_PHOTOS_NORMAL = 3
MIN_PHOTOS_STRICT = 5
IMAGE_EXTS = {".jpg", ".jpeg", ".png", ".webp", ".avif", ".gif"}

AVISO_LEGAL_DEFAULT = (
    "Servicio de gestión de importación. El vehículo se importa y matricula "
    "a nombre del cliente; JJ Import Motors actúa como gestor de importación, "
    "no como vendedor del vehículo. Precio sujeto a confirmación de emisiones "
    "(CO2/COC) y a disponibilidad del vehículo en origen."
)

# ── Utilidades ────────────────────────────────────────────────────────────────


def log(emoji: str, msg: str) -> None:
    """Log con emoji prefijo (compatible Windows cp1252 fallback a utf-8)."""
    line = f"{emoji}  {msg}"
    try:
        print(line, flush=True)
    except UnicodeEncodeError:
        line = line.encode("ascii", "replace").decode("ascii")
        print(line, flush=True)


def warn(msg: str) -> None:
    log("⚠️ ", msg)


def info(msg: str) -> None:
    log("ℹ️ ", msg)


def ok(msg: str) -> None:
    log("✅", msg)


def fail(msg: str) -> None:
    log("❌", msg)


def run_validator(script: Path, args: list[str], cwd: Path) -> list[str]:
    """Ejecuta un validador (check_marketing.py o check_ficha_cliente.py) y
    devuelve los hallazgos CRÍTICOS como strings legibles.

    Si el primer argumento de `args` apunta a un .zip, lo extrae a un
    directorio temporal y pasa a los checks los .txt que necesiten
    (los checks NO leen .zip directamente). Si apuntan a un .txt, lo pasa
    tal cual.

    Consideramos CRÍTICO cualquier línea con 🔴. Si el validador retorna
    código != 0 y la línea tiene ❌, también.
    """
    try:
        import subprocess  # noqa: PLC0415
        import tempfile  # noqa: PLC0415
        import zipfile  # noqa: PLC0415
    except ImportError:
        warn("subprocess/tempfile/zipfile no disponible — saltando validación")
        return []

    # Si el validador recibe un ZIP, extraer los .txt que espera.
    final_args = list(args)
    if final_args and final_args[0].lower().endswith(".zip"):
        zip_path = Path(final_args[0])
        if zip_path.exists():
            tmp_dir = Path(tempfile.mkdtemp(prefix="val-"))
            try:
                with zipfile.ZipFile(zip_path) as zf:
                    for member in zf.namelist():
                        if member.endswith((".txt", ".json")) and not member.endswith("/"):
                            zf.extract(member, tmp_dir)
                # check_marketing.py espera leer un .txt; le pasamos el más
                # relevante (redes-sociales.txt o ficha-cliente.txt).
                candidatos = ["redes-sociales.txt", "ficha-cliente.txt", "ficha-publicitaria.txt"]
                target = None
                for nombre in candidatos:
                    posible = tmp_dir / "contenido" / nombre
                    if posible.exists():
                        target = str(posible)
                        break
                # Si no encontramos un .txt específico, le pasamos el ZIP
                # original — algunos checks (check_ficha_cliente.py) leen el ZIP.
                if target is not None:
                    final_args = [target, *final_args[1:]]
            except (OSError, zipfile.BadZipFile) as exc:
                warn(f"No se pudo extraer ZIP para validar: {exc}")

    try:
        proc = subprocess.run(
            [sys.executable, str(script), *final_args],
            cwd=str(cwd),
            capture_output=True,
            text=True,
            encoding="utf-8",
            timeout=60,
        )
    except subprocess.TimeoutExpired:
        warn(f"{script.name} excedió 60s — saltando")
        return []
    except (OSError, ValueError) as exc:
        warn(f"No se pudo ejecutar {script.name}: {exc}")
        return []

    output = (proc.stdout or "") + "\n" + (proc.stderr or "")
    criticos: list[str] = []
    for linea in output.splitlines():
        stripped = linea.strip()
        if not stripped:
            continue
        if "🔴" in stripped:
            criticos.append(stripped)
        elif proc.returncode != 0 and "❌" in stripped:
            criticos.append(stripped)
    return criticos


def safe_filename(name: str) -> str:
    """Igual que ValuationPackageIngestor::safeFilename de Laravel."""
    cleaned = re.sub(r"[^A-Za-z0-9._-]+", "-", name)
    return cleaned.strip("-") or "archivo"


def write_block(path: Path, lines: list[str]) -> None:
    """Escribe líneas UTF-8 + salto final (PHP fgets prefiere \n)."""
    path.parent.mkdir(parents=True, exist_ok=True)
    with path.open("w", encoding="utf-8", newline="\n") as fh:
        for line in lines:
            fh.write(line)
            if not line.endswith("\n"):
                fh.write("\n")


def fmt_eur(value) -> str:
    """12345.6 → '12.346 €'."""
    try:
        n = float(value)
    except (TypeError, ValueError):
        return str(value)
    return f"{n:,.0f} €".replace(",", ".")


def fmt_pct(value) -> str:
    """17.4 → '17,4 %'."""
    try:
        n = float(value)
    except (TypeError, ValueError):
        return str(value)
    s = f"{n:.1f}".replace(".", ",")
    return f"{s} %"


def join_inline(*parts: str, sep: str = " | ") -> str:
    return sep.join(p for p in parts if p)


# ── Carga del JSON ────────────────────────────────────────────────────────────


def load_payload(json_path: Path) -> dict:
    if not json_path.is_file():
        fail(f"No existe el archivo: {json_path}")
        sys.exit(2)
    try:
        data = json.loads(json_path.read_text(encoding="utf-8"))
    except json.JSONDecodeError as e:
        fail(f"JSON inválido: {e}")
        sys.exit(2)
    if "_meta" not in data:
        fail("Falta _meta en el JSON.")
        sys.exit(2)
    if data["_meta"].get("schema_version") != SCHEMA_VERSION:
        warn(
            f"schema_version esperado {SCHEMA_VERSION}, "
            f"recibido {data['_meta'].get('schema_version')}"
        )
    return data


def derive_coche_id(payload: dict) -> str:
    return payload.get("_meta", {}).get("coche_id") or "coche-sin-id"


def derive_auto_path(payload: dict, base: Path | None = None) -> Path:
    """Ruta canonica Desktop/JJImportMotors/investigaciones/<marca>/<modelo>/.

    Marca y modelo vienen de vehiculo.marca + vehiculo.modelo (normalizados a slug).
    Si base es None, usa ~/Desktop/JJImportMotors/investigaciones.
    """
    veh = payload.get("vehiculo") or {}
    marca = (veh.get("marca") or "").strip().lower().replace(" ", "-")
    modelo = (veh.get("modelo") or "").strip().lower().replace(" ", "-")
    if not marca:
        marca = "sin-marca"
    if not modelo:
        modelo = "sin-modelo"

    if base is None:
        if os.name == "nt":
            base = Path(os.environ["USERPROFILE"]) / "Desktop" / "JJImportMotors" / "investigaciones"
        else:
            base = Path.home() / "Desktop" / "JJImportMotors" / "investigaciones"
    return base / marca / modelo


def find_project_root(start: Path | None = None) -> Path:
    """Encuentra la raíz del proyecto Laravel (donde está artisan).

    Busca hacia arriba desde `start` (default: cwd) hasta encontrar un
    directorio que contenga `artisan` + `composer.json`. Si no encuentra,
    devuelve el cwd.
    """
    cur = Path(start or Path.cwd()).resolve()
    for _ in range(10):
        if (cur / "artisan").is_file() and (cur / "composer.json").is_file():
            return cur
        if cur.parent == cur:
            break
        cur = cur.parent
    return Path.cwd().resolve()


def derive_laravel_storage_path(payload: dict, base: Path | None = None) -> Path:
    """Ruta canonica del proyecto Laravel: <root>/storage/app/private/investigaciones/<marca>/<modelo>/.

    Esta es la ruta preferida cuando se ejecuta desde el repo del proyecto
    (no contaminar `C:\\Users\\jacar\\Downloads`).
    """
    veh = payload.get("vehiculo") or {}
    marca = (veh.get("marca") or "").strip().lower().replace(" ", "-")
    modelo = (veh.get("modelo") or "").strip().lower().replace(" ", "-")
    if not marca:
        marca = "sin-marca"
    if not modelo:
        modelo = "sin-modelo"

    if base is None:
        root = find_project_root()
        base = root / "storage" / "app" / "private" / "investigaciones"
    return base / marca / modelo


def output_zip_path(payload: dict, out_dir: Path, with_date: bool = False) -> Path:
    coche_id = derive_coche_id(payload)
    if with_date:
        fecha = datetime.datetime.now().strftime("%Y-%m-%d")
        return out_dir / f"{coche_id}-{fecha}.zip"
    return out_dir / f"{coche_id}.zip"


# ── Descarga de fotos ─────────────────────────────────────────────────────────


def detect_image_ext(content_type: str, url: str) -> str:
    ct = (content_type or "").lower().split(";")[0].strip()
    mapping = {
        "image/jpeg": ".jpg",
        "image/jpg": ".jpg",
        "image/png": ".png",
        "image/webp": ".webp",
        "image/avif": ".avif",
        "image/gif": ".gif",
    }
    if ct in mapping:
        return mapping[ct]
    # fallback: extensión de la URL
    path = urllib.parse.urlparse(url).path.lower()
    for ext in IMAGE_EXTS:
        if path.endswith(ext):
            return ext
    return ".jpg"


def download_photo(
    url: str,
    referer: str | None,
    dest: Path,
) -> tuple[bool, str]:
    """Descarga una foto. Devuelve (ok, mensaje). NUNCA captura."""
    if not url or not url.lower().startswith(("http://", "https://")):
        return False, "URL vacía o no http(s)"

    headers = {"User-Agent": USER_AGENT, "Accept": "image/*,*/*;q=0.8"}
    if referer:
        headers["Referer"] = referer

    req = urllib.request.Request(url, headers=headers)
    try:
        with urllib.request.urlopen(req, timeout=TIMEOUT_S) as resp:
            status = resp.status
            ct = resp.headers.get("Content-Type", "")
            data = resp.read(MAX_PHOTO_BYTES + 1)
    except urllib.error.HTTPError as e:
        return False, f"HTTP {e.code}"
    except urllib.error.URLError as e:
        return False, f"URL error: {e.reason}"
    except TimeoutError:
        return False, "timeout"
    except Exception as e:  # noqa: BLE001
        return False, f"error: {type(e).__name__} {e}"

    if status != 200:
        return False, f"HTTP {status}"

    if len(data) > MAX_PHOTO_BYTES:
        return False, f"> {MAX_PHOTO_BYTES // (1024*1024)} MB"

    if not ct.lower().startswith("image/"):
        return False, f"Content-Type no es imagen: {ct!r}"

    if len(data) < MIN_PHOTO_BYTES:
        return False, f"< {MIN_PHOTO_BYTES} bytes"

    dest.parent.mkdir(parents=True, exist_ok=True)
    dest.write_bytes(data)
    return True, f"{len(data)//1024} KB · {ct.split(';')[0]}"


def collect_photos(
    payload: dict,
    fotos_dir: Path,
    strict: bool,
    skip_photos: bool,
) -> tuple[list[dict], list[str]]:
    """
    Descarga las fotos del anuncio. Devuelve (fotos_ok, warnings).
    NUNCA sustituye por capturas: si falla, registra warning y continúa.
    """
    vehiculo = payload.get("vehiculo") or {}
    urls = vehiculo.get("fotos") or []
    anuncio = payload.get("anuncio") or {}
    referer = anuncio.get("url") or ""

    warnings: list[str] = []
    saved: list[dict] = []
    seen_hashes: set[str] = set()

    if skip_photos:
        warn("--no-photos: se omite la descarga (no válido para entrega)")
        return saved, warnings

    if not urls:
        msg = "Sin URLs en vehiculo.fotos[] (no se descargó ninguna foto)"
        if strict:
            fail(msg + " — modo --strict aborta")
            sys.exit(3)
        warn(msg)
        return saved, warnings

    info(f"Descargando {len(urls)} foto(s) del anuncio…")
    for idx, raw_url in enumerate(urls, start=1):
        # Aceptar tanto string como {url:..., tipo:...}
        url = raw_url if isinstance(raw_url, str) else raw_url.get("url", "")
        if not url:
            warnings.append(f"foto #{idx}: URL vacía")
            continue

        # Bajada provisional a /tmp para inspeccionar Content-Type
        tmp_path = fotos_dir / f"_tmp_{idx:03d}"
        ok_dl, motivo = download_photo(url, referer, tmp_path)
        if not ok_dl:
            warnings.append(f"foto #{idx} ({url[:80]}…): {motivo}")
            try:
                tmp_path.unlink(missing_ok=True)
            except OSError:
                pass
            continue

        # Dedup por hash
        h = hashlib.sha256(tmp_path.read_bytes()).hexdigest()[:16]
        if h in seen_hashes:
            warnings.append(f"foto #{idx}: duplicada (sha256:{h}) — descartada")
            tmp_path.unlink(missing_ok=True)
            continue
        seen_hashes.add(h)

        ext = detect_image_ext(
            "", tmp_path.read_bytes()[:0]  # content-type ya consumido
        )
        # Mejor: detectar desde URL por consistencia
        url_ext = Path(urllib.parse.urlparse(url).path).suffix.lower()
        ext = url_ext if url_ext in IMAGE_EXTS else ".jpg"

        final = fotos_dir / f"{idx:03d}{ext}"
        tmp_path.rename(final)

        saved.append({
            "archivo": f"fotos/{final.name}",
            "orden": idx,
            "categoria": "exterior",
            "sha256": h,
        })
        ok(f"foto {idx:02d}/{len(urls)}: {final.name} ({motivo})")

    # B6 auditoría 09-sep-2026: 0 fotos NO es inválido para entrega si
    # vehiculo.fotos[] tiene URLs — Laravel las descarga él mismo desde
    # ValuationImporter::savePhotos(). Con --strict, abortamos SOLO si
    # tampoco hay URLs en el payload para que Laravel descargue.
    vehiculo_fotos = ((payload.get("vehiculo") or {}).get("fotos") or [])
    vehiculo_fotos = [u for u in vehiculo_fotos if isinstance(u, str) and u.strip()]

    if not saved:
        if strict and not vehiculo_fotos:
            fail("0 fotos válidas y vehiculo.fotos[] vacío — modo --strict aborta")
            sys.exit(3)
        warn("0 fotos válidas en el ZIP; Laravel descargará desde vehiculo.fotos[] si hay URLs.")
    elif len(saved) < MIN_PHOTOS_NORMAL:
        warn(f"Solo {len(saved)} fotos válidas (< {MIN_PHOTOS_NORMAL} mínimas)")
    elif len(saved) < MIN_PHOTOS_STRICT and strict:
        warn(
            f"Solo {len(saved)} fotos (< {MIN_PHOTOS_STRICT} en --strict). "
            "Considere añadir más antes de subir."
        )

    return saved, warnings


# ── Bloques comunes ───────────────────────────────────────────────────────────


def bloque(nombre: str, valor: str | None) -> str:
    """[NOMBRE] valor  (una línea, valor sanitizado)."""
    v = (valor or "").strip()
    if not v:
        return ""
    # Evitar saltos de línea dentro de un bloque inline
    v = v.replace("\r", " ").replace("\n", " ")
    return f"[{nombre}] {v}"


def bloque_multi(nombre: str, valor: str | None) -> list[str]:
    """[NOMBRE] texto multilínea (continuación en líneas siguientes)."""
    v = (valor or "").strip()
    if not v:
        return []
    lines = [f"[{nombre}] {v.splitlines()[0]}"]
    for cont in v.splitlines()[1:]:
        lines.append(cont.rstrip())
    return lines


def gastos_cliente(cost: dict) -> list[tuple[str, float]]:
    """Agrupa `payload.costes` (esquema en 03-informes/contrato.md § costes) en
    las líneas que puede ver el cliente — regla dura nº3: nunca "honorarios"
    como línea propia, siempre fundido en la gestión (ni tampoco "margen").

    Devuelve una lista de (concepto, importe) con solo las líneas que tienen
    importe > 0. Si `cost` está vacío, devuelve [] (A18: nunca se inventan
    gastos — que Laravel caiga a su propia estimación es mejor que mostrar
    ceros o partidas ficticias).

    Fuente preferida sobre la que Laravel calcula el desglose del cliente
    (`PrecioClienteCalculator::desgloseDeSkill()`), vía los bloques
    `[GASTO]`/`[FC_GASTO]` que emiten `generar_ficha_publicitaria()` y
    `generar_ficha_cliente()`.
    """
    if not cost:
        return []

    transporte = float(cost.get("transporte") or 0)
    exportacion = float(cost.get("itv_matriculacion") or 0) + float(cost.get("tasa_dgt") or 0) + float(cost.get("otros") or 0)
    iedmt = float(cost.get("iedmt_estimado") or cost.get("iedmt") or 0)
    gestion = float(cost.get("gestoria") or 0) + float(cost.get("honorarios") or 0)

    lineas = [
        ("Transporte hasta España", transporte),
        ("Trámites de exportación e ITV de importación", exportacion),
        ("Impuesto de matriculación (IEDMT)", iedmt),
        ("Gestión y matriculación", gestion),
    ]
    return [(concepto, importe) for concepto, importe in lineas if importe > 0]


# ── Generadores de .txt ───────────────────────────────────────────────────────


def generar_ficha_publicitaria(payload: dict) -> list[str]:
    """ficha-publicitaria.txt — alimenta ficha-coche.blade.php + folleto."""
    veh = payload.get("vehiculo") or {}
    anun = payload.get("anuncio") or {}
    ver = payload.get("veredicto") or {}
    cost = payload.get("costes") or {}
    pub = payload.get("publicidad") or {}
    merc = payload.get("mercado") or {}

    lines: list[str] = []

    # Cabecera documental
    lines += [
        f"# Ficha publicitaria — {veh.get('marca', '')} {veh.get('modelo', '')}".rstrip(),
        bloque("TITULO", pub.get("titular")
               or f"{veh.get('marca', '')} {veh.get('modelo', '')} {veh.get('version', '')}".strip()),
        bloque("CLAIM", pub.get("claim") or ""),
    ]

    # Etiqueta DGT (mapeada desde etiqueta_ambiental si está)
    inv = payload.get("investigacion") or {}
    etq = inv.get("etiqueta_ambiental", {}).get("etiqueta") if isinstance(inv.get("etiqueta_ambiental"), dict) else None
    if etq:
        lines.append(bloque("ETIQUETA_DGT", etq))

    # SPEC: pares Etiqueta | Valor
    spec_pairs = [
        ("Año", str(veh.get("anio") or "")),
        ("Kilómetros", f"{veh.get('km', 0):,}".replace(",", ".") + " km" if veh.get("km") else ""),
        ("Combustible", veh.get("combustible") or ""),
        ("Cambio", veh.get("cambio") or ""),
        ("Potencia", f"{veh.get('potencia_cv')} CV" if veh.get("potencia_cv") else ""),
        ("Etiqueta", etq or ""),
    ]
    for etiqueta, valor in spec_pairs:
        if valor:
            lines.append(bloque("SPEC", f"{etiqueta} | {valor}"))

    # Precio
    precio = cost.get("coste_total") or anun.get("precio_negociado") or anun.get("precio_publicado")
    if precio:
        lines.append(bloque("PRECIO", fmt_eur(precio)))
        lines.append(bloque("PRECIO_CAPTION", "puesto en Huelva · honorarios incluidos"))
        for concepto, importe in gastos_cliente(cost):
            lines.append(bloque("GASTO", f"{concepto} | {fmt_eur(importe)}"))

    # Plazo entrega
    plazo = "4-6 semanas desde la reserva"
    lines.append(bloque("PLAZO", plazo))

    # AHORRO vs mercado
    if merc.get("nuestra_oferta") and merc.get("precio_medio"):
        ahorro_eur = merc["precio_medio"] - merc["nuestra_oferta"]
        if ahorro_eur > 0:
            lines.append(bloque("AHORRO", f"{fmt_eur(ahorro_eur)} vs mercado ES"))

    # DESCRIPCION (60-120 palabras) — solo si viene redactada en publicidad
    if pub.get("descripcion"):
        lines.append("")
        lines += bloque_multi("DESCRIPCION", pub["descripcion"])

    # POR_QUE (2-4 frases para el cliente)
    if pub.get("por_que"):
        lines.append("")
        lines += bloque_multi("POR_QUE", pub["por_que"])

    # VALORACION (1-2 frases para el folleto)
    if pub.get("valoracion"):
        lines.append("")
        lines += bloque_multi("VALORACION", pub["valoracion"])

    # Secciones dinámicas H2 + INCLUYE/ARGUMENTO/EQUIPAMIENTO
    if pub.get("argumentos"):
        lines.append("")
        lines.append("[H2] Por qué lo recomendamos")
        for arg in pub["argumentos"]:
            lines.append(bloque("ARGUMENTO", arg))

    if pub.get("incluye"):
        lines.append("")
        lines.append("[H2] Qué incluye el servicio")
        for inc in pub["incluye"]:
            lines.append(bloque("INCLUYE", inc))

    if veh.get("equipamiento"):
        lines.append("")
        lines.append("[H2] Equipamiento destacado")
        for eq in veh["equipamiento"][:15]:  # top 15 para el folleto
            lines.append(bloque("EQUIPAMIENTO", eq))

    # Cierre (CTA + contacto + QR + legal)
    lines.append("")
    lines += [
        bloque("CTA", "Reserva con 1.000 € — bloqueamos la unidad"),
        bloque("CONTACTO", "JJ Import Motors · 675 70 14 39 · jjimportmotors@gmail.com"),
        bloque("QR", "/img/qr-jj-import.png"),
        bloque("QR_TEXTO", "Escanea para ver el vídeo de inspección"),
        bloque("LEGAL", AVISO_LEGAL_DEFAULT),
    ]

    # Quitar líneas vacías duplicadas
    return _clean_lines(lines)


def generar_informe_interno(payload: dict) -> list[str]:
    """informe-interno.txt — alimenta informe-interno.blade.php (equipo)."""
    veh = payload.get("vehiculo") or {}
    anun = payload.get("anuncio") or {}
    ver = payload.get("veredicto") or {}
    bal = payload.get("balance") or {}
    cost = payload.get("costes") or {}
    merc = payload.get("mercado") or {}
    inv = payload.get("investigacion") or {}
    meta = payload.get("_meta") or {}

    lines: list[str] = []

    coche_id = meta.get("coche_id") or "coche"
    fecha = meta.get("generado_el", "")[:10] or time.strftime("%Y-%m-%d")
    reco = ver.get("recomendacion", "")
    score = (payload.get("extras") or {}).get("score_global", "")

    # Cabecera documental
    lines += [
        f"# Informe interno — {veh.get('marca', '')} {veh.get('modelo', '')}".rstrip(),
        bloque("COCHE_ID", coche_id),
        bloque("FECHA_INFORME", fecha),
        bloque("VALIDO_HASTA", "7 días desde generación"),
        bloque("FLUJO", meta.get("flujo", "A")),
        bloque("SCORE_GLOBAL", str(score) if score != "" else ""),
        bloque("RECOMENDACION", reco),
        bloque("ORIGEN", f"{anun.get('pais_origen', '?')} ({anun.get('ciudad', '')})".strip(" ()")),
        bloque("VIN", veh.get("vin") or "—"),
        bloque("URL_ANUNCIO", anun.get("url") or ""),
        bloque("PRECIO_OBJETIVO", fmt_eur(ver.get("precio_objetivo")) if ver.get("precio_objetivo") else ""),
    ]

    # Cabecera ejecutiva
    semaforo = _semaforo_de_reco(reco)
    lines.append("")
    lines += [
        bloque("SEMAFORO", semaforo),
        bloque("DICTAMEN", reco or "—"),
        bloque("CONFIANZA", ver.get("confianza") or "—"),
    ]
    lines += bloque_multi("RESUMEN", ver.get("razonamiento") or "")
    lines += bloque_multi("QUE_CAMBIARIA", ver.get("que_cambiaria") or "")

    # Balance
    lines.append("")
    lines.append("# Balance")
    for a in bal.get("a_favor") or []:
        lines.append(bloque("A_FAVOR", f"{a.get('texto','')} | {a.get('peso','')}"))
    for c in bal.get("en_contra") or []:
        lines.append(bloque("EN_CONTRA", f"{c.get('texto','')} | {c.get('peso','')}"))

    # Auditoría por aspecto
    lines.append("")
    lines.append("# Auditoría por aspecto")
    aspectos = [
        ("problemas_comunes", "Problemas comunes"),
        ("recalls",           "Recalls"),
        ("precio_mercado",    "Precio de mercado"),
        ("fiabilidad",        "Fiabilidad"),
        ("homologacion",      "Homologación"),
        ("etiqueta_ambiental", "Etiqueta ambiental"),
        ("seguro",            "Seguro"),
        ("piezas",            "Piezas"),
        ("otros",             "Otros"),
    ]
    for key, titulo in aspectos:
        asp = inv.get(key) or {}
        if not asp:
            continue
        lines.append(bloque("ASPECTO", titulo))
        lines.append(bloque("VALORACION", _valoracion_es(asp.get("valoracion"))))
        lines += bloque_multi("TEXTO", asp.get("hallazgo") or "")
        if asp.get("fuente"):
            lines.append(bloque("FUENTE", asp["fuente"]))

    # Checklist
    lines.append("")
    lines.append("# Checklist")
    for chk in payload.get("avisos") or []:
        lines.append(bloque("CHECK", str(chk)))

    # Cobertura fuentes
    lines.append("")
    lines.append("# Cobertura de fuentes")
    for fu in payload.get("fuentes") or []:
        lines.append(bloque("COBERTURA", join_inline(
            fu.get("nombre"), fu.get("estado"), str(fu.get("n", "")),
            fu.get("nota"), sep=" | ",
        )))

    # Mercado ES
    es = merc.get("es") or {}
    if es:
        lines.append("")
        lines.append("# Mercado ES")
        for k in ("min", "q1", "mediana", "q3", "max", "n"):
            if k in es:
                lines.append(bloque(f"MERCADO_ES_{k.upper()}", str(es[k])))

    # Comparables
    for comp in merc.get("comparables") or []:
        lines.append(bloque("COMPARABLE", join_inline(
            comp.get("titulo"), str(comp.get("km", "")),
            fmt_eur(comp.get("precio")), comp.get("url"), sep=" | ",
        )))

    # Candidato (datos del anuncio)
    lines.append("")
    lines.append("# Candidato")
    lines.append(bloque("CAND_URL", anun.get("url") or ""))
    lines.append(bloque("CAND_VENDEDOR", anun.get("vendedor_nombre") or ""))
    lines.append(bloque("CAND_VENDEDOR_TIPO", anun.get("vendedor_tipo") or ""))
    lines.append(bloque("CAND_CIUDAD", anun.get("ciudad") or ""))
    lines.append(bloque("CAND_PRECIO", fmt_eur(anun.get("precio_publicado"))))
    if anun.get("precio_negociado"):
        lines.append(bloque("CAND_PRECIO_OBJ", fmt_eur(anun["precio_negociado"])))
    if anun.get("dias_publicado") is not None:
        lines.append(bloque("CAND_DIAS", str(anun["dias_publicado"])))
    if anun.get("tuv_vigente_hasta"):
        lines.append(bloque("CAND_TUV", anun["tuv_vigente_hasta"]))

    # Ficha técnica
    lines.append("")
    lines.append("# Ficha técnica")
    for key in ("marca", "modelo", "version", "anio", "km", "combustible",
                "cambio", "traccion", "potencia_cv", "color_exterior",
                "carroceria", "puertas", "plazas", "co2_gkm"):
        v = veh.get(key)
        if v is not None and v != "":
            label = {"potencia_cv": "Potencia (CV)", "co2_gkm": "CO2 (g/km)"}.get(key, key.capitalize())
            lines.append(bloque("FICHA", f"{label} | {v}"))

    # Equipamiento completo (regla dura: NO solo 15 destacados)
    if veh.get("equipamiento"):
        lines.append("")
        lines.append("# Equipamiento completo (Ausstattung)")
        for eq in veh["equipamiento"]:
            lines.append(bloque("EQUIP", eq))

    # Costes
    if cost:
        lines.append("")
        lines.append("# Costes")
        for key in ("precio_coche", "transporte", "itv_matriculacion",
                    "tasa_dgt", "iedmt_estimado", "gestoria", "otros",
                    "coste_total", "honorarios"):
            v = cost.get(key)
            if v is not None and v != "":
                label = {
                    "precio_coche": "Compra del vehículo",
                    "itv_matriculacion": "ITV matriculación",
                    "tasa_dgt": "Tasa DGT",
                    "iedmt_estimado": "IEDMT estimado",
                    "coste_total": "Coste total",
                }.get(key, key.replace("_", " ").capitalize())
                lines.append(bloque("COSTE", f"{label} | {fmt_eur(v)}"))

    return _clean_lines(lines)


def generar_dossier_cliente(payload: dict) -> list[str] | None:
    """dossier-cliente.txt — solo si veredicto es Comprar/Comprar si baja."""
    ver = (payload.get("veredicto") or {}).get("recomendacion", "")
    if not ver.lower().startswith("comprar"):
        return None

    veh = payload.get("vehiculo") or {}
    anun = payload.get("anuncio") or {}
    dos = payload.get("dossier") or {}
    inv = payload.get("investigacion") or {}
    meta = payload.get("_meta") or {}

    if not dos:
        return None

    lines: list[str] = []

    # §1 Portada
    lines += [
        f"# Dossier #{dos.get('dossier_num', 'JJM-SIN-NUM')}".rstrip(),
        bloque("TITULO", f"{veh.get('marca','')} {veh.get('modelo','')} {veh.get('version','')}".strip()),
        bloque("PRECIO_PUESTO_HUELVA", fmt_eur(dos.get("nuestra_oferta", ""))),
        bloque("FECHA", meta.get("generado_el", "")[:10]),
    ]

    # §2 Carta
    lines.append("")
    lines += bloque_multi("CARTA", dos.get("carta_presentacion", "") or "")

    # §3 Resumen ejecutivo
    res = dos.get("resumen_30s") or {}
    lines.append("")
    lines.append("# Resumen 30s")
    for op in res.get("oportunidades") or []:
        lines.append(bloque("RESUMEN_OP", op))
    for at in res.get("atencion") or []:
        lines.append(bloque("RESUMEN_AT", at))
    if res.get("proximo_paso"):
        lines.append(bloque("RESUMEN_PROX", res["proximo_paso"]))

    # §4 Ficha
    lines.append("")
    lines.append("# Ficha técnica")
    for key in ("marca", "modelo", "version", "anio", "km", "combustible",
                "cambio", "traccion", "potencia_cv", "color_exterior",
                "color_interior", "carroceria", "puertas", "plazas"):
        v = veh.get(key)
        if v is not None and v != "":
            label = key.replace("_", " ").capitalize()
            lines.append(bloque("FICHA", f"{label} | {v}"))
    if veh.get("vin"):
        vin = str(veh["vin"])
        masked = (vin[:6] + "·" * max(0, len(vin) - 10) + vin[-4:]) if len(vin) >= 10 else vin
        lines.append(bloque("FICHA", f"VIN (parcial) | {masked}"))

    # §5 Equipamiento
    if dos.get("equipamiento_destacado"):
        lines.append("")
        lines.append("# Equipamiento destacado")
        for eq in dos["equipamiento_destacado"]:
            lines.append(bloque("EQUIP_DEST", eq))

    # §6 Estado
    lines.append("")
    lines.append("# Estado verificado")
    for v in dos.get("estado_verificado") or []:
        lines.append(bloque("ESTADO_OK", v))
    for p in dos.get("estado_pendiente") or []:
        lines.append(bloque("ESTADO_PEND", p))

    # §7 Mercado ES
    es = dos.get("mercado_es") or {}
    if es:
        lines.append("")
        lines.append("# Mercado ES")
        for k in ("min", "q1", "mediana", "q3", "max", "n"):
            if k in es:
                lines.append(bloque(f"MERCADO_{k.upper()}", str(es[k])))
        if dos.get("nuestra_oferta") and dos.get("ahorro_eur"):
            lines.append(bloque("AHORRO_VS_MEDIANA",
                                f"{fmt_eur(dos['ahorro_eur'])} ({fmt_pct(dos.get('ahorro_pct', 0))})"))

    # §8 DE vs ES
    de_vs = dos.get("de_vs_es") or {}
    if de_vs:
        lines.append("")
        lines.append("# Comparativa DE vs ES")
        lines.append(bloque("DE_VS_ES_PRECIO_DE", fmt_eur(de_vs.get("precio_de"))))
        lines.append(bloque("DE_VS_ES_PRECIO_ES", fmt_eur(de_vs.get("precio_es"))))
        lines.append(bloque("DE_VS_ES_UDS_DE", str(de_vs.get("uds_de", ""))))
        lines.append(bloque("DE_VS_ES_UDS_ES", str(de_vs.get("uds_es", ""))))
        lines.append(bloque("DE_VS_ES_HUECO", fmt_pct(de_vs.get("hueco_pct"))))

    # §9 Análisis técnico
    eval_t = dos.get("eval_tecnica") or {}
    if eval_t:
        lines.append("")
        lines.append("# Análisis técnico")
        if eval_t.get("motor"):
            lines.append(bloque("TEC_MOTOR", eval_t["motor"]))
        if eval_t.get("fiabilidad"):
            lines.append(bloque("TEC_FIAB", eval_t["fiabilidad"]))
        for prob in eval_t.get("problemas_conocidos") or []:
            lines.append(bloque("TEC_PROBLEMA", prob))
        if "recalls_activos" in eval_t:
            lines.append(bloque("TEC_RECALLS", "Sí" if eval_t["recalls_activos"] else "No"))

    # §10 Costes
    if dos.get("coste_transparente"):
        lines.append("")
        lines.append("# Coste transparente")
        for c in dos["coste_transparente"]:
            lines.append(bloque("COSTE_LINEA",
                                f"{c.get('concepto','')} | {fmt_eur(c.get('importe'))} | {c.get('nota','')}",
                                ))
        if dos.get("coste_total"):
            lines.append(bloque("COSTE_TOTAL", fmt_eur(dos["coste_total"])))

    # §11 Timeline
    if dos.get("timeline"):
        lines.append("")
        lines.append("# Timeline")
        for t in dos["timeline"]:
            lines.append(bloque("TIMELINE_SEMANA",
                                f"{t.get('semana','')} | {t.get('fase','')}"))

    # §12 Garantías
    lines.append("")
    lines.append("# Garantías incluidas")
    for g in dos.get("garantia_incluido") or []:
        lines.append(bloque("GARANTIA_INCLUIDO", g))
    for g in dos.get("garantia_no_incluido") or []:
        lines.append(bloque("GARANTIA_NO_INCLUIDO", g))

    # §13 FAQ
    if dos.get("faq"):
        lines.append("")
        lines.append("# FAQ")
        for f in dos["faq"]:
            lines.append(bloque("FAQ_Q", f.get("q", "")))
            lines.append(bloque("FAQ_A", f.get("a", "")))

    # §14 Pasos
    if dos.get("pasos"):
        lines.append("")
        lines.append("# Próximos pasos")
        for p in dos["pasos"]:
            lines.append(bloque("PASO", p))

    return _clean_lines(lines)


# ── Ficha del cliente (v2, 07-sep-2026) ───────────────────────────────────────

FC_HACEMOS = [
    "Localizamos la unidad y comprobamos su historial y su documentación",
    "Negociamos y coordinamos la compra con el vendedor",
    "Organizamos el transporte hasta España",
    "Tramitamos la ITV de importación, los impuestos y la matriculación",
    "Te acompañamos hasta que tienes el coche a tu nombre",
]

FC_NO_HACEMOS = [
    "No vendemos coches: JJ Import Motors no es el vendedor ni el propietario del vehículo. "
    "La compraventa es entre el vendedor y tú, y el coche se matricula directamente a tu nombre.",
    "No respondemos de averías, desgastes o defectos que no sean visibles en la documentación "
    "y en la inspección previa.",
    "No hacemos mantenimiento ni reparaciones, ni ofrecemos financiación.",
]

FC_NO_GARANTIA = (
    "JJ Import Motors no ofrece garantía de ningún tipo sobre el vehículo. Cualquier garantía o "
    "responsabilidad que exista corresponde al vendedor, según la ley que le sea aplicable. Nuestro "
    "servicio es la gestión de la búsqueda, la verificación y la importación, con honorarios "
    "acordados de antemano."
)

FC_AVISO_LEGAL = (
    "JJ Import Motors (Huelva) presta un servicio de gestión de búsqueda, compra e importación de "
    "vehículos. No es vendedora ni propietaria del vehículo y no ofrece garantía sobre él. El precio "
    "indicado es el del vehículo puesto en España; a él se suman los gastos de gestión de compra "
    "detallados arriba. No incluye seguro, impuesto de circulación ni mantenimiento. Disponibilidad "
    "y precio sujetos a confirmación en el momento de la reserva."
)

FC_PASOS = [
    ("Semana 0", "Reserva y bloqueo de la unidad con el vendedor"),
    ("Semana 1", "Compra, documentación y preparación de la exportación"),
    ("Semanas 2-3", "Transporte hasta España"),
    ("Semanas 3-4", "ITV de importación, impuestos y matriculación"),
    ("Semana 4", "Entrega, con el coche ya a tu nombre"),
]

FC_FAQ = [
    ("¿El coche es vuestro?",
     "No. Nosotros gestionamos la compra: el vehículo se compra al vendedor y se matricula "
     "directamente a tu nombre."),
    ("¿Lleva garantía?",
     "JJ Import Motors no da garantía. La que pueda existir es la del vendedor, según la ley que le "
     "sea aplicable. Si quieres cobertura mecánica, se puede contratar aparte con una compañía "
     "especializada."),
    ("¿Qué pasa si al llegar no es como se dijo?",
     "Antes de comprar se hace una inspección previa con fotos y vídeo. Si aparece algo que no encaja "
     "con lo publicado, te informamos y decides tú si se sigue adelante."),
    ("¿Cuánto tarda?",
     "Entre tres y cinco semanas desde la reserva. Es una estimación: depende del transporte y de las "
     "citas de ITV."),
    ("¿Puedo verlo antes de comprarlo?",
     "No somos concesionario y el coche no está en nuestras instalaciones. Puedes ir a verlo al "
     "vendedor o pedir la inspección previa con fotos y vídeo detallados."),
    ("¿Qué pasa si lo compra otro antes?",
     "Puede ocurrir mientras no hay reserva. Si pasa, te buscamos una unidad equivalente sin coste "
     "adicional de gestión."),
    ("¿Qué pasa con la ITV y la matrícula?",
     "Lo tramitamos nosotros: ITV de importación, impuestos y matriculación española a tu nombre."),
    ("¿Cómo se paga?",
     "Con una reserva inicial para bloquear la unidad y el resto según el calendario acordado antes "
     "de empezar."),
]

FC_NO_INCLUYE = [
    "Seguro del vehículo",
    "Impuesto municipal de circulación",
    "Mantenimiento, reparaciones y desgaste",
    "Garantía mecánica (ver más abajo)",
]


def generar_ficha_cliente(payload: dict, fotos_ok: list[dict] | None = None) -> list[str] | None:
    """ficha-cliente.txt — la PÁGINA que se manda al cliente por enlace (/c/<token>).

    Reglas: 07-marketing/ficha_cliente.md · A22b (el enlace es público, cero datos
    internos) · A28 (pega declarada) · A31 (gestor, no vendedor, sin garantía).

    Solo se genera con veredicto Comprar* — igual que el dossier.
    """
    ver = payload.get("veredicto") or {}
    reco = str(ver.get("recomendacion") or "").lower()
    if not reco.startswith("comprar"):
        return None

    veh = payload.get("vehiculo") or {}
    anun = payload.get("anuncio") or {}
    cost = payload.get("costes") or {}
    merc = payload.get("mercado") or {}
    pub = payload.get("publicidad") or {}
    dos = payload.get("dossier") or {}
    inv = payload.get("investigacion") or {}

    etq = None
    if isinstance(inv.get("etiqueta_ambiental"), dict):
        etq = inv["etiqueta_ambiental"].get("etiqueta")

    por_confirmar = "Por confirmar"
    km_txt = f"{veh.get('km', 0):,}".replace(",", ".") + " km" if veh.get("km") else por_confirmar
    fecha_datos = (anun.get("fecha_captura") or payload.get("_meta", {}).get("generado_el") or "")[:10]
    if len(fecha_datos) == 10 and fecha_datos[4] == "-":
        fecha_datos = f"{fecha_datos[8:10]}/{fecha_datos[5:7]}/{fecha_datos[0:4]}"

    lines: list[str] = [
        f"# Ficha del cliente — {veh.get('marca', '')} {veh.get('modelo', '')}".rstrip(),
        "# Generado por empaquetar.py · reglas en 07-marketing/ficha_cliente.md",
        "# PROHIBIDO aquí (A22b): vendibilidad, hueco, comparables, vendedor de origen,",
        "# precio de origen, ahorro estimado, veredicto interno.",
        "",
        bloque("COCHE_ID", derive_coche_id(payload)),
        bloque("FC_FECHA_DATOS", fecha_datos or time.strftime("%d/%m/%Y")),
        "",
        bloque("FC_TITULO", f"{veh.get('marca', '')} {veh.get('modelo', '')} {veh.get('version', '')}".strip()),
        bloque("FC_SUBTITULO", join_inline(
            str(veh.get("anio") or ""), km_txt, veh.get("combustible") or "", veh.get("cambio") or "",
            sep=" · ")),
    ]

    precio_cliente = cost.get("coste_total") or merc.get("nuestra_oferta")
    if precio_cliente:
        lines.append(bloque("FC_PRECIO", fmt_eur(precio_cliente)))
        lines.append(bloque("FC_PRECIO_NOTA",
                            "Precio del vehículo puesto en España. Incluye transporte, ITV de "
                            "importación, trámites de matriculación y honorarios de gestión."))
        for concepto, importe in gastos_cliente(cost):
            lines.append(bloque("FC_GASTO", f"{concepto} | {fmt_eur(importe)}"))
    lines.append(bloque("FC_ESTADO_PROCESO", "Disponible"))
    lines.append("")

    # ── En 30 segundos ────────────────────────────────────────────────────────
    resumen = dos.get("resumen_30s") or {}
    bueno = (resumen.get("oportunidades") or [None])[0] or pub.get("valoracion") or pub.get("claim")
    ojo = (resumen.get("atencion") or [None])[0]
    paso = resumen.get("proximo_paso") or "Si te encaja, bloqueamos la unidad con el vendedor y arrancamos la gestión."
    lines.append(bloque("FC_RESUMEN_BUENO", bueno or ""))
    lines.append(bloque("FC_RESUMEN_OJO", ojo or
                        "Antes de cerrar se revisan frenos, neumáticos y documentación completa en "
                        "la inspección previa."))
    lines.append(bloque("FC_RESUMEN_PASO", paso))
    lines.append("")

    # ── Ficha técnica (16 campos; lo que no se sepa va "Por confirmar") ───────
    specs = [
        ("Marca y modelo", f"{veh.get('marca', '')} {veh.get('modelo', '')}".strip()),
        ("Versión", veh.get("version")),
        ("Año", veh.get("anio")),
        ("Primera matriculación", veh.get("primera_matriculacion") or anun.get("primera_matriculacion")),
        ("Kilómetros", km_txt),
        ("Combustible", veh.get("combustible")),
        ("Cambio", veh.get("cambio")),
        ("Potencia", f"{veh.get('potencia_cv')} CV" if veh.get("potencia_cv") else None),
        ("Tracción", veh.get("traccion")),
        ("Carrocería", veh.get("carroceria")),
        ("Puertas y plazas", f"{veh.get('puertas')} puertas · {veh.get('plazas')} plazas"
         if veh.get("puertas") and veh.get("plazas") else None),
        ("Color", veh.get("color")),
        ("Etiqueta DGT", etq),
        ("Emisiones CO₂", f"{veh.get('co2_gkm')} g/km" if veh.get("co2_gkm") else None),
        ("Consumo homologado", veh.get("consumo")),
        ("Propietarios", veh.get("propietarios") or anun.get("propietarios")),
    ]
    for etiqueta, valor in specs:
        lines.append(bloque("FC_SPEC", f"{etiqueta} | {valor if valor not in (None, '') else por_confirmar}"))
    lines.append("")

    # ── Equipamiento verificado ───────────────────────────────────────────────
    equipamiento = dos.get("equipamiento_destacado") or veh.get("equipamiento") or []
    for item in equipamiento[:14]:
        lines.append(bloque("FC_EQUIP", item))
    lines.append("")

    # ── Estado: verificado / pendiente (A28) ─────────────────────────────────
    for item in (dos.get("estado_verificado") or []):
        lines.append(bloque("FC_VERIFICADO", item))
    pendientes = dos.get("estado_pendiente") or []
    if not pendientes:
        pendientes = ["Estado de frenos y neumáticos: se revisa en la inspección previa a la compra",
                      "Documentación completa (COC y ficha técnica): se solicita antes de cerrar"]
    for item in pendientes:
        lines.append(bloque("FC_PENDIENTE_COMPROBAR", item))
    lines.append("")

    # ── Fotos ─────────────────────────────────────────────────────────────────
    fotos = [f.get("archivo") for f in (fotos_ok or []) if isinstance(f, dict) and f.get("archivo")]
    if not fotos:
        fotos = [f.get("archivo") for f in (payload.get("fotos") or [])
                 if isinstance(f, dict) and f.get("archivo")]
    if fotos:
        lines.append(bloque("FC_FOTOS", " | ".join(fotos)))
        lines.append("")

    # ── Argumentos (nunca de balance.a_favor: eso es interno) ────────────────
    for arg in (pub.get("argumentos") or []):
        lines.append(bloque("FC_ARGUMENTO", arg))
    lines.append("")

    # ── Mercado: rango, nº de unidades y fecha. SIN cifra de ahorro (A22b) ────
    mercado_es = merc.get("es") if isinstance(merc.get("es"), dict) else {}
    minimo = mercado_es.get("min") or merc.get("precio_min")
    mediana = mercado_es.get("mediana") or merc.get("precio_medio")
    maximo = mercado_es.get("max") or merc.get("precio_max")
    n_uds = mercado_es.get("n") or len(merc.get("comparables") or []) or None
    if mediana and n_uds:
        if minimo:
            lines.append(bloque("FC_MERCADO_MIN", fmt_eur(minimo)))
        lines.append(bloque("FC_MERCADO_MEDIANA", fmt_eur(mediana)))
        if maximo:
            lines.append(bloque("FC_MERCADO_MAX", fmt_eur(maximo)))
        lines.append(bloque("FC_MERCADO_N", str(n_uds)))
        lines.append(bloque("FC_MERCADO_FECHA", fecha_datos or time.strftime("%d/%m/%Y")))
        lines.append(bloque("FC_MERCADO_NOTA",
                            "Rango de precios de unidades similares publicadas en España en la fecha "
                            "indicada. Es una referencia de mercado, no una promesa de ahorro."))
        lines.append("")

    # ── Qué incluye / qué no ──────────────────────────────────────────────────
    incluye = pub.get("incluye") or [
        "El vehículo",
        "Búsqueda, inspección y verificación documental",
        "Transporte hasta España",
        "ITV de importación, impuestos y matriculación",
        "Honorarios de gestión de JJ Import Motors",
    ]
    for item in incluye:
        lines.append(bloque("FC_INCLUYE", item))
    for item in FC_NO_INCLUYE:
        lines.append(bloque("FC_NO_INCLUYE", item))
    lines.append("")

    # ── Proceso (semanas estimadas, nunca fechas cerradas) ───────────────────
    for cuando, que in FC_PASOS:
        lines.append(bloque("FC_PASO", f"{cuando} | {que}"))
    lines.append("")

    # ── Bloques fijos A31 ────────────────────────────────────────────────────
    for item in FC_HACEMOS:
        lines.append(bloque("FC_HACEMOS", item))
    lines.append("")
    for item in FC_NO_HACEMOS:
        lines.append(bloque("FC_NO_HACEMOS", item))
    lines.append("")
    lines += bloque_multi("FC_NO_GARANTIA", FC_NO_GARANTIA)
    lines.append("")

    # ── FAQ ───────────────────────────────────────────────────────────────────
    for pregunta, respuesta in FC_FAQ:
        lines.append(bloque("FC_FAQ", f"{pregunta} | {respuesta}"))
    lines.append("")

    # ── Cierre ────────────────────────────────────────────────────────────────
    lines.append(bloque("FC_CTA", "Quiero gestionar la compra"))
    lines.append(bloque("FC_CONTACTO", "Teléfono y WhatsApp: 675 70 14 39 · jjimportmotors@gmail.com · Huelva"))
    lines.append("")
    lines += bloque_multi("FC_AVISO_LEGAL", FC_AVISO_LEGAL)

    return _clean_lines(lines)


def _slug_hashtag(*partes: str) -> str:
    """'BMW', '320d' -> '#BMW320d' (sin acentos ni espacios)."""
    txt = "".join(str(p or "") for p in partes)
    txt = unicodedata.normalize("NFKD", txt).encode("ascii", "ignore").decode()
    txt = re.sub(r"[^A-Za-z0-9]", "", txt)
    return f"#{txt}" if txt else ""


def _pega_del_payload(payload: dict) -> str:
    """La pega honesta (A28): lo pendiente de comprobar, con qué hacemos al respecto."""
    dos = payload.get("dossier") or {}
    pendientes = dos.get("estado_pendiente") or []
    if pendientes:
        texto = str(pendientes[0]).rstrip(".")
        return f"⚠️ {texto}: se comprueba en la inspección previa a la compra."
    return ("⚠️ Frenos, neumáticos y documentación completa: se revisan en la inspección previa "
            "antes de cerrar la compra.")


def _bloques_v2_redes(payload: dict) -> list[str]:
    """Bloques del módulo 07-marketing (IG_/VT_/FB_/FBMP_) para `check_marketing.py`.

    Se emiten ADEMÁS de los bloques que lee hoy `ValuationPackageIngestor`
    (TIKTOK_/INSTAGRAM_/FACEBOOK_POST_n), para no romper la importación mientras
    el panel migra al vocabulario v2. Ver 07-marketing/handoff_laravel.md.
    """
    veh = payload.get("vehiculo") or {}
    pub = payload.get("publicidad") or {}
    inv = payload.get("investigacion") or {}
    cost = payload.get("costes") or {}
    merc = payload.get("mercado") or {}

    etq = inv.get("etiqueta_ambiental", {}).get("etiqueta") if isinstance(inv.get("etiqueta_ambiental"), dict) else None
    km_txt = f"{veh.get('km', 0):,}".replace(",", ".") if veh.get("km") else None
    modelo = f"{veh.get('marca', '')} {veh.get('modelo', '')}".strip()
    argumentos = pub.get("argumentos") or []
    gancho_a = pub.get("titular") or modelo
    gancho_b = pub.get("claim") or (argumentos[0] if argumentos else modelo)
    pega = _pega_del_payload(payload)
    precio = cost.get("coste_total") or merc.get("nuestra_oferta")

    ficha_iconos = [
        f"🗓️ {veh.get('anio')}" if veh.get("anio") else None,
        f"🛣️ {km_txt} km" if km_txt else None,
        f"🐎 {veh.get('potencia_cv')} CV" if veh.get("potencia_cv") else None,
        f"⚙️ {veh.get('cambio')}" if veh.get("cambio") else None,
        f"🏷️ Etiqueta {etq}" if etq else None,
    ]
    ficha_iconos = [f for f in ficha_iconos if f]

    hashtags = [h for h in [
        _slug_hashtag(veh.get("marca"), veh.get("modelo")),
        _slug_hashtag(veh.get("carroceria") or "Ocasion"),
        "#ImportacionDeCoches",
        "#Huelva",
        "#JJImportMotors",
    ] if h]

    L: list[str] = ["", "# ── Bloques v2 del módulo 07-marketing (los valida check_marketing.py) ──"]

    # Instagram feed
    L.append(bloque("IG_GANCHO", gancho_a))
    L.append(bloque("IG_GANCHO_B", gancho_b))
    for linea in ficha_iconos:
        L.append(bloque("IG_FICHA", linea))
    if pub.get("descripcion") or pub.get("por_que"):
        L += bloque_multi("IG_CONTEXTO", pub.get("descripcion") or pub.get("por_que"))
    for arg in argumentos[:3]:
        L.append(bloque("IG_ARGUMENTO", arg))
    L.append(bloque("IG_PEGA", pega))
    L.append(bloque("IG_CTA", "Te paso la ficha completa por DM."))
    L.append(bloque("IG_SEND_ASK", f"Mándaselo a quien lleve meses buscando un {modelo}."))
    L.append(bloque("IG_HASHTAGS", " ".join(hashtags[:5])))

    # Vídeo corto (Reel / TikTok / Shorts): dos ganchos obligatorios
    L.append(bloque("VT_GANCHO_A", gancho_a))
    L.append(bloque("VT_GANCHO_B", gancho_b))
    L.append(bloque("VT_CTA", "Comenta y te paso la ficha."))
    L.append(bloque("VT_HASHTAGS", " ".join(hashtags[:4])))

    # Facebook página
    L.append(bloque("FB_GANCHO", gancho_a))
    for linea in ficha_iconos:
        L.append(bloque("FB_FICHA", linea))
    for arg in argumentos[:3]:
        L.append(bloque("FB_ARGUMENTO", arg))
    L.append(bloque("FB_PEGA", pega))
    L.append(bloque("FB_CTA", "Escríbenos por WhatsApp y te mandamos la ficha completa con fotos."))

    # Facebook Marketplace: sin iconos, sin hashtags
    subtitulo = " · ".join([x for x in [str(veh.get("anio") or ""), f"{km_txt} km" if km_txt else "",
                                        veh.get("combustible") or "", veh.get("cambio") or ""] if x])
    L.append(bloque("FBMP_TITULO", f"{modelo} {veh.get('version', '')}".strip() + (f" · {subtitulo}" if subtitulo else "")))
    L += bloque_multi("FBMP_DESCRIPCION",
                      (pub.get("descripcion") or pub.get("por_que") or
                       f"{modelo} localizado y verificado por nosotros antes de traerlo."))
    L.append(bloque("FBMP_PEGA", pega.replace("⚠️ ", "")))
    if precio:
        L.append(bloque("FBMP_PRECIO", fmt_eur(precio)))
    L.append(bloque("FBMP_CONTACTO", "Escríbeme por Messenger y te paso la ficha completa."))

    return L


def _bloques_v2_portales(payload: dict) -> list[str]:
    """Bloques PT_* del módulo 07-marketing para `check_marketing.py`."""
    veh = payload.get("vehiculo") or {}
    pub = payload.get("publicidad") or {}
    inv = payload.get("investigacion") or {}
    dos = payload.get("dossier") or {}

    etq = inv.get("etiqueta_ambiental", {}).get("etiqueta") if isinstance(inv.get("etiqueta_ambiental"), dict) else None
    km_txt = f"{veh.get('km', 0):,}".replace(",", ".") if veh.get("km") else None
    modelo = f"{veh.get('marca', '')} {veh.get('modelo', '')}".strip()
    rasgo = (dos.get("estado_verificado") or [""])[0]

    titulo_a = " · ".join([x for x in [
        f"{modelo} {veh.get('version', '')}".strip(),
        str(veh.get("anio") or ""),
        f"{km_txt} km" if km_txt else "",
    ] if x])
    titulo_b = " · ".join([x for x in [
        f"{modelo} {veh.get('potencia_cv')}CV".strip() if veh.get("potencia_cv") else modelo,
        str(veh.get("anio") or ""),
        f"Etiqueta {etq}" if etq else "",
    ] if x])

    L: list[str] = ["", "# ── Bloques v2 del módulo 07-marketing (los valida check_marketing.py) ──"]
    L.append(bloque("PT_TITULO_A", titulo_a[:70]))
    L.append(bloque("PT_TITULO_B", titulo_b[:70]))
    L += bloque_multi("PT_RESUMEN", pub.get("descripcion") or pub.get("por_que") or
                      f"{modelo} localizado y verificado antes de traerlo a España.")

    for etiqueta, valor in [
        ("Año", veh.get("anio")),
        ("Kilómetros", f"{km_txt} km" if km_txt else None),
        ("Combustible", veh.get("combustible")),
        ("Cambio", veh.get("cambio")),
        ("Potencia", f"{veh.get('potencia_cv')} CV" if veh.get("potencia_cv") else None),
        ("Etiqueta DGT", etq),
    ]:
        if valor:
            L.append(bloque("PT_FICHA", f"{etiqueta} | {valor}"))

    for item in (dos.get("estado_verificado") or [])[:3]:
        L.append(bloque("PT_ESTADO", item))
    L.append(bloque("PT_ESTADO", _pega_del_payload(payload)))

    equipamiento = dos.get("equipamiento_destacado") or veh.get("equipamiento") or []
    if equipamiento:
        L += bloque_multi("PT_EQUIPAMIENTO",
                          ", ".join(str(e) for e in equipamiento[:10]) +
                          ". (Equipamiento verificado en la ficha del vehículo.)")

    for item in (pub.get("incluye") or ["El vehículo", "Transporte hasta España",
                                        "ITV de importación y trámites de matriculación"]):
        L.append(bloque("PT_QUE_INCLUYE", item))

    L += bloque_multi("PT_COMO_FUNCIONA",
                      "Gestionamos la búsqueda, la verificación y la importación: localizamos el coche, "
                      "comprobamos su historial y lo traemos legalizado a España. Plazo aproximado de "
                      "entrega: 3-5 semanas desde la reserva.")
    cost = payload.get("costes") or {}
    merc = payload.get("mercado") or {}
    precio_cliente = cost.get("coste_total") or merc.get("nuestra_oferta")
    primera_matriculacion = (veh.get("primera_matriculacion")
                             or (payload.get("anuncio") or {}).get("primera_matriculacion")
                             or (f"{veh.get('anio')}" if veh.get("anio") else "por confirmar"))

    L += bloque_multi("PT_AVISO", "\n".join([
        "JJ Import Motors — gestión de búsqueda, compra e importación de vehículos.",
        "Identidad del empresario: JJ Import Motors, Huelva · jjimportmotors@gmail.com · 675 70 14 39.",
        "El vehículo NO es propiedad del establecimiento: la compraventa es entre el vendedor y el cliente.",
        f"Precio total cliente: {fmt_eur(precio_cliente)} — a este importe se suman los gastos de gestión de compra."
        if precio_cliente else
        "Precio total cliente: pendiente de confirmar — a él se suman los gastos de gestión de compra.",
        "No incluye: seguro, impuesto municipal de circulación, mantenimiento ni garantía mecánica.",
        "Garantía: JJ Import Motors no ofrece garantía sobre el vehículo (A31). La que exista corresponde al vendedor.",
        f"Fecha de primera matriculación: {primera_matriculacion}.",
    ]))
    return L


def generar_redes_sociales(payload: dict, coche_id: str = "") -> list[str]:
    """redes-sociales.txt — Laravel importa a CarMarketingContent.

    Esquema v2 (05-sep-2026): 3 redes (TikTok, Instagram, Facebook) ×
    (3 posts + 3 stories) por canal + hashtags por red + pasos para subir.
    Las redes tienen tonos distintos (TikTok viral 15-30s, Instagram visual,
    Facebook informativo masivo) pero comparten GANCHO y hashtags globales.

    Bloques emitidos:
      [COCHE_ID]                            -> C1 auditoría 09-sep-2026: el ID
                                              del coche se propaga al TXT y al
                                              JSON v2 para que el panel pueda
                                              relacionarlos sin parsear el nombre.
      [GANCHO]                              -> común a las 3 redes
      [HASHTAGS]                            -> globales (fallback si no hay por red)
      [PIE_FOTO]N                           -> pies de foto (1 por foto destacada)

      Por cada red (tiktok/instagram/facebook):
        [RED_POST_1..3]                     -> 3 publicaciones (caption copy)
        [RED_STORY_1..3]                    -> 3 stories (texto corto)
        [RED_HASHTAGS]N                      -> hashtags específicos de la red
        [RED_SUBIR_PASOS]                    -> instrucciones de subida para esa red
    """
    veh = payload.get("vehiculo") or {}
    pub = payload.get("publicidad") or {}
    redes = pub.get("redes") or {}

    lines: list[str] = [
        "# Marketing — Redes sociales (3 redes × 3 posts + 3 stories)".rstrip(),
        # C1 auditoría 09-sep-2026: propagamos coche_id al TXT para que el
        # panel pueda correlacionarlo sin parsear el nombre del archivo.
        bloque("COCHE_ID", coche_id),
        bloque("GANCHO", redes.get("gancho") or pub.get("titular") or ""),
    ]

    # Hashtags compartidos (fallback si la red no tiene los suyos)
    tags_globales = redes.get("hashtags") or []
    if isinstance(tags_globales, str):
        tags_globales = [t.strip() for t in re.split(r"[,\s]+", tags_globales) if t.strip()]
    for t in tags_globales:
        h = t if t.startswith("#") else f"#{t}"
        lines.append(bloque("HASHTAGS", h))

    # Pies de foto (referencia visual; slot 1 los lleva; slots 2-3 los omiten)
    pies = redes.get("pie_foto") or [pub.get("claim") or ""]
    for p in pies:
        lines.append(bloque("PIE_FOTO", p))

    # Por cada red: 3 posts + 3 stories + hashtags específicos + pasos para subir
    # Tono de cada red (3 frases cortas para guiar al redactor):
    tonos = {
        "tiktok":    "viral 15-30s, hook en el primer segundo, hashtag trending + nicho",
        "instagram": "visual, storytelling, hashtags nichos (15-20), estética cuidada",
        "facebook":  "informativo masivo, datos y precio visibles, hashtags mínimos (3-5)",
    }
    redes_sociales = ["tiktok", "instagram", "facebook"]

    for red in redes_sociales:
        rdata = redes.get(red) or {}
        lines.append("")
        lines.append(f"# ── {red.upper()} · tono: {tonos[red]} ──".rstrip())

        # 3 posts (caption copy de cada publicación)
        posts = rdata.get("posts") or []
        for slot in range(1, 4):
            copy = posts[slot - 1] if slot - 1 < len(posts) else ""
            if copy:
                # POST de red social admite multilínea (caption largo de IG/FB)
                lines += bloque_multi(f"{red.upper()}_POST_{slot}", copy)
            else:
                # placeholder vacío: el ingestor advertirá pero no falla
                lines.append(bloque(f"{red.upper()}_POST_{slot}", ""))

        # 3 stories
        stories = rdata.get("stories") or []
        for slot in range(1, 4):
            story = stories[slot - 1] if slot - 1 < len(stories) else ""
            if story:
                lines.append(bloque(f"{red.upper()}_STORY_{slot}", story))

        # Hashtags específicos de la red
        tags_red = rdata.get("hashtags") or []
        if isinstance(tags_red, str):
            tags_red = [t.strip() for t in re.split(r"[,\s]+", tags_red) if t.strip()]
        for t in tags_red:
            h = t if t.startswith("#") else f"#{t}"
            lines.append(bloque(f"{red.upper()}_HASHTAGS", h))

        # Pasos para subir (1 por red)
        if rdata.get("subir_pasos"):
            lines += bloque_multi(f"{red.upper()}_SUBIR_PASOS", rdata["subir_pasos"])

    lines += _bloques_v2_redes(payload)

    return _clean_lines(lines)


def generar_anuncio_portales(payload: dict, coche_id: str = "") -> list[str]:
    """anuncio-portales.txt — Laravel importa a CarMarketingContent.

    Esquema v2 (05-sep-2026): MISMA ficha base reutilizada para los 4 portales
    web (Milanuncios, Coches.net, Wallapop, Facebook Marketplace). La diferencia
    entre portales es solo el formulario de subida, no el contenido.

    Bloques emitidos:
      [TITULO]              -> común
      [DESCRIPCION]         -> común
      [FICHA_RAPIDA]N       -> datos clave del coche
      [QUE_INCLUYE]N        -> qué incluye el servicio
      [AVISO_LEGAL]         -> aviso legal común
      [SUBIR_PASOS]         -> cómo pegarlo en cada portal (1 entrada común
                              porque el contenido es idéntico, solo cambia el sitio)
    """
    veh = payload.get("vehiculo") or {}
    anun = payload.get("anuncio") or {}
    pub = payload.get("publicidad") or {}
    cost = payload.get("costes") or {}
    port = pub.get("portales") or {}

    anio = veh.get("anio", "")
    km = veh.get("km", "")
    km_fmt = f"{km:,} km".replace(",", ".") if km else ""

    titulo = port.get("titulo") or pub.get("titular") or (
        f"{veh.get('marca','')} {veh.get('modelo','')} {veh.get('version','')}".strip()
    )
    descripcion = port.get("descripcion") or pub.get("descripcion") or ""

    ficha_rapida = port.get("ficha_rapida") or [
        join_inline(str(anio), km_fmt, veh.get("combustible"), veh.get("cambio"),
                    f"{veh.get('potencia_cv')} CV" if veh.get("potencia_cv") else None,
                    sep=" | "),
        join_inline(f"Color: {veh.get('color_exterior')}" if veh.get("color_exterior") else None,
                    f"Ciudad: {anun.get('ciudad')}" if anun.get("ciudad") else None,
                    sep=" | "),
    ]

    que_incluye = port.get("que_incluye") or pub.get("incluye") or []

    # SUBIR_PASOS: instrucciones para los 4 portales (contenido idéntico).
    # Si el payload ya trae `portales.subir_pasos`, se usa. Si no, se genera uno
    # por defecto indicando que el mismo contenido va en los 4 sitios.
    subir_pasos_default = (
        "1. Milanuncios: entra en milanuncios.coches y 'Poner anuncio', pega TITULO + DESCRIPCION.\n"
        "2. Coches.net: 'Publicar anuncio' en coches.net, sube fotos + pega TITULO + DESCRIPCION.\n"
        "3. Wallapop: 'Vender' en la app, sube fotos + pega TITULO + DESCRIPCION.\n"
        "4. Facebook Marketplace: 'Crear nuevo anuncio' en Vehículos, sube fotos + pega TITULO + DESCRIPCION."
    )

    lines: list[str] = [
        "# Anuncio portales (misma ficha para Milanuncios · Coches.net · Wallapop · Facebook Marketplace)".rstrip(),
        # C1 auditoría 09-sep-2026: propagamos coche_id al TXT.
        bloque("COCHE_ID", coche_id),
        bloque("TITULO", titulo),
        bloque("DESCRIPCION", descripcion),
    ]

    for fr in ficha_rapida:
        if fr:
            lines.append(bloque("FICHA_RAPIDA", fr))

    for inc in que_incluye:
        lines.append(bloque("QUE_INCLUYE", inc))

    lines.append(bloque("AVISO_LEGAL", port.get("aviso_legal") or AVISO_LEGAL_DEFAULT))
    lines += bloque_multi("SUBIR_PASOS", port.get("subir_pasos") or subir_pasos_default)

    lines += _bloques_v2_portales(payload)

    return _clean_lines(lines)


# ── JSON para Laravel ─────────────────────────────────────────────────────────


def generar_json_para_laravel(contents: dict[str, list[str] | None]) -> dict[str, dict]:
    """Convierte los esqueletos en JSON tipado (contenido/json/*.json).

    Laravel lee el JSON y no interpreta texto: ver 07-marketing/handoff_laravel.md.
    Si el conversor no está disponible, se avisa y el ZIP sale igualmente con los .txt.
    """
    try:
        sys.path.insert(0, str(Path(__file__).resolve().parent))
        import esqueleto_a_json as e2j  # noqa: PLC0415
    except Exception as exc:  # pragma: no cover - depende del entorno
        warn(f"No se pudo cargar esqueleto_a_json.py ({exc}): el ZIP irá sin contenido/json/")
        return {}

    objetivo = ("ficha-cliente.txt", "redes-sociales.txt", "anuncio-portales.txt")
    docs: dict[str, dict] = {}
    for nombre, lineas in contents.items():
        if nombre not in objetivo or not lineas:
            continue
        texto = "\n".join(lineas) + "\n"
        try:
            docs[nombre.replace(".txt", ".json")] = e2j.a_json_desde_texto(texto, nombre)
        except Exception as exc:  # pragma: no cover
            warn(f"No se pudo convertir {nombre} a JSON: {exc}")

    return docs


# ── Manifest ──────────────────────────────────────────────────────────────────


def build_manifest(
    coche_id: str,
    payload: dict,
    fotos_ok: list[dict],
    has_dossier: bool,
    has_ficha_cliente: bool = False,
    json_files: list[str] | None = None,
    paquete_version: int = PACKAGE_VERSION,
) -> dict:
    contents: list[dict] = [
        {"archivo": "contenido/ficha-publicitaria.txt",
         "plantilla": "ficha-coche", "visibilidad": "publico"},
        {"archivo": "contenido/informe-interno.txt",
         "plantilla": "informe-interno", "visibilidad": "interno"},
        {"archivo": "contenido/redes-sociales.txt",
         "plantilla": "marketing", "visibilidad": "marketing"},
        {"archivo": "contenido/anuncio-portales.txt",
         "plantilla": "marketing", "visibilidad": "marketing"},
    ]
    if has_dossier:
        contents.insert(2, {"archivo": "contenido/dossier-cliente.txt",
                            "plantilla": "dossier", "visibilidad": "cliente"})

    # La ficha del cliente (la página del enlace /c/<token>) y su JSON tipado:
    # Laravel lee el JSON, el .txt queda como formato editable por personas.
    if has_ficha_cliente:
        contents.insert(1, {"archivo": "contenido/ficha-cliente.txt",
                            "plantilla": "ficha-cliente", "visibilidad": "cliente"})
    for nombre in (json_files or []):
        contents.append({"archivo": f"contenido/json/{nombre}",
                         "plantilla": "json", "visibilidad": "cliente"})

    return {
        "manifest_version": 1,
        "paquete_version": paquete_version,
        "coche_id": coche_id,
        "generado_el": payload.get("_meta", {}).get("generado_el")
                       or time.strftime("%Y-%m-%dT%H:%M:%S+02:00"),
        "flujo": payload.get("_meta", {}).get("flujo", "A"),
        "schema_version": SCHEMA_VERSION,
        "fotos": [{"archivo": f["archivo"], "orden": f["orden"], "categoria": f["categoria"]}
                  for f in fotos_ok],
        "contenido": contents,
    }


# ── Helpers internos ──────────────────────────────────────────────────────────


def _clean_lines(lines: list[str]) -> list[str]:
    """Quita líneas vacías duplicadas/consecutivas."""
    out: list[str] = []
    prev_empty = True
    for ln in lines:
        if ln.strip() == "":
            if prev_empty:
                continue
            prev_empty = True
        else:
            prev_empty = False
        out.append(ln)
    # Quita posible vacía final
    while out and out[-1].strip() == "":
        out.pop()
    return out


def _valoracion_es(val: str | None) -> str:
    mapping = {
        "favorable": "Positiva",
        "neutral": "Neutral",
        "desfavorable": "Negativa",
    }
    return mapping.get((val or "").lower(), val or "")


def _semaforo_de_reco(reco: str) -> str:
    r = (reco or "").lower()
    if r.startswith("comprar") and "si no" not in r and "baja" not in r:
        return "verde"
    if "baja" in r or "dudoso" in r:
        return "ambar"
    if "descartar" in r:
        return "rojo"
    return "neutro"


# ── Empaquetado final ─────────────────────────────────────────────────────────


# --------------------------------------------------------------------------- #
# Busquedas realizadas (URLs de las búsquedas que originaron la investigación).
#
# Genera las URLs de los principales portales de búsqueda a partir de los
# datos del coche (marca, modelo, año, potencia, carroceria, país). Las
# URLs se inyectan en informe.json → mercado.busquedas_realizadas[] para que
# el panel admin pueda mostrarlas al cliente (C2 auditoría 09-sep-2026).
#
# Si el payload YA trae mercado.busquedas_realizadas (entrada manual de
# Claude), se respeta tal cual — esto permite pasar URLs custom por modelo.
# --------------------------------------------------------------------------- #

# Mapeos por portal: clave → (pais, builder_callable)
# Los callable reciben (marca, modelo, anio_min, anio_max, cv_min, cv_max,
# carroceria, query_params) y devuelven la URL completa.
#
# Los IDs y formato de parámetro son los oficiales (a 09-2026):
#   - mobile.de: ms=MarcaID;ModeloID; (model IDs estables)
#   - autoscout24.es: Marca modelo en path
#   - coches.net: MakeIds[] + Versions[] (versión libre)
#   - wallapop: query libre
#   - autouncle: no tiene buscador público usable, omitido
MARCA_MODEL_ID_MOBILE_DE = {
    "vw": "25200",
    "volkswagen": "25200",
    "bmw": "3500",
    "mercedes": "17200",
    "mercedes-benz": "17200",
    "audi": "1900",
    "opel": "47000",
    "ford": "9000",
    "seat": "12200",
    "skoda": "2400",
    "renault": "7300",
    "peugeot": "5900",
    "citroen": "5000",
    "fiat": "4000",
    "honda": "11000",
    "hyundai": "21000",
    "kia": "22300",
    "mazda": "16800",
    "nissan": "21000",
    "toyota": "24100",
    "volvo": "25100",
    "cupra": "25900",
    "ds": "19500",
}

CARROCERIA_ID_MOBILE_DE = {
    # 'sedan', 'familiar', 'coupe', 'suv', 'compacto', 'monovolumen'
    "sedan": "Saloon",
    "familiar": "EstateCar",
    "coupe": "SportsCar",
    "suv": "OffRoad",
    "compacto": "Compact",
    "monovolumen": "Van",
}


def _url_mobile_de(marca: str, modelo: str, anio_min: int, anio_max: int,
                   cv_min: int, cv_max: int, carroceria: str) -> str:
    """Genera URL de búsqueda mobile.de. Si no tenemos el ID de marca/modelo,
    devuelve una URL con query libre (que SÍ funciona pero no es tan precisa)."""
    mid = MARCA_MODEL_ID_MOBILE_DE.get(marca.lower())
    # En mobile.de, `ms=MarcaID;ModeloID;;` (4 segmentos; el segundo es modelo).
    if mid:
        ms = f"{mid};;;"
    else:
        ms = ""
    cid = CARROCERIA_ID_MOBILE_DE.get(carroceria.lower(), "")

    params = {
        "dam": "0",
        "fr": f"{anio_min}:{anio_max}",
        "isSearchRequest": "true",
        "od": "up",
        "s": "Car",
        "sb": "p",
        "vc": "Car",
    }
    if cid:
        params["c"] = cid
    if cv_min and cv_max:
        params["pw"] = f"{cv_min}:{cv_max}"
    if ms:
        params["ms"] = ms

    qs = "&".join(f"{k}={quote_plus(str(v))}" for k, v in params.items() if v != "")
    return f"https://suchen.mobile.de/fahrzeuge/search.html?{qs}" if qs else ""


def _url_autoscout24_es(marca: str, modelo: str, anio_min: int, anio_max: int,
                        cv_min: int, cv_max: int, carroceria: str) -> str:
    """URL de búsqueda en autoscout24.es por marca/modelo."""
    from urllib.parse import quote_plus as _q
    slug = f"{marca}-{modelo}".lower().replace(" ", "-").replace("--", "-")
    return (
        f"https://www.autoscout24.es/lst/{_q(slug)}?"
        f"atype=C&cy={anio_min}%2C{anio_max}&"
        f"powerfrom={cv_min}&powerto={cv_max}&sort=price&desc=0&"
        f"ustate=N%2CU&"
        f"fregfrom={anio_min}&fregto={anio_max}"
    )


def _url_coches_net(marca: str, modelo: str, anio_min: int, anio_max: int,
                    cv_min: int, cv_max: int, carroceria: str) -> str:
    """URL de búsqueda en coches.net (mercado español). Usa MakeIds y
    Versions como query param array; el ID exacto de marca hay que mapearlo."""
    marca_ids = {
        "vw": 47, "volkswagen": 47, "bmw": 11, "mercedes": 12, "audi": 4,
        "opel": 7, "ford": 5, "seat": 9, "skoda": 17, "renault": 13,
        "peugeot": 14, "citroen": 15, "fiat": 16, "honda": 18,
        "hyundai": 22, "kia": 23, "mazda": 24, "nissan": 25,
        "toyota": 10, "volvo": 26, "cupra": 27,
    }
    body_type = {
        "sedan": 1, "compacto": 2, "familiar": 4, "suv": 5,
        "monovolumen": 6, "coupe": 7,
    }
    make_id = marca_ids.get(marca.lower(), 0)
    bt = body_type.get(carroceria.lower(), 0)
    parts = [f"MakeIds[0]={make_id}"] if make_id else []
    if modelo:
        parts.append(f"Versions[0]={quote_plus(modelo)}")
    if bt:
        parts.append(f"ArrBodyType={bt}")
    if cv_min and cv_max:
        parts.append(f"PowerHpFrom={cv_min}")
        parts.append(f"PowerHpTo={cv_max}")
    parts.append("fi=Price")
    parts.append("or=1")
    return f"https://www.coches.net/segunda-mano/?{'&'.join(parts)}"


def _url_wallapop(marca: str, modelo: str, anio_min: int, anio_max: int,
                  cv_min: int, cv_max: int, carroceria: str) -> str:
    """URL de búsqueda en wallapop (España). Query libre + filtro año/potencia."""
    q = quote_plus(f"{marca} {modelo}".strip())
    return f"https://es.wallapop.com/search?keywords={q}"


def generar_busquedas_realizadas(payload: dict) -> list[dict]:
    """Construye las URLs de búsqueda que originaron la investigación de
    mercado, a partir de los datos del vehiculo. Es una estimación: el
    operador puede editar las URLs en el JSON de entrada si las precisas
    mejor (ver CLAUDE.md).

    Cada item del resultado es:
      { pais: 'DE'|'ES', portal: str, url: str, descripcion: str,
        params: {...}, generado_el: ISO 8601 }

    Si el payload YA trae mercado.busquedas_realizadas (manual), se respeta.
    """
    # Si ya viene relleno (manual), respetar tal cual.
    mercado = payload.get("mercado") or {}
    if isinstance(mercado.get("busquedas_realizadas"), list) and mercado["busquedas_realizadas"]:
        return mercado["busquedas_realizadas"]

    veh = payload.get("vehiculo") or {}
    marca = (veh.get("marca") or "").strip()
    modelo = (veh.get("modelo") or "").strip()
    if not marca or not modelo:
        return []

    # Año: si es un modelo vigente (anio_hasta=None), usamos 2015:año_actual+1.
    anio_min = int(veh.get("anio_min") or veh.get("anio") or 2015)
    anio_hasta = veh.get("anio_hasta") or veh.get("anio_max")
    anio_max = int(anio_hasta or datetime.date.today().year + 1)
    cv_min = int(veh.get("cv_min") or (veh.get("potencia_cv") or 0))
    cv_max = int(veh.get("cv_max") or (veh.get("potencia_cv") or 0))
    if cv_min and cv_max == 0:
        cv_max = cv_min + 20
    carroceria = (veh.get("carroceria") or "").strip().lower() or "sedan"

    ahora = datetime.datetime.now().astimezone().isoformat(timespec="seconds")
    out: list[dict] = []

    # DE: mobile.de + autoscout24 (Alemania)
    url = _url_mobile_de(marca, modelo, anio_min, anio_max, cv_min, cv_max, carroceria)
    if url:
        out.append({
            "pais": "DE",
            "portal": "mobile.de",
            "url": url,
            "descripcion": f"{marca} {modelo} {anio_min}-{anio_max}, {cv_min}-{cv_max} CV",
            "params": {
                "marca": marca, "modelo": modelo,
                "anio_min": anio_min, "anio_max": anio_max,
                "cv_min": cv_min, "cv_max": cv_max,
                "carroceria": carroceria,
            },
            "generado_el": ahora,
        })
    url = _url_autoscout24_es(marca, modelo, anio_min, anio_max, cv_min, cv_max, carroceria)
    if url:
        out.append({
            "pais": "DE",
            "portal": "autoscout24.de",
            "url": url,
            "descripcion": f"{marca} {modelo} {anio_min}-{anio_max}, {cv_min}-{cv_max} CV",
            "params": {
                "marca": marca, "modelo": modelo,
                "anio_min": anio_min, "anio_max": anio_max,
            },
            "generado_el": ahora,
        })

    # ES: coches.net + wallapop
    url = _url_coches_net(marca, modelo, anio_min, anio_max, cv_min, cv_max, carroceria)
    if url:
        out.append({
            "pais": "ES",
            "portal": "coches.net",
            "url": url,
            "descripcion": f"{marca} {modelo} {anio_min}-{anio_max}, {cv_min}-{cv_max} CV",
            "params": {
                "marca": marca, "modelo": modelo,
                "anio_min": anio_min, "anio_max": anio_max,
                "cv_min": cv_min, "cv_max": cv_max,
                "carroceria": carroceria,
            },
            "generado_el": ahora,
        })
    url = _url_wallapop(marca, modelo, anio_min, anio_max, cv_min, cv_max, carroceria)
    if url:
        out.append({
            "pais": "ES",
            "portal": "wallapop",
            "url": url,
            "descripcion": f"{marca} {modelo} (España)",
            "params": {
                "marca": marca, "modelo": modelo,
            },
            "generado_el": ahora,
        })

    return out


def build_zip(
    zip_path: Path,
    informe_payload: dict,
    manifest: dict,
    contents: dict[str, list[str]],
    fotos_dir: Path,
    json_docs: dict[str, dict] | None = None,
) -> int:
    zip_path.parent.mkdir(parents=True, exist_ok=True)

    # Borrar ZIP previo (idempotencia)
    if zip_path.exists():
        zip_path.unlink()

    fotos_paths = []
    if fotos_dir.is_dir():
        fotos_paths = sorted(p for p in fotos_dir.iterdir() if p.is_file()
                             and p.suffix.lower() in IMAGE_EXTS)

    with zipfile.ZipFile(zip_path, "w", compression=zipfile.ZIP_DEFLATED) as zf:
        # 1) informe.json (copia exacta del input)
        zf.writestr("informe.json",
                    json.dumps(informe_payload, ensure_ascii=False, indent=2))

        # 2) manifest.json
        zf.writestr("manifest.json",
                    json.dumps(manifest, ensure_ascii=False, indent=2))

        # 3) contenido/*.txt
        for nombre_archivo, lineas in contents.items():
            if lineas is None:
                continue
            zf.writestr(f"contenido/{nombre_archivo}",
                        "\n".join(lineas) + "\n")

        # 3b) contenido/json/*.json — lo que consume Laravel
        for nombre_json, doc in (json_docs or {}).items():
            zf.writestr(f"contenido/json/{nombre_json}",
                        json.dumps(doc, ensure_ascii=False, indent=2))

        # 4) fotos/*.jpg (etc)
        for foto_path in fotos_paths:
            zf.write(foto_path, arcname=f"fotos/{foto_path.name}")

    return len(fotos_paths)


# ── Main ──────────────────────────────────────────────────────────────────────


def main() -> int:
    parser = argparse.ArgumentParser(
        description="Empaqueta un JSON Flujo A en un ZIP para Laravel (ImportnexCore)."
    )
    parser.add_argument("json", type=Path, help="Ruta al export/flujo-a-<coche_id>.json")
    parser.add_argument("--out", type=Path, default=None,
                        help=f"Carpeta de salida (defecto: ./{PAQUETES_DIRNAME}/)")
    parser.add_argument("--auto-path", action="store_true",
                        help="Ruta canonica: ~/Desktop/JJImportMotors/investigaciones/<marca>/<modelo>/. "
                             "Crea la estructura si no existe. Incluye fecha en el nombre del ZIP.")
    parser.add_argument("--laravel-storage", action="store_true",
                        help="Ruta del proyecto Laravel: <root>/storage/app/private/investigaciones/<marca>/<modelo>/. "
                             "Es la preferida desde el repo (no contamina Downloads). "
                             "Busca la raíz del proyecto hacia arriba desde cwd.")
    parser.add_argument("--strict", action="store_true",
                        help="Modo validación dura: aborta si faltan fotos o marketing.")
    parser.add_argument("--no-photos", action="store_true",
                        help="Omitir descarga de fotos (NO válido para entrega; sólo debug).")
    parser.add_argument("--keep-tmp", action="store_true",
                        help="Mantener carpeta temporal tras empaquetar (debug).")
    args = parser.parse_args()

    payload = load_payload(args.json)
    coche_id = derive_coche_id(payload)

    # C2 auditoría 09-sep-2026 (panel admin): inyectamos las URLs de
    # búsqueda de mercado (mobile.de / autoscout24 / coches.net / wallapop)
    # en informe.json → mercado.busquedas_realizadas[] para que el panel
    # admin pueda mostrarlas en la pestaña Mercado. Si el payload YA las
    # trae, se respetan (manual, p.ej. URLs custom por modelo).
    busquedas = generar_busquedas_realizadas(payload)
    if busquedas:
        if not isinstance(payload.get("mercado"), dict):
            payload["mercado"] = {}
        payload["mercado"]["busquedas_realizadas"] = busquedas
        info(f"{len(busquedas)} búsqueda(s) de mercado generadas (mobile.de, coches.net…)")

    # C2 auditoría 09-sep-2026: inyectamos las URLs de búsqueda de mercado
    # (mobile.de / autoscout24 / coches.net / wallapop) en informe.json →
    # mercado.busquedas_realizadas[] para que el panel admin pueda
    # mostrarlas. Si el payload YA las trae, se respetan (manual).
    busquedas = generar_busquedas_realizadas(payload)
    if busquedas:
        payload.setdefault("mercado", {})
        if not isinstance(payload["mercado"], dict):
            payload["mercado"] = {}
        payload["mercado"]["busquedas_realizadas"] = busquedas
        info(f"{len(busquedas)} búsqueda(s) de mercado generadas (mobile.de, coches.net…)")

    if args.auto_path:
        out_dir = derive_auto_path(payload)
        out_dir.mkdir(parents=True, exist_ok=True)
        zip_path = output_zip_path(payload, out_dir, with_date=True)
    elif args.laravel_storage:
        out_dir = derive_laravel_storage_path(payload)
        out_dir.mkdir(parents=True, exist_ok=True)
        zip_path = output_zip_path(payload, out_dir, with_date=True)
    elif args.out:
        out_dir = args.out
        out_dir.mkdir(parents=True, exist_ok=True)
        zip_path = output_zip_path(payload, out_dir)
    else:
        out_dir = Path.cwd() / PAQUETES_DIRNAME
        out_dir.mkdir(parents=True, exist_ok=True)
        zip_path = output_zip_path(payload, out_dir)

    info(f"Coche: {coche_id}")
    info(f"ZIP destino: {zip_path}")

    # Directorios temporales (en la misma carpeta de salida)
    work_dir = out_dir / f".tmp_{coche_id}_{int(time.time())}"
    fotos_dir = work_dir / "fotos"
    work_dir.mkdir(parents=True, exist_ok=True)

    # 1) Fotos
    fotos_ok, photo_warnings = collect_photos(
        payload, fotos_dir, args.strict, args.no_photos,
    )

    # 2) Esqueletos .txt
    info("Generando esqueletos .txt…")
    ficha_lines = generar_ficha_publicitaria(payload)
    interno_lines = generar_informe_interno(payload)
    dossier_lines = generar_dossier_cliente(payload)
    ficha_cliente_lines = generar_ficha_cliente(payload, fotos_ok)
    redes_lines = generar_redes_sociales(payload, coche_id)
    portales_lines = generar_anuncio_portales(payload, coche_id)

    contents: dict[str, list[str] | None] = {
        "ficha-publicitaria.txt": ficha_lines,
        "ficha-cliente.txt": ficha_cliente_lines,
        "informe-interno.txt": interno_lines,
        "dossier-cliente.txt": dossier_lines,
        "redes-sociales.txt": redes_lines,
        "anuncio-portales.txt": portales_lines,
    }

    n_generados = sum(1 for v in contents.values() if v)
    ok(f"{n_generados}/6 esqueletos generados")

    # 2b) JSON canónico para Laravel (Blade lee el JSON, no el .txt).
    #     Ver 07-marketing/handoff_laravel.md.
    json_docs = generar_json_para_laravel(contents)
    if json_docs:
        ok(f"{len(json_docs)} JSON generados para el panel (contenido/json/)")
    else:
        warn("No se generó ningún JSON para el panel: revisa scripts/esqueleto_a_json.py")
    if args.strict and not dossier_lines and (payload.get("veredicto") or {}).get("recomendacion", "").lower().startswith("comprar"):
        fail("Veredicto Comprar* pero sin dossier-cliente.txt — modo --strict aborta")
        return 4

    # 3) Manifest
    manifest = build_manifest(
        coche_id, payload, fotos_ok,
        has_dossier=bool(dossier_lines),
        has_ficha_cliente=bool(ficha_cliente_lines),
        json_files=sorted(json_docs.keys()),
    )

    # 4) ZIP
    info("Empaquetando ZIP…")
    n_fotos = build_zip(zip_path, payload, manifest, contents, fotos_dir, json_docs)
    ok(f"ZIP generado: {zip_path} ({n_fotos} fotos)")

    # 5) Validación de calidad — A5 auditoría 09-sep-2026.
    #    El ZIP se genera aunque el copy no cumpla los checks; eso hace que
    #    la ficha llegue al panel sin control. Ahora los dos validadores se
    #    ejecutan ANTES del cleanup. Con --strict, un hallazgo 🔴 aborta.
    check_args = [
        ("check_marketing.py", [str(zip_path)]),
        ("check_ficha_cliente.py", [str(zip_path)]),
    ]
    criticos_totales: list[str] = []
    for script, script_args in check_args:
        ruta_check = Path(__file__).parent / script
        if not ruta_check.exists():
            warn(f"{script} no encontrado en {ruta_check.parent} — saltando validación")
            continue
        info(f"Ejecutando {script}…")
        criticos = run_validator(ruta_check, script_args, work_dir)
        criticos_totales.extend(criticos)

    if criticos_totales:
        print()
        warn(f"{len(criticos_totales)} hallazgo(s) CRÍTICO(S) en la validación del ZIP:")
        for c in criticos_totales:
            # Forzar UTF-8 stdout en Windows (cp1252 no soporta 🔴).
            try:
                sys.stdout.reconfigure(encoding="utf-8")
            except (AttributeError, ValueError):
                pass
            try:
                print(f"     \U0001f534 {c}")
            except UnicodeEncodeError:
                # Fallback: reemplazar el emoji por ASCII para Windows cp1252.
                print(f"     [CRIT] {c.encode('ascii', 'replace').decode('ascii')}")
        if args.strict:
            fail(f"Modo --strict: abortando por {len(criticos_totales)} hallazgo(s) crítico(s).")
            return 5

    # 6) Cleanup
    if not args.keep_tmp:
        try:
            for p in work_dir.glob("**/*"):
                if p.is_file():
                    p.unlink()
            work_dir.rmdir()
        except OSError:
            warn(f"No se pudo limpiar tmp {work_dir} — usa --keep-tmp para diagnóstico")

    # 6) Resumen final
    print()
    info("─── Resumen ───")
    print(f"   Coche:       {coche_id}")
    print(f"   ZIP:         {zip_path}")
    print(f"   Fotos:       {n_fotos} (warnings: {len(photo_warnings)})")
    print(f"   Esqueletos:  {n_generados}/6")
    print(f"   Ficha cliente: {'SÍ' if ficha_cliente_lines else 'NO (solo con veredicto Comprar*)'}")
    print(f"   JSON panel:  {len(json_docs)} en contenido/json/")
    if dossier_lines:
        print(f"   Dossier:     SÍ (veredicto Comprar*)")
    else:
        reco = (payload.get('veredicto') or {}).get('recomendacion', '?')
        print(f"   Dossier:     NO (recomendación: {reco})")

    if photo_warnings:
        print()
        warn("Warnings de fotos:")
        for w in photo_warnings:
            print(f"     · {w}")

    print()
    print("   Mensaje de cierre literal:")
    print('   "Informe completo. Dile a Copilot \'importa el ZIP\' para fusionarlo con Laravel."')
    print()
    return 0


if __name__ == "__main__":
    sys.exit(main())
