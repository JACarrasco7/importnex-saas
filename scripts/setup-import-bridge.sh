#!/bin/bash

# Script para configurar el bridge de importación de informes del chat
# Genera un token seguro y configura el servidor para aceptar peticiones del chat

set -e

echo "🚀 Configurando Importnex Chat Bridge..."

# Generar token seguro si no existe
if [ -z "$IMPORTNEX_CHAT_IMPORT_TOKEN" ]; then
    echo "📋 Generando token seguro..."
    export IMPORTNEX_CHAT_IMPORT_TOKEN=$(openssl rand -hex 32)
    echo "✅ Token generado: $IMPORTNEX_CHAT_IMPORT_TOKEN"

    # Guardar en .env
    echo "IMPORTNEX_CHAT_IMPORT_TOKEN=$IMPORTNEX_CHAT_IMPORT_TOKEN" >> .env
    echo "✅ Token guardado en .env"
else
    echo "✅ Token existente: $IMPORTNEX_CHAT_IMPORT_TOKEN"
fi

# Crear estructura de carpetas
echo "📁 Creando estructura de carpetas..."
ORG_DIR="storage/app/importnex/import/JJ_Import_Motors/vehicles"
mkdir -p "$ORG_DIR"
echo "✅ Carpeta creada: $ORG_DIR"

# Crear carpeta de procesados
mkdir -p "storage/app/importnex/import/JJ_Import_Motors/processed"
echo "✅ Carpeta de procesados creada"

# Establecer permisos
echo "🔐 Estableciendo permisos..."
chmod -R 775 storage/app/importnex
echo "✅ Permisos establecidos"

# Limpiar caché
echo "🧹 Limpiando caché..."
php artisan config:clear
php artisan cache:clear
echo "✅ Caché limpiado"

# Mostrar URL del endpoint
echo ""
echo "🎯 Bridge configurado correctamente!"
echo ""
echo "📍 Endpoint: https://dev.aktive.cloud/importnexcore/api/import-valuation"
echo "🔑 Token: $IMPORTNEX_CHAT_IMPORT_TOKEN"
echo ""
echo "📋 Instrucciones para el chat:"
echo "   curl -X POST https://dev.aktive.cloud/importnexcore/api/import-valuation \\"
echo "     -H 'X-Import-Token: $IMPORTNEX_CHAT_IMPORT_TOKEN' \\"
echo "     -H 'Content-Type: application/json' \\"
echo "     --data @informe.json"
echo ""
echo "📚 Nota: Guarda el token en laravel/api_token.txt para referencia futura"
echo "$IMPORTNEX_CHAT_IMPORT_TOKEN" > laravel/api_token.txt
echo "✅ Token guardado en laravel/api_token.txt"
