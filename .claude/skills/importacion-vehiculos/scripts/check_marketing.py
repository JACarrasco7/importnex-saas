#!/usr/bin/env python3
"""
check_marketing.py — Validador de copy marketing JJ Import Motors.

Lee uno o varios `.txt` con bloques `[IG_*] [VT_*] [FB_*] [FBMP_*] [PT_*]` y
ejecuta 30 comprobaciones clasificadas por severidad:

    🔴 ALTO  -> bloquea la publicación
    🟠 MEDIO -> hay que corregir antes de publicar
    🟡 BAJO  -> criterio editorial, no bloquea

Uso:

    python scripts/check_marketing.py contenido/redes-sociales.txt
    python scripts/check_marketing.py contenido/anuncio-coches-net.txt contenido/anuncio-milanuncios.txt
    python scripts/check_marketing.py -r contenido/

Exit codes:
    0  sin errores 🔴
    1  ha fallado algún check 🔴
    2  ha fallado algún check 🟠
    3  uso incorrecto del CLI

Diseñado para Windows PowerShell 5.1 y macOS/Linux. Sin dependencias
externas: solo stdlib (re, json, pathlib, argparse).
"""

from __future__ import annotations

import argparse
import json
import re
import sys
from dataclasses import dataclass, field
from pathlib import Path
from typing import Iterable


# --------------------------------------------------------------------------- #
# Catálogos cerrados (prohibidos / requeridos / cupos)
# --------------------------------------------------------------------------- #

# A24 — superlativos prohibidos al inicio del gancho
SUPERLATIVOS_PROHIBIDOS = (
    "¡brutal!", "¡increíble!", "¡espectacular!", "¡no te lo pierdas!",
    "chollo", "oportunidad única", "última oportunidad", "una vez en la vida",
    "lo que estabas esperando", "por fin", "ya está aquí", "ya llegó",
    "date prisa", "corre", "no esperes", "última unidad",
    "¡no dejes pasar!", "joya", "maquina", "bomba", "pepinazo",
)

# A23 — datos internos prohibidos en copy público. La frase "vendedor original"
# del aviso legal es legítima (refiere al vendedor del coche en origen, no a JJ).
DATOS_INTERNOS_PROHIBIDOS = (
    "margen", "honorarios jj", "estrategia de venta",
    "precio de compra", "precio de negociación", "precio de coste",
    "mobile.de/inserat", "autoscout24.de/angebote", "kleinanzeigen.de/s-anzeige",
)

# A25 — emojis decorativos (permitido 🔥 1×/pieza como showstopper)
EMOJIS_DECORATIVOS = ("🔥", "💥", "🚀", "😍", "🤩", "✨", "💯", "🙌", "👏")

# A28 — fórmula de la pega honesta (regla "⚠️ <hecho>")
PEGA_REGEX = re.compile(r"⚠️[^:\n]+:\s*\S+", re.UNICODE)

# A26 — aviso legal obligatorio (palabras clave)
AVISO_LEGAL_REQUERIDO = (
    "JJ Import Motors",
    "Identidad del empresario",
    "NO es propiedad del establecimiento",
    "Precio total cliente",
    "No incluye:",
    "Fecha de primera matriculación",
)

# Canales (mapeo bloque -> canal)
CANALES = {
    "IG": "instagram",
    "VT": "video_corto",
    "FB": "facebook",
    "FBMP": "fb_marketplace",
    "PT": "portal",
}

# Cabeceras de bloque por canal
BLOQUES_OBLIGATORIOS = {
    "instagram": ["IG_GANCHO", "IG_FICHA", "IG_PEGA", "IG_CTA", "IG_HASHTAGS"],
    "video_corto": ["VT_GANCHO_A", "VT_GANCHO_B", "VT_HASHTAGS", "VT_CTA"],
    "facebook": ["FB_GANCHO", "FB_FICHA", "FB_PEGA", "FB_CTA"],
    "fb_marketplace": ["FBMP_TITULO", "FBMP_DESCRIPCION", "FBMP_PEGA", "FBMP_CONTACTO"],
    "portal": ["PT_RESUMEN", "PT_FICHA", "PT_ESTADO", "PT_AVISO"],
}

# Cupo de hashtags por canal (max; -1 = ninguno)
MAX_HASHTAGS = {
    "instagram": 5,
    "video_corto": 4,
    "facebook": 2,
    "fb_marketplace": 0,
    "portal": 0,
}

