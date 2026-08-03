#!/bin/bash
curl -s 'https://jjimportmotors.on-forge.com/cars/import-valuation' -o /tmp/imp3.html -w "HTTP:%{http_code}\n" -k
echo "--- assets js ---"
grep -oE 'src="[^"]+\.js"' /tmp/imp3.html | head -10
echo "--- data-page ---"
grep -oE 'data-page="[^"]+"' /tmp/imp3.html | head -3
echo "--- title ---"
grep -oE '<title>[^<]+</title>' /tmp/imp3.html | head -1
echo "--- tamano ---"
wc -c /tmp/imp3.html
echo "--- ultimas 30 lineas ---"
tail -30 /tmp/imp3.html