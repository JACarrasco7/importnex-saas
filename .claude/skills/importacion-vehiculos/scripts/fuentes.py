#!/usr/bin/env python3
"""Genera el BLOQUE DE FUENTES (enlaces re-ejecutables) de un informe.

REGLA DURA (15-sep-2026): todo informe de busqueda/mercado lleva, por cada
modelo-version medido, los enlaces exactos de cada fuente CON los parametros
que se usaron. Sin ellos el usuario no puede comprobar de donde sale cada dato
(el conteo, el suelo, la mediana) y el informe no es auditable.

Uso rapido (un vehiculo):
    py fuentes.py --marca Volkswagen --modelo Golf \\
        --etiqueta "Golf R Variant Mk7.5 (310cv, 2017-2020)" \\
        --cv 310 --anio-desde 2017 --anio-hasta 2020 --km 180000 \\
        --carroceria familiar --precio-max 40000 --pais-de DE \\
        --anuncios-de 30 --anuncios-es 3

Varios vehiculos (repetir --spec, campos separados por |) - TODOS en UNA sola llamada:
    py fuentes.py --seccion --fecha 2026-09-16 --precio-max 40000 --pais-de DE \\
        --spec "Volkswagen|Golf|Golf GTI (230cv)|230|230|2017||170000|compacto||1039|676" \\
        --spec "Volkswagen|Golf|Golf R (310cv)|310|310|2017||170000|compacto||964|234"

Campos de --spec (los ultimos son opcionales):
    marca|modelo|etiqueta|cv_min|cv_max|anio_desde|anio_hasta|km|carroceria|anuncios_de|anuncios_es|versions_es|q_de
    - `versions_es`: filtro ADICIONAL `Versions[0]` en coches.net (GTI, R, S line...) para
      separar dos versiones del mismo modelo. Sin el, la URL no reproduce la medicion.
    - `q_de`: texto libre `q=` en mobile.de (acabados que no tienen ID de modelo).

Opciones:
    --seccion       envuelve la salida en la seccion lista para pegar en el informe
    --ascii         sin emojis (consolas antiguas)
    --fecha         fecha de medicion (YYYY-MM-DD) -> "medido 16 de septiembre de 2026"
    --precio-max    tope de precio en EUR (va en la URL y en los parametros anotados)
    --pais-de DE    solo vendedores alemanes en mobile.de (cn=DE)
    --combustible   diesel|gasolina|petrol (ft=PETROL/DIESEL y Fueltype2List=1/2)
    --anuncios-de   conteo medido en mobile.de (default si el --spec no lo trae)
    --anuncios-es   conteo medido en Coches.net (default si el --spec no lo trae)
    --versions-es   filtro `Versions[0]` de coches.net (GTI, R, S line...)
    --q-de          texto libre `q=` de mobile.de (acabados sin ID de modelo)

Sin conteo y fecha la linea sale incompleta (sin "-> **N anuncios** (medido ...)"):
darlos siempre (regla dura 28-sep-2026). El usuario necesita el conteo para
comprobar que la URL reproduce la medicion.

Los IDs salen del catalogo COMPARTIDO (references/mobile-de-ids.json), el mismo
que usa Laravel: nunca se inventan. Si un modelo no esta en el catalogo, el
script lo dice y usa el plan B (`q=` en mobile.de, `Versions[0]` en coches.net).
"""

from __future__ import annotations

import sys
from pathlib import Path

try:
    sys.stdout.reconfigure(encoding="utf-8", errors="replace")
except Exception:  # consolas muy antiguas
    pass

sys.path.insert(0, str(Path(__file__).resolve().parent))

import empaquetar as E  # noqa: E402  (mismo directorio, fuente unica de IDs)

CARROCERIA_TXT = {
    "sedan": "berlina",
    "compacto": "compacto",
    "familiar": "familiar",
    "suv": "SUV",
    "coupe": "coupe",
    "monovolumen": "monovolumen",
}
# ⚠️ ArrBodyType de Coches.net: 5 es MONOVOLUMEN; el SUV es 6 (verificado por
# conteo el 16-sep-2026: el T-Roc, que es SUV, daba 0 anuncios con `=5`).
# Validar SIEMPRE por conteo antes de publicar una URL con filtro de carroceria.
CARROCERIA_ES_ID = {
    "sedan": 1, "compacto": 2, "familiar": 4, "suv": 6,
    "monovolumen": 5, "coupe": 7,
}
MESES = ["enero", "febrero", "marzo", "abril", "mayo", "junio", "julio",
         "agosto", "septiembre", "octubre", "noviembre", "diciembre"]