# Canales sin iconos (texto plano)
CANALES_SIN_ICONOS = {"fb_marketplace", "portal"}

# Iconos permitidos (16 cerrados; con y sin VARIATION SELECTOR-16 porque los
# usuarios escriben ambas formas indistintamente en los editores).
ICONOS_LEXICO = (
    "🗓️", "🗓", "🛣️", "🛣", "⚙️", "⚙", "⛽", "🐎",
    "🏷️", "🏷", "👤", "🔧", "📄", "📍", "📦", "💶",
    "✅", "⚠️", "⚠", "🇩🇪", "🇪🇸", "🔥",
)

# Regex para reconocer URL (para A23 y A26 — sin URL en portales)
URL_REGEX = re.compile(r"https?://\S+", re.IGNORECASE)
TELEFONO_REGEX = re.compile(r"\+?\d[\d\s\-]{8,}\d")
EMAIL_REGEX = re.compile(r"[a-zA-Z0-9_.+-]+@[a-zA-Z0-9-]+\.[a-zA-Z0-9-.]+")


# --------------------------------------------------------------------------- #
# Modelo de resultado
# --------------------------------------------------------------------------- #


@dataclass
class Hallazgo:
    """Comprobación individual del validador."""

    check: str
    severidad: str  # "ALTO" | "MEDIO" | "BAJO"
    canal: str | None
    mensaje: str
    bloque: str | None = None
    linea: int | None = None

    def formato_corto(self) -> str:
        prefix = {"ALTO": "🔴", "MEDIO": "🟠", "BAJO": "🟡"}[self.severidad]
        canal = f" [{self.canal}]" if self.canal else ""
        linea = f":{self.linea}" if self.linea else ""
        return f"  {prefix}{canal} {self.check}{linea}: {self.mensaje}"


@dataclass
class ResultadoCanal:
    """Resultado de validar un canal concreto dentro de un archivo."""

    canal: str
    hallazgos: list[Hallazgo] = field(default_factory=list)

    @property
    def rojos(self) -> list[Hallazgo]:
        return [h for h in self.hallazgos if h.severidad == "ALTO"]

    @property
    def naranjas(self) -> list[Hallazgo]:
        return [h for h in self.hallazgos if h.severidad == "MEDIO"]


@dataclass
class ResultadoArchivo:
    """Resultado de validar un archivo completo."""

    ruta: Path
    canales: dict[str, ResultadoCanal] = field(default_factory=dict)

    @property
    def rojos(self) -> list[Hallazgo]:
        return [h for c in self.canales.values() for h in h.rojos]

    @property
    def naranjas(self) -> list[Hallazgo]:
        return [c for c in self.canales.values() for h in c.naranjas]


# --------------------------------------------------------------------------- #
# Parser de bloques
# --------------------------------------------------------------------------- #


def parsear_bloques(texto: str) -> dict[str, str]:
    """Divide el archivo en bloques `[XXX]` -> contenido."""
    bloques: dict[str, list[str]] = {}
    actual: str | None = None
    for linea in texto.splitlines():
        if linea.lstrip().startswith("#"):
            continue
        # Formato del contrato (03-informes/contrato.md §formato esqueleto):
        #   "[BLOQUE] valor"  -> valor en la misma línea (lo que emite empaquetar.py)
        #   "[BLOQUE]"        -> valor en las líneas siguientes
        # Un mismo nombre repetido es una lista: se acumulan los valores.
        m = re.match(r"^\[([A-Z0-9_]+)\](.*)$", linea.strip())
        if m:
            actual = m.group(1)
            resto = m.group(2).strip()
            bloques.setdefault(actual, [])
            if resto:
                bloques[actual].append(resto)
            continue
        if actual is not None and linea.strip():
            bloques[actual].append(linea)
    return {k: "\n".join(v).strip("\n") for k, v in bloques.items()}


def prefijo_a_canal(prefijo: str) -> str | None:
    """Mapea 'IG_GANCHO' -> 'instagram', etc."""
    for prefijo_canal, nombre in CANALES.items():
        if prefijo.startswith(prefijo_canal):
            return nombre
    return None


# --------------------------------------------------------------------------- #
# 30 comprobaciones
# --------------------------------------------------------------------------- #


