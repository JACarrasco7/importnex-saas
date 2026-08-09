#!/usr/bin/env python3
# -*- coding: utf-8 -*-
"""
Generador de informes de valoracion e importacion — JJ Import Motors.

Toma un JSON con los datos medidos y produce el PDF en el formato de la casa,
con ENLACE en cada anuncio, cada busqueda y cada fuente.

    python3 informe_importacion.py datos/astra_opc.json --salida informes/

Estructura del JSON de entrada: ver PLANTILLA al final del fichero.
Todo campo que falte se omite del informe; no se inventa nada.
"""

import json, sys, os, argparse, statistics as st
from datetime import date

# ----------------------------------------------------------------------------- utilidades

def eur(v, dec=0):
    if v is None or v == '':
        return '—'
    s = f'{float(v):,.{dec}f}'
    return s.replace(',', '@').replace('.', ',').replace('@', '.') + ' €'

def num(v):
    if v is None or v == '':
        return '—'
    return f'{int(v):,}'.replace(',', '.')

def pct(v, dec=1):
    if v is None:
        return '—'
    return f'{v:+.{dec}f} %'.replace('.', ',')

def esc(t):
    if t is None:
        return ''
    return (str(t).replace('&', '&amp;').replace('<', '&lt;').replace('>', '&gt;'))

def enlace(url, texto=None, corto=True):
    """Ancla siempre visible. Si no hay texto, muestra el dominio + cola."""
    if not url:
        return '—'
    if texto is None:
        t = url.replace('https://', '').replace('http://', '').replace('www.', '')
        texto = (t[:46] + '…') if corto and len(t) > 47 else t
    return f'<a href="{esc(url)}">{esc(texto)}</a>'

def pastilla(texto, clase='p-info'):
    return f'<span class="pill {clase}">{esc(texto)}</span>'

def mediana(vals):
    v = [x for x in vals if x is not None]
    return int(st.median(v)) if v else None

def cuartil_bajo(vals):
    v = sorted(x for x in vals if x is not None)
    return v[int(len(v) * 0.25)] if v else None

# ----------------------------------------------------------------------------- CSS

CSS = """
@page{size:A4;margin:15mm 13mm 16mm 13mm;
  @bottom-center{content:"__PIE__ · " counter(page) " / " counter(pages);
    font-size:8pt;color:#8a929c;font-family:-apple-system,'Segoe UI',Arial,sans-serif}}
:root{--tinta:#1a1d21;--suave:#5b6470;--linea:#e2e6ea;--caja:#f6f8fa;
      --verde:#0f7b4f;--verdebg:#e7f5ee;--ambar:#8a5a00;--ambarbg:#fdf3e0;
      --rojo:#a32020;--rojobg:#fbebeb;--azul:#1d4f91;--azulbg:#eaf1fa;}
*{box-sizing:border-box}
body{font-family:-apple-system,'Segoe UI',Roboto,Arial,sans-serif;color:var(--tinta);
     line-height:1.45;font-size:9.4pt;margin:0}
h1{font-size:19pt;margin:0 0 3px;letter-spacing:-.4px}
.sub{color:var(--suave);font-size:8.8pt;margin-bottom:15px;padding-bottom:9px;border-bottom:2px solid var(--tinta)}
h2{font-size:12.5pt;margin:20px 0 5px;padding-bottom:4px;border-bottom:2px solid var(--tinta);page-break-after:avoid}
h3{font-size:8.8pt;margin:14px 0 4px;text-transform:uppercase;letter-spacing:.6px;color:var(--suave);page-break-after:avoid}
table{width:100%;border-collapse:collapse;margin:7px 0 4px;font-size:8.3pt;page-break-inside:avoid}
thead{display:table-header-group}
th{text-align:left;background:var(--caja);padding:5px 6px;border-bottom:2px solid var(--linea);
   font-size:7.3pt;text-transform:uppercase;letter-spacing:.4px;color:var(--suave);vertical-align:bottom}
td{padding:5px 6px;border-bottom:1px solid var(--linea);vertical-align:top}
tr:last-child td{border-bottom:none}
.num{white-space:nowrap;text-align:right;font-variant-numeric:tabular-nums}
.coche{font-weight:650}
.coche small{display:block;font-weight:400;color:var(--suave);font-size:7.3pt;margin-top:1px}
.pill{display:inline-block;padding:1px 6px;border-radius:20px;font-size:7.3pt;font-weight:650;white-space:nowrap}
.p-alto{background:var(--verdebg);color:var(--verde)}
.p-medio{background:var(--ambarbg);color:var(--ambar)}
.p-bajo{background:var(--rojobg);color:var(--rojo)}
.p-info{background:var(--azulbg);color:var(--azul)}
.caja{background:var(--caja);border-left:3px solid var(--tinta);padding:9px 12px;margin:11px 0;
      border-radius:0 4px 4px 0;page-break-inside:avoid}
.caja.clave{border-left-color:var(--verde);background:var(--verdebg)}
.caja.aviso{border-left-color:var(--ambar);background:var(--ambarbg)}
.caja.mal{border-left-color:var(--rojo);background:var(--rojobg)}
.caja p:first-child{margin-top:0}.caja p:last-child{margin-bottom:0}
ul{margin:5px 0;padding-left:15px}li{margin:2px 0}
.leyenda{font-size:7.5pt;color:var(--suave);margin:4px 0 0}
.formula{font-family:ui-monospace,Menlo,monospace;font-size:7.8pt;background:#fff;
         border:1px solid var(--linea);padding:7px 9px;border-radius:4px;margin:6px 0;white-space:pre-wrap}
a{color:var(--azul);text-decoration:none;border-bottom:.5px solid #b9cbe4}
.tot td{border-top:2px solid var(--tinta);font-weight:700;background:var(--caja)}
.url{font-family:ui-monospace,Menlo,monospace;font-size:7pt;color:var(--suave);word-break:break-all}
footer{margin-top:22px;padding-top:9px;border-top:1px solid var(--linea);font-size:7.5pt;color:var(--suave)}
footer a{font-size:7.5pt}
"""

