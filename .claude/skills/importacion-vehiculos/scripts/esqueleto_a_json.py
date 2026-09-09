#!/usr/bin/env python3
# -*- coding: utf-8 -*-
"""
esqueleto_a_json.py — convierte los esqueletos .txt de marketing/ficha en JSON canónico.

Por qué existe: que Laravel NO tenga que interpretar texto. El .txt sigue siendo el
formato que edita una persona; el .json es lo que consume el Blade. Mismo contenido,
cero ambigüedad.

Uso:
    python scripts/esqueleto_a_json.py contenido/                 # convierte todos los .txt
    python scripts/esqueleto_a_json.py contenido/ficha-cliente.txt
    python scripts/esqueleto_a_json.py contenido/ --out contenido/json/

Salida: un .json por archivo, con esta forma:

    {
      "_meta": {"archivo": "ficha-cliente.txt", "generado": "2026-09-07", "version": 2},
      "coche_id": "...",
      "ficha": {                       # secciones tipadas según el archivo
         "titulo": "...",
         "spec": [{"etiqueta": "Kilómetros", "valor": "62.400 km"}],
         "faq":  [{"pregunta": "...", "respuesta": "..."}],
         "pasos":[{"cuando": "Semana 0", "que": "..."}],
         ...
      },
      "_bloques": { "FC_TITULO": ["..."], ... }   # crudo, por si Laravel lo necesita
    }
"""
import json
import re
import sys
from datetime import date
from pathlib import Path

BLOQUE_RE = re.compile(r"^\[([A-Z0-9_]+)\](.*)$")

# Bloques que SIEMPRE son lista aunque aparezcan una vez
LISTAS = {
    "FC_SPEC", "FC_EQUIP", "FC_EQUIP_PENDIENTE", "FC_VERIFICADO", "FC_PENDIENTE_COMPROBAR",
    "FC_ARGUMENTO", "FC_INCLUYE", "FC_NO_INCLUYE", "FC_PASO", "FC_HACEMOS", "FC_NO_HACEMOS",
    "FC_FAQ", "IG_FICHA", "FB_FICHA", "FB_INCLUYE", "MP_FICHA", "ST_PANTALLA", "RL_ESCENA",
    "PT_FICHA", "PT_ESTADO", "PT_INCLUYE", "CAMPO", "FUENTE_DATO", "PROOF", "PENDIENTE",
}
# Bloques "clave | valor"
KV = {"FC_SPEC": ("etiqueta", "valor"), "PT_FICHA": ("etiqueta", "valor"),
      "MP_FICHA": ("etiqueta", "valor"), "CAMPO": ("campo", "valor"),
      "FC_FAQ": ("pregunta", "respuesta"), "FC_PASO": ("cuando", "que"),
      "FUENTE_DATO": ("dato", "campo"), "PROOF": ("angulo", "campo")}
# Bloques con 3 partes
TRIPLES = {"RL_ESCENA": ("tiempo", "plano", "texto")}
# Bloques numéricos (se limpian a número cuando se puede)
NUMERICOS = {"FC_MERCADO_MIN", "FC_MERCADO_MEDIANA", "FC_MERCADO_MAX", "FC_MERCADO_N"}

# Nombre bonito de sección para el JSON de la ficha del cliente
MAPA_FICHA = {
    "FC_TITULO": "titulo", "FC_SUBTITULO": "subtitulo", "FC_PRECIO": "precio",
    "FC_PRECIO_NOTA": "precio_nota", "FC_ESTADO_PROCESO": "estado_proceso",
    "FC_RESUMEN_BUENO": "resumen_bueno", "FC_RESUMEN_OJO": "resumen_ojo",
    "FC_RESUMEN_PASO": "resumen_paso", "FC_SPEC": "spec", "FC_EQUIP": "equipamiento",
    "FC_EQUIP_PENDIENTE": "equipamiento_pendiente", "FC_VERIFICADO": "verificado",
    "FC_PENDIENTE_COMPROBAR": "pendiente_comprobar", "FC_FOTOS": "fotos",
    "FC_ARGUMENTO": "argumentos", "FC_MERCADO_MIN": "mercado_min",
    "FC_MERCADO_MEDIANA": "mercado_mediana", "FC_MERCADO_MAX": "mercado_max",
    "FC_MERCADO_N": "mercado_n", "FC_MERCADO_FECHA": "mercado_fecha",
    "FC_MERCADO_NOTA": "mercado_nota", "FC_INCLUYE": "incluye",
    "FC_NO_INCLUYE": "no_incluye", "FC_PASO": "pasos", "FC_HACEMOS": "hacemos",
    "FC_NO_HACEMOS": "no_hacemos", "FC_NO_GARANTIA": "no_garantia", "FC_FAQ": "faq",
    "FC_CTA": "cta", "FC_CONTACTO": "contacto", "FC_AVISO_LEGAL": "aviso_legal",
    "FC_FECHA_DATOS": "fecha_datos",
}