KILO = "\U0001f1e9\U0001f1ea"  # bandera DE
KILO_ES = "\U0001f1ea\U0001f1f8"  # bandera ES


def miles(n) -> str:
    """180000 -> '180.000' (formato espanol)."""
    try:
        return f"{int(n):,}".replace(",", ".")
    except (TypeError, ValueError):
        return str(n)


def fecha_larga(iso: str) -> str:
    """'2026-09-16' -> '16 de septiembre de 2026'. Si no es ISO, la deja tal cual."""
    try:
        anio, mes, dia = str(iso).strip().split("-")
        return f"{int(dia)} de {MESES[int(mes) - 1]} de {int(anio)}"
    except (ValueError, IndexError, AttributeError):
        return str(iso)


def _entero(valor) -> int:
    """'40.000' | '40000' | 40000 -> 40000. Vacío o inválido -> 0."""
    if valor is None:
        return 0
    try:
        return int(str(valor).replace(".", "").replace(",", "").strip() or 0)
    except (TypeError, ValueError):
        return 0


def rango_kw(cv_min: int, cv_max: int) -> tuple[int, int] | None:
    """cv -> banda de kW con +-4 de margen (igual que empaquetar.py)."""
    if not (cv_min and cv_max):
        return None
    desde = int(cv_min * 0.7355) - 4
    hasta = int(cv_max * 0.7355) + 4
    if desde <= 0 or hasta <= desde:
        return None
    return desde, hasta


def banda_cv(cv_min: int, cv_max: int) -> tuple[int, int]:
    """Banda de cv que se publica en los enlaces.

    Si el usuario da UNA sola cifra ("310 cv"), se abre al ancho de la banda
    de kW (±4 kW = ±5 cv) para no dejar fuera variantes del mismo motor
    (310 -> 305-315). Si ya da un rango, se respeta tal cual.
    """
    if not (cv_min and cv_max):
        return (cv_min, cv_max)
    if cv_min != cv_max:
        return (cv_min, cv_max)
    kw = rango_kw(cv_min, cv_max)
    if not kw:
        return (cv_min, cv_max)
    return (int(round(kw[0] / 0.7355)), int(round(kw[1] / 0.7355)))