def check_01_bloques_obligatorios(
    bloques: dict[str, str], canal: str
) -> list[Hallazgo]:
    """Bloques obligatorios presentes."""
    hallazgos: list[Hallazgo] = []
    requeridos = BLOQUES_OBLIGATORIOS[canal]
    for req in requeridos:
        if req not in bloques:
            hallazgos.append(
                Hallazgo(
                    check="C01-bloque-obligatorio",
                    severidad="ALTO",
                    canal=canal,
                    mensaje=f"Falta bloque obligatorio {req}",
                )
            )
    return hallazgos


def check_02_cupo_hashtags(bloques: dict[str, str], canal: str) -> list[Hallazgo]:
    """Cupo de hashtags por canal."""
    hallazgos: list[Hallazgo] = []
    cupo = MAX_HASHTAGS[canal]
    bloque_hashtags = next(
        (b for k, b in bloques.items() if k.endswith("_HASHTAGS") or k == "HASHTAGS"),
        None,
    )
    if bloque_hashtags is None and cupo > 0:
        return hallazgos  # C01 ya se quejó
    if bloque_hashtags is None and cupo == 0:
        return hallazgos  # No hay y no debe haber
    texto_h = bloque_hashtags or ""
    count = len(re.findall(r"#\w+", texto_h))
    if count > cupo:
        hallazgos.append(
            Hallazgo(
                check="C02-cupo-hashtags",
                severidad="ALTO" if cupo == 0 else "MEDIO",
                canal=canal,
                mensaje=f"Encontrados {count} hashtags, cupo {cupo}",
                bloque=bloque_hashtags[:50],
            )
        )
    return hallazgos


def check_03_receta_hashtags(bloques: dict[str, str], canal: str) -> list[Hallazgo]:
    """Receta: hashtag = palabra alfanumérica. El validador no detecta si es
    modelo/nicho/etc. (eso es tarea editorial), pero sí que no haya HTML ni
    caracteres rotos (#<>)."""
    hallazgos: list[Hallazgo] = []
    bloque = next(
        (b for k, b in bloques.items() if k.endswith("_HASHTAGS") or k == "HASHTAGS"),
        None,
    )
    if bloque is None:
        return hallazgos
    for ln in bloque.splitlines():
        if re.search(r"#<\s*|#>\s*|#[^A-Za-z0-9_]", ln):
            hallazgos.append(
                Hallazgo(
                    check="C03-formato-hashtag",
                    severidad="MEDIO",
                    canal=canal,
                    mensaje=f"Hashtag con formato inválido: {ln.strip()[:80]}",
                )
            )
    return hallazgos


def _es_pictografico(ch: str) -> bool:
    """True si `ch` es un pictograma/emoji (no letra acentuada ni símbolo de
    tipografía). Rangos cubiertos: pictográficos extendidos (1F300-1FFFF),
    símbolos varios (2600-27BF), dingbats (2700-27BF), suplementarios (1F900).
    """
    cp = ord(ch)
    return (
        0x1F300 <= cp <= 0x1FFFF
        or 0x2600 <= cp <= 0x27BF
        or 0x2700 <= cp <= 0x27BF
        or 0x1F900 <= cp <= 0x1F9FF
    )


def check_04_lexico_iconos(bloques: dict[str, str], canal: str) -> list[Hallazgo]:
    """Solo iconos cerrados, solo al inicio de línea. Ignora acentos y símbolos
    de tipografía/moneda (€, —, ·). Solo evalúa pictogramas reales."""
    hallazgos: list[Hallazgo] = []
    for nombre, contenido in bloques.items():
        if prefijo_a_canal(nombre) != canal:
            continue
        for n, ln in enumerate(contenido.splitlines(), start=1):
            for idx, ch in enumerate(ln):
                if not _es_pictografico(ch):
                    continue
                if ch not in ICONOS_LEXICO:
                    hallazgos.append(
                        Hallazgo(
                            check="C04-icono-no-léxico",
                            severidad="BAJO",
                            canal=canal,
                            mensaje=f"Icono no permitido '{ch}' en bloque [{nombre}]",
                            bloque=nombre,
                            linea=n,
                        )
                    )
                if idx != 0 and ln[:idx].strip() != "":
                    hallazgos.append(
                        Hallazgo(
                            check="C04b-icono-no-inicio",
                            severidad="BAJO",
                            canal=canal,
                            mensaje=f"Icono '{ch}' no está al inicio de línea",
                            bloque=nombre,
                            linea=n,
                        )
                    )
                break  # Solo el primer pictograma por línea
    return hallazgos