def parsear(texto):
    """[(nombre, contenido)] respetando el formato [MARCADOR] de la skill."""
    # Mismo criterio que App\\Support\\Esqueleto en Laravel: "[BLOQUE] valor" admite
    # continuación en las líneas siguientes, y un nombre repetido es una lista.
    bloques: list[list] = []
    for linea in texto.splitlines():
        if linea.lstrip().startswith("#"):
            continue
        m = BLOQUE_RE.match(linea)
        if m:
            bloques.append([m.group(1), m.group(2).strip()])
        elif bloques and linea.strip():
            bloques[-1][1] = (bloques[-1][1] + "\n" + linea.rstrip()).strip()
    return [(n, c) for n, c in bloques if c != ""]


def num(valor):
    limpio = re.sub(r"[^\d,.-]", "", valor).replace(".", "").replace(",", ".")
    try:
        f = float(limpio)
        return int(f) if f.is_integer() else f
    except ValueError:
        return valor


def valor_de(nombre, crudo):
    partes = [x.strip() for x in crudo.split("|")]
    if nombre in TRIPLES and len(partes) >= 3:
        return dict(zip(TRIPLES[nombre], partes[:3]))
    if nombre in KV:
        k, v = KV[nombre]
        return {k: partes[0], v: " | ".join(partes[1:]).strip() if len(partes) > 1 else ""}
    if nombre in ("FC_FOTOS", "FOTOS") and "|" in crudo:
        return [x for x in partes if x]
    if nombre in NUMERICOS:
        return num(crudo)
    return crudo


def a_json(ruta):
    """Convierte un esqueleto en disco. Envoltorio de a_json_desde_texto()."""
    return a_json_desde_texto(ruta.read_text(encoding="utf-8"), ruta.name)


def a_json_desde_texto(texto, nombre):
    """Convierte el contenido de un esqueleto (en memoria) al JSON canónico.

    Lo usa `empaquetar.py` para escribir contenido/json/*.json dentro del ZIP sin
    pasar por disco.
    """
    bloques = parsear(texto)
    crudo = {}
    for n, c in bloques:
        crudo.setdefault(n, []).append(c)

    datos = {}
    for n, valores in crudo.items():
        conv = [valor_de(n, v) for v in valores]
        if n in LISTAS or len(conv) > 1:
            datos[n] = conv if not (len(conv) == 1 and isinstance(conv[0], list)) else conv[0]
        else:
            datos[n] = conv[0]

    doc = {
        "_meta": {
            "archivo": nombre,
            "generado": date.today().isoformat(),
            "version": 2,
            "fuente": "esqueleto_a_json.py",
        },
        "_bloques": crudo,
    }
    if any(k.startswith("FC_") for k in crudo):
        ficha = {}
        for bloque, clave in MAPA_FICHA.items():
            if bloque in datos:
                ficha[clave] = datos[bloque]
        doc["tipo"] = "ficha_cliente"
        doc["ficha"] = ficha
        doc["coche_id"] = datos.get("COCHE_ID", "")
        faltan = [b for b in MAPA_FICHA if b not in crudo]
        doc["_meta"]["bloques_ausentes"] = faltan
    elif any(k.startswith(("IG_", "FB_", "MP_", "RL_", "ST_", "TT_", "YS_")) for k in crudo):
        doc["tipo"] = "redes_sociales"
        doc["canales"] = {
            "instagram_feed": {k[3:].lower(): datos[k] for k in datos if k.startswith("IG_")},
            "stories": datos.get("ST_PANTALLA", []),
            "video": {k: datos[k] for k in datos if k.startswith(("RL_", "TT_", "YS_"))},
            "facebook_pagina": {k[3:].lower(): datos[k] for k in datos if k.startswith("FB_")},
            "marketplace": {k[3:].lower(): datos[k] for k in datos if k.startswith("MP_")},
        }
        doc["coche_id"] = datos.get("COCHE_ID", "")
    elif any(k.startswith(("PT_", "WP_")) for k in crudo):
        doc["tipo"] = "anuncio_portales"
        doc["portales"] = {
            "base": {k[3:].lower(): datos[k] for k in datos if k.startswith("PT_")},
            "wallapop": {k[3:].lower(): datos[k] for k in datos if k.startswith("WP_")},
            "formulario": datos.get("CAMPO", []),
        }
        doc["coche_id"] = datos.get("COCHE_ID", "")
    else:
        doc["tipo"] = "desconocido"
        doc["datos"] = datos
    return doc


def main(argv):
    if len(argv) < 2:
        print(__doc__)
        return 2
    destino = None
    args = argv[1:]
    if "--out" in args:
        i = args.index("--out")
        destino = Path(args[i + 1])
        args = args[:i] + args[i + 2:]

    rutas = []
    for a in args:
        p = Path(a)
        if p.is_dir():
            rutas += sorted(p.glob("*.txt"))
        elif p.is_file():
            rutas.append(p)
    if not rutas:
        print("No hay .txt que convertir.")
        return 2

    for r in rutas:
        doc = a_json(r)
        out_dir = destino or r.parent
        out_dir.mkdir(parents=True, exist_ok=True)
        salida = out_dir / (r.stem + ".json")
        salida.write_text(json.dumps(doc, ensure_ascii=False, indent=2), encoding="utf-8")
        ausentes = doc["_meta"].get("bloques_ausentes") or []
        aviso = f"  ⚠️ {len(ausentes)} bloque(s) ausente(s)" if ausentes else ""
        print(f"✅ {r.name} → {salida.name}  ({doc['tipo']}){aviso}")
    return 0


if __name__ == "__main__":
    sys.exit(main(sys.argv))