def bloque(v: dict, ascii_mode: bool = False) -> list[str]:
    """Lineas del bloque de un vehiculo."""
    marca = (v.get("marca") or "").strip()
    modelo = (v.get("modelo") or "").strip()
    etiqueta = (v.get("etiqueta") or f"{marca} {modelo}").strip()
    carroceria = (v.get("carroceria") or "").strip().lower()
    anio_desde = v.get("anio_desde")
    anio_hasta = v.get("anio_hasta")
    km = v.get("km")
    cv_min = int(v.get("cv_min") or 0)
    cv_max = int(v.get("cv_max") or cv_min or 0)

    dir_de = "[DE]" if ascii_mode else KILO
    dir_es = "[ES]" if ascii_mode else KILO_ES
    avisos: list[str] = []

    # Filtros que van a la URL Y a los parametros anotados (re-ejecutable de verdad).
    precio_max = v.get("precio_max")
    solo_alemania = str(v.get("pais_de") or "").strip().upper() == "DE"
    combustible = str(v.get("combustible") or "").strip().lower()
    anuncios_de = int(v.get("anuncios_de") or 0)
    anuncios_es = int(v.get("anuncios_es") or 0)
    versions_es = str(v.get("versions_es") or "").strip()
    q_de = str(v.get("q_de") or "").strip()
    fecha_txt = fecha_larga(v.get("fecha")) if v.get("fecha") else ""

    # --- IDS (nunca inventados: del catalogo compartido) ---
    make_de = E._make_id_mobile_de(marca)
    mod_de = E._modelo_id_mobile_de(marca, modelo) if make_de else None
    make_es = E._make_id_coches_net(marca)
    mod_es = E._modelo_id_coches_net(make_es, modelo) if make_es else None

    url_de = E._url_mobile_de(marca, modelo, anio_desde or 0, anio_hasta or 0,
                              cv_min, cv_max, carroceria, km,
                              precio_max=precio_max,
                              solo_alemania=solo_alemania,
                              combustible=combustible,
                              q_extra=q_de)
    # En coches.net el filtro va en cv, asi que se le pasa la banda ya abierta
    # (310 -> 305-315). En mobile.de se le pasa la cifra original: el propio
    # constructor de empaquetar.py aplica su margen de +-4 kW.
    cv_desde, cv_hasta = banda_cv(cv_min, cv_max)
    url_es = E._url_coches_net(marca, modelo, anio_desde or 0, anio_hasta or 0,
                               cv_desde, cv_hasta, carroceria, km,
                               precio_max=precio_max,
                               combustible=combustible,
                               version_extra=versions_es)

    # --- Anotacion DE ---
    txt_car = CARROCERIA_TXT.get(carroceria, carroceria or "-")
    partes_de = []
    if make_de and mod_de:
        partes_de.append(f"marca {marca}={make_de}, modelo {modelo}={mod_de}")
    elif make_de:
        partes_de.append(f"marca {marca}={make_de} (modelo no esta en el catalogo)")
        avisos.append(
            f"mobile.de: `{modelo}` no está en el catálogo de modelos → la URL filtra "
            f"por texto (`q={marca} {modelo}`) en vez de por ID. Menos preciso: "
            "confirmar el <h1> de la página antes de dar el conteo por bueno."
        )
    else:
        partes_de.append(f"marca {marca} no esta en el catalogo")
        avisos.append(
            f"mobile.de: la marca `{marca}` no está en el catálogo de marcas → URL por "
            "texto. Añadirla al catálogo (ver references/mobile-de-ids.json)."
        )
    partes_de.append(f"carrocería {txt_car}")
    if anio_desde or anio_hasta:
        partes_de.append(f"año {anio_desde or ''}-{anio_hasta or ''}".rstrip("-"))
    if km:
        partes_de.append(f"km≤{miles(km)}")
    if precio_max:
        partes_de.append(f"precio ≤{miles(precio_max)} €")
    if solo_alemania:
        partes_de.append("solo vendedores en Alemania (cn=DE)")
    if combustible:
        partes_de.append(f"combustible {combustible}")
    if q_de:
        partes_de.append(f"texto libre «{q_de}» (q=)")
    kw = rango_kw(cv_min, cv_max)
    if kw:
        partes_de.append(f"potencia {kw[0]}-{kw[1]} kW = {cv_desde}-{cv_hasta} cv")
    partes_de.append("orden precio ascendente")

    # --- Anotacion ES ---
    partes_es = []
    if make_es:
        partes_es.append(f"marca {marca}=MakeIds[0]={make_es}")
    else:
        partes_es.append(f"marca {marca} no esta en el catalogo")
        avisos.append(
            f"coches.net: la marca `{marca}` no está en el catálogo → la URL sale sin "
            "`MakeIds[0]` y devuelve todas las marcas. Añadirla al catálogo."
        )
    if mod_es:
        partes_es.append(f"modelo {modelo}=ModelIds[0]={mod_es}")
    elif modelo:
        partes_es.append(f"modelo {modelo}=Versions[0] (texto libre)")
        avisos.append(
            f"coches.net: no hay ID de modelo para `{modelo}` en el catálogo → se usa "
            "`Versions[0]`, que es TEXTO del vendedor: puede mezclar generaciones y "
            "da un conteo distinto al de `ModelIds[0]`. Decirlo en la nota metodológica."
        )
    if carroceria in CARROCERIA_ES_ID:
        partes_es.append(f"carrocería {txt_car}=ArrBodyType={CARROCERIA_ES_ID[carroceria]}")
    if cv_desde and cv_hasta:
        partes_es.append(f"potencia {cv_desde}-{cv_hasta} cv")
    if km:
        partes_es.append(f"km≤{miles(km)}")
    if precio_max:
        partes_es.append(f"precio ≤{miles(precio_max)} €")
    if combustible:
        partes_es.append(f"combustible {combustible}")
    if versions_es:
        partes_es.append(f"versión «{versions_es}» (Versions[0])")
    if anio_desde:
        partes_es.append(f"año {anio_desde}-{anio_hasta or ''}".rstrip("-"))
    partes_es.append("orden precio ascendente")

    def _sufijo(n: int) -> str:
        if not n:
            return ""
        txt = f" → **{n} anuncios**"
        if fecha_txt:
            txt += f" (medido {fecha_txt})"
        return txt

    out = [etiqueta, ""]
    if url_de:
        out.append(f"- {dir_de} mobile.de: `{url_de}` ({', '.join(partes_de)}){_sufijo(anuncios_de)}")
    if url_es:
        out.append(f"- {dir_es} Coches.net: `{url_es}` ({', '.join(partes_es)}){_sufijo(anuncios_es)}")
    for a in avisos:
        out.append(f"  ⚠️ {a}")
    return out


