#!/usr/bin/env python3
# -*- coding: utf-8 -*-
"""
generar_guia_marcas.py — Guía de búsqueda manual MD+PDF POR MARCA (estudio-mercado).

Para el USUARIO (JJ): una guía por marca con todos sus modelos del mapa
(datos_mercado.json), para buscar a mano en coches.net y mobile.de sin
equivocarse de generación ni de motor:
  - Qué años = qué generación (y por qué ese filtro de año).
  - Qué CV tiene cada motor (tabla de identificación, referencia fabricante).
  - Los filtros exactos de los sliders de cada portal + el POR QUÉ de cada uno.
  - Datos del mapa (suelos, medianas, hueco, veredicto) tal cual, sin recalcular.

Outputs (por marca): .md (guía) + .html (temporal) + .pdf (Chrome headless).
Rutas: Desktop JJImportMotors/guias-busqueda/ (principal) y espejo en la skill
(informes-mercado/). Nombres: guia-<marca>_<YYYY-MM-DD>.md|pdf.

Uso:
    py generar_guia_marcas.py                    # todas las marcas
    py generar_guia_marcas.py --marca vw --marca audi
    py generar_guia_marcas.py --no-pdf
    py generar_guia_marcas.py --out D:\\ruta\\salida

Sin dependencias externas: stdlib + Chrome/Edge local para el PDF.
"""
from __future__ import annotations

import argparse
import datetime
import json
import re
import subprocess
import sys
from pathlib import Path

if sys.stdout and hasattr(sys.stdout, "reconfigure"):
    sys.stdout.reconfigure(encoding="utf-8", errors="replace")
    sys.stderr.reconfigure(encoding="utf-8", errors="replace")

# ── Constantes ────────────────────────────────────────────────────────────────

SKILL_DIR = Path(__file__).resolve().parent.parent
DATOS_REPO = SKILL_DIR.parent / "datos_mercado.json"
DATOS_DESKTOP = Path(r"C:/Users/jacar/Desktop/JJImportMotors/datos_mercado.json")
IDS_JSON = SKILL_DIR / "references" / "mobile-de-ids.json"
OUT_DESKTOP = Path(r"C:/Users/jacar/Desktop/JJImportMotors/guias-busqueda")
OUT_SKILL = SKILL_DIR / "informes-mercado"
CHROME_CANDIDATOS = (
    r"C:\Program Files\Google\Chrome\Application\chrome.exe",
    r"C:\Program Files (x86)\Google\Chrome\Application\chrome.exe",
    r"C:\Program Files (x86)\Microsoft\Edge\Application\msedge.exe",
    r"C:\Program Files\Microsoft\Edge\Application\msedge.exe",
)

CATEGORIAS_ORDEN = ("showstoppers", "alta_rotacion", "gemas_economicas")
NOMBRE_MARCA = {
    "vw": "Volkswagen", "audi": "Audi", "mercedes": "Mercedes-Benz", "bmw": "BMW",
    "hyundai": "Hyundai", "ford": "Ford", "cupra": "Cupra", "toyota": "Toyota",
    "renault": "Renault", "kia": "Kia", "mazda": "Mazda", "seat": "SEAT",
    "opel": "Opel", "peugeot": "Peugeot",
}
VEREDICTO_EMOJI = {"verde": "🟢 verde", "amarillo": "🟡 amarillo", "rojo": "🔴 rojo"}

# ── Identificación curada por slug (referencia fabricante, NO medición) ───────
# gen: etiqueta de generación · motores: (motor, cv, años) · cv_min: filtro
# "potencia desde" · kw: texto de versión · md: clave modelo en mobile-de-ids ·
# razon_anyo: por qué ese año (generación) · trampa: aviso de identificación.
IDENT: dict[str, dict] = {
    "vw-golf-gti": dict(
        gen="Golf Mk7.5 GTI (2017-2019) y Mk8 GTI (2020+)", cv_min=240, kw="GTI", md="golf",
        motores=[("2.0 TSI GTI Performance (Mk7.5)", "245 CV", "2017-2019"),
                 ("2.0 TSI GTI (Mk8)", "245 CV", "2020+"),
                 ("2.0 TSI GTI (Mk7, fuera del filtro)", "230 CV", "2013-2017")],
        razon_anyo="El mapa fija 2019+ para pillar el final del Mk7.5 (245 CV) y el Mk8; el GTI de 230 CV (2013-2017) se queda fuera a propósito.",
        trampa="Sin filtro de CV entran los TSI de acceso del mismo carrozado. Con 'Potencia desde 240' aterriza solo en GTI de verdad.",
    ),
    "vw-golf-r": dict(
        gen="Golf R (reemplazado por la entrada granular vw-golf-75-r)", cv_min=290, kw="R", md="golf",
        motores=[("2.0 TSI 4Motion Mk8", "320 CV", "2021+"),
                 ("2.0 TSI 4Motion Mk7.5", "310 CV", "2017-2019"),
                 ("2.0 TSI 4Motion Mk7", "300 CV", "2013-2017")],
        razon_anyo="Entrada antigua agregada del mapa; para negocio usar vw-golf-75-r (Mk7.5 granular).",
        trampa="Entrada marcada `reemplazado_por: vw-golf-75-r` en el mapa: no usar sus cifras.",
    ),
    "audi-s3": dict(
        gen="Audi S3 8V FL (2016-2020) y 8Y (2020+)", cv_min=300, kw="S3", md="s3",
        motores=[("2.0 TFSI quattro (8Y)", "310 CV", "2020+"),
                 ("2.0 TFSI quattro (8V facelift)", "310 CV", "2016-2020"),
                 ("2.0 TFSI quattro (8V pre-FL, fuera)", "300 CV", "2013-2016")],
        razon_anyo="2019+ coge el final del 8V facelift y todo el 8Y; ambos son el 2.0 TFSI de 310 CV.",
        trampa="El S3 comparte carrocería con el A3 normal: sin filtro de CV o de letra 'S3' entran A3 35/40 TFSI.",
    ),
    "mercedes-a45-amg": dict(
        gen="Mercedes-AMG A 45 W177 (2018+)", cv_min=380, kw="A 45", md="a45amg",
        motores=[("2.0 A 45 4MATIC+ (W177)", "387 CV", "2018+"),
                 ("2.0 A 45 S 4MATIC+ (W177)", "421 CV", "2019+"),
                 ("2.0 A 45 AMG (W176, fuera)", "360-381 CV", "2013-2018")],
        razon_anyo="2019+ deja fuera el W176 anterior y asegura la plataforma W177 (la del estudio).",
        trampa="A 35 AMG (306 CV) es otro coche: si el encargo dice A45, filtra por CV desde 380.",
    ),
    "bmw-serie-1-m135": dict(
        gen="BMW Serie 1 F40 M135i xDrive (2019+)", cv_min=300, kw="M135", md="m135",
        motores=[("M135i xDrive (F40)", "306 CV", "2019+"),
                 ("M140i (F20, fuera)", "340 CV", "2016-2019")],
        razon_anyo="El mapa no fija control de año (muestra ES de 13 unidades): añade 2019 a mano para pillar solo F40.",
        trampa="F20 y F40 no comparten nada (tracción y plataforma): filtra año 2019+ y CV desde 300.",
    ),
    "hyundai-i30-n": dict(
        gen="Hyundai i30 N (PDE, 2017+; facelift 2021+)", cv_min=245, kw="i30 N", md=None,
        motores=[("2.0 T-GDI N (base)", "250 CV", "2017-2021"),
                 ("2.0 T-GDI N Performance / facelift", "280 CV", "2017+ / 2021+")],
        razon_anyo="Sin control de año en el mapa: el N solo existe desde 2017, no hay confusión de generación posible.",
        trampa="Sin filtro de CV entran los i30 T-GDI de 120-140 CV. Mobile.de sin ID de marca verificado: busca a mano 'Hyundai' → 'i30'.",
    ),
    "ford-focus-st": dict(
        gen="Focus ST Mk3 (2012-2018) y Mk4 (2019+)", cv_min=245, kw="ST", md="focus",
        motores=[("2.3 EcoBoost (Mk4)", "280 CV", "2019+"),
                 ("2.0 EcoBoost (Mk3)", "250 CV", "2012-2018")],
        razon_anyo="Sin control de año: decide tú si quieres Mk4 (280 CV, 2019+) filtrando año, o ambas generaciones.",
        trampa="El ST Diesel (2.0 EcoBlue 190 CV) comparte nombre: filtra gasolina o CV desde 245.",
    ),
    "audi-tt": dict(
        gen="Audi TT 8S (2014+)", cv_min=225, kw="TT", md="tt",
        motores=[("2.0 TFSI (40 TFSI)", "197 CV", "2019+"),
                 ("2.0 TFSI (45 TFSI)", "245 CV", "2014-2019 / 230 CV 2014-2016"),
                 ("2.0 TTS", "310 CV", "2014+")],
        razon_anyo="Sin control de año: el 8S es la única generación vigente (2014+); el 8J murió en 2014.",
        trampa="En ES la muestra incluye TTS (310 CV); en DE el estudio contó solo TT. Decide antes de comparar precios.",
    ),
    "cupra-leon": dict(
        gen="Cupra León Mk1 (2020+)", cv_min=0, kw="León", md="leon",
        motores=[("2.0 TSI VZ", "300 CV", "2020+"),
                 ("2.0 TSI VZ Clubsport", "310 CV", "2021+"),
                 ("1.4 eHybrid VZ", "245-272 CV", "2020+"),
                 ("1.5 eTSI", "150 CV", "2020+")],
        razon_anyo="Modelo 2020+ en ambos mercados (no hay generación anterior de Cupra León): no hace falta filtro de año.",
        trampa="586 de 655 unidades ES del estudio eran 2023+: la oferta ES es muy reciente. VZ y eHybrid son precios distintos, no los mezcles en una misma comparación.",
    ),
    "vw-golf-75-gti": dict(
        gen="Golf Mk7.5 GTI (facelift 2017-2019)", cv_min=228, kw="GTI", md="golf",
        motores=[("2.0 TSI GTI", "230 CV", "2017-2019"),
                 ("2.0 TSI GTI Performance", "245 CV", "2017-2019")],
        razon_anyo="2017+ = facelift Mk7.5 (pilotos LED, interior nuevo). El Clubsport NO existe en esta carrocería.",
        trampa="Es la entrada GRANULAR del estudio 23-ago (suelo-a-suelo). No la mezcles con la entrada agregada vw-golf-gti.",
    ),
    "vw-golf-75-tcr": dict(
        gen="Golf Mk7.5 GTI TCR (2019, edición limitada)", cv_min=288, kw="TCR", md="golf",
        motores=[("2.0 TSI TCR", "290 CV", "2019")],
        razon_anyo="El TCR es de 2019 dentro del facelift Mk7.5; edición limitada (290 CV, overboost 300).",
        trampa="Muy escaso: sin filtro de versión ('TCR') la búsqueda devuelve GTI normales.",
    ),
    "vw-golf-7-clubsport": dict(
        gen="Golf Mk7 GTI Clubsport PRE-facelift (2016-2017)", cv_min=260, kw="Clubsport", md="golf",
        anios_display="2016-2017",
        motores=[("2.0 TSI Clubsport", "265 CV (overboost 290)", "2016-2017")],
        razon_anyo="El Clubsport genuino SOLO existe en Mk7 pre-facelift 2016-2017: filtrar 2017+ lo ELIMINA de los resultados.",
        trampa="⚠️ NO pongas el filtro de año 2017+ aquí: ese filtro es para el resto de Mk7.5 y borraría todos los Clubsport.",
    ),
    "vw-golf-75-r": dict(
        gen="Golf Mk7.5 R 4Motion (2017-2019)", cv_min=305, kw="R", md="golf",
        motores=[("2.0 TSI 4Motion", "310 CV", "2017-2019")],
        razon_anyo="2017+ = facelift Mk7.5. El R de Mk8 (320 CV, 2021+) entra si no pones año hasta 2019: decide si lo quieres o no.",
        trampa="Con 'R' como texto de versión entran también los R-Line (150-150 CV TSI): filtra CV desde 305.",
    ),
    "toyota-auris-hibrido-2016": dict(
        gen="Toyota Auris II híbrido (2016-2018)", cv_min=None, kw=None, md="auris",
        motores=[("1.8 HSD híbrido", "136 CV combinados", "2016-2018"),
                 ("(desde 2019 el modelo pasa a llamarse Corolla)", "—", "2019+")],
        razon_anyo="2016+ fija el último ciclo del Auris II antes del salto a Corolla (2019).",
        trampa="Si buscas 'Auris' en 2019+ ya no hay: el mismo coche se llama Corolla. Usa la entrada de Corolla para 2019+.",
    ),
    "vw-golf-gasolina-2016": dict(
        gen="Golf Mk7 gasolina (2016+; facelift Mk7.5 desde 2017)", cv_min=None, kw=None, md="golf",
        motores=[("1.0 TSI", "85-110 CV", "2015-2017 (85 CV) / 2017+ (110 CV)"),
                 ("1.2 TSI", "110 CV", "2014-2017"),
                 ("1.4 TSI ACT", "125-150 CV", "2014-2019"),
                 ("1.5 TSI EVO", "130-150 CV", "2017+")],
        razon_anyo="2016+ evita los TSI de primera serie (2012-2015) con cadena de distribución problemática en el 1.2/1.4 early.",
        trampa="Es la familia entera de gasolina (gema): si el encargo pide un CV concreto, afina con 'Potencia desde'.",
    ),
    "renault-megane-gasolina-2016": dict(
        gen="Renault Megane IV (2016+)", cv_min=None, kw=None, md=None,
        motores=[("TCe 100/115", "100-115 CV", "2016+"),
                 ("TCe 130/140", "130-140 CV", "2016+"),
                 ("TCe 160 EDC", "160 CV", "2017+")],
        razon_anyo="2016+ = plataforma Megane IV (la del estudio); el III es otro coche y otro precio.",
        trampa="Mobile.de sin ID de marca verificado: busca 'Renault' → 'Megane' a mano.",
    ),
    "kia-ceed-gasolina-2016": dict(
        gen="Kia cee'd JD (2016-2018) y Ceed CD (2018+)", cv_min=None, kw=None, md=None,
        motores=[("1.0 T-GDi", "100-120 CV", "2016+"),
                 ("1.4 MPI (JD)", "100 CV", "2016-2018"),
                 ("1.4 T-GDi (CD)", "140 CV", "2018+"),
                 ("1.5 T-GDi (CD FL)", "160 CV", "2021+")],
        razon_anyo="2016+ mezcla dos generaciones (JD y CD): si quieres solo CD, filtra año desde 2018.",
        trampa="El nombre cambia de 'cee'd' a 'Ceed' en 2018: en los portales puede aparecer escrito de las dos formas.",
    ),
    "mazda-3-gasolina-2016": dict(
        gen="Mazda 3 BN (2016-2019) y BP (2019+)", cv_min=None, kw=None, md=None,
        motores=[("Skyactiv-G 1.5", "100 CV", "2016-2019"),
                 ("Skyactiv-G 2.0", "120 CV", "2016+"),
                 ("Skyactiv-G 2.0 (BP)", "122-150 CV", "2019+"),
                 ("e-Skyactiv X (BP)", "180 CV", "2019+")],
        razon_anyo="2016+ coge el final del BN; el BP (2019+) es otro diseño: decide generación con el filtro de año.",
        trampa="Sin híbridos ni diésel en esta entrada: filtra gasolina para no mezclar.",
    ),
    "seat-leon-gasolina-2016": dict(
        gen="SEAT León 5F (2016-2020)", cv_min=None, kw=None, md="leon",
        motores=[("1.0 TSI", "115 CV", "2017+"),
                 ("1.2 TSI", "110 CV", "2013-2017"),
                 ("1.4 TSI ACT", "125-150 CV", "2014-2018"),
                 ("1.5 TSI EVO", "130-150 CV", "2018+")],
        razon_anyo="2016+ fija el último ciclo del 5F (hasta su fin en 2020); el 1P anterior es otra época de precios.",
        trampa="No confundir con el Cupra León (2020+): otra entrada del mapa y otro rango de precios.",
    ),
    "toyota-corolla-hibrido-2016": dict(
        gen="Toyota Auris HSD (2016-2018) / Corolla E210 (2019+)", cv_min=None, kw="Corolla", md="corolla",
        motores=[("1.8 HSD (Auris)", "136 CV combinados", "2016-2018"),
                 ("1.8 HSD (Corolla)", "122 CV combinados", "2019+"),
                 ("2.0 HSD (Corolla)", "184 CV combinados", "2019+")],
        razon_anyo="2016+ incluye el último Auris y todo el Corolla E210: el propio estudio lo definió así.",
        trampa="El 2.0 HSD (184 CV) cotiza por encima del 1.8: si comparas precios, separa por CV.",
    ),
    "opel-astra-gasolina-2016": dict(
        gen="Opel Astra K (2015-2021; facelift 2019+)", cv_min=None, kw=None, md="astra",
        motores=[("1.0 Turbo", "105 CV", "2015-2019"),
                 ("1.4 Turbo", "125-150 CV", "2015-2021"),
                 ("1.2 Turbo (FL)", "110-145 CV", "2019+")],
        razon_anyo="2016+ fija el K ya asentado; el facelift 2019+ cambia a los 1.2 Turbo de 3 cilindros.",
        trampa="El 1.4 Turbo de 150 CV es el motor más buscado de la serie: acota con 'Potencia desde 140' si el encargo lo pide.",
    ),
    "peugeot-308-gasolina-2016": dict(
        gen="Peugeot 308 T9 (2013-2021; facelift 2017+)", cv_min=None, kw=None, md=None,
        motores=[("PureTech 1.2", "82-110 CV", "2013+"),
                 ("PureTech 1.2 (FL)", "130 CV", "2017+"),
                 ("PureTech 1.2 EAT8", "155 CV", "2017+")],
        razon_anyo="2016+ deja fuera la primera serie y pilla el FL de 2017 con el 1.2 de 130 CV.",
        trampa="Mobile.de sin ID de marca verificado: busca 'Peugeot' → '308' a mano.",
    ),
    "ford-focus-gasolina-2016": dict(
        gen="Ford Focus Mk3.5 (2016-2018) y Mk4 (2018+)", cv_min=None, kw=None, md="focus",
        motores=[("1.0 EcoBoost", "100-125 CV", "2016-2018"),
                 ("1.0 EcoBoost (Mk4)", "125-155 CV", "2018+")],
        razon_anyo="2016+ coge el último ciclo del Mk3.5 y todo el Mk4: son dos plataformas distintas.",
        trampa="No mezclar con el ST (280 CV): el ST es otra entrada del mapa con otro precio.",
    ),
}

