# Decisiones clave (compacto)

> Detalle completo en _archive/Lecciones_*.md (bajo demanda).

1. Laravel (importnexcore) = fuente única (12-ago). Drive queda como respaldo histórico.
2. Briefing PDF eliminado → dossier profesional (15 sec) + ficha + folleto = 3 PDFs cliente.
3. Skill Claude Desktop → única vía con navegación real (screenshot+clic+scroll).
4. Score 0-100 con 6 dimensiones (margen, vendibilidad, cobertura, calidad, riesgo, confianza) → veredicto objetivo.
5. IEDMT con sensibilidad obligatoria (CO2 ±5g → ±85€) → cliente sabe que es estimación antes de pagar.
6. git commit antes de deploy SIEMPRE (sin excepciones, todos los proyectos).
7. Backup antes de tocar BD prod SIEMPRE.
8. 7 fuentes mínimo para veredicto (cobertura parcial = informe PARCIAL).
9. Memoria 3 niveles (corto/medio/largo) → continuidad entre sesiones.

10. (23-ago) Empaquetado real vía `empaquetar.py`, no generación manual de esqueletos: el contrato real solo lleva 2 .txt (`ficha-publicitaria.txt`, `informe-interno.txt`) + `informe.json` + `manifest.json` — no 5 como se asumió en una sesión anterior sin acceso a Desktop.
11. (23-ago) Fotos de coches.net/mobile.de nunca se descargan solas desde el entorno de Claude (bloqueo de red/anti-bot) ni desde este sandbox de sesión: `empaquetar.py` ya lo espera y deja el aviso en `manifest.json` para que el servidor las traiga de las URLs del informe. No es un fallo a resolver, es el diseño.

Plantilla nueva decisión: Fecha · Contexto · Alternativas · Razón final