def main(argv: list[str]) -> int:
    if len(argv) < 2:
        print(__doc__)
        return 2

    args = argv[1:]
    ascii_mode = "--ascii" in args
    seccion = "--seccion" in args
    args = [a for a in args if a not in ("--ascii", "--seccion")]

    def valor(nombre: str):
        if nombre in args:
            i = args.index(nombre)
            v = args[i + 1] if i + 1 < len(args) else None
            del args[i:i + 2]
            return v
        return None

    specs: list[dict] = []
    while "--spec" in args:
        i = args.index("--spec")
        crudo = args[i + 1] if i + 1 < len(args) else ""
        del args[i:i + 2]
        campos = [c.strip() for c in crudo.split("|")]
        campos += [""] * (13 - len(campos))
        specs.append({
            "marca": campos[0], "modelo": campos[1], "etiqueta": campos[2],
            "cv_min": campos[3] or 0, "cv_max": campos[4] or 0,
            "anio_desde": int(campos[5]) if campos[5] else None,
            "anio_hasta": int(campos[6]) if campos[6] else None,
            "km": int(campos[7]) if campos[7] else None,
            "carroceria": campos[8],
            "anuncios_de": _entero(campos[9]),
            "anuncios_es": _entero(campos[10]),
            "versions_es": campos[11],
            "q_de": campos[12],
        })

    marca, modelo = valor("--marca"), valor("--modelo")
    if marca or modelo:
        etiqueta = valor("--etiqueta") or ""
        cv_min = valor("--cv-min") or valor("--cv") or 0
        cv_max = valor("--cv-max") or valor("--cv") or 0
        anio_desde = valor("--anio-desde")
        anio_hasta = valor("--anio-hasta")
        km = valor("--km")
        carroceria = valor("--carroceria") or ""
        specs.insert(0, {
            "marca": marca or "", "modelo": modelo or "",
            "etiqueta": etiqueta,
            "cv_min": cv_min, "cv_max": cv_max,
            "anio_desde": int(anio_desde) if anio_desde else None,
            "anio_hasta": int(anio_hasta) if anio_hasta else None,
            "km": int(km) if km else None,
            "carroceria": carroceria,
            "anuncios_de": _entero(valor("--anuncios-de")),
            "anuncios_es": _entero(valor("--anuncios-es")),
            "versions_es": valor("--versions-es") or "",
            "q_de": valor("--q-de") or "",
        })

    if not specs:
        print(__doc__)
        return 2

    # --- filtros y conteos GLOBALES (aplican a todos los --spec; el --spec manda) ---
    g_precio = _entero(valor("--precio-max"))
    g_pais = valor("--pais-de") or ""
    g_comb = valor("--combustible") or ""
    g_fecha = valor("--fecha") or ""
    g_ade = _entero(valor("--anuncios-de"))
    g_aes = _entero(valor("--anuncios-es"))
    g_ver = valor("--versions-es") or ""
    g_q = valor("--q-de") or ""

    for v in specs:
        if not v.get("precio_max"):
            v["precio_max"] = g_precio or None
        v.setdefault("pais_de", g_pais)
        v.setdefault("combustible", g_comb)
        v.setdefault("fecha", g_fecha)
        v.setdefault("versions_es", g_ver)
        v.setdefault("q_de", g_q)
        if not v.get("anuncios_de"):
            v["anuncios_de"] = g_ade or 0
        if not v.get("anuncios_es"):
            v["anuncios_es"] = g_aes or 0

    lineas: list[str] = []
    if seccion:
        lineas += [
            "## 🔗 FUENTES CONSULTADAS — re-ejecutables",
            "",
            "> Cada URL reproduce EXACTAMENTE los filtros con los que se midió. "
            "Pégala en el navegador y compara el conteo con el del informe.",
            "",
        ]
    for i, v in enumerate(specs):
        if i:
            lineas.append("")
        lineas += bloque(v, ascii_mode)

    print("\n".join(lineas))
    return 0


if __name__ == "__main__":
    sys.exit(main(sys.argv))
