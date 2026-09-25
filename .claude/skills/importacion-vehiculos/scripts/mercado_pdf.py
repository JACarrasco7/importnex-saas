# -*- coding: utf-8 -*-
"""
mercado_pdf.py — Renderiza un informe de mercado (.md de la skill) a PDF
visual: portada con KPIs, tabla de decisión con semáforo, una ficha por
página y fuentes re-ejecutables al final.

Uso:
    python mercado_pdf.py <informe.md> [--out salida.pdf] [--keep-html]

Diseño:
    - MD íntegro (no se salta nada) pero con jerarquía visual fuerte:
      menos sensación de muro de texto, más aire y semáforos.
    - Paleta de marca docs/BRAND.md: estoril #1A306D, asphalt #38393D,
      platinum #BEC0C3. Semánticos: green/amber/red SOLO para datos.
    - PDF vía Chrome/Edge headless (--headless=new --print-to-pdf).
      No requiere Laravel ni Browsershot.

Regla (v3.10.0): el informe de mercado se entrega COMO PDF al operador.
El .md sigue existiendo (contrato interno de la skill + diff entre
versiones), pero lo que lee el humano es el PDF.
"""
from __future__ import annotations

import argparse
import html as html_mod
import json
import re
import shutil
import subprocess
import sys
import tempfile
from datetime import date
from pathlib import Path

# ── Paleta (docs/BRAND.md) ────────────────────────────────────────────────────
ESTORIL = "#1A306D"
ESTORIL_DARK = "#101d42"
ESTORIL_SOFT = "#eef1fa"
ASPHALT = "#38393D"
PLATINUM = "#BEC0C3"
PLATINUM_SOFT = "#f3f3f4"
GREEN_OK = "#1e7b34"
GREEN_BG = "#eaf6ed"
AMBER_WARN = "#b45309"
AMBER_BG = "#fef6e7"
RED_BAD = "#b3261e"
RED_BG = "#fbecea"
GREY_NODATA = "#6b7280"

