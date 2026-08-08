#!/usr/bin/env python3
# -*- coding: utf-8 -*-
"""Extrae anuncios de un volcado HTML de AutoUncle -> CSV + resumen.
Uso: python3 au_parse.py <fichero.html|.xml> [salida.csv]
"""
import re, sys, csv, html, statistics

def txt(s):
    s = re.sub(r'<!--.*?-->', '', s)
    s = re.sub(r'<[^>]+>', ' ', s)
    return html.unescape(re.sub(r'\s+', ' ', s)).strip()

def parse(path):
    d = open(path, encoding='utf-8', errors='replace').read()
    out = []
    for blk in re.split(r'<article\b', d)[1:]:
        a = re.search(r'href="(/es/d/[^"]+|/de/d/[^"]+)"', blk)
        if not a:
            continue
        h3 = re.search(r'<h3[^>]*>(.*?)</h3>', blk, re.S)
        lis = [txt(x) for x in re.findall(r'<li class="_ZTpYr[^"]*">(.*?)</li>', blk, re.S)]
        t = txt(blk)
        precios = re.findall(r'([\d]{1,3}(?:\.\d{3})+)\s*€', t)
        precios = [int(p.replace('.', '')) for p in precios]
        km = next((l for l in lis if 'km' in l), '')
        cv = next((l for l in lis if 'CV' in l or 'PS' in l), '')
        fecha = lis[0] if lis else ''
        comb = next((l for l in lis if re.search(r'Gasolina|Diesel|Di[eé]sel|H[ií]brido|El[eé]ctrico|Benzin', l)), '')
        camb = next((l for l in lis if re.search(r'Manual|Autom', l)), '')
        acab = next((l for l in lis if 'Acabado' in l or 'Ausstattungslinie' in l), '')
        dias = re.search(r'(?:D[ií]as (?:en )?(?:anuncio|publicado)|Tage inseriert)\D{0,20}(\d+)', t)
        ev = re.search(r'(Superprecio|Buen precio|Precio justo|Algo caro|Caro|Superpreis|Guter Preis|Fairer Preis|Teuer)', t)
        loc = re.search(r'\b(\d{4,5}\s+[A-ZÁÉÍÓÚÑ][\w\.\- ]{2,40})', t)
        out.append({
            'titulo': txt(h3.group(1)) if h3 else '',
            'precio': precios[0] if precios else '',
            'tasacion_AU': precios[1] if len(precios) > 1 else '',
            'valoracion': (ev.group(1) if ev else (txt(h3.group(1)).split('|')[-1].strip() if h3 and '|' in txt(h3.group(1)) else '')),
            'matric': fecha, 'km': km, 'cv': cv, 'combustible': comb,
            'cambio': camb, 'acabado': acab.replace('Acabado: ', ''),
            'dias': dias.group(1) if dias else '',
            'ubicacion': loc.group(1).strip() if loc else '',
            'url': 'https://www.autouncle.es' + a.group(1),
        })
    return out

if __name__ == '__main__':
    src = sys.argv[1]
    dst = sys.argv[2] if len(sys.argv) > 2 else 'autouncle.csv'
    rows = parse(src)
    if not rows:
        print('Sin anuncios detectados.'); sys.exit(1)
    with open(dst, 'w', newline='', encoding='utf-8-sig') as f:
        w = csv.DictWriter(f, fieldnames=list(rows[0].keys())); w.writeheader(); w.writerows(rows)
    ps = [r['precio'] for r in rows if isinstance(r['precio'], int)]
    print(f'{len(rows)} anuncios -> {dst}')
    if ps:
        print(f'Precio min {min(ps):,} / mediana {int(statistics.median(ps)):,} / max {max(ps):,} EUR'.replace(',', '.'))
    print()
    for r in rows:
        print(f"{r['precio']:>8} | {r['km']:>12} | {r['matric']:>9} | {r['cv']:>14} | {r['valoracion']:<12} | {r['titulo'][:55]}")