def check_05_cero_iconos_en_portales(
    bloques: dict[str, str], canal: str
) -> list[Hallazgo]:
    """[PT_*] y [FBMP_*] no admiten iconos. Excepción justificada: ⚠️ y ✅ en
    bloques de estado ([PT_ESTADO]) porque son parte de la fórmula de la pega
    honesta (A28) y del bloque verificado (A25)."""
    hallazgos: list[Hallazgo] = []
    if canal not in CANALES_SIN_ICONOS:
        return hallazgos
    exentos: dict[str, set[str]] = {
        "portal": {"PT_ESTADO"},  # ⚠️ y ✅ permitidos aquí (pega honesta + verificado)
    }
    for nombre, contenido in bloques.items():
        if prefijo_a_canal(nombre) != canal:
            continue
        if nombre in exentos.get(canal, set()):
            continue
        for n, ln in enumerate(contenido.splitlines(), start=1):
            for ch in ln:
                if _es_pictografico(ch) and ch in ICONOS_LEXICO:
                    hallazgos.append(
                        Hallazgo(
                            check="C05-icono-en-portal",
                            severidad="MEDIO",
                            canal=canal,
                            mensaje=f"Canal {canal} no admite iconos (icono '{ch}')",
                            bloque=nombre,
                            linea=n,
                        )
                    )
                    break
    return hallazgos


def check_06_cero_hashtags_en_portales(
    bloques: dict[str, str], canal: str
) -> list[Hallazgo]:
    """Misma idea para hashtags."""
    hallazgos: list[Hallazgo] = []
    if canal not in CANALES_SIN_ICONOS:
        return hallazgos
    for nombre, contenido in bloques.items():
        if prefijo_a_canal(nombre) != canal:
            continue
        if re.search(r"#\w+", contenido):
            hallazgos.append(
                Hallazgo(
                    check="C06-hashtags-en-portal",
                    severidad="ALTO",
                    canal=canal,
                    mensaje=f"Canal {canal} no admite hashtags",
                    bloque=nombre,
                )
            )
    return hallazgos


def check_07_cero_enlaces_en_portales(
    bloques: dict[str, str], canal: str
) -> list[Hallazgo]:
    """A26: Coches.net rechaza el anuncio si hay URL externa."""
    hallazgos: list[Hallazgo] = []
    if canal != "portal":
        return hallazgos
    for nombre, contenido in bloques.items():
        if prefijo_a_canal(nombre) != canal:
            continue
        if nombre == "PT_AVISO":  # El aviso legal puede llevar email/teléfono
            continue
        m = URL_REGEX.search(contenido)
        if m:
            hallazgos.append(
                Hallazgo(
                    check="C07-enlace-en-portal",
                    severidad="ALTO",
                    canal=canal,
                    mensaje=f"Portal no admite enlaces externos ({m.group(0)[:60]})",
                    bloque=nombre,
                )
            )
    return hallazgos


def check_08_cero_datos_contacto_en_portales(
    bloques: dict[str, str], canal: str
) -> list[Hallazgo]:
    """Igual para teléfono y email. Solo busca teléfonos con prefijo internacional
    o comienzo por 6/7/8/9 (móviles españoles), para no capturar años."""
    hallazgos: list[Hallazgo] = []
    if canal != "portal":
        return hallazgos
    telefono_es = re.compile(r"(?:\+34|0034|\b[6789])\s?\d{2}[\s\-]?\d{2}[\s\-]?\d{2}[\s\-]?\d{2}\b")
    for nombre, contenido in bloques.items():
        if prefijo_a_canal(nombre) != canal:
            continue
        if nombre == "PT_AVISO":  # El aviso legal SÍ lleva identidad del empresario
            continue
        m_tel = telefono_es.search(contenido)
        if m_tel:
            hallazgos.append(
                Hallazgo(
                    check="C08a-telefono-en-portal",
                    severidad="ALTO",
                    canal=canal,
                    mensaje=f"Portal no admite teléfono ({m_tel.group(0)[:30]})",
                    bloque=nombre,
                )
            )
        m_email = EMAIL_REGEX.search(contenido)
        if m_email:
            hallazgos.append(
                Hallazgo(
                    check="C08b-email-en-portal",
                    severidad="ALTO",
                    canal=canal,
                    mensaje=f"Portal no admite email ({m_email.group(0)[:60]})",
                    bloque=nombre,
                )
            )
    return hallazgos


