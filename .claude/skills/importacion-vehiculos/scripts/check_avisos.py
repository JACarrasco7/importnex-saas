# -*- coding: utf-8 -*-
"""
check_avisos.py — Revisa coches.json y clientes.json y genera una lista de
avisos: coches parados demasiado tiempo en un estado activo, clientes sin
contactar recientemente, y citas/ITV proximas. Es la pieza B2 del plan de
mejoras (Plan_Mejoras_JJ_Import_Motors.md): "que el sistema te persiga a ti
y no al reves".

No modifica nada — solo lee los dos JSON (ya descargados de Drive con
download_file_content, mismo patron que el resto de la skill) e imprime un
resumen en espaniol listo para pegar en la respuesta al usuario. Pensado
para ejecutarse: (a) cuando el usuario pregunta "hay algo pendiente/
atrasado?", o (b) automaticamente desde una tarea programada diaria (ver
seccion "Avisos automaticos" en SKILL.md).

Depende de que sync_web_data.py haya ido estampando fechaCambioEstado
(coches) y fechaUltimoContacto (clientes) — si un registro no tiene esas
fechas (por ejemplo, datos sembrados antes de que existiera este mecanismo),
se lista aparte como "sin fecha registrada" en vez de tratarlo como
urgente/no urgente a ciegas.

Uso:
    python check_avisos.py coches.json clientes.json
    python check_avisos.py coches.json clientes.json --dias-coche 15 --dias-cliente 7 --dias-cita 7
"""
import argparse
import json
import sys
from datetime import datetime
from pathlib import Path

ESTADOS_ACTIVOS_COCHE = [
    "Localizado", "Valorando", "Ofrecido", "Reservado", "Comprado",
    "En transito", "En tramites",
]
ESTADOS_ACTIVOS_CLIENTE = [
    "Nuevo lead", "Buscando opciones", "Coche propuesto", "Operacion en curso",
]


def parse_fecha(s):
    if not s:
        return None
    for fmt in ("%d/%m/%Y", "%Y-%m-%d"):
        try:
            return datetime.strptime(s.strip(), fmt)
        except (ValueError, AttributeError):
            continue
    return None


def load_list(path):
    p = Path(path)
    if not p.exists():
        return []
    text = p.read_text(encoding="utf-8").strip()
    return json.loads(text) if text else []


def nombre_coche(c):
    return f"{c.get('marca', '')} {c.get('modelo', '')}".strip() or c.get("id", "?")


def check_coches(coches, dias_umbral, dias_cita, hoy):
    parados, sin_fecha, citas = [], [], []
    for c in coches:
        if c.get("estado") not in ESTADOS_ACTIVOS_COCHE:
            continue
        fecha = parse_fecha(c.get("fechaCambioEstado"))
        if fecha is None:
            sin_fecha.append(c)
        else:
            dias = (hoy - fecha).days
            if dias >= dias_umbral:
                parados.append((c, dias))
        cita = parse_fecha(c.get("proximaCita"))
        if cita is not None:
            dias_para_cita = (cita - hoy).days
            if 0 <= dias_para_cita <= dias_cita:
                citas.append((c, dias_para_cita))
    parados.sort(key=lambda x: -x[1])
    citas.sort(key=lambda x: x[1])
    return parados, sin_fecha, citas


def check_clientes(clientes, dias_umbral, dias_cita, hoy):
    sin_contactar, sin_fecha, citas = [], [], []
    for cl in clientes:
        if cl.get("estado") not in ESTADOS_ACTIVOS_CLIENTE:
            continue
        fecha = parse_fecha(cl.get("fechaUltimoContacto"))
        if fecha is None:
            sin_fecha.append(cl)
        else:
            dias = (hoy - fecha).days
            if dias >= dias_umbral:
                sin_contactar.append((cl, dias))
        cita = parse_fecha(cl.get("proximaCita"))
        if cita is not None:
            dias_para_cita = (cita - hoy).days
            if 0 <= dias_para_cita <= dias_cita:
                citas.append((cl, dias_para_cita))
    sin_contactar.sort(key=lambda x: -x[1])
    citas.sort(key=lambda x: x[1])
    return sin_contactar, sin_fecha, citas


def main():
    ap = argparse.ArgumentParser()
    ap.add_argument("coches_json")
    ap.add_argument("clientes_json")
    ap.add_argument("--dias-coche", type=int, default=15, help="Dias en el mismo estado activo antes de avisar (default 15)")
    ap.add_argument("--dias-cliente", type=int, default=7, help="Dias sin contacto antes de avisar (default 7)")
    ap.add_argument("--dias-cita", type=int, default=7, help="Ventana de dias hacia adelante para avisar de citas proximas (default 7)")
    args = ap.parse_args()

    coches = load_list(args.coches_json)
    clientes = load_list(args.clientes_json)
    hoy = datetime.now()

    coches_parados, coches_sin_fecha, coches_citas = check_coches(coches, args.dias_coche, args.dias_cita, hoy)
    clientes_sin_contactar, clientes_sin_fecha, clientes_citas = check_clientes(clientes, args.dias_cliente, args.dias_cita, hoy)

    total_avisos = len(coches_parados) + len(clientes_sin_contactar) + len(coches_citas) + len(clientes_citas)

    if total_avisos == 0:
        print("Sin avisos pendientes: ningun coche parado, ningun cliente sin contactar, ninguna cita proxima.")
    else:
        print(f"AVISOS ({total_avisos}):\n")

        if coches_parados:
            print(f"Coches parados >= {args.dias_coche} dias en el mismo estado:")
            for c, dias in coches_parados:
                print(f"  - {nombre_coche(c)} — {c.get('estado')} desde hace {dias} dias (id: {c.get('id')})")
            print()

        if clientes_sin_contactar:
            print(f"Clientes sin contactar >= {args.dias_cliente} dias:")
            for cl, dias in clientes_sin_contactar:
                print(f"  - {cl.get('nombre', cl.get('id'))} — {cl.get('estado')}, ultimo contacto hace {dias} dias")
            print()

        if coches_citas:
            print(f"Citas de coches en los proximos {args.dias_cita} dias:")
            for c, dias in coches_citas:
                cuando = "hoy" if dias == 0 else f"en {dias} dia(s)"
                desc = f" — {c.get('proximaCitaDesc')}" if c.get("proximaCitaDesc") else ""
                print(f"  - {nombre_coche(c)}: {c.get('proximaCita')} ({cuando}){desc}")
            print()

        if clientes_citas:
            print(f"Citas de clientes en los proximos {args.dias_cita} dias:")
            for cl, dias in clientes_citas:
                cuando = "hoy" if dias == 0 else f"en {dias} dia(s)"
                desc = f" — {cl.get('proximaCitaDesc')}" if cl.get("proximaCitaDesc") else ""
                print(f"  - {cl.get('nombre', cl.get('id'))}: {cl.get('proximaCita')} ({cuando}){desc}")
            print()

    if coches_sin_fecha or clientes_sin_fecha:
        print("Sin fecha registrada todavia (no cuentan como urgentes, solo informativo):")
        for c in coches_sin_fecha:
            print(f"  - Coche {nombre_coche(c)} (id: {c.get('id')}) sin fechaCambioEstado")
        for cl in clientes_sin_fecha:
            print(f"  - Cliente {cl.get('nombre', cl.get('id'))} sin fechaUltimoContacto")

    sys.exit(0)


if __name__ == "__main__":
    main()
