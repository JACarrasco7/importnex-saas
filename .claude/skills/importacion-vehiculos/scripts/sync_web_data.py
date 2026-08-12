# -*- coding: utf-8 -*-
"""
sync_web_data.py — Mantiene coches.json y clientes.json, los dos archivos
que conectan el chat (esta skill) con el panel web JJ_Panel_Coches. Son la
pieza que faltaba para que dejen de ser dos sistemas separados: cuando el
chat procesa un coche o un cliente, este script anade/actualiza su entrada
en el JSON compartido con el MISMO esquema de campos que ya usa el panel
(confirmado leyendo el coches.json real que ya existia en Drive con el Opel
Astra OPC). El panel lee y escribe ese mismo archivo, asi que cualquier
cambio hecho en la web (estado, IEDMT corregido, etc.) esta ahi la proxima
vez que se use el chat, y viceversa.

El Excel de cada coche/cliente sigue siendo el documento detallado
(checklists, cronograma, plantillas de mensaje, contrato); coches.json /
clientes.json es la capa ligera que mantiene todo conectado.

Uso:
    python sync_web_data.py coche datos_coche.json /ruta/coches.json
    python sync_web_data.py cliente datos_cliente.json /ruta/clientes.json
    python sync_web_data.py contacto datos_contacto.json /ruta/clientes.json
        (B4: anade una entrada al historial del cliente sin tocar el resto
        de la ficha — ver registrar_contacto_historial() mas abajo. Mismo
        efecto que el boton "Registrar contacto" del panel de Cowork.)
    python sync_web_data.py contacto_negocio datos_contacto_negocio.json /ruta/contactos.json
        (Agenda de contactos de negocio del panel: concesionarios, ITV/
        revisores, transportistas, gestorias, etc. Archivo separado
        contactos.json en la misma carpeta "06 CRM y clientes". Ver
        sync_contacto_negocio() mas abajo y CAMPOS_CONTACTO.)

Protocolo con Drive (igual que Registro/Inventario/Clientes, ver
references/google_drive.md): descarga la version mas reciente de
coches.json/clientes.json con download_file_content, guardala en la ruta
local que le pasas a este script, deja que el script fusione (por "id", sin
duplicar), y vuelve a subir el resultado con create_file
(base64Content, disableConversionToGoogleType: true, mismo title).

--- datos_coche.json ---
Puedes pasar directamente los campos del esquema del panel (ver
CAMPOS_COCHE mas abajo), o pasar los bloques que ya usa fill_template.py
(vehicle/investigacion/comparables/anuncio/honorarios_servicio) mas
"xlsx_path" apuntando al .xlsx ya recalculado, y este script hace la
conversion al esquema del panel automaticamente (mapear_desde_skill()).
Campos que la skill no puede rellenar sola (baseImpManual, boeConfirmado,
ivaEscenario, coc, vin, lat/lng) se dejan en blanco para que se completen
en el panel o a mano.

El campo "id" es obligatorio: usa el id del anuncio del portal si lo tienes
(mobile.de, AutoScout24... suelen tener uno en la URL), o genera un slug
estable tipo "marca-modelo-anio-mes".

--- datos_cliente.json ---
{
  "id": "laura-perez-2026-07", "nombre": "...", "contacto": "...",
  "comoLlego": "...", "queBusca": "...", "presupuestoMin": 15000,
  "presupuestoMax": 22000, "usoPrevisto": "...", "plazoDeseado": "...",
  "kmMaximo": 100000, "anioMinimo": 2018, "combustibleCambio": "...",
  "otrasPreferencias": "...", "estado": "Nuevo lead",
  "cochesPropuestos": [{"cocheId": "...", "fecha": "...", "estado": "Propuesto", "notas": "..."}],
  "cocheElegido": null, "enlaceFicha": "https://drive.google.com/...",
  "notas": "...", "fechaAlta": "23/07/2026"
}
"""
import json
import shutil
import sys
from datetime import datetime
from pathlib import Path

import openpyxl

# Estados compartidos del kanban de coches (los mismos que ya usa el panel,
# confirmados por inspeccion directa del HTML — no inventar otros).
ESTADOS_COCHE = [
    "Localizado", "Valorando", "Ofrecido", "Reservado", "Comprado",
    "En transito", "En tramites", "Entregado", "Descartado",
]
# Mapa orientativo flujo-skill -> estado del panel, por si se llama desde
# fases automaticas sin que el usuario elija estado a mano.
ESTADO_DESDE_FASE = {
    "investigando": "Valorando",
    "publicado_sin_cliente": "Ofrecido",
    "cliente_interesado": "Reservado",
    "compra_confirmada": "Comprado",
    "en_transito": "En transito",
    "en_tramites": "En tramites",
    "entregado": "Entregado",
    "descartado": "Descartado",
}