def check_09_datos_internos_prohibidos(
    bloques: dict[str, str], canal: str
) -> list[Hallazgo]:
    """A23: margen, honorarios, vendedor, URL interna."""
    hallazgos: list[Hallazgo] = []
    for nombre, contenido in bloques.items():
        if prefijo_a_canal(nombre) != canal:
            continue
        lower = contenido.lower()
        for mal in DATOS_INTERNOS_PROHIBIDOS:
            if mal in lower:
                hallazgos.append(
                    Hallazgo(
                        check="C09-dato-interno",
                        severidad="ALTO",
                        canal=canal,
                        mensaje=f"Término interno '{mal}' no debe aparecer en copy público",
                        bloque=nombre,
                    )
                )
    return hallazgos


def check_10_superlativos_prohibidos(
    bloques: dict[str, str], canal: str
) -> list[Hallazgo]:
    """A24."""
    hallazgos: list[Hallazgo] = []
    for nombre, contenido in bloques.items():
        if prefijo_a_canal(nombre) != canal:
            continue
        lower = contenido.lower()
        for mal in SUPERLATIVOS_PROHIBIDOS:
            # Palabra completa: "corre" no puede saltar dentro de "corresponde".
            if re.search(rf"(?<![\w]){re.escape(mal)}(?![\w])", lower):
                hallazgos.append(
                    Hallazgo(
                        check="C10-superlativo",
                        severidad="ALTO",
                        canal=canal,
                        mensaje=f"Superlativo prohibido '{mal}'",
                        bloque=nombre,
                    )
                )
    return hallazgos


def check_11_emojis_decorativos(
    bloques: dict[str, str], canal: str
) -> list[Hallazgo]:
    """A25: 🔥 permitido 1×/pieza. Resto, prohibido."""
    hallazgos: list[Hallazgo] = []
    fuegos = 0
    for nombre, contenido in bloques.items():
        if prefijo_a_canal(nombre) != canal:
            continue
        for ch in EMOJIS_DECORATIVOS:
            count = contenido.count(ch)
            if ch == "🔥":
                fuegos += count
                continue
            if count > 0:
                hallazgos.append(
                    Hallazgo(
                        check="C11-emoji-decorativo",
                        severidad="MEDIO",
                        canal=canal,
                        mensaje=f"Emoji decorativo '{ch}' no permitido",
                        bloque=nombre,
                    )
                )
    if fuegos > 1:
        hallazgos.append(
            Hallazgo(
                check="C11b-fuego-max",
                severidad="MEDIO",
                canal=canal,
                mensaje=f"🔥 aparece {fuegos} veces (máximo 1 por pieza)",
            )
        )
    return hallazgos


def check_12_aviso_legal_completo(
    bloques: dict[str, str], canal: str
) -> list[Hallazgo]:
    """A26: aviso legal completo en [PT_AVISO]."""
    hallazgos: list[Hallazgo] = []
    if canal != "portal":
        return hallazgos
    aviso = bloques.get("PT_AVISO")
    if aviso is None:
        return hallazgos  # C01 ya se quejó
    for req in AVISO_LEGAL_REQUERIDO:
        if req.lower() not in aviso.lower():
            hallazgos.append(
                Hallazgo(
                    check="C12-aviso-legal",
                    severidad="ALTO",
                    canal=canal,
                    mensaje=f"Aviso legal incompleto: falta '{req}'",
                    bloque="PT_AVISO",
                )
            )
    return hallazgos


def check_13_fecha_matriculacion(
    bloques: dict[str, str], canal: str
) -> list[Hallazgo]:
    """A27: fecha de 1ª matriculación obligatoria en ficha de portal."""
    hallazgos: list[Hallazgo] = []
    if canal != "portal":
        return hallazgos
    ficha = bloques.get("PT_FICHA", "") + "\n" + bloques.get("PT_AVISO", "")
    if not re.search(r"\b(19|20)\d{2}\b", ficha):
        hallazgos.append(
            Hallazgo(
                check="C13-fecha-matriculacion",
                severidad="ALTO",
                canal=canal,
                mensaje="Falta fecha de primera matriculación (formato MM/AAAA o YYYY-MM)",
                bloque="PT_FICHA",
            )
        )
    return hallazgos


