#!/usr/bin/env python3
"""
Verifica que los scripts referenciados en el skill existen en Desktop.

Uso:
    python scripts/verify_desktop_sync.py

Propósito:
    El skill referencia scripts en C:\\Users\\jacar\\Desktop\\JJImportMotors\\laravel\\
    Este script verifica que todos los scripts necesarios están presentes antes
    de iniciar una sesión de investigación.
"""

import os
import sys
from pathlib import Path

# Ruta base de scripts en Desktop
DESKTOP_BASE = Path.home() / "Desktop" / "JJImportMotors" / "laravel"

# Scripts requeridos (referenciados en operaciones.md y SKILL.md)
REQUIRED_SCRIPTS = [
    "franja.py",                    # Cálculo de franjas de precio
    "comparativa_cliente.py",       # Comparativa para Flujo B (MODELO)
    "cache_investigacion.py",       # Caché de investigaciones previas
    "pdf_kit.py",                   # Generación de PDFs (legacy, solo backup)
    "fill_template.py",             # Relleno de plantillas Excel
    "fill_client_template.py",      # Relleno de ficha cliente
    "generate_summary_pdf.py",      # PDF resumen (legacy)
    "generate_browser_dashboard.py", # Dashboard HTML
    "sync_web_data.py",             # Sincronización web (legacy)
    "update_master_list.py",        # Actualización lista maestra
    "update_registro.py",           # Actualización registro
    "check_avisos.py",              # Verificación de avisos
]

# Archivos de datos requeridos
REQUIRED_DATA = [
    "marca.json",                   # Datos de marca JJ Import Motors
    "datos_mercado.json",           # Caché de datos de mercado (puede no existir al inicio)
]


def check_file(path: Path, category: str) -> tuple[bool, str]:
    """Verifica si un archivo existe y devuelve (existe, mensaje)."""
    if path.exists():
        size_kb = path.stat().st_size / 1024
        return True, f"✅ {category}: {path.name} ({size_kb:.1f} KB)"
    else:
        return False, f"❌ {category}: {path.name} FALTANTE"


def verify():
    """Verifica que todos los scripts y datos requeridos existen en Desktop."""
    print(f"\n🔍 Verificando sincronización Desktop ↔ Skill\n")
    print(f"📁 Ruta base: {DESKTOP_BASE}\n")

    if not DESKTOP_BASE.exists():
        print(f"❌ ERROR: La carpeta {DESKTOP_BASE} no existe")
        print("   ¿Estás en el PC correcto? ¿Se movió la carpeta del proyecto?\n")
        return False

    # Verificar scripts
    print("=" * 60)
    print("SCRIPTS REQUERIDOS")
    print("=" * 60)

    scripts_ok = 0
    scripts_missing = []

    for script in REQUIRED_SCRIPTS:
        path = DESKTOP_BASE / script
        exists, msg = check_file(path, "Script")
        print(msg)
        if exists:
            scripts_ok += 1
        else:
            scripts_missing.append(script)

    print(f"\n📊 Scripts: {scripts_ok}/{len(REQUIRED_SCRIPTS)} presentes")

    # Verificar datos
    print("\n" + "=" * 60)
    print("ARCHIVOS DE DATOS")
    print("=" * 60)

    data_ok = 0
    data_missing = []

    for data_file in REQUIRED_DATA:
        path = DESKTOP_BASE / data_file
        exists, msg = check_file(path, "Datos")
        print(msg)
        if exists:
            data_ok += 1
        else:
            # datos_mercado.json puede no existir al inicio, no es crítico
            if data_file == "datos_mercado.json":
                print("   ℹ️  Este archivo se crea automáticamente en la primera investigación")
            else:
                data_missing.append(data_file)

    print(f"\n📊 Datos: {data_ok}/{len(REQUIRED_DATA)} presentes")

    # Resumen final
    print("\n" + "=" * 60)
    print("RESUMEN")
    print("=" * 60)

    total_missing = len(scripts_missing) + len(data_missing)

    if total_missing == 0:
        print("\n✅ TODO OK: Todos los archivos están presentes en Desktop")
        print("   Puedes iniciar la sesión de investigación con confianza.\n")
        return True
    else:
        print(f"\n❌ FALTAN {total_missing} archivos:")

        if scripts_missing:
            print(f"\n   Scripts faltantes ({len(scripts_missing)}):")
            for s in scripts_missing:
                print(f"     - {s}")

        if data_missing:
            print(f"\n   Datos faltantes ({len(data_missing)}):")
            for d in data_missing:
                print(f"     - {d}")

        print("\n💡 SOLUCIÓN:")
        print("   1. Verifica que la carpeta Desktop/JJImportMotors/laravel/ esté completa")
        print("   2. Si falta algo, copia desde .claude/skills/importacion-vehiculos/scripts/")
        print("   3. Re-ejecuta este script para confirmar\n")

        return False


if __name__ == "__main__":
    success = verify()
    sys.exit(0 if success else 1)