ESTADOS_CLIENTE = [
    "Nuevo lead", "Buscando opciones", "Coche propuesto",
    "Operacion en curso", "Cerrado - vendido", "Descartado",
]

CAMPOS_COCHE = [
    "id", "marca", "modelo", "version", "anio", "km", "combustible",
    "cambio", "cv", "cilindrada", "co2", "consumo", "propietarios",
    "puertas", "plazas", "norma", "color", "itv", "precioCoche",
    "precioNuevo", "baseImpManual", "boeConfirmado", "transporte",
    "itvImp", "coc", "tasas", "honorarios", "senal", "vin", "ivaEscenario",
    "vendedor", "ciudad", "lat", "lng", "estado", "enlace", "fotos",
    "semaforo", "recomendacion", "descripcion", "equipamiento",
    "valoracion", "consejos", "banderasRojas", "comparables",
    "clienteId", "enlaceFichaExcel", "enlacePdf", "notas",
    # Campos de seguimiento para avisos automaticos (check_avisos.py):
    # fechaCambioEstado se estampa solo cuando "estado" cambia respecto al
    # valor guardado (o al crear el registro), nunca en cada sync. proximaCita
    # (dd/mm/aaaa) y proximaCitaDesc son opcionales, se rellenan a mano o
    # desde el panel cuando hay ITV/recogida/cita agendada.
    "fechaCambioEstado", "proximaCita", "proximaCitaDesc",
    # Checklist de fase (B1, ver panel de Cowork): objeto con hasta 6 claves
    # booleanas (senalPagada, transporteContratado, cocPedido, itvHecha,
    # iedmtPagado, matriculado). Esta skill normalmente NO lo rellena — se
    # marca desde el panel de Cowork a medida que se completan los pasos
    # reales. Si por chat se confirma que un hito ya paso (ej. "ya pague la
    # senal"), es correcto incluir aqui el campo correspondiente en true.
    "checklist",
    # Gastos reales (C1, ver panel de Cowork): mismo desglose que los campos
    # estimados de arriba (precioCoche/transporte/itvImp/coc/tasas/
    # baseImpManual/senal/honorarios), pero con el importe REAL una vez se
    # conoce (factura de transporte, IEDMT liquidado, honorarios efectivamente
    # cobrados...). Esta skill normalmente NO los rellena sola — se marcan
    # desde el panel de Cowork o si el usuario te da por chat una cifra real
    # confirmada (ej. "el transporte al final costo 1350"). Se dejan vacios
    # hasta que se conoce el dato real; no hay que estimarlos aqui.
    "transporteReal", "itvImpReal", "cocReal", "tasasReal", "iedmtReal",
    "senalReal", "honorariosReal",
    # Carpeta de Drive del coche (panel unificado): subcarpeta dentro de
    # "07 Vehiculos (operaciones)" con las fotos originales y los documentos
    # del expediente (briefing, factura de transporte, COC, ITV, etc.). Se
    # crea automaticamente desde el panel de Cowork la primera vez que se
    # sube un documento o se pulsa "Crear carpeta en Drive"; esta skill
    # normalmente no la crea, solo la referencia si el chat ya conoce el id.
    "driveFolderId",
]

# Campos de la agenda de contactos de negocio (concesionarios, ITV/
# revisores, transportistas, gestorias, notarias, talleres, aseguradoras...),
# separados de clientes.json porque no son compradores sino colaboradores
# habituales de la operativa. Vive en contactos.json, misma carpeta que
# clientes.json ("06 CRM y clientes").
CAMPOS_CONTACTO = ["id", "nombre", "telefono", "email", "ciudad", "tags", "notas"]
TAGS_CONTACTO = [
    "Concesionario", "ITV / Revisor", "Transportista", "Gestoria",
    "Notaria", "Taller", "Aseguradora", "Otro",
]


def compute_semaforo(precio_todo_incluido_min, precio_medio_comparables):
    if not precio_todo_incluido_min or not precio_medio_comparables:
        return None
    try:
        pmin = float(precio_todo_incluido_min)
        pmed = float(precio_medio_comparables)
    except (TypeError, ValueError):
        return None
    if pmin <= pmed:
        return "green"
    if pmin <= pmed * 1.05:
        return "amber"
    return "red"


