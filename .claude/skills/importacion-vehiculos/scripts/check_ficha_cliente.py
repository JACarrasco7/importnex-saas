#!/usr/bin/env python3
# -*- coding: utf-8 -*-
"""
check_ficha_cliente.py — validador de `ficha-cliente.txt`, la página que se manda
al cliente por enlace (/c/<token>).

Complementa a `check_marketing.py` (redes y portales). Se mantiene aparte para no
tocar los 30 checks que ese ya tiene.

Uso:
    python scripts/check_ficha_cliente.py contenido/
    python scripts/check_ficha_cliente.py contenido/ficha-cliente.txt

Severidad:
    🔴 ALTO   bloquea la entrega        🟠 MEDIO  se corrige        🟡 BAJO  criterio editorial

Reglas: 07-marketing/ficha_cliente.md · anti_patrones A18, A22, A22b, A26, A28, A29, A31
"""
import re
import sys
from pathlib import Path

BLOQUE_RE = re.compile(r"^\[([A-Z0-9_]+)\](.*)$")

REQUERIDOS = [
    "FC_FECHA_DATOS", "FC_TITULO", "FC_SUBTITULO", "FC_PRECIO", "FC_PRECIO_NOTA",
    "FC_ESTADO_PROCESO", "FC_RESUMEN_BUENO", "FC_RESUMEN_OJO", "FC_RESUMEN_PASO",
    "FC_SPEC", "FC_EQUIP", "FC_VERIFICADO", "FC_PENDIENTE_COMPROBAR", "FC_FOTOS",
    "FC_ARGUMENTO", "FC_MERCADO_MEDIANA", "FC_MERCADO_N", "FC_MERCADO_FECHA",
    "FC_INCLUYE", "FC_NO_INCLUYE", "FC_PASO", "FC_HACEMOS", "FC_NO_HACEMOS",
    "FC_NO_GARANTIA", "FC_FAQ", "FC_CTA", "FC_CONTACTO", "FC_AVISO_LEGAL",
]

SPEC_MINIMAS = ["marca", "versi", "año", "matricula", "kil", "combustible",
                "cambio", "potencia", "etiqueta", "propietario"]

# Fugas REALES detectadas en la ficha en producción el 07-sep-2026 (A22b).
INTERNOS = [
    "vendibilidad", r"\d{1,3}\s*/\s*100", r"\bscore\b", r"puntuaci[óo]n",
    r"hueco\s+(de|del|%)", r"antes\s+de\s+(costes|gastos)", "ahorro estimado",
    r"\bmargen\b", "precio de origen", "coste puesto",
    "mercado muy estrecho", r"unidades\s+(disponibles|en\s+toda)", r"en\s+toda\s+espa[ñn]a",
    r"\bcomparables?\b", "vendedor profesional", "vendedor alem", "★",
    "operaciones de exportaci", "mobile.de", "autoscout", "kleinanzeigen",
    r"honorarios?\D{0,15}\d", "veredicto", "excelente compra", "buena compra",
]
VENDEDOR = [
    r"(?<!no )\bvendemos\b", r"\bse vende\b", r"\ben venta\b",
    r"nuestro coche|nuestros coches|nuestro stock",
    r"(nuestro|nuestra|somos un?|en nuestro)\s+concesionario",
    r"(?<!no )\bgarantizamos\b", r"con garant[ií]a", r"garant[ií]a de \d+\s*(meses|a[ñn]os)",
]
SUPERLATIVOS = [
    r"\bchollo\b", r"\bganga\b", "irrepetible", r"oportunidad (única|unica)",
    "impecable", "inmaculado", r"\bjoya\b", "no te lo pierdas", "garantizado",
]
PRECIO_PROHIBIDO = r"iva incluido|impuestos incluidos|precio final|llave en mano|sin sorpresas"


def parsear(texto):
    bloques, actual, buf = [], None, []
    for linea in texto.splitlines():
        if linea.lstrip().startswith("#"):
            continue
        m = BLOQUE_RE.match(linea)
        if m:
            if actual is not None:
                bloques.append((actual, "\n".join(buf).strip()))
            actual, buf = m.group(1), []
            resto = m.group(2).strip()
            if resto:
                bloques.append((actual, resto))
                actual, buf = None, []
        elif actual is not None:
            buf.append(linea)
    if actual is not None:
        bloques.append((actual, "\n".join(buf).strip()))
    return [(n, c) for n, c in bloques if c != ""]