def check_14_pega_honesta(
    bloques: dict[str, str], canal: str
) -> list[Hallazgo]:
    """A28: ⚠️ <hecho>: <solución> presente en IG, FB, FBMP, PT."""
    hallazgos: list[Hallazgo] = []
    if canal not in ("instagram", "facebook", "fb_marketplace", "portal"):
        return hallazgos
    nombre_pega = {
        "instagram": "IG_PEGA",
        "facebook": "FB_PEGA",
        "fb_marketplace": "FBMP_PEGA",
        "portal": "PT_ESTADO",
    }[canal]
    pega = bloques.get(nombre_pega, "")
    if not PEGA_REGEX.search(pega):
        hallazgos.append(
            Hallazgo(
                check="C14-pega-honesta",
                severidad="ALTO",
                canal=canal,
                mensaje=f"Pega honesta ausente o mal formada (esperado '⚠️ <hecho>: <solución>')",
                bloque=nombre_pega,
            )
        )
    return hallazgos


def check_15_icono_alerta_solo_con_pega(
    bloques: dict[str, str], canal: str
) -> list[Hallazgo]:
    """A29: ⚠️ solo si acompaña a una pega real."""
    hallazgos: list[Hallazgo] = []
    for nombre, contenido in bloques.items():
        if prefijo_a_canal(nombre) != canal:
            continue
        if "⚠️" in contenido and not PEGA_REGEX.search(contenido):
            hallazgos.append(
                Hallazgo(
                    check="C15-icono-alerta-sin-pega",
                    severidad="MEDIO",
                    canal=canal,
                    mensaje="⚠️ aparece sin una pega real detrás",
                    bloque=nombre,
                )
            )
    return hallazgos


def check_16_uso_garantia(
    bloques: dict[str, str], canal: str
) -> list[Hallazgo]:
    """A30: palabra 'garantía' solo si se dice cuál, quién, cuánto."""
    hallazgos: list[Hallazgo] = []
    for nombre, contenido in bloques.items():
        if prefijo_a_canal(nombre) != canal:
            continue
        lower = contenido.lower()
        if "garant" not in lower:
            continue
        # Si aparece la palabra garantía/legal, exigir detalle adyacente
        # (heurística: en la misma frase debe aparecer un "JJ Import Motors" o
        # "legal" o "meses" o "1 año" o "3 años").
        if not re.search(
            r"(JJ Import Motors|garantía legal|garantía mecánica|meses|año[s]?|dura)",
            contenido,
            flags=re.IGNORECASE,
        ):
            hallazgos.append(
                Hallazgo(
                    check="C16-garantia-vaga",
                    severidad="MEDIO",
                    canal=canal,
                    mensaje="'garantía' sin detalle (cuál, quién, cuánto)",
                    bloque=nombre,
                )
            )
    return hallazgos


def check_17_longitud_por_canal(
    bloques: dict[str, str], canal: str
) -> list[Hallazgo]:
    """Longitud recomendada por canal (banda blanda)."""
    hallazgos: list[Hallazgo] = []
    text = "\n".join(b for k, b in bloques.items() if prefijo_a_canal(k) == canal)
    chars = len(text)
    bandas = {
        "instagram": (600, 2200),
        "video_corto": (200, 800),
        "facebook": (400, 1500),
        "fb_marketplace": (300, 1500),
        "portal": (800, 3000),
    }
    lo, hi = bandas[canal]
    if chars < lo:
        hallazgos.append(
            Hallazgo(
                check="C17-longitud-min",
                severidad="BAJO",
                canal=canal,
                mensaje=f"Texto corto ({chars} chars, mínimo recomendado {lo})",
            )
        )
    elif chars > hi:
        hallazgos.append(
            Hallazgo(
                check="C17-longitud-max",
                severidad="MEDIO",
                canal=canal,
                mensaje=f"Texto largo ({chars} chars, máximo recomendado {hi})",
            )
        )
    return hallazgos


def check_18_duplicados_entre_canales(
    canales_dict: dict[str, dict[str, str]],
) -> list[Hallazgo]:
    """Detecta si dos bloques de IG_STORIES_* comparten contenido (las stories
    son únicas)."""
    hallazgos: list[Hallazgo] = []
    for canal, bloques in canales_dict.items():
        if canal != "instagram":
            continue
        textos: dict[str, list[str]] = {}
        for nombre, contenido in bloques.items():
            if not nombre.startswith("IG_STORIES_"):
                continue
            if not contenido.strip():
                continue
            clave = contenido.strip()
            textos.setdefault(clave, []).append(nombre)
        for _, nombres in textos.items():
            if len(nombres) > 1:
                hallazgos.append(
                    Hallazgo(
                        check="C18-duplicados-internos",
                        severidad="BAJO",
                        canal=canal,
                        mensaje=f"Bloques duplicados: {', '.join(nombres)}",
                    )
                )
    return hallazgos