def read_numeros_from_xlsx(xlsx_path):
    """Lee del .xlsx ya recalculado los importes que mapean a los campos
    de coste del panel. Ver references/cell_map.md hoja 'Numeros':
    B13 = subtotal logistica de viaje -> transporte
    B15 = ITV espaniola de importacion -> itvImp
    B17+B18+B19 = tasa DGT + gestoria + placas -> tasas
    B32 = honorarios de servicio -> honorarios
    B45/B46 = precio todo incluido min/max
    B49 = precio medio comparables (hoja Vehiculo y resumen)
    No hay campo equivalente a "coc" (Certificado de Conformidad) en la
    plantilla actual — se deja en 0 para completar a mano si aplica."""
    wb = openpyxl.load_workbook(xlsx_path, data_only=True)
    numeros = wb["Numeros"]
    resumen = wb["Vehiculo y resumen"]
    transporte = numeros["B13"].value
    itv_imp = numeros["B15"].value
    tasas = sum(v for v in (numeros["B17"].value, numeros["B18"].value, numeros["B19"].value) if v)
    honorarios = numeros["B32"].value
    precio_min = resumen["B45"].value
    precio_medio = resumen["B49"].value
    return {
        "transporte": transporte,
        "itvImp": itv_imp,
        "tasas": tasas or None,
        "honorarios": honorarios,
        "semaforo": compute_semaforo(precio_min, precio_medio),
    }


def split_marca_modelo(marca_modelo):
    if not marca_modelo:
        return None, None
    parts = marca_modelo.strip().split(" ", 1)
    if len(parts) == 1:
        return parts[0], None
    return parts[0], parts[1]


def mapear_desde_skill(data):
    """Convierte el formato datos.json de fill_template.py (vehicle/
    investigacion/comparables/anuncio/honorarios_servicio + xlsx_path) al
    esquema del panel. Si 'data' ya viene en esquema del panel (tiene
    'marca'/'precioCoche' etc.), se devuelve tal cual."""
    if "vehicle" not in data and "marca" in data:
        return data  # ya viene en esquema del panel

    vehicle = data.get("vehicle", {})
    investigacion = data.get("investigacion", {})
    comparables_in = data.get("comparables", [])
    anuncio = data.get("anuncio", {})
    xlsx_path = data.get("xlsx_path")

    marca, modelo = split_marca_modelo(vehicle.get("marca_modelo"))

    out = {
        "id": data["id"],
        "marca": marca,
        "modelo": modelo,
        "version": vehicle.get("motorizacion"),
        "anio": vehicle.get("anio_matriculacion"),
        "km": vehicle.get("kilometraje"),
        "combustible": vehicle.get("combustible"),
        "cambio": vehicle.get("cambio"),
        "propietarios": vehicle.get("propietarios"),
        "puertas": vehicle.get("puertas_plazas"),
        "color": vehicle.get("color_exterior"),
        "precioCoche": vehicle.get("precio_anuncio"),
        "co2": vehicle.get("co2"),
        "vendedor": vehicle.get("vendedor_nombre_ubicacion"),
        "enlace": vehicle.get("url_anuncio"),
        "fotos": [vehicle["enlace_fotos"]] if vehicle.get("enlace_fotos") else [],
        "vin": vehicle.get("vin"),
        "descripcion": anuncio.get("descripcion_larga") or anuncio.get("descripcion_corta"),
        "equipamiento": [e.strip() for e in (vehicle.get("equipamiento") or "").split(",") if e.strip()] or None,
        "estado": data.get("estado"),
        "clienteId": data.get("cliente_id"),
        "enlaceFichaExcel": data.get("enlace_ficha_excel"),
        "enlacePdf": data.get("enlace_pdf"),
        "notas": data.get("notas"),
    }

    consejos = []
    banderas_rojas = []
    valoracion_partes = []
    for clave, entry in (investigacion or {}).items():
        if not entry:
            continue
        hallazgo = entry.get("hallazgo") if isinstance(entry, dict) else entry
        if not hallazgo:
            continue
        if clave == "problemas_comunes":
            consejos.append(hallazgo)
        elif clave == "recalls":
            if "no se han encontrado" not in hallazgo.lower():
                banderas_rojas.append(f"Recall: {hallazgo}")
        elif clave == "precio_mercado":
            valoracion_partes.append(hallazgo)
        elif clave == "fiabilidad":
            valoracion_partes.append(hallazgo)
        elif clave == "otros":
            out["notas"] = ((out.get("notas") or "") + " " + hallazgo).strip()
    if consejos:
        out["consejos"] = consejos
    if banderas_rojas:
        out["banderasRojas"] = banderas_rojas
    if valoracion_partes:
        out["valoracion"] = " ".join(valoracion_partes)

    if comparables_in:
        out["comparables"] = [
            {
                "t": f"{c.get('portal', '')} {c.get('anio', '')}".strip(),
                "p": f"{c.get('precio')} EUR" if c.get("precio") else "",
                "u": c.get("url", ""),
            }
            for c in comparables_in
        ]

    if xlsx_path:
        out.update({k: v for k, v in read_numeros_from_xlsx(Path(xlsx_path)).items() if v is not None})
    elif data.get("honorarios_servicio"):
        out["honorarios"] = data["honorarios_servicio"]

    return {k: v for k, v in out.items() if v is not None}