MD_LOOKUP_FALLBACK = {"cupra-leon": ("seat", "leon")}


# ── Utilidades ────────────────────────────────────────────────────────────────


def log(msg: str) -> None:
    print(msg, flush=True)


def cargar_json(ruta: Path) -> dict:
    with ruta.open(encoding="utf-8") as fh:
        return json.load(fh)


def localizar_datos() -> tuple[Path, dict]:
    for candidato in (DATOS_REPO, DATOS_DESKTOP):
        if candidato.exists():
            return candidato, cargar_json(candidato)
    raise SystemExit(f"❌ datos_mercado.json no encontrado en {DATOS_REPO} ni {DATOS_DESKTOP}")


def anyo_desde(control_anyo: str | None) -> int | None:
    if not control_anyo:
        return None
    m = re.search(r"(\d{4})", control_anyo)
    return int(m.group(1)) if m else None


def eur(v) -> str:
    if v in (None, ""):
        return "—"
    try:
        return f"{float(v):,.0f} €".replace(",", ".")
    except (TypeError, ValueError):
        return str(v)


def pct(v) -> str:
    if v in (None, ""):
        return "—"
    try:
        return f"+{float(v):.1f}%" if float(v) >= 0 else f"{float(v):.1f}%"
    except (TypeError, ValueError):
        return str(v)