CSS = f"""
@page {{ size: A4; margin: 13mm 11mm 16mm 11mm; }}
* {{ box-sizing: border-box; }}
body {{
  font-family: 'Segoe UI', 'Segoe UI Emoji', -apple-system, sans-serif;
  color: {ASPHALT}; font-size: 9.2pt; line-height: 1.45; margin: 0;
}}
a {{ color: {ESTORIL}; text-decoration: none; word-break: break-all; }}
code {{ font-family: 'Cascadia Code', Consolas, monospace; font-size: 8pt;
  background: {PLATINUM_SOFT}; padding: 0 3px; border-radius: 3px; }}

/* ── Portada ─────────────────────────────────────────── */
.cover {{ text-align: left; padding-top: 8mm; }}
.cover-band {{ height: 7mm; background: linear-gradient(90deg, {ESTORIL}, {ESTORIL_DARK});
  border-radius: 2mm; margin-bottom: 10mm; }}
.cover .brand {{ font-size: 9pt; letter-spacing: 2.5px; color: {PLATINUM};
  text-transform: uppercase; font-weight: 600; }}
.cover-band-wrap {{ background: {ESTORIL_DARK}; border-radius: 2mm; padding: 4mm 6mm; margin-bottom: 8mm; }}
.cover h1 {{ font-size: 21pt; color: {ESTORIL}; margin: 4mm 0 2mm; line-height: 1.2; }}
.cover .sub {{ color: #5d5e62; font-size: 9.5pt; margin-bottom: 8mm; }}
.kpis {{ display: flex; gap: 4mm; margin: 6mm 0; }}
.kpi {{ flex: 1; border: 0.4mm solid {PLATINUM}; border-top: 1.2mm solid {ESTORIL};
  border-radius: 2mm; padding: 4mm; background: white; }}
.kpi .k-label {{ font-size: 7pt; text-transform: uppercase; letter-spacing: 1px;
  color: #7e7f83; margin-bottom: 2mm; }}
.kpi .k-value {{ font-size: 10.5pt; font-weight: 700; color: {ESTORIL}; line-height: 1.3; }}
.kpi .k-note {{ font-size: 7.5pt; color: #5d5e62; margin-top: 1.5mm; }}
.kpi.k-good {{ border-top-color: {GREEN_OK}; }}
.kpi.k-good .k-value {{ color: {GREEN_OK}; }}
.kpi.k-bad {{ border-top-color: {RED_BAD}; }}
.kpi.k-bad .k-value {{ color: {RED_BAD}; }}
.cover .meta {{ margin-top: 6mm; font-size: 8pt; color: #7e7f83; }}

/* ── Secciones ───────────────────────────────────────── */
h2.sec {{
  font-size: 13pt; color: white; background: {ESTORIL};
  padding: 2.4mm 4mm; border-radius: 1.5mm; margin: 8mm 0 4mm;
  page-break-after: avoid;
}}
h2.sec .num {{ opacity: 0.65; font-weight: 400; margin-right: 2mm; }}

/* ── Ficha de MODELO: una página por modelo ──────────── */
.model-card {{ page-break-before: always; }}
h3.model {{
  font-size: 15pt; color: {ESTORIL}; margin: 0 0 1mm;
  border-bottom: 1mm solid {PLATINUM}; padding-bottom: 2mm;
  page-break-after: avoid;
}}
h4.ver {{
  font-size: 11pt; margin: 6mm 0 2.5mm; color: {ASPHALT};
  background: {PLATINUM_SOFT}; padding: 2mm 3mm; border-left: 1.6mm solid {ESTORIL};
  border-radius: 0 1.5mm 1.5mm 0; page-break-after: avoid;
}}
h5.gen {{ font-size: 9.5pt; margin: 4mm 0 2mm; color: {ESTORIL};
  text-transform: uppercase; letter-spacing: 0.5px; page-break-after: avoid; }}

/* pill de veredicto dentro del título */
.pill {{ display: inline-block; font-size: 7.5pt; font-weight: 700; color: white;
  border-radius: 6mm; padding: 0.8mm 3mm; vertical-align: middle; margin-left: 2mm;
  letter-spacing: 0.3px; }}
.pill.p-good {{ background: {GREEN_OK}; }}
.pill.p-warn {{ background: {AMBER_WARN}; }}
.pill.p-bad {{ background: {RED_BAD}; }}
.pill.p-nodata {{ background: {GREY_NODATA}; }}

/* ── Tablas ──────────────────────────────────────────── */
table {{ border-collapse: collapse; width: 100%; margin: 3mm 0; page-break-inside: auto; }}
th {{ background: {ESTORIL}; color: white; font-weight: 600; text-align: left;
  padding: 1.8mm 2.2mm; font-size: 8pt; }}
td {{ border-bottom: 0.25mm solid {PLATINUM}; padding: 1.6mm 2.2mm;
  font-size: 8.2pt; vertical-align: top; }}
tr {{ page-break-inside: avoid; }}
tr:nth-child(even) td {{ background: {PLATINUM_SOFT}; }}
table.decision td {{ font-size: 8.6pt; }}
.row-good td {{ background: {GREEN_BG} !important; }}
.row-warn td {{ background: {AMBER_BG} !important; }}
.row-bad td {{ background: {RED_BG} !important; }}
.money {{ font-weight: 700; color: {ESTORIL}; white-space: nowrap; }}
.money.pos {{ color: {GREEN_OK}; }}
.money.neg {{ color: {RED_BAD}; }}

/* ── Callouts (blockquotes del MD) ───────────────────── */
.callout {{ border-radius: 1.5mm; padding: 2.8mm 3.5mm; margin: 3mm 0;
  font-size: 8.4pt; page-break-inside: avoid; }}
.callout.c-warn {{ background: {AMBER_BG}; border-left: 1.6mm solid {AMBER_WARN}; }}
.callout.c-bad {{ background: {RED_BG}; border-left: 1.6mm solid {RED_BAD}; }}
.callout.c-ok {{ background: {GREEN_BG}; border-left: 1.6mm solid {GREEN_OK}; }}
.callout.c-info {{ background: {ESTORIL_SOFT}; border-left: 1.6mm solid {ESTORIL}; }}

/* ── Listas ──────────────────────────────────────────── */
ul, ol {{ margin: 2mm 0; padding-left: 6mm; }}
li {{ margin-bottom: 1.2mm; }}
li::marker {{ color: {ESTORIL}; }}

/* ── Bloque copiable ─────────────────────────────────── */
pre.copybox {{ background: {ASPHALT}; color: #e7e7e9; border-radius: 1.5mm;
  padding: 3.5mm; font-size: 7.6pt; line-height: 1.5; white-space: pre-wrap;
  font-family: 'Cascadia Code', Consolas, monospace; }}
pre.copybox::before {{ content: '📋 RESUMEN PARA COPIAR';
  display: block; color: {PLATINUM}; font-size: 6.8pt; letter-spacing: 1.5px;
  margin-bottom: 2mm; }}

/* ── Fuentes finales ─────────────────────────────────── */
.sources {{ font-size: 7.3pt; line-height: 1.55; }}
.sources a {{ color: {ESTORIL}; }}
.fuentes-titulo {{ font-size: 8pt; font-weight: 700; color: {ESTORIL};
  margin-top: 2.5mm; }}

/* pie de página */
footer {{ position: fixed; bottom: -12mm; left: 0; right: 0; font-size: 7pt;
  color: {PLATINUM}; border-top: 0.25mm solid {PLATINUM}; padding-top: 1mm;
  display: flex; justify-content: space-between; }}
"""