# ----------------------------------------------------------------------------- secciones

CERTEZA = {
    'ok':   ('p-alto',  'VERIFICADO'),
    'est':  ('p-medio', 'ESTIMADO'),
    'man':  ('p-bajo',  'COMPROBAR'),
    'no':   ('p-bajo',  'DESCONOCIDO'),
    'info': ('p-info',  'INFORMATIVO'),
}

def sello(c):
    """Pastilla de nivel de certeza."""
    cl, tx = CERTEZA.get(c, CERTEZA['info'])
    return pastilla(tx, cl)


def sec_verificacion(d):
    """Panel de lo que NO está verificado, con la acción exacta. Va al principio."""
    v = d.get('verificacion', {})
    pend = v.get('pendientes', [])
    if not pend:
        return ''
    filas = []
    for i, x in enumerate(pend, 1):
        filas.append(
            f"<tr><td class='num' style='width:5%'>{i}</td>"
            f"<td style='width:22%' class='coche'>{esc(x['dato'])}</td>"
            f"<td style='width:14%'>{sello(x.get('certeza','man'))}</td>"
            f"<td>{x['accion']}</td>"
            f"<td style='width:17%'>{x.get('impacto','—')}</td></tr>")
    conteo = v.get('conteo', {})
    resumen = ' · '.join(
        f"{sello(k)} {n}" for k, n in conteo.items()) if conteo else ''
    return f"""<h2>Panel de verificación — lo que tienes que comprobar tú</h2>
<p class="leyenda">Todo lo que este informe <strong>no</strong> ha podido verificar contra una fuente, con la acción
concreta para cerrarlo y lo que cambia si sale mal. {resumen}</p>
<table><thead><tr><th>#</th><th>Dato sin cerrar</th><th>Estado</th>
<th>Qué hay que hacer</th><th>Qué cambia</th></tr></thead>{''.join(filas)}</table>
{f'<div class="caja aviso"><p>{v["nota"]}</p></div>' if v.get('nota') else ''}"""


def sec_fuentes(d):
    f = d.get('fuentes', [])
    if not f:
        return ''
    filas = []
    for x in f:
        filas.append(
            f"<tr><td class='coche'>{esc(x['nombre'])}"
            f"<small>{esc(x.get('perfil',''))}</small></td>"
            f"<td>{esc(x.get('filtros','—'))}<div class='url'>{enlace(x.get('url'), corto=False)}</div></td>"
            f"<td class='num'>{esc(x.get('resultado','—'))}</td>"
            f"<td>{esc(x.get('uso','—'))}</td></tr>")
    return f"""<h2>1 · Qué se ha medido y dónde</h2>
<p class="leyenda">Cada búsqueda es reproducible: el enlace lleva a la consulta exacta con sus filtros aplicados.</p>
<table><thead><tr><th style="width:17%">Fuente</th><th style="width:40%">Consulta ejecutada</th>
<th style="width:12%">Resultado</th><th>Para qué se usa</th></tr></thead>{''.join(filas)}</table>"""