def veredicto_emoji(e: dict) -> str:
    return VEREDICTO_EMOJI.get(e.get("veredicto") or "", "—")


def md_modelo_code(ids: dict, slug: str) -> tuple[str | None, str | None]:
    """Devuelve (codigo_marca, codigo_modelo) para el filtro ms= de mobile.de."""
    marca, clave = None, None
    # Resolución simple: el slug arranca por la marca canónica
    for alias in ("vw", "audi", "mercedes", "bmw", "ford", "cupra", "toyota", "seat", "opel"):
        if slug.startswith(alias + "-"):
            marca = alias
            break
    datos_marca = ids.get("marcas", {}).get(marca)
    if datos_marca is None:
        return None, None
    clave = IDENT.get(slug, {}).get("md")
    code_modelo = ids.get("modelos", {}).get(datos_marca, {}).get(clave) if clave else None
    return datos_marca, code_modelo


# ── Construcción de la guía (MD) ──────────────────────────────────────────────


def filas_filtros(e: dict, ident: dict, ids: dict) -> list[tuple[str, str, str, str]]:
    """(filtro, valor, por_qué) por portal."""
    filas: list[tuple[str, str, str, str]] = []
    slug = e["slug"]
    code_marca, code_modelo = md_modelo_code(ids, slug)
    nombre = NOMBRE_MARCA.get(slug.split("-")[0], slug.split("-")[0].upper())

    ms = f"ms={code_marca};{code_modelo}" if code_marca and code_modelo else None
    if ms:
        filas.append((
            "Marca + Modelo",
            f"coches.net: {nombre} → {e.get('modelo', '')}\nmobile.de: Marke → Modell",
            f"mobile.de directo: {ms}",
            "Marca y modelo exactos: es el filtro raíz; sin él todo lo demás no sirve.",
        ))
    else:
        filas.append((
            "Marca + Modelo",
            f"coches.net: {nombre} → {e.get('modelo', '')}\nmobile.de: busca la marca a mano (ID sin verificar)",
            "—",
            "Marca y modelo exactos: es el filtro raíz; sin él todo lo demás no sirve.",
        ))

    anyo = anyo_desde(e.get("control_anyo"))
    if anyo:
        filas.append((
            f"Año desde {anyo}",
            f"coches.net: «Año · desde» = {anyo}\nmobile.de: «Erstzulassung ab» = {anyo}",
            "—",
            ident.get("razon_anyo", "Fija la generación del estudio."),
        ))

    cv_min = ident.get("cv_min")
    if cv_min:
        filas.append((
            f"Potencia desde {cv_min} CV",
            f"coches.net: «Potencia · desde» = {cv_min} CV\nmobile.de: «Leistung ab» = {cv_min} PS",
            "—",
            "Aísla los motores potentes del estudio y excluye las versiones de acceso que comparten carrocería.",
        ))

    p_de = e.get("precio_desde_de")
    p_es = e.get("precio_desde_es")
    if p_de:
        filas.append((
            f"Precio desde {eur(p_de)} (DE)",
            f"mobile.de: «Preis ab» = {eur(p_de)}",
            "—",
            "Suelo verificado del estudio DE: por debajo suele haber siniestro, tuning o kilometraje loco.",
        ))
    if p_es:
        filas.append((
            f"Precio desde {eur(p_es)} (ES)",
            f"coches.net: «Precio · desde» = {eur(p_es)}",
            "—",
            "Suelo verificado del estudio ES: mismo criterio, para comparar apples con apples.",
        ))

    kw = ident.get("kw")
    if kw:
        filas.append((
            f"Versión «{kw}»",
            f"coches.net: campo «Versión» = {kw}\nmobile.de: añade q={kw} a la URL",
            "—",
            "Texto de versión: sin él entran acabados hermanos que rompen la comparación del estudio.",
        ))

    comb = e.get("combustible")
    if comb:
        etiqueta = {"gasolina": "Gasolina / Benzin", "hibrido": "Híbrido / Hybrid"}.get(comb, comb)
        filas.append((
            f"Combustible: {etiqueta}",
            "coches.net: «Combustible»\nmobile.de: «Kraftstoff»",
            "—",
            "El estudio midió solo este combustible: mezclar diésel desplaza las medianas.",
        ))
    return filas


