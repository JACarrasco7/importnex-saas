# Proyectos activos — Estado actual

> Estado de proyectos en curso. Claude consulta este archivo al retomar trabajo.

## 🚗 Skill importacion-vehiculos (Claude Desktop)

Estado: ✅ LISTO PARA USAR (v1.5)
Última actualización: 2026-08-12 18:36

Completado:
- [x] Navegación real 7 portales (paginas_reales.md)
- [x] Playbook filtrado + DOBLE PASADA por kW (tope de gama)
- [x] Briefing de encargo (preguntas previas OBLIGATORIAS)
- [x] Optimización de fases (ahorro tokens, early exits, caché)
- [x] Informe interno 15 sec + score 0-100
- [x] Dossier cliente 15 sec profesional
- [x] Memoria persistente (modelos, trampas, vendedores, mejoras)
- [x] Skill actualizado en Desktop (246KB, 44 archivos)

Pendiente:
- [ ] Probar en real con cliente real
- [ ] Recoger feedback y mejorar

## 🚙 Encargo Tiguan familia (Huelva) — 23-ago-2026

Estado: ✅ EMPAQUETADO, PENDIENTE SUBIDA MANUAL A LARAVEL
Coche: VW Tiguan Sport 1.4 TSI 150cv 4Motion DSG (2018), 139.269km, 16.990€, coches.net anuncio 71036643.

Completado:
- [x] Investigación completa 7 fuentes (2x, última re-verificación 23-ago)
- [x] `laravel/export/vw-tiguan-sport-2018-71036643.json` — validado con `empaquetar.py --solo-validar` sin errores ni avisos
- [x] `laravel/paquetes/vw-tiguan-sport-2018-71036643.zip` generado (informe.json + manifest.json + 2 esqueletos .txt)
- [x] Informe .md + .pdf en `informes/vw/tiguan/informe_unidad_2026-08-23.*`

Pendiente:
- [ ] Subir el ZIP en el panel: Coches → Añadir coche desde informe → Subir ZIP (dev.aktive.cloud/importnexcore). Claude no tiene el `X-Import-Token` para hacerlo por API.
- [ ] Confirmar con el vendedor: libre de accidentes + VIN (recalls)
- [x] (23-ago tarde) ZIP re-empaquetado con bloque `dossier` (15 secciones, JJM-2026-08-23-0001) tras leer contrato.md/dossier_cliente.md actualizados. Descripción del anuncio corregida a texto literal completo. Entrega ahora solo en .md (regla nueva: sin PDF salvo petición explícita).

## 📊 Laravel importnexcore

Estado: ✅ OPERATIVO
Última actualización: 2026-08-12

Completado:
- [x] Multi-tenancy + Cashier Stripe
- [x] Endpoints API importación
- [x] PDFs (dossier, ficha, folleto) con Blade+Browsershot
- [x] Briefing API deprecado (410 Gone)
- [x] Tests 4/4 pasando

## 📚 Contexto de Claude

Estado: ✅ OPTIMIZADO
Última actualización: 2026-08-12

Completado:
- [x] CLAUDE.md compacto en raíz (3KB)
- [x] Docs a _contexto/ (bajo demanda)
- [x] Memoria compacta 10KB
- [x] Sin Prompt length error
- [x] Briefing encargo + doble pasada documentados