def sec_mercado(d, clave, titulo, orden):
    m = d.get(clave, {})
    unidades = m.get('unidades', [])
    if not unidades:
        return ''
    cols = m.get('columnas', ['precio', 'anio', 'km', 'vendedor', 'fuente', 'nota'])
    cab = {'precio': 'Precio', 'anio': 'Año', 'km': 'Km', 'vendedor': 'Vendedor',
           'fuente': 'Fuente', 'nota': 'Nota', 'dias': 'Días', 'tasacion': 'Tasación',
           'matriculacion': 'Matriculación', 'version': 'Versión', 'portal': 'Portal'}
    filas = []
    for u in unidades:
        celdas = []
        for c in cols:
            v = u.get(c)
            if c == 'precio':
                celdas.append(f"<td class='num'>{enlace(u.get('url'), eur(v))}</td>")
            elif c in ('km',):
                celdas.append(f"<td class='num'>{num(v)}</td>")
            elif c in ('tasacion',):
                celdas.append(f"<td class='num'>{eur(v)}</td>")
            elif c in ('dias',):
                celdas.append(f"<td class='num'>{v if v is not None else '—'}</td>")
            elif c == 'portal' and u.get('url_portal'):
                celdas.append(f"<td>{enlace(u.get('url_portal'), v)}</td>")
            else:
                celdas.append(f"<td>{esc(v) if v is not None else '—'}</td>")
        filas.append('<tr>' + ''.join(celdas) + '</tr>')
    precios = [u.get('precio') for u in unidades]
    med, q1 = mediana(precios), cuartil_bajo(precios)
    pie = (f"<tr class='tot'><td class='num'>Mediana {eur(med)}</td>"
           f"<td colspan='2'>Cuartil bajo {eur(q1)}</td>"
           f"<td colspan='{max(1,len(cols)-3)}'>{esc(m.get('lectura',''))}</td></tr>")
    thead = ''.join(f"<th>{cab.get(c,c)}</th>" for c in cols)
    extra = ''
    if m.get('excluidos'):
        extra = f"<p class='leyenda'>{esc(m['excluidos'])}</p>"
    return (f"<h2>{orden} · {esc(titulo)}</h2>"
            f"<p class='leyenda'>El precio de cada unidad es un enlace al anuncio.</p>"
            f"<table><thead><tr>{thead}</tr></thead>{''.join(filas)}{pie}</table>{extra}")


def sec_candidato(d):
    c = d.get('candidato')
    if not c:
        return ''
    filas = []
    for k, v in c.get('ficha', {}).items():
        filas.append(f"<tr><td class='coche' style='width:26%'>{esc(k)}</td><td>{v}</td></tr>")
    enl = []
    for e in c.get('enlaces', []):
        enl.append(f"<tr><td class='coche'>{esc(e['que'])}</td><td>{enlace(e['url'], corto=False)}</td></tr>")
    return (f"<h2>4 · El candidato</h2><table>{''.join(filas)}</table>"
            + (f"<h3>Enlaces del candidato</h3><table>{''.join(enl)}</table>" if enl else ''))


def sec_comparable(d):
    cp = d.get('comparable')
    if not cp:
        return ''
    filas = []
    for r in cp['filas']:
        filas.append(
            f"<tr><td>{enlace(r.get('url'), r['origen'])}</td>"
            f"<td class='num'>{r.get('anio','—')}</td>"
            f"<td class='num'>{num(r.get('km'))}</td>"
            f"<td class='num'>{pct(r.get('aj_anio'))}</td>"
            f"<td class='num'>{pct(r.get('aj_km'))}</td>"
            f"<td class='num'>{eur(r['proyectado'])}</td></tr>")
    proy = [r['proyectado'] for r in cp['filas']]
    return f"""<h2>5 · El comparable, ajustado línea a línea</h2>
<p>{esc(cp.get('objetivo',''))}</p>
<div class="formula">{esc(cp['formula'])}</div>
<table><thead><tr><th>Unidad española</th><th>Año</th><th>Km</th>
<th>Ajuste año</th><th>Ajuste km</th><th>Proyectado</th></tr></thead>
{''.join(filas)}
<tr class="tot"><td colspan="5">Mediana proyectada</td><td class="num">{eur(mediana(proy))}</td></tr>
<tr class="tot"><td colspan="5">Cuartil bajo proyectado</td><td class="num">{eur(cuartil_bajo(proy))}</td></tr>
</table>
{f'<div class="caja aviso"><p>{cp["aviso"]}</p></div>' if cp.get('aviso') else ''}"""