def bloque_modelo_md(e: dict, ids: dict) -> str:
    slug = e["slug"]
    ident = IDENT.get(slug, {})
    cat = e.get("categoria", "")
    out: list[str] = []
    out.append(f"\n## {e.get('modelo', slug)} — {ident.get('gen', e.get('version', ''))} · {veredicto_emoji(e)}\n")
    out.append(f"Categoría: **{cat}** · Segmento: **{e.get('segmento', '—')}** · Cliente: **{e.get('tipo_cliente', '—')}** · Precio: banda **{e.get('rango_precio', '—')}**\n")

    out.append("**Motores para identificar (referencia fabricante, no medición):**\n")
    out.append("| Motor | CV | Años |")
    out.append("|---|---|---|")
    for motor, cv, anios in ident.get("motores", []):
        out.append(f"| {motor} | {cv} | {anios} |")
    out.append("")

    out.append("**Filtros exactos (sliders de cada portal) y su porqué:**\n")
    out.append("| Filtro | Dónde se pone | Valor directo URL | Por qué |")
    out.append("|---|---|---|---|")
    for filtro, donde, url, porque in filas_filtros(e, ident, ids):
        out.append(f"| {filtro} | {donde.replace(chr(10), '<br>')} | {url} | {porque} |")
    out.append("")

    out.append("**Cómo está el mercado (datos del mapa, tal cual):**\n")
    out.append("| Suelo DE | Suelo ES | Mediana DE | Mediana ES | Oferta DE | Oferta ES | Hueco | Hueco neto | Confianza |")
    out.append("|---|---|---|---|---|---|---|---|---|")
    out.append(
        f"| {eur(e.get('precio_desde_de'))} | {eur(e.get('precio_desde_es'))} "
        f"| {eur(e.get('mediana_de'))} | {eur(e.get('mediana_es'))} "
        f"| {e.get('oferta_de') or '—'} | {e.get('oferta_es') or '—'} "
        f"| {pct(e.get('hueco_pct'))} | {pct(e.get('hueco_neto_pct'))} | {e.get('confianza_precio', '—')}/5 |"
    )
    banda = e.get("banda") or "—"
    out.append(f"\nBanda del estudio: {banda} · Refrescar antes de: {e.get('refrescar_antes_de_categoria') or '—'}\n")

    enlaces = e.get("enlaces_muestra") or []
    q = e.get("query_reejecutable") or {}
    urls = list(enlaces)
    if isinstance(q, dict):
        urls = [q["de"], q["es"]] + urls if q.get("de") else urls
    if urls:
        out.append("**Búsquedas listas:**")
        vistos: set[str] = set()
        for u in urls:
            # query_reejecutable.es a veces llega sin esquema
            if u and not u.startswith("http"):
                u = "https://" + u
            if u not in vistos:
                vistos.add(u)
                out.append(f"- <{u}>")
        out.append("")

    if ident.get("trampa"):
        out.append(f"⚠️ **Trampa:** {ident['trampa']}\n")
    nota = (e.get("nota") or "").strip()
    if nota:
        out.append(f"<details><summary>Nota del estudio</summary>\n\n{nota}\n\n</details>\n")
    return "\n".join(out)