def revisar(ruta):
    """@return list[tuple[severidad, bloque, mensaje]]"""
    bloques = parsear(ruta.read_text(encoding="utf-8"))
    nombres = {n for n, _ in bloques}
    hallazgos = []

    def alto(b, m):
        hallazgos.append(("ALTO", b, m))

    def medio(b, m):
        hallazgos.append(("MEDIO", b, m))

    def bajo(b, m):
        hallazgos.append(("BAJO", b, m))

    if not bloques:
        return [("ALTO", "-", "archivo vacío o sin bloques [MARCADOR]")]

    for req in REQUERIDOS:
        if req not in nombres:
            alto(req, "bloque obligatorio ausente")

    if "PENDIENTE" in nombres:
        alto("PENDIENTE", "quedan puntos pendientes: la ficha NO se envía al cliente")

    texto = " ".join(c for _, c in bloques).lower()
    for patron in INTERNOS:
        if re.search(patron, texto, re.I):
            alto("FICHA", f"dato interno en una página pública (A22b): «{patron}»")
    for patron in VENDEDOR:
        for m in re.finditer(patron, texto, re.I):
            previo = texto[max(0, m.start() - 20):m.start()].lower()
            # "no vendemos", "no somos concesionario", "no es el vendedor": son la negación,
            # que es justo lo que la ficha DEBE decir.
            if re.search(r"\bno\s+(somos|es|damos|ofrec\w*|hay)?\s*$", previo):
                continue
            alto("FICHA", f"lenguaje de vendedor o de garantía (A31): «{m.group(0)}»")
            break
    for patron in SUPERLATIVOS:
        if re.search(patron, texto, re.I):
            alto("FICHA", f"superlativo sin prueba (A29): «{patron}»")

    specs = " ".join(c for n, c in bloques if n == "FC_SPEC").lower()
    faltan = [x for x in SPEC_MINIMAS if x not in specs]
    if faltan:
        alto("FC_SPEC", "faltan campos de ficha técnica: " + ", ".join(faltan))

    precio = " ".join(c for n, c in bloques if n in ("FC_PRECIO", "FC_PRECIO_NOTA"))
    if re.search(PRECIO_PROHIBIDO, precio, re.I):
        alto("FC_PRECIO", "no vendemos el coche: prohibido «IVA incluido», «precio final», "
                          "«llave en mano» o «sin sorpresas» (A31)")
    if re.search(r"a consultar|\bdesde\b", precio, re.I):
        alto("FC_PRECIO", "el precio no puede ser «desde» ni «a consultar»")

    nogar = next((c for n, c in bloques if n == "FC_NO_GARANTIA"), "")
    if nogar and "no ofrece garant" not in nogar.lower():
        alto("FC_NO_GARANTIA", "el bloque fijo se ha reescrito: debe decir que "
                               "JJ Import Motors no ofrece garantía (A31)")

    aviso = next((c for n, c in bloques if n == "FC_AVISO_LEGAL"), "").lower()
    for exigido, msg in (("no es vendedora", "que no somos vendedores"),
                         ("gastos de gesti", "que al precio se suman los gastos de gestión de compra"),
                         ("no ofrece garant", "que no ofrecemos garantía")):
        if aviso and exigido not in aviso:
            alto("FC_AVISO_LEGAL", f"el aviso legal debe declarar {msg}")

    if len([c for n, c in bloques if n == "FC_CTA"]) > 1:
        alto("FC_CTA", "solo puede haber un CTA")

    if "FC_PENDIENTE_COMPROBAR" not in nombres:
        alto("FC_PENDIENTE_COMPROBAR", "falta lo que queda por comprobar: la honestidad "
                                       "es lo que sostiene la ficha (A28)")

    pasos = [c for n, c in bloques if n == "FC_PASO"]
    if any(re.search(r"\b\d{1,2}/\d{1,2}/\d{4}\b", pp.split("|")[0]) for pp in pasos):
        alto("FC_PASO", "los plazos van en semanas estimadas, nunca en fechas cerradas")
    if len(pasos) < 4:
        medio("FC_PASO", f"solo {len(pasos)} pasos del proceso (mínimo 4)")

    faqs = [c for n, c in bloques if n == "FC_FAQ"]
    if len(faqs) < 6:
        medio("FC_FAQ", f"solo {len(faqs)} preguntas frecuentes (mínimo 6)")
    for f in faqs:
        if "|" not in f:
            medio("FC_FAQ", f"formato «Pregunta | Respuesta»: «{f[:40]}»")

    fotos = next((c for n, c in bloques if n == "FC_FOTOS"), "")
    n_fotos = len([x for x in fotos.split("|") if x.strip()]) if fotos else 0
    if 0 < n_fotos < 5:
        alto("FC_FOTOS", f"solo {n_fotos} fotos reales (mínimo 5)")
    if n_fotos > 12:
        medio("FC_FOTOS", f"{n_fotos} fotos: la galería visible se queda en 8-10 y el resto "
                          "va tras «ver todas»")

    for n, c in bloques:
        if n.startswith("FC_") and re.search(r"\bpor confirmar\b", c, re.I) is None:
            continue
    equip_pend = [c for n, c in bloques if n == "FC_EQUIP_PENDIENTE"]
    if not equip_pend:
        bajo("FC_EQUIP_PENDIENTE", "sin equipamiento por confirmar: comprueba que todo lo "
                                   "publicado está verificado en la ficha real (A18)")

    return hallazgos


def main(argv):
    if len(argv) < 2:
        print(__doc__)
        return 2

    rutas = []
    for a in argv[1:]:
        p = Path(a)
        if p.is_dir():
            rutas += sorted(p.glob("ficha-cliente*.txt"))
        elif p.is_file():
            rutas.append(p)
    if not rutas:
        print("No hay ficha-cliente.txt que revisar.")
        return 2

    iconos = {"ALTO": "🔴", "MEDIO": "🟠", "BAJO": "🟡"}
    total_alto = 0

    for ruta in rutas:
        hallazgos = revisar(ruta)
        altos = [h for h in hallazgos if h[0] == "ALTO"]
        total_alto += len(altos)
        if altos:
            estado = "🔴 BLOQUEADO — no se envía al cliente"
        elif hallazgos:
            estado = "🟠 CORREGIR antes de enviar"
        else:
            estado = "✅ OK"
        print(f"\n{estado}  {ruta.name}")
        for nivel in ("ALTO", "MEDIO", "BAJO"):
            for sev, bloque, msg in [h for h in hallazgos if h[0] == nivel]:
                print(f"   {iconos[sev]} {sev:<5} [{bloque}] {msg}")

    print("\n" + "-" * 66)
    print(f"{len(rutas)} archivo(s) · {total_alto} hallazgo(s) de severidad ALTA")
    print("Reglas: 07-marketing/ficha_cliente.md · A18, A22, A22b, A26, A28, A29, A31")
    return 1 if total_alto else 0


if __name__ == "__main__":
    sys.exit(main(sys.argv))
