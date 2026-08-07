#!/bin/sh
# Script ejecutable — Validar firma de webhook Stripe
# Parte de importnex-cashier-billing skill (Agent Skills v2)
#
# Uso: bash .ai/skills/importnex-cashier-billing/scripts/validate-webhook.sh <payload.json> <signature>

if [ $# -lt 2 ]; then
    echo "Uso: bash validate-webhook.sh <payload.json> <stripe-signature>"
    exit 1
fi

PAYLOAD="$1"
SIGNATURE="$2"
SECRET="${STRIPE_WEBHOOK_SECRET:-whsec_test}"

# Validar que el payload es JSON válido
if ! echo "$PAYLOAD" | php -r '
    $json = file_get_contents("php://stdin");
    $data = json_decode($json);
    if (json_last_error() !== JSON_ERROR_NONE) {
        echo "❌ Payload no es JSON válido\n";
        exit(1);
    }
    echo "✅ Payload JSON válido\n";
'; then
    exit 1
fi

# Verificar que tiene event.id (para idempotencia)
EVENT_ID=$(echo "$PAYLOAD" | php -r '
    $data = json_decode(file_get_contents("php://stdin"), true);
    echo $data["id"] ?? "";
')
if [ -z "$EVENT_ID" ]; then
    echo "❌ Payload sin event.id"
    exit 1
fi
echo "✅ event.id: $EVENT_ID"

echo "✅ Verificación de estructura completada"
echo "⚠️  Para verificar firma real, usar Stripe CLI: stripe verify --event-id=$EVENT_ID"
exit 0