def guia_marca_md(marca: str, entradas: list[dict], datos: dict, ids: dict, fecha: str) -> str:
    nombre = NOMBRE_MARCA.get(marca, marca)
    L: list[str] = []
    L.append(f"# Guía de búsqueda manual — {nombre}")
    L.append("")
    L.append(f"**Fecha:** {fecha} · **Fuente de datos:** datos_mercado.json (`actualizado: {datos.get('actualizado', '—')}`) · **IDs mobile.de verificados:** {ids.get('verificado', '—')}")
    L.append("")
    L.append("> Guía de USUARIO: para buscar a mano en coches.net y mobile.de sin equivocarte de generación ni de motor. Los precios y huecos salen TAL CUAL del mapa; la tabla de motores/CV es referencia del fabricante para IDENTIFICAR, no una medición de mercado.")
    L.append("")

    validos = [e for e in entradas if not e.get("reemplazado_por")]
    L.append("## Los modelos de esta marca (de un vistazo)")
    L.append("")
    L.append("| Modelo | Qué es (generación) | Años | CV | Veredicto | Hueco neto |")
    L.append("|---|---|---|---|---|---|")
    for e in validos:
        ident = IDENT.get(e["slug"], {})
        anyo = anyo_desde(e.get("control_anyo"))
        cv_min = ident.get("cv_min")
        rango_cv = f"desde {cv_min}" if cv_min else "—"
        # anios_display curado gana (p.ej. Clubsport es 2016-2017, no 2017+)
        anios_txt = ident.get("anios_display") or (f"{anyo}+" if anyo else "—")
        L.append(
            f"| {e.get('modelo', e['slug'])} | {ident.get('gen', '—')} | {anios_txt} | {rango_cv} "
            f"| {veredicto_emoji(e)} | {pct(e.get('hueco_neto_pct'))} |"
        )
    L.append("")

    reemplazados = [e for e in entradas if e.get("reemplazado_por")]
    if reemplazados:
        L.append(f"> ℹ️ No usar: {', '.join('`' + e['slug'] + '`' for e in reemplazados)} — entradas antiguas del mapa reemplazadas por la entrada granular. Sus datos están obsoletos a propósito.")
        L.append("")

    L.append("## Chuleta: dónde está cada filtro")
    L.append("")
    L.append("| Filtro | coches.net | mobile.de |")
    L.append("|---|---|---|")
    L.append("| Marca y modelo | Selectores «Marca» y «Modelo» arriba del buscador | «Marke» y «Modell» (o añade `ms=<marca>;<modelo>` a la URL) |")
    L.append("| Año | Slider «Año» → desde | «Erstzulassung ab» |")
    L.append("| Potencia | Slider «Potencia» → desde (CV) | «Leistung ab» (PS) |")
    L.append("| Precio | Sliders «Precio» → desde / hasta | «Preis ab / bis» |")
    L.append("| Versión/motor | Campo «Versión» (texto libre) | Palabra clave en `q=` |")
    L.append("| Combustible | «Combustible» | «Kraftstoff» |")
    L.append("")

    for e in validos:
        L.append(bloque_modelo_md(e, ids))

    return "\n".join(L) + "\n"