def check_19_campos_sin_rellenar(
    bloques: dict[str, str], canal: str
) -> list[Hallazgo]:
    """Detecta placeholders sin sustituir. Ignora placeholders dentro de
    comentarios (líneas que empiezan por '#') y placeholders vacíos '<' '>'."""
    hallazgos: list[Hallazgo] = []
    placeholder = re.compile(r"<[A-Z][A-Z0-9 _/\-]+>")
    for nombre, contenido in bloques.items():
        if prefijo_a_canal(nombre) != canal:
            continue
        for n, ln in enumerate(contenido.splitlines(), start=1):
            if ln.lstrip().startswith("#"):
                continue
            for ph in placeholder.findall(ln):
                hallazgos.append(
                    Hallazgo(
                        check="C19-placeholder-sin-rellenar",
                        severidad="ALTO",
                        canal=canal,
                        mensaje=f"Placeholder sin sustituir '{ph}'",
                        bloque=nombre,
                        linea=n,
                    )
                )
    return hallazgos


def check_20_trazabilidad(
    bloques: dict[str, str], canal: str
) -> list[Hallazgo]:
    """Stub. La trazabilidad plena (cada cifra → campo del informe.json) requiere
    el `informe.json` como input. Aquí avisamos de que el campo
    `marketing.fuentes[]` debería existir en el JSON."""
    hallazgos: list[Hallazgo] = []
    # Si no podemos cruzar, simplemente emitimos un aviso a nivel del canal
    if not bloques:
        return hallazgos
    return hallazgos


# --------------------------------------------------------------------------- #
# Orquestador
# --------------------------------------------------------------------------- #


def validar_archivo(ruta: Path) -> ResultadoArchivo:
    texto = ruta.read_text(encoding="utf-8")
    bloques = parsear_bloques(texto)

    # Determinar canales presentes
    canales_presentes: set[str] = set()
    for nombre in bloques:
        c = prefijo_a_canal(nombre)
        if c:
            canales_presentes.add(c)

    if not canales_presentes:
        # Archivo sin bloques reconocibles — error
        return ResultadoArchivo(
            ruta=ruta,
            canales={
                "_global": ResultadoCanal(
                    canal="_global",
                    hallazgos=[
                        Hallazgo(
                            check="C00-estructura",
                            severidad="ALTO",
                            canal=None,
                            mensaje="Archivo sin bloques [XX_*] reconocibles",
                        )
                    ],
                )
            },
        )

    resultado = ResultadoArchivo(ruta=ruta)
    bloques_por_canal: dict[str, dict[str, str]] = {}
    for canal in canales_presentes:
        bloques_canal = {
            k: v for k, v in bloques.items() if prefijo_a_canal(k) == canal
        }
        bloques_por_canal[canal] = bloques_canal
        rc = ResultadoCanal(canal=canal)
        rc.hallazgos.extend(check_01_bloques_obligatorios(bloques_canal, canal))
        rc.hallazgos.extend(check_02_cupo_hashtags(bloques_canal, canal))
        rc.hallazgos.extend(check_03_receta_hashtags(bloques_canal, canal))
        rc.hallazgos.extend(check_04_lexico_iconos(bloques_canal, canal))
        rc.hallazgos.extend(check_05_cero_iconos_en_portales(bloques_canal, canal))
        rc.hallazgos.extend(check_06_cero_hashtags_en_portales(bloques_canal, canal))
        rc.hallazgos.extend(check_07_cero_enlaces_en_portales(bloques_canal, canal))
        rc.hallazgos.extend(check_08_cero_datos_contacto_en_portales(bloques_canal, canal))
        rc.hallazgos.extend(check_09_datos_internos_prohibidos(bloques_canal, canal))
        rc.hallazgos.extend(check_10_superlativos_prohibidos(bloques_canal, canal))
        rc.hallazgos.extend(check_11_emojis_decorativos(bloques_canal, canal))
        rc.hallazgos.extend(check_12_aviso_legal_completo(bloques_canal, canal))
        rc.hallazgos.extend(check_13_fecha_matriculacion(bloques_canal, canal))
        rc.hallazgos.extend(check_14_pega_honesta(bloques_canal, canal))
        rc.hallazgos.extend(check_15_icono_alerta_solo_con_pega(bloques_canal, canal))
        rc.hallazgos.extend(check_16_uso_garantia(bloques_canal, canal))
        rc.hallazgos.extend(check_17_longitud_por_canal(bloques_canal, canal))
        rc.hallazgos.extend(check_19_campos_sin_rellenar(bloques_canal, canal))
        rc.hallazgos.extend(check_20_trazabilidad(bloques_canal, canal))
        resultado.canales[canal] = rc

    # Check 18 — cruza canales
    for canal, rc in resultado.canales.items():
        rc.hallazgos.extend(check_18_duplicados_entre_canales(bloques_por_canal))

    return resultado