def sec_coste(d):
    co = d.get('coste')
    if not co:
        return ''
    filas, total = [], 0.0
    for l in co['lineas']:
        total += float(l['importe'])
        filas.append(f"<tr><td>{l['concepto']}</td><td class='num'>{eur(l['importe'],2)}</td>"
                     f"<td style='width:11%'>{sello(l.get('certeza','ok'))}</td>"
                     f"<td>{l.get('origen','')}</td></tr>")
    hon = float(co['honorarios'])
    filas.append(f"<tr class='tot'><td>Coste total en España</td><td class='num'>{eur(total,2)}</td><td colspan='2'></td></tr>")
    filas.append(f"<tr><td>Honorarios JJ Import Motors</td><td class='num'>{eur(hon,2)}</td>"
                 f"<td>{sello('ok')}</td><td>{esc(co.get('origen_honorarios',''))}</td></tr>")
    filas.append(f"<tr class='tot'><td>Precio final al cliente</td><td class='num'>{eur(total+hon,2)}</td><td colspan='2'></td></tr>")
    bloque = (f"<h2>6 · El coste puesto en {esc(co.get('destino','Huelva'))}</h2>"
              f"<table><thead><tr><th style='width:38%'>Concepto</th><th>Importe</th>"
              f"<th>Certeza</th><th style='width:30%'>De dónde sale</th></tr></thead>{''.join(filas)}</table>")
    if co.get('iedmt_detalle'):
        i = co['iedmt_detalle']
        bloque += f"<h3>Cómo sale el IEDMT</h3><div class='formula'>{esc(i['desarrollo'])}</div>"
        if i.get('escenarios'):
            fs = ''.join(f"<tr><td>{esc(e['caso'])}</td><td class='num'>{eur(e['importe'],2)}</td>"
                         f"<td>{esc(e['cuando'])}</td></tr>" for e in i['escenarios'])
            bloque += f"<table><thead><tr><th>Escenario</th><th>IEDMT</th><th>Cuándo</th></tr></thead>{fs}</table>"
        if i.get('nota'):
            bloque += f"<div class='caja'><p>{i['nota']}</p></div>"
    d['_final'] = total + hon
    return bloque


def sec_veredicto(d):
    v = d.get('veredicto')
    if not v:
        return ''
    final = d.get('_final')
    filas = []
    for c in v['contra']:
        ah = c['comparable'] - final
        p = 100 * ah / c['comparable']
        cl = 'p-alto' if p >= 15 else ('p-medio' if p >= 8 else 'p-bajo')
        filas.append(f"<tr><td>{esc(c['nombre'])}</td><td class='num'>{eur(c['comparable'])}</td>"
                     f"<td class='num'>{eur(final)}</td><td class='num'><strong>{eur(ah)} ({pct(p)})</strong></td>"
                     f"<td>{pastilla(c.get('lectura',''), cl)}</td></tr>")
    bloque = (f"<h2>7 · Margen y veredicto</h2><table><thead><tr><th>Contra</th><th>Comparable</th>"
              f"<th>Precio final</th><th>Ahorro del cliente</th><th>Lectura</th></tr></thead>"
              f"{''.join(filas)}</table>")
    if v.get('vendibilidad'):
        vv = v['vendibilidad']
        fs = ''.join(f"<tr><td>{esc(f['factor'])}</td><td class='num'>{f['peso']}</td>"
                     f"<td class='num'>{f['puntos']}</td>"
                     f"<td>{sello(f.get('certeza','ok'))}</td>"
                     f"<td>{f['justificacion']}</td></tr>" for f in vv['factores'])
        tot = sum(f['puntos'] for f in vv['factores'])
        bloque += (f"<h3>Vendibilidad</h3><table><thead><tr><th style='width:22%'>Factor</th><th>Peso</th>"
                   f"<th>Puntos</th><th>Certeza</th><th>Justificación</th></tr></thead>{fs}"
                   f"<tr class='tot'><td>Total</td><td class='num'>100</td><td class='num'>{tot}</td>"
                   f"<td colspan='2'>{esc(vv.get('lectura',''))}</td></tr></table>")
    if v.get('casilla'):
        bloque += f"<div class='caja clave'>{v['casilla']}</div>"
    return bloque