PAGE_FOOTER = (
    "<footer><span>JJ Import Motors · confidencial</span>"
    f"<span>Generado {date.today().strftime('%d-%m-%Y')} · skill importacion-vehiculos</span></footer>"
)

RE_SEMAFORO = re.compile(r"(🟢|🟡|🔴|⚪|🟠)")


def pill_for(text: str) -> str:
    if "🟢" in text:
        return '<span class="pill p-good">COMPENSA</span>'
    if "🟡" in text:
        return '<span class="pill p-warn">dudoso</span>'
    if "🔴" in text:
        return '<span class="pill p-bad">no compensa</span>'
    if "⚪" in text:
        return '<span class="pill p-nodata">sin dato</span>'
    return ""


# ── Inline ────────────────────────────────────────────────────────────────────
def inline(md: str) -> str:
    s = html_mod.escape(md, quote=False)
    s = re.sub(r"\[([^\]]+)\]\(([^)\s]+)\)", r'<a href="\2">\1</a>', s)
    # autolink de URLs crudas (los informes ponen la URL en texto plano en tablas)
    s = re.sub(
        r"(?<![\"'>=])\b(https?://[^\s<)]+)",
        r'<a href="\1">\\1</a>',
        s,
    )
    s = re.sub(r"\*\*([^*]+)\*\*", r"<strong>\1</strong>", s)
    s = re.sub(r"(?<!\w)\*([^*\n]+)\*(?!\w)", r"<em>\1</em>", s)
    s = re.sub(r"`([^`]+)`", r"<code>\1</code>", s)
    return s


def moneyfy(cell_html: str) -> str:
    """Resalta cifras € con .money (positivo verde si lleva +, rojo si −)."""
    def rep(m):
        sign = m.group(1) or ""
        cls = "money pos" if "+" in sign else ("money neg" if "−" in sign or "-" in sign else "money")
        return f'<span class="{cls}">{m.group(0)}</span>'
    return re.sub(r"([+−-]?\s?\d[\d.,]*\s?€)", rep, cell_html)


# ── Parser de bloques ─────────────────────────────────────────────────────────
def parse_table(lines: list[str], in_decision: bool) -> str:
    rows = []
    for ln in lines:
        cells = [c.strip() for c in ln.strip().strip("|").split("|")]
        rows.append(cells)
    # quitar separador
    rows = [r for r in rows if not all(re.fullmatch(r":?-{2,}:?", c or "---") for c in r)]
    if not rows:
        return ""
    cls = "decision" if in_decision else ""
    out = [f'<table class="{cls}">']
    head = rows[0]
    out.append("<tr>" + "".join(f"<th>{inline(c)}</th>" for c in head) + "</tr>")
    for r in rows[1:]:
        joined = " ".join(r)
        rowcls = ""
        if "🟢" in joined:
            rowcls = ' class="row-good"'
        elif "🟡" in joined:
            rowcls = ' class="row-warn"'
        elif "🔴" in joined:
            rowcls = ' class="row-bad"'
        tds = "".join(f"<td>{moneyfy(inline(c))}</td>" for c in r)
        out.append(f"<tr{rowcls}>{tds}</tr>")
    out.append("</table>")
    return "\n".join(out)


def parse_blockquote(block: list[str]) -> str:
    text = " ".join(l.lstrip(">").strip() for l in block).strip()
    if "⚠️" in text or "trampa" in text.lower():
        cls = "c-warn"
    elif "🔴" in text or "error" in text.lower() or "no compensa" in text.lower():
        cls = "c-bad"
    elif "✅" in text:
        cls = "c-ok"
    else:
        cls = "c-info"
    return f'<div class="callout {cls}">{inline(text)}</div>'


# ── KPIs de portada ───────────────────────────────────────────────────────────
def build_cover(title: str, subtitle: str, md: str) -> str:
    kpis = []
    m = re.search(r"Se han estudiado \*\*(.+?)\*\*", md)
    if m:
        kpis.append(("", "MODELO(S) ESTUDIADO(S)", re.sub(r"\s+", " ", m.group(1)).strip()[:90], ""))

    m = re.search(
        r"\*\*La mejor oportunidad[^*]*\*\*\s*(?:es\s*)?(.+?)\.\s", md, re.S
    )
    if m:
        txt = re.sub(r"\s+", " ", m.group(1)).strip()
        # el ahorro es "N € de ahorro" si existe; si no, el último "→ N €" del texto
        mm = re.search(r"([\d.,]+)\s*€\s*de ahorro", txt) or re.search(
            r"→\s*\*\*?([\d.,]+)\s*€", txt
        )
        val = f"{mm.group(1)} € de ahorro" if mm else (txt[:80])
        note = re.sub(r"^[^:]*:\s*", "", txt)[:160]
        kpis.append(("k-good", "MEJOR OPORTUNIDAD", val, note))

    m = re.search(r"\*\*🔴 Cambio de veredicto[^*]*\*\*[:\s]*(.+?)\.", md, re.S)
    if m:
        txt = re.sub(r"\s+", " ", m.group(1)).strip()
        kpis.append(("k-bad", "OJO · CAMBIO DE VEREDICTO", "revisado", txt[:180]))

    m = re.search(r"\*\*Apunte a validar[^*]*:\*\*\s*(.+?)\.", md, re.S)
    if m and len(kpis) < 3:
        txt = re.sub(r"\s+", " ", m.group(1)).strip()
        kpis.append(("", "APUNTE A VALIDAR", txt[:60], txt[60:200]))

    kpi_html = "".join(
        f'<div class="kpi {k[0]}"><div class="k-label">{k[1]}</div>'
        f'<div class="k-value">{inline(k[2])}</div>'
        + (f'<div class="k-note">{inline(k[3])}</div>' if k[3] else "")
        + "</div>"
        for k in kpis[:3]
    )
    return (
        '<div class="cover">'
        '<div class="cover-band-wrap"><div class="brand">JJ Import Motors</div></div>'
        f"<h1>{inline(title)}</h1>"
        f'<div class="sub">{inline(subtitle)}</div>'
        f'<div class="kpis">{kpi_html}</div>'
        '<div class="meta">Precios "puesto en Huelva" = precio Alemania + gastos fijos · '
        "IVA e impuesto de matriculación aparte · fiabilidad 👁️ = visto en listado sin abrir ficha</div>"
        "</div>"
    )


# ── MD → HTML ─────────────────────────────────────────────────────────────────
def md_to_html(md: str) -> str:
    lines = md.splitlines()
    # título + subtítulo (primer párrafo no vacío tras el #)
    title = ""
    sub = ""
    for i, ln in enumerate(lines):
        if ln.startswith("# ") and not title:
            title = ln[2:].strip()
            rest = [l for l in lines[i + 1 :] if l.strip()]
            if rest and not rest[0].startswith(("#", ">", "|", "-", "```")):
                sub = rest[0].strip()
            break

    body: list[str] = [build_cover(title, sub, md)]
    in_decision = False
    in_sources = False
    i = 0
    n = len(lines)
    first_h2 = True
    card_open = False
    sources_open = False

    while i < n:
        ln = lines[i]

        if ln.startswith("```"):
            buf = []
            i += 1
            while i < n and not lines[i].startswith("```"):
                buf.append(lines[i])
                i += 1
            i += 1
            body.append(f'<pre class="copybox">{html_mod.escape(chr(10).join(buf))}</pre>')
            continue

        if ln.strip().startswith("|"):
            buf = []
            while i < n and lines[i].strip().startswith("|"):
                buf.append(lines[i])
                i += 1
            body.append(parse_table(buf, in_decision))
            continue

        if ln.startswith(">"):
            buf = []
            while i < n and lines[i].startswith(">"):
                buf.append(lines[i])
                i += 1
            body.append(parse_blockquote(buf))
            continue

        if ln.startswith("##### "):
            t = ln[6:].strip()
            body.append(f"<h5 class='gen'>{inline(t)}{pill_for(t)}</h5>")
            i += 1
            continue
        if ln.startswith("#### "):
            t = ln[5:].strip()
            body.append(f"<h4 class='ver'>{inline(t)}{pill_for(t)}</h4>")
            i += 1
            continue
        if ln.startswith("### "):
            t = ln[4:].strip()
            if card_open:
                body.append("</div>")
            body.append(f'<div class="model-card"><h3 class="model">{inline(t)}</h3>')
            card_open = True
            i += 1
            continue
        if ln.startswith("## "):
            t = ln[3:].strip()
            low = t.lower()
            in_decision = "conclusi" in low or "resumen" in low
            in_sources = "fuente" in low
            num = ""
            m = re.match(r"^(\d+)\.\s*(.*)$", t)
            if m:
                num, t = m.group(1), m.group(2)
            if sources_open and not in_sources:
                body.append("</div>")
                sources_open = False
            body.append(
                f'<h2 class="sec">{f"<span class=num>{num}.</span>" if num else ""}{inline(t)}</h2>'
            )
            if in_sources and not sources_open:
                body.append('<div class="sources">')
                sources_open = True
            first_h2 = False
            i += 1
            continue

        if re.match(r"^\s*[-*]\s+", ln):
            items = []
            while i < n and re.match(r"^\s*[-*]\s+", lines[i]):
                items.append(re.sub(r"^\s*[-*]\s+", "", lines[i]))
                i += 1
            body.append("<ul>" + "".join(f"<li>{moneyfy(inline(it))}</li>" for it in items) + "</ul>")
            continue

        if re.match(r"^\s*\d+\.\s+", ln):
            items = []
            while i < n and re.match(r"^\s*\d+\.\s+", lines[i]):
                items.append(re.sub(r"^\s*\d+\.\s+", "", lines[i]))
                i += 1
            body.append("<ol>" + "".join(f"<li>{moneyfy(inline(it))}</li>" for it in items) + "</ol>")
            continue

        if ln.strip() == "---":
            body.append("<hr>")
            i += 1
            continue

        if ln.strip():
            body.append(f"<p>{inline(ln.strip())}</p>")
        i += 1

    if card_open:
        body.append("</div>")
    if sources_open:
        body.append("</div>")
    return "\n".join(body)


