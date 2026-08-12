# -*- coding: utf-8 -*-
"""
generate_browser_dashboard.py — Genera JJ_Centro_Operaciones.html: el panel
que se abre directamente en el navegador (doble clic, sin pasar por Cowork),
con mapa real (Leaflet + OpenStreetMap), organizador de viajes con rutas
reales y enlace a Google Maps, kanban de coches, clientes con matching y
finanzas con graficos.

Es una FOTO del momento (no lee ni escribe Drive por si mismo — un HTML
suelto no puede hablar con el conector de Drive sin que el usuario conecte
su cuenta de Google directamente, ver conversacion). Por eso hay que
regenerarlo cada vez que coches.json/clientes.json cambian, y esta es
responsabilidad del chat: cada vez que Fase 4 paso 8 o Gestion de clientes
paso 4 sincronizan coches.json/clientes.json, tambien hay que ejecutar este
script y volver a subir el resultado a Drive.

La geocodificacion (convertir "ciudad" en lat/lng) y las rutas reales
(distancia por carretera) SI funcionan, porque las hace el navegador del
usuario en tiempo real cuando abre el archivo (fetch a Nominatim y a OSRM),
no este script — el entorno donde corre esta skill no tiene salida de red
libre para geocodificar en el momento de generar el archivo.

Uso:
    python generate_browser_dashboard.py coches.json clientes.json contactos.json /ruta/salida/JJ_Centro_Operaciones.html [template.html]

Nota: contactos.json es la agenda de contactos de negocio del panel
unificado (concesionarios, ITV/revisores, transportistas...). Si el archivo
no existe todavia (negocio que aun no ha dado de alta ninguno), pasa
"-" en su lugar y se incrusta una lista vacia.
"""
import json
import sys
from datetime import datetime
from pathlib import Path

DEFAULT_TEMPLATE = Path(__file__).resolve().parent.parent / "assets" / "dashboard_template.html"


def main():
    if len(sys.argv) < 5:
        print("Uso: python generate_browser_dashboard.py coches.json clientes.json contactos.json /ruta/salida/JJ_Centro_Operaciones.html [template.html]")
        sys.exit(1)

    coches_path = Path(sys.argv[1])
    clientes_path = Path(sys.argv[2])
    contactos_path = Path(sys.argv[3])
    out_path = Path(sys.argv[4])
    template_path = Path(sys.argv[5]) if len(sys.argv) > 5 else DEFAULT_TEMPLATE

    coches_json = coches_path.read_text(encoding="utf-8") if coches_path.exists() else "[]"
    clientes_json = clientes_path.read_text(encoding="utf-8") if clientes_path.exists() else "[]"
    contactos_json = contactos_path.read_text(encoding="utf-8") if (str(contactos_path) != "-" and contactos_path.exists()) else "[]"

    # Validamos que sean JSON valido antes de incrustarlo (si no, mejor fallar
    # aqui con un error claro que generar un HTML roto).
    json.loads(coches_json)
    json.loads(clientes_json)
    json.loads(contactos_json)

    template = template_path.read_text(encoding="utf-8")
    generated_at = datetime.now().strftime("%d/%m/%Y %H:%M")

    html = (
        template
        .replace("__COCHES_JSON__", coches_json)
        .replace("__CLIENTES_JSON__", clientes_json)
        .replace("__CONTACTOS_JSON__", contactos_json)
        .replace("__GENERATED_AT__", generated_at)
    )

    out_path.parent.mkdir(parents=True, exist_ok=True)
    out_path.write_text(html, encoding="utf-8")
    print(f"OK: {out_path} generado ({len(html)} caracteres, {generated_at})")
    print("Sube este archivo a Drive (carpeta '07 Vehiculos (operaciones)') con create_file, base64Content, disableConversionToGoogleType: true.")


if __name__ == "__main__":
    main()