def sec_riesgos(d):
    r = d.get('riesgos', [])
    if not r:
        return ''
    est = {'bloqueante': 'p-bajo', 'pendiente': 'p-bajo', 'descartado': 'p-alto',
           'confirmar': 'p-medio', 'estimacion': 'p-medio', 'informativo': 'p-info', 'declarado': 'p-medio'}
    fs = ''.join(f"<tr><td class='coche' style='width:24%'>{esc(x['bandera'])}</td><td>{x['detalle']}</td>"
                 f"<td style='width:16%'>{pastilla(x['estado_texto'], est.get(x['estado'],'p-info'))}</td></tr>"
                 for x in r)
    return f"<h2>8 · Riesgos y banderas</h2><table><thead><tr><th>Bandera</th><th>Detalle</th><th>Estado</th></tr></thead>{fs}</table>"


def sec_alternativas(d):
    a = d.get('alternativas', [])
    if not a:
        return ''
    return "<h2>9 · Alternativas</h2>" + ''.join(f"<div class='caja'><p>{x}</p></div>" for x in a)


def sec_acciones(d):
    a = d.get('acciones', [])
    if not a:
        return ''
    fs = ''.join(f"<tr><td class='num' style='width:7%'>{i+1}</td><td>{x}</td></tr>" for i, x in enumerate(a))
    return f"<h2>10 · Qué hacer</h2><table><thead><tr><th>Orden</th><th>Acción</th></tr></thead>{fs}</table>"


def sec_pie(d):
    p = d.get('pie', {})
    fu = p.get('fuentes_enlazadas', [])
    lista = ' · '.join(enlace(x['url'], x['nombre']) for x in fu)
    return f"""<h2>11 · Fuentes y trazabilidad</h2>
<p><strong>Consultado el {esc(d.get('fecha',''))}.</strong> {lista}</p>
<div class="caja aviso"><p><strong>Lo que es estimación:</strong> {p.get('estimaciones','—')}</p></div>
<footer>{p.get('legal','')}</footer>"""


# ----------------------------------------------------------------------------- ensamblado

def construir_html(d):
    partes = [
        f"<h1>{esc(d['titulo'])}</h1>",
        f"<p class='sub'>{d['subtitulo']}</p>",
    ]
    if d.get('resumen'):
        partes.append(f"<div class='caja clave'><p>{d['resumen']}</p></div>")
    partes.append(sec_verificacion(d))
    partes += [
        sec_fuentes(d),
        sec_mercado(d, 'espana', 'La oferta española', 2),
        sec_mercado(d, 'alemania', 'La oferta alemana', 3),
        sec_candidato(d),
        sec_comparable(d),
        sec_coste(d),
        sec_veredicto(d),
        sec_riesgos(d),
        sec_alternativas(d),
        sec_acciones(d),
        sec_pie(d),
    ]
    css = CSS.replace('__PIE__', esc(d.get('pie_pagina', 'JJ Import Motors')))
    return (f"<!DOCTYPE html><html lang='es'><head><meta charset='utf-8'>"
            f"<title>{esc(d['titulo'])}</title><style>{css}</style></head>"
            f"<body>{''.join(partes)}</body></html>")


def main():
    ap = argparse.ArgumentParser()
    ap.add_argument('datos')
    ap.add_argument('--salida', default='.')
    ap.add_argument('--solo-html', action='store_true')
    a = ap.parse_args()

    d = json.load(open(a.datos, encoding='utf-8'))
    d.setdefault('fecha', date.today().strftime('%d/%m/%Y'))
    html = construir_html(d)

    os.makedirs(a.salida, exist_ok=True)
    base = d.get('slug') or os.path.splitext(os.path.basename(a.datos))[0]
    ruta_html = os.path.join(a.salida, base + '.html')
    open(ruta_html, 'w', encoding='utf-8').write(html)
    print('HTML  ->', ruta_html)

    if not a.solo_html:
        from weasyprint import HTML
        ruta_pdf = os.path.join(a.salida, base + '.pdf')
        HTML(string=html).write_pdf(ruta_pdf)
        print('PDF   ->', ruta_pdf)


if __name__ == '__main__':
    main()