def render_html(md_text: str, css: str) -> str:
    return (
        "<!DOCTYPE html><html lang='es'><head><meta charset='utf-8'>"
        f"<style>{css}</style></head><body>{md_to_html(md_text)}{PAGE_FOOTER}</body></html>"
    )


# ── Chrome/Edge headless ───────────────────────────────────────────────────────
def find_browser() -> str | None:
    candidates = [
        Path.home() / "AppData" / "Local" / "Google" / "Chrome" / "Application" / "chrome.exe",
        Path("C:/Program Files/Google/Chrome/Application/chrome.exe"),
        Path("C:/Program Files (x86)/Google/Chrome/Application/chrome.exe"),
        Path("C:/Program Files (x86)/Microsoft/Edge/Application/msedge.exe"),
        Path("C:/Program Files/Microsoft/Edge/Application/msedge.exe"),
    ]
    for c in candidates:
        if c.exists():
            return str(c)
    for exe in ("chrome", "msedge"):
        p = shutil.which(exe)
        if p:
            return p
    return None


def html_to_pdf(html_path: Path, pdf_path: Path) -> bool:
    browser = find_browser()
    if not browser:
        print("⚠️  No se encontró Chrome/Edge para el PDF. Se genera solo el HTML.", file=sys.stderr)
        return False
    cmd = [
        browser, "--headless=new", "--disable-gpu", "--no-sandbox",
        "--no-pdf-header-footer", "--print-to-pdf=" + str(pdf_path),
        html_path.resolve().as_uri(),
    ]
    r = subprocess.run(cmd, capture_output=True, text=True, timeout=120)
    return pdf_path.exists() and pdf_path.stat().st_size > 1000


# ── Main ──────────────────────────────────────────────────────────────────────
def main(argv=None) -> int:
    ap = argparse.ArgumentParser(description="MD de informe de mercado → HTML + PDF")
    ap.add_argument("informe", help="informe .md de la skill")
    ap.add_argument("--out", help="PDF destino (defecto: junto al .md, misma base)")
    ap.add_argument("--keep-html", action="store_true", help="no borrar el HTML intermedio")
    args = ap.parse_args(argv)

    src = Path(args.informe)
    if not src.is_file():
        print(f"❌ No existe: {src}", file=sys.stderr)
        return 2
    md = src.read_text(encoding="utf-8")

    html = render_html(md, CSS)
    out_pdf = Path(args.out) if args.out else src.with_suffix(".pdf")
    html_path = out_pdf.with_suffix(".html")

    html_path.write_text(html, encoding="utf-8")
    ok_pdf = html_to_pdf(html_path, out_pdf)
    if not args.keep_html and ok_pdf:
        html_path.unlink(missing_ok=True)

    print(f"✅ HTML: {html_path}" + (" (mantenido)" if args.keep_html or not ok_pdf else " (intermedio)"))
    if ok_pdf:
        print(f"✅ PDF:  {out_pdf} ({out_pdf.stat().st_size // 1024} KB)")
        return 0
    return 1


if __name__ == "__main__":
    sys.exit(main())
