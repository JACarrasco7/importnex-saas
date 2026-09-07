# modelos-medidos — volcado del estudio de mercado del 17-ago-2026

> Registro histórico compartido con la skill `importacion-vehiculos`.
> Pegar/fusionar en `importacion-vehiculos/memoria/modelos-medidos.md`.
> Fuente de medición: `estudio` · Portales: Coches.net (ES) y mobile.de (DE).
> Bandas aplicadas en AMBOS mercados: showstoppers ≥20.000 € · compactos 8.000-17.000 € + 2016+ + gasolina/híbrido.

| slug | modelo / versión | oferta ES | mediana ES | oferta DE | mediana DE | hueco bruto | hueco neto | mejor mercado | confianza | veredicto | caduca |
|---|---|---:|---:|---:|---:|---:|---:|:--:|:--:|:--:|---|
| vw-golf-gti | VW Golf GTI | 683 | 29.500 € | 3.763 | 29.900 € | −1,4 % | −11,3 % | ES | 4 | 🟡 | 2026-08-31 |
| vw-golf-r | VW Golf R (≥213 kW 4Motion) | 264 | 34.250 € | 798 | 36.940 € | −7,9 % | −16,4 % | ES | 3 | 🟡 | 2026-08-31 |
| audi-s3 | Audi S3 (ES incluye RS3) | 113 | 38.500 € | 966 | 39.290 € | −2,1 % | −9,7 % | ES | 3 | 🟡 | 2026-08-31 |
| mercedes-a45-amg | Mercedes A 45 AMG | 314 | 33.900 € | 257 | 38.980 € | −15,0 % | −23,6 % | ES | 3 | 🟡 | 2026-08-31 |
| cupra-leon | Cupra León | 655 | 27.970 € | 5.321 | 30.790 € | −10,1 % | −20,6 % | ES | 3 | 🟢 | 2026-08-31 |
| bmw-serie-1-m135 | BMW Serie 1 M135 xDrive | 13 | 47.900 € | — | *pendiente* | — | — | — | 2 | 🟡 | 2026-08-31 |
| audi-tt | Audi TT (incl. TTS) | 86 | 29.350 € | — | *pendiente* | — | — | — | 2 | 🟡 | 2026-08-31 |
| toyota-auris-hibrido-2016 | Toyota Auris Híbrido 1.8 HSD | 97 | 14.400 € | 216 | 12.500 € | **+13,2 %** | **+5,4 %** | **DE** | 3 | 🟢 | 2026-09-14 |
| toyota-corolla-hibrido-2016 | Toyota Corolla Híbrido | 139 | 15.900 € | 205 | 15.500 € | +2,5 % | −4,6 % | ES | 3 | 🟢 | 2026-09-14 |
| renault-megane-gasolina-2016 | Renault Mégane TCe | 383 | 12.550 € | 947 | 12.000 € | +4,4 % | −7,4 % | ES | 3 | 🟡 | 2026-09-14 |
| kia-ceed-gasolina-2016 | Kia Ceed T-GDi | 269 | 14.580 € | 533 | 13.950 € | +4,3 % | −5,8 % | ES | 3 | 🟡 | 2026-09-14 |
| peugeot-308-gasolina-2016 | Peugeot 308 PureTech | 431 | 11.500 € | 1.353 | 11.500 € | 0,0 % | −12,9 % | ES | 4 | 🟡 | 2026-09-14 |
| vw-golf-gasolina-2016 | VW Golf 1.0-1.5 TSI | 405 | 14.590 € | 7.351 | *pendiente* | — | — | — | 3 | 🟡 | 2026-09-14 |
| seat-leon-gasolina-2016 | Seat León TSI | 468 | 13.950 € | 2.518 | *pendiente* | — | — | — | 3 | 🟡 | 2026-09-14 |
| ford-focus-gasolina-2016 | Ford Focus 1.0 EcoBoost | 431 | 12.790 € | 5.123 | *pendiente* | — | — | — | 3 | 🟡 | 2026-09-14 |
| opel-astra-gasolina-2016 | Opel Astra Turbo | 330 | 11.950 € | 3.492 | *pendiente* | — | — | — | 3 | 🟡 | 2026-09-14 |
| mazda-3-gasolina-2016 | Mazda 3 Skyactiv-G | 33 | 14.100 € | 241 | *pendiente* | — | — | — | 2 | 🟡 | 2026-09-14 |

---

## Trampas nuevas (para `trampas-encontradas.md`)

1. **Coches.net · versión por subcadena.** `Versions[0]=ST` captura *ST-Line*; `=N` captura *N Line*; `=GR` captura *GR Sport*; `=S3` captura *RS3*. Siempre verificar el título de los primeros anuncios antes de anotar la cifra.
2. **Coches.net · el modelo se pierde.** `/mercedes-benz/clase-a/segunda-mano/?Versions[0]=AMG` devuelve **toda la marca**. Verificar el `h1` del resultado (`h1` = "N MARCA MODELO de segunda mano y ocasión").
3. **Coches.net · lista virtualizada.** Solo se renderizan ~8 tarjetas de las 30 de cada página. Para medianas hay que navegar a la página del elemento N/2 e interpolar, o contar con `MaxPrice`.
4. **mobile.de · el 3.er campo de `ms` rompe la búsqueda.** `ms=25200;14;GTI` devuelve el catálogo completo (726.000 anuncios). Usar `q=` para texto libre.
5. **mobile.de · tope de paginación.** Vacío a partir de ~100 páginas (2.000 resultados). Con `N > 2.000`, mediana por conteo de bandas de precio.
6. **mobile.de · bloqueo por ritmo.** "Zugriff verweigert" tras ~40 peticiones seguidas; no se levanta en 15+ min. Espaciar ≥8 s y partir el estudio en lotes por categoría.
7. **mobile.de · el símbolo ¹ NO es patrocinado**, significa "MwSt. ausweisbar" (IVA deducible). Son precios reales y deben entrar en la mediana.

## Parámetros de portal (para `playbook_filtrado.md`)

**Coches.net** — ruta `/{marca}/{modelo}/{combustible}/segunda-mano/`
`MinPrice` · `MaxPrice` · `MinYear` · `Versions[0]` · `fi=Price` · `or=1` (asc) / `or=-1` (desc) · `pg`
Combustibles como segmento de ruta: `gasolina` · `hibrido` · `hibrido_enchufable` · `diesel`

**mobile.de** — `/fahrzeuge/search.html`
`ms=makeId;modelId;;` · `p=min:max` · `fr=año:` · `fu=PETROL|HYBRID|DIESEL` · `pw=kW:` · `q=texto` · `sb=p&od=up` · `pageNumber`

**IDs mobile.de** — marcas: VW 25200 · Audi 1900 · BMW 3500 · Mercedes 17200 · Cupra 3 · Seat 22500 · Ford 9000 · Opel 19000 · Peugeot 19300 · Porsche 20100 · Renault 20700 · Kia 13200 · Mazda 16800 · Toyota 24100 · Hyundai 11600
**Modelos:** Golf 14 · A3 8 · S3 19 · RS3 36 · TT 23 · TTS 4 · TT RS 35 · M135 69 · M140i 122 · A45 AMG 229 · A35 AMG 298 · Cupra León 6 · Seat León 9 · Focus 20 · Astra 5 · 308 32 · Mégane 17 · Ceed 26 · Mazda 3 = 4 · Corolla 9 · Auris 39 · GR86 92 · Yaris 36
