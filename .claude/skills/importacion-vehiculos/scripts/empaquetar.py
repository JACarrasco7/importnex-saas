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
import hashlib
import json
import os
import re
import sys
import time
import urllib.error
import urllib.request
import zipfile
from pathlib import Path

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


def output_zip_path(payload: dict, out_dir: Path) -> Path:
    coche_id = derive_coche_id(payload)
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

    if not saved:
        msg = "0 fotos válidas — entrega SIN fotos (inválida para cliente)"
        if strict:
            fail(msg + " — modo --strict aborta")
            sys.exit(3)
        warn(msg)
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


def generar_redes_sociales(payload: dict) -> list[str]:
    """redes-sociales.txt — Laravel importa a CarMarketingContent.

    Esquema v2 (05-sep-2026): 3 redes (TikTok, Instagram, Facebook) ×
    (3 posts + 3 stories) por canal + hashtags por red + pasos para subir.
    Las redes tienen tonos distintos (TikTok viral 15-30s, Instagram visual,
    Facebook informativo masivo) pero comparten GANCHO y hashtags globales.

    Bloques emitidos:
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

    return _clean_lines(lines)


def generar_anuncio_portales(payload: dict) -> list[str]:
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

    return _clean_lines(lines)


# ── Manifest ──────────────────────────────────────────────────────────────────


def build_manifest(
    coche_id: str,
    payload: dict,
    fotos_ok: list[dict],
    has_dossier: bool,
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


def build_zip(
    zip_path: Path,
    informe_payload: dict,
    manifest: dict,
    contents: dict[str, list[str]],
    fotos_dir: Path,
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
    parser.add_argument("--strict", action="store_true",
                        help="Modo validación dura: aborta si faltan fotos o marketing.")
    parser.add_argument("--no-photos", action="store_true",
                        help="Omitir descarga de fotos (NO válido para entrega; sólo debug).")
    parser.add_argument("--keep-tmp", action="store_true",
                        help="Mantener carpeta temporal tras empaquetar (debug).")
    args = parser.parse_args()

    out_dir = args.out or (Path.cwd() / PAQUETES_DIRNAME)
    out_dir.mkdir(parents=True, exist_ok=True)

    payload = load_payload(args.json)
    coche_id = derive_coche_id(payload)
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
    redes_lines = generar_redes_sociales(payload)
    portales_lines = generar_anuncio_portales(payload)

    contents: dict[str, list[str] | None] = {
        "ficha-publicitaria.txt": ficha_lines,
        "informe-interno.txt": interno_lines,
        "dossier-cliente.txt": dossier_lines,
        "redes-sociales.txt": redes_lines,
        "anuncio-portales.txt": portales_lines,
    }

    n_generados = sum(1 for v in contents.values() if v)
    ok(f"{n_generados}/5 esqueletos generados")
    if args.strict and not dossier_lines and (payload.get("veredicto") or {}).get("recomendacion", "").lower().startswith("comprar"):
        fail("Veredicto Comprar* pero sin dossier-cliente.txt — modo --strict aborta")
        return 4

    # 3) Manifest
    manifest = build_manifest(coche_id, payload, fotos_ok, has_dossier=bool(dossier_lines))

    # 4) ZIP
    info("Empaquetando ZIP…")
    n_fotos = build_zip(zip_path, payload, manifest, contents, fotos_dir)
    ok(f"ZIP generado: {zip_path} ({n_fotos} fotos)")

    # 5) Cleanup
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
    print(f"   Esqueletos:  {n_generados}/5")
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