#!/bin/bash
echo "=== Asset info ==="
ls -la /var/www/importnex-saas/public/build/assets/ImportValuation*

echo ""
echo "=== Manifest entry ==="
python3 -c "import json; m = json.load(open('/var/www/importnex-saas/public/build/manifest.json')); print(m.get('resources/js/Pages/Cars/ImportValuation.vue'))"

echo ""
echo "=== Test fetch the asset ==="
curl -s -o /dev/null -w "HTTP:%{http_code} | size:%{size_download} bytes\n" "https://jjimportmotors.on-forge.com/build/assets/ImportValuation-CHmCPoOl.js" -k