# ── HTML + PDF ────────────────────────────────────────────────────────────────


def md_a_html(md: str, titulo: str) -> str:
    """Conversor minimal (tablas pipe, encabezados, listas, negritas, párrafos).
    Suficiente para el subconjunto de Markdown que emite esta guía."""
    import html as _html

    lineas = md.splitlines()
    out: list[str] = []
    en_tabla = False
    for raw in lineas:
        linea = _html.escape(raw)
        # negritas y código inline
        linea = re.sub(r"\*\*(.+?)\*\*", r"<strong>\1</strong>", linea)
        linea = re.sub(r"`([^`]+)`", r"<code>\1</code>", linea)
        es_fila = linea.lstrip().startswith("|")
        if es_fila and not en_tabla:
            out.append("<table>")
            en_tabla = True
        if not es_fila and en_tabla:
            out.append("</table>")
            en_tabla = False
        if es_fila:
            celdas = [c.strip() for c in linea.strip().strip("|").split("|")]
            if all(re.fullmatch(r":?-{2,}:?", c) for c in celdas):
                continue
            out.append("<tr>" + "".join(f"<td>{c}</td>" for c in celdas) + "</tr>")
            continue
        if linea.startswith("# "):
            out.append(f"<h1>{linea[2:]}</h1>")
        elif linea.startswith("## "):
            out.append(f"<h2>{linea[3:]}</h2>")
        elif linea.startswith("### "):
            out.append(f"<h3>{linea[4:]}</h3>")
        elif linea.startswith("> "):
            out.append(f"<blockquote>{linea[2:]}</blockquote>")
        elif linea.startswith("- "):
            out.append(f"<li>{linea[2:]}</li>")
        elif linea.strip():
            out.append(f"<p>{linea}</p>")
    if en_tabla:
        out.append("</table>")
    body = "\n".join(out)
    # Agrupar <li> consecutivos en <ul>
    body = re.sub(r"(<li>.*?</li>\n)+", lambda m: "<ul>\n" + m.group(0) + "</ul>\n", body)
    return f"""<!doctype html>
<html lang="es"><head><meta charset="utf-8"><title>{_html.escape(titulo)}</title>
<style>
  @page {{ size: A4; margin: 16mm 14mm; }}
  body {{ font-family: 'Segoe UI', Arial, sans-serif; font-size: 10.5px; color: #1a1a1a; line-height: 1.45; }}
  h1 {{ color: #0B1F3A; font-size: 20px; border-bottom: 2px solid #0B1F3A; padding-bottom: 6px; }}
  h2 {{ color: #0B1F3A; font-size: 15px; margin-top: 22px; border-bottom: 1px solid #d5dae2; padding-bottom: 3px; page-break-after: avoid; }}
  h3 {{ color: #E8590C; font-size: 12px; margin-top: 14px; page-break-after: avoid; }}
  table {{ border-collapse: collapse; width: 100%; margin: 8px 0 14px; page-break-inside: avoid; }}
  td {{ border: 1px solid #c9cfd8; padding: 4px 6px; vertical-align: top; }}
  tr:first-child td {{ background: #0B1F3A; color: #fff; font-weight: 600; }}
  blockquote {{ background: #f4f6f8; border-left: 3px solid #E8590C; margin: 8px 0; padding: 6px 10px; }}
  code {{ background: #eef1f4; padding: 1px 3px; border-radius: 3px; font-size: 9.5px; }}
  p {{ margin: 6px 0; }}
</style></head><body>
{body}
</body></html>"""


def html_a_pdf(chrome: str, html_path: Path, pdf_path: Path) -> bool:
    # Rutas SIEMPRE absolutas: Chrome headless no resuelve --print-to-pdf
    # relativo a cwd de forma fiable (probado 06-oct-2026).
    cmd = [
        chrome, "--headless=new", "--disable-gpu", "--no-first-run",
        "--disable-extensions", "--no-pdf-header-footer",
        f"--print-to-pdf={pdf_path.resolve()}", html_path.resolve().as_uri(),
    ]
    try:
        proc = subprocess.run(cmd, capture_output=True, text=True, timeout=90)
    except (OSError, subprocess.TimeoutExpired) as exc:
        log(f"  ⚠️ Chrome falló para {pdf_path.name}: {exc}")
        return False
    ok = pdf_path.exists() and pdf_path.stat().st_size > 1000
    if not ok:
        detalle = (proc.stderr or proc.stdout or "").strip().splitlines()
        cola = detalle[-1] if detalle else "sin salida"
        log(f"  ⚠️ PDF no generado ({pdf_path.name}): exit={proc.returncode} · {cola[:160]}")
    return ok


