#!/bin/bash
set -e
BASE="https://jjimportmotors.on-forge.com"
JAR=/tmp/cookies.txt

rm -f $JAR

echo "=== 1) GET /login (saca CSRF) ==="
curl -s -c $JAR -o /tmp/login.html -w "HTTP:%{http_code}\n" "$BASE/login" -k

TOKEN=$(grep csrf-token /tmp/login.html | grep -oE 'content="[^"]+"' | head -1 | sed 's/content="//;s/"//')
echo "Token: ${TOKEN:0:20}..."

echo ""
echo "=== 2) POST /login (autenticar) ==="
curl -s -b $JAR -c $JAR -L -o /tmp/dash.html -w "HTTP:%{http_code}\nURL:%{url_effective}\n" \
  -X POST "$BASE/login" \
  -d "_token=$TOKEN" \
  -d "email=carra@jjimportmotors.com" \
  -d "password=joselete7" \
  -k

echo ""
echo "=== 3) GET /cars/import-valuation (autenticado) ==="
curl -s -b $JAR -o /tmp/imp.html -w "HTTP:%{http_code}\n" "$BASE/cars/import-valuation" -k
echo ""
echo "Body size: $(wc -c < /tmp/imp.html) bytes"
echo "Title: $(grep -oE '<title>[^<]+</title>' /tmp/imp.html | head -1)"
echo "Has 'Subir paquete'? $(grep -c 'Subir paquete' /tmp/imp.html)"
echo "Has 'import-valuation'? $(grep -c 'import-valuation' /tmp/imp.html)"
echo ""
echo "=== 4) GET /cars (control) ==="
curl -s -b $JAR -o /tmp/cars.html -w "HTTP:%{http_code}\n" "$BASE/cars" -k
echo "Has 'Inventory'? $(grep -c 'Inventory' /tmp/cars.html)"
echo "Has 'Subir ZIP' (boton)? $(grep -c 'Subir ZIP' /tmp/cars.html)"