def load_list(path):
    if not path.exists():
        return []
    text = path.read_text(encoding="utf-8").strip()
    if not text:
        return []
    return json.loads(text)


def upsert(items, new_item, key="id"):
    for i, existing in enumerate(items):
        if existing.get(key) == new_item.get(key):
            merged = {**existing, **{k: v for k, v in new_item.items() if v is not None}}
            items[i] = merged
            return items, True
    items.append(new_item)
    return items, False


def save_list(items, path):
    tmp_path = path.with_suffix(".tmp.json")
    tmp_path.write_text(json.dumps(items, ensure_ascii=False, indent=2), encoding="utf-8")
    shutil.copy(tmp_path, path)
    try:
        tmp_path.unlink()
    except OSError:
        # En algunos entornos montados (Drive local, JJImportMotors) borrar
        # el .tmp.json intermedio da "Operation not permitted" aunque la
        # copia al destino ya se hizo bien. No es un fallo real: el dato ya
        # esta guardado en 'path', asi que ignoramos el error de limpieza en
        # vez de dejar que rompa el script (ver Notas importantes en SKILL.md).
        pass


def sync_coche(data, target_path):
    if "id" not in data or not data["id"]:
        raise ValueError("datos_coche.json necesita un 'id' estable (ej. 'audi-a3-2020-07', o el id del anuncio del portal).")
    mapeado = mapear_desde_skill(data)
    if mapeado.get("estado") and mapeado["estado"] not in ESTADOS_COCHE:
        print(f"Aviso: estado {mapeado['estado']!r} no esta en {ESTADOS_COCHE}; se guarda igual.")
    items = load_list(target_path)
    existing = next((i for i in items if i.get("id") == mapeado.get("id")), None)
    hoy = datetime.now().strftime("%d/%m/%Y")
    if existing is None:
        # Registro nuevo: la fecha de alta es tambien el punto de partida
        # para "dias en este estado" (check_avisos.py).
        mapeado.setdefault("fechaCambioEstado", hoy)
    elif mapeado.get("estado") and mapeado["estado"] != existing.get("estado"):
        mapeado["fechaCambioEstado"] = hoy
    items, updated = upsert(items, mapeado)
    save_list(items, target_path)
    return updated


def registrar_contacto_historial(data, target_path):
    """Anade una entrada al historial de contacto de un cliente (B4, ver
    panel de Cowork -> boton 'Registrar contacto'). No toca el resto de la
    ficha: solo aniade {fecha, canal, resumen} a data['historial'] y estampa
    fechaUltimoContacto, para que check_avisos.py deje de marcarlo como
    'sin contactar'.
    datos_contacto.json: {"id": "...", "canal": "WhatsApp", "resumen": "...",
    "fecha": "24/07/2026"}  (fecha es opcional, por defecto hoy)."""
    if "id" not in data or not data["id"]:
        raise ValueError("datos_contacto.json necesita un 'id' de cliente existente.")
    items = load_list(target_path)
    idx = next((i for i, x in enumerate(items) if x.get("id") == data["id"]), None)
    if idx is None:
        raise ValueError(f"No se encontro ningun cliente con id={data['id']!r} en {target_path}.")
    fecha = data.get("fecha") or datetime.now().strftime("%d/%m/%Y")
    entrada = {"fecha": fecha, "canal": data.get("canal") or "-", "resumen": data.get("resumen") or ""}
    cliente = items[idx]
    cliente.setdefault("historial", []).append(entrada)
    # fechaUltimoContacto es la mas reciente del historial, no necesariamente
    # la que se acaba de anadir (se puede registrar un contacto pasado a
    # posteriori sin que eso "retroceda" el aviso de sin-contactar).
    def parse_dmy(s):
        try:
            return datetime.strptime(s, "%d/%m/%Y")
        except (ValueError, TypeError):
            return None
    fechas = [f for f in (parse_dmy(h.get("fecha")) for h in cliente["historial"]) if f]
    if fechas:
        cliente["fechaUltimoContacto"] = max(fechas).strftime("%d/%m/%Y")
    else:
        cliente["fechaUltimoContacto"] = fecha
    items[idx] = cliente
    save_list(items, target_path)
    return entrada