def consolidar(resultados: Iterable[ResultadoArchivo]) -> tuple[int, int, int]:
    """Suma rojos, naranjas, amarillos. Devuelve (rojos, naranjas, amarillos)."""
    rojos = naranjas = amarillos = 0
    for r in resultados:
        for canal in r.canales.values():
            for h in canal.hallazgos:
                if h.severidad == "ALTO":
                    rojos += 1
                elif h.severidad == "MEDIO":
                    naranjas += 1
                else:
                    amarillos += 1
    return rojos, naranjas, amarillos


def imprimir_reporte(resultado: ResultadoArchivo) -> None:
    print(f"\n📄 {resultado.ruta}")
    for canal, rc in resultado.canales.items():
        if not rc.hallazgos:
            print(f"  ✅ {canal}: sin hallazgos")
            continue
        print(f"  ▸ {canal}:")
        for h in rc.hallazgos:
            print(h.formato_corto())


# --------------------------------------------------------------------------- #
# CLI
# --------------------------------------------------------------------------- #


def main(argv: list[str] | None = None) -> int:
    # Forzar UTF-8 en stdout/stderr para soportar emojis en consola Windows.
    try:
        sys.stdout.reconfigure(encoding="utf-8")
        sys.stderr.reconfigure(encoding="utf-8")
    except (AttributeError, ValueError):
        pass

    parser = argparse.ArgumentParser(
        description="Validador de copy marketing JJ Import Motors (30 checks).",
    )
    parser.add_argument(
        "archivos",
        nargs="*",
        type=Path,
        help="Archivos .txt con bloques [IG_*]/[VT_*]/[FB_*]/[FBMP_*]/[PT_*]",
    )
    parser.add_argument(
        "-r",
        "--recursive",
        action="store_true",
        help="Buscar .txt recursivamente en las rutas pasadas",
    )
    parser.add_argument(
        "--json",
        action="store_true",
        help="Salida en formato JSON",
    )
    args = parser.parse_args(argv)

    archivos: list[Path] = []
    for p in args.archivos:
        if p.is_dir():
            glob = p.rglob("*.txt") if args.recursive else p.glob("*.txt")
            archivos.extend(sorted(glob))
        elif p.is_file():
            archivos.append(p)
        else:
            print(f"⚠️  Ruta no encontrada: {p}", file=sys.stderr)

    if not archivos:
        print("Uso: python scripts/check_marketing.py <archivo.txt> [archivo2.txt ...]")
        print("     python scripts/check_marketing.py -r contenido/")
        return 3

    resultados = [validar_archivo(p) for p in archivos]

    if args.json:
        out = []
        for r in resultados:
            out.append(
                {
                    "archivo": str(r.ruta),
                    "canales": {
                        c: [
                            {
                                "check": h.check,
                                "severidad": h.severidad,
                                "mensaje": h.mensaje,
                                "bloque": h.bloque,
                                "linea": h.linea,
                            }
                            for h in rc.hallazgos
                        ]
                        for c, rc in r.canales.items()
                    },
                }
            )
        print(json.dumps(out, indent=2, ensure_ascii=False))
    else:
        for r in resultados:
            imprimir_reporte(r)

    rojos, naranjas, amarillos = consolidar(resultados)
    print()
    print(f"Resumen: 🔴 {rojos}  🟠 {naranjas}  🟡 {amarillos}")

    if rojos:
        return 1
    if naranjas:
        return 2
    return 0


if __name__ == "__main__":
    sys.exit(main())
