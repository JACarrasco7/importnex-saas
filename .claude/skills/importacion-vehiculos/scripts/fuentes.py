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
        --carroceria familiar

Varios vehiculos (repetir --spec, campos separados por |):
    py fuentes.py --spec "Volkswagen|Golf|Golf R Variant Mk7.5 (310cv, 2017-2020)|310|310|2017|2020|180000|familiar"
                  --spec "Volkswagen|Arteon|Arteon Shooting Brake R (320cv, 2020-)|320|320|2020||||familiar"

Campos de --spec (los 3 ultimos son opcionales):
    marca|modelo|etiqueta|cv_min|cv_max|anio_desde|anio_hasta|km|carroceria

Opciones:
    --seccion   envuelve la salida en la seccion lista para pegar en el informe
    --ascii     sin emojis (consolas antiguas)

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
CARROCERIA_ES_ID = {
    "sedan": 1, "compacto": 2, "familiar": 4, "suv": 5,
    "monovolumen": 6, "coupe": 7,
}
KILO = "\U0001f1e9\U0001f1ea"  # bandera DE
KILO_ES = "\U0001f1ea\U0001f1f8"  # bandera ES


def miles(n) -> str:
    """180000 -> '180.000' (formato espanol)."""
    try:
        return f"{int(n):,}".replace(",", ".")
    except (TypeError, ValueError):
        return str(n)


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

    # --- IDS (nunca inventados: del catalogo compartido) ---
    make_de = E._make_id_mobile_de(marca)
    mod_de = E._modelo_id_mobile_de(marca, modelo) if make_de else None
    make_es = E._make_id_coches_net(marca)
    mod_es = E._modelo_id_coches_net(make_es, modelo) if make_es else None

    url_de = E._url_mobile_de(marca, modelo, anio_desde or 0, anio_hasta or 0,
                              cv_min, cv_max, carroceria, km)
    # En coches.net el filtro va en cv, asi que se le pasa la banda ya abierta
    # (310 -> 305-315). En mobile.de se le pasa la cifra original: el propio
    # constructor de empaquetar.py aplica su margen de +-4 kW.
    cv_desde, cv_hasta = banda_cv(cv_min, cv_max)
    url_es = E._url_coches_net(marca, modelo, anio_desde or 0, anio_hasta or 0,
                               cv_desde, cv_hasta, carroceria, km)

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
    if anio_desde:
        partes_es.append(f"año {anio_desde}-{anio_hasta or ''}".rstrip("-"))
    partes_es.append("orden precio ascendente")

    out = [etiqueta, ""]
    if url_de:
        out.append(f"- {dir_de} mobile.de: `{url_de}` ({', '.join(partes_de)})")
    if url_es:
        out.append(f"- {dir_es} Coches.net: `{url_es}` ({', '.join(partes_es)})")
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
        campos += [""] * (9 - len(campos))
        specs.append({
            "marca": campos[0], "modelo": campos[1], "etiqueta": campos[2],
            "cv_min": campos[3] or 0, "cv_max": campos[4] or 0,
            "anio_desde": int(campos[5]) if campos[5] else None,
            "anio_hasta": int(campos[6]) if campos[6] else None,
            "km": int(campos[7]) if campos[7] else None,
            "carroceria": campos[8],
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
        })

    if not specs:
        print(__doc__)
        return 2

    lineas: list[str] = []
    if seccion:
        lineas += [
            "## 🔗 FUENTES CONSULTADAS — re-ejecutable",
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