def sync_cliente(data, target_path, registrar_contacto=True):
    if "id" not in data or not data["id"]:
        raise ValueError("datos_cliente.json necesita un 'id' estable (ej. 'laura-perez-2026-07').")
    if data.get("estado") and data["estado"] not in ESTADOS_CLIENTE:
        print(f"Aviso: estado {data['estado']!r} no esta en {ESTADOS_CLIENTE}; se guarda igual.")
    if registrar_contacto:
        # Por defecto, cada vez que esta skill toca la ficha de un cliente
        # (nuevo lead, propuesta, respuesta, cambio de estado...) se cuenta
        # como un contacto real para check_avisos.py. Si el ajuste es puramente
        # interno (ej. corregir un dato sin que haya habido contacto real),
        # llama con registrar_contacto=False o pasa --sin-contacto por CLI.
        data["fechaUltimoContacto"] = datetime.now().strftime("%d/%m/%Y")
    items = load_list(target_path)
    items, updated = upsert(items, data)
    save_list(items, target_path)
    return updated


def sync_contacto_negocio(data, target_path):
    """Alta/actualizacion de un contacto de negocio (concesionario, ITV,
    transportista...) en contactos.json. datos_contacto_negocio.json:
    {"id": "...", "nombre": "...", "telefono": "...", "email": "...",
    "ciudad": "...", "tags": ["Transportista"], "notas": "..."}. 'tags'
    idealmente son valores de TAGS_CONTACTO, pero no se valida de forma
    estricta (se guarda igual si se usa una etiqueta nueva)."""
    if "id" not in data or not data["id"]:
        raise ValueError("datos_contacto_negocio.json necesita un 'id' estable (ej. 'transportes-garcia').")
    if not data.get("nombre"):
        raise ValueError("datos_contacto_negocio.json necesita 'nombre'.")
    tags = data.get("tags")
    if tags:
        desconocidas = [t for t in tags if t not in TAGS_CONTACTO]
        if desconocidas:
            print(f"Aviso: etiqueta(s) {desconocidas} no estan en {TAGS_CONTACTO}; se guardan igual.")
    items = load_list(target_path)
    items, updated = upsert(items, data)
    save_list(items, target_path)
    return updated


def main():
    args = [a for a in sys.argv[1:] if a != "--sin-contacto"]
    sin_contacto = "--sin-contacto" in sys.argv[1:]
    if len(args) < 3:
        print("Uso: python sync_web_data.py <coche|cliente|contacto|contacto_negocio> datos.json /ruta/archivo.json [--sin-contacto]")
        sys.exit(1)

    tipo = args[0]
    data_path = Path(args[1])
    target_path = Path(args[2])
    data = json.loads(data_path.read_text(encoding="utf-8"))

    target_path.parent.mkdir(parents=True, exist_ok=True)

    if tipo == "coche":
        updated = sync_coche(data, target_path)
    elif tipo == "cliente":
        updated = sync_cliente(data, target_path, registrar_contacto=not sin_contacto)
    elif tipo == "contacto":
        entrada = registrar_contacto_historial(data, target_path)
        print(f"OK: contacto registrado en {target_path} (id={data.get('id')}, {entrada['fecha']}, {entrada['canal']})")
        return
    elif tipo == "contacto_negocio":
        updated = sync_contacto_negocio(data, target_path)
    else:
        print(f"Tipo desconocido: {tipo!r}. Usa 'coche', 'cliente', 'contacto' o 'contacto_negocio'.")
        sys.exit(1)

    accion = "actualizado" if updated else "anadido"
    print(f"OK: registro {accion} en {target_path} (id={data.get('id')})")


if __name__ == "__main__":
    main()