def buscar_chrome() -> str | None:
    for c in CHROME_CANDIDATOS:
        if Path(c).exists():
            return c
    return None


# ── Main ──────────────────────────────────────────────────────────────────────


def indice_md(generadas: list[tuple[str, int]], fecha: str) -> str:
    L = ["# Índice de guías de búsqueda manual", ""]
    L.append(f"**Fecha:** {fecha} · Una guía por marca con todos sus modelos del mapa de mercado.")
    L.append("")
    L.append("> Cómo usar cada guía: 1) mira la tabla resumen y elige el modelo; 2) copia los filtros de la sección del modelo (año = generación, CV = motor, precio = suelo del estudio); 3) abre los enlaces «Búsquedas listas».")
    L.append("")
    L.append("| Guía | Modelos |")
    L.append("|---|---|")
    for marca, n in generadas:
        L.append(f"| [guia-{marca}_{fecha}.md](guia-{marca}_{fecha}.md) · [PDF](guia-{marca}_{fecha}.pdf) | {n} |")
    return "\n".join(L) + "\n"


def main() -> int:
    parser = argparse.ArgumentParser(description="Guía MD+PDF de búsqueda manual por marca (desde datos_mercado.json).")
    parser.add_argument("--marca", action="append", help="Marca a generar (repetible). Sin esto: todas.")
    parser.add_argument("--out", type=Path, default=None, help="Carpeta de salida única (desactiva dual Desktop+skill).")
    parser.add_argument("--no-pdf", action="store_true", help="Solo .md (saltar HTML+PDF).")
    parser.add_argument("--fecha", default=datetime.date.today().isoformat(), help="Fecha para el nombre de archivo (YYYY-MM-DD).")
    args = parser.parse_args()

    datos_path, datos = localizar_datos()
    ids = cargar_json(IDS_JSON) if IDS_JSON.exists() else {}

    # Slug → entrada (las entradas viven una sola vez en categorias)
    entradas: dict[str, dict] = {}
    for mods in datos.get("categorias", {}).values():
        for m in mods:
            entradas[m["slug"]] = m

    # Agrupar por marca usando `marcas` del mapa
    por_marca: dict[str, list[dict]] = {}
    for marca, info in (datos.get("marcas") or {}).items():
        por_marca[marca] = [entradas[s] for s in info.get("modelos", []) if s in entradas]

    marcas_target = args.marca or sorted(por_marca)
    desconocidas = [m for m in marcas_target if m not in por_marca]
    if desconocidas:
        raise SystemExit(f"❌ Marca(s) sin modelos en el mapa: {', '.join(desconocidas)}")

    outs = [args.out] if args.out else [OUT_DESKTOP, OUT_SKILL]
    chrome = None if args.no_pdf else buscar_chrome()
    if not args.no_pdf and chrome is None:
        log("⚠️ Chrome/Edge no encontrado: solo se generará .md")

    fecha = args.fecha
    generadas: list[tuple[str, int]] = []
    for marca in marcas_target:
        lista = por_marca[marca]
        if not lista:
            continue
        generadas.append((marca, len(lista)))
        md = guia_marca_md(marca, lista, datos, ids, fecha)
        titulo = f"Guía de búsqueda manual — {NOMBRE_MARCA.get(marca, marca)}"
        nombre = f"guia-{marca}_{fecha}"
        for out_dir in outs:
            out_dir.mkdir(parents=True, exist_ok=True)
            ruta_md = out_dir / f"{nombre}.md"
            ruta_md.write_text(md, encoding="utf-8")
            log(f"✅ {ruta_md}")
            if args.no_pdf or chrome is None:
                continue
            ruta_html = out_dir / f"{nombre}.html"
            ruta_html.write_text(md_a_html(md, titulo), encoding="utf-8")
            ruta_pdf = out_dir / f"{nombre}.pdf"
            if html_a_pdf(chrome, ruta_html, ruta_pdf):
                log(f"✅ {ruta_pdf}")
            ruta_html.unlink(missing_ok=True)

    # Índice de guías en cada carpeta de salida (si se generaron todas o varias)
    if generadas and not args.out or len(generadas) > 1:
        idx = indice_md(generadas, fecha)
        for out_dir in outs:
            (out_dir / "00-INDICE.md").write_text(idx, encoding="utf-8")
            log(f"✅ {out_dir / '00-INDICE.md'}")

    log(f"\nFuente de datos: {datos_path}")
    return 0


if __name__ == "__main__":
    sys.exit(main())
