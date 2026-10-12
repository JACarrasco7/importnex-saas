# Flujo operativo — JJ Import Motors

Este documento describe cómo funciona el sistema completo: qué hace el chat, qué hace el panel web, dónde vive cada dato, y qué se crea exactamente en cada paso. Sirve como referencia para no perder de vista cómo encajan las piezas.

## 1. Arquitectura general (las 3 piezas)

> ⚠️ **12-ago-2026 — Arquitectura actualizada.** Anteriormente Google Drive era la fuente única de verdad. Ahora **Laravel (importnexcore)** es la pieza central. Google Drive queda como **backup histórico** opcional.

**Laravel (`importnexcore`) es la fuente única de verdad actual.** Todo se persiste en su base de datos (MySQL en producción / SQLite en tests) y en el storage local (`storage/app/private/cars/{id}/contenido/*.txt`).

**El chat (aquí, con la skill `importacion-vehiculos`) es donde se investiga y se generan los esqueletos.** Es el único sitio con navegación real de portales: aquí se analizan anuncios, se investigan recalls/fiabilidad/precios de mercado, y se redactan los esqueletos `[MARCADOR]` que se empaquetan en ZIPs y se suben vía API a Laravel.

**El panel web (`jj-panel-operaciones`, un artifact de Cowork) sigue siendo la herramienta de uso diario.** Lee y muestra datos de Laravel. **No investiga por su cuenta** — solo tiene una IA ligera para tareas simples.

> **Legado:** Las carpetas "07 Vehículos (operaciones)" y "06 CRM y clientes" de Google Drive **siguen siendo válidas como respaldo histórico** pero ya no son la fuente primaria. Los archivos Excel/PDF antiguos se mantienen para auditoría.

## 2. Flujo 1 — Oferta (sourcing propio, sin cliente todavía)

Se dispara cuando pegas en el chat una captura, foto o enlace de un anuncio (mobile.de, AutoScout24, Coches.net, Wallapop...).

### Fase 1 — Extraer datos del anuncio
El chat lee la imagen/texto y rellena los campos del vehículo: marca/modelo, motorización, combustible, cambio, tracción, km, año, propietarios, color, equipamiento, precio del anuncio, CO2, vendedor, enlace, etc. Lo que no aparece en el anuncio se deja en blanco (nunca se inventa), especialmente el CO2 si no viene indicado.

### Fase 2 — Investigar el modelo (búsqueda web real)
Se investigan 5 puntos, cada uno con su fuente citada:
1. Problemas/averías comunes de ese motor y modelo.
2. Campañas de recall oficiales pendientes (con VIN si el anuncio lo da).
3. Precio de mercado real: 3-5 comparables de anuncios similares.
4. Fiabilidad general / opinión media de propietarios y prensa.
5. Otros datos relevantes de esa unidad concreta (garantía restante, campañas ya aplicadas...).

### Fase 3 — Presentar resumen y ESPERAR confirmación (parada obligatoria)
Antes de generar nada, se te presenta: los datos extraídos, los 5 hallazgos con fuente, los comparables y cómo se posiciona el precio, y cualquier bandera roja. **No se genera ningún archivo hasta que confirmes explícitamente** ("sí", "adelante", etc.).

### Fase 4 — Generar todo (solo tras tu confirmación)
Esto es lo que se crea, en orden, y dónde acaba cada cosa:

| Paso | Qué hace | Qué se crea/sube | Dónde |
|---|---|---|---|
| 1 | Redacta el copy del anuncio (título, descripción corta RRSS, descripción larga portal, hashtags) en lenguaje de servicio ("te lo importamos", nunca "vendo") | — | (se guarda dentro de la ficha) |
| 2 | `fill_template.py` rellena la plantilla Excel del coche | `Plantilla_Importacion_MARCA_MODELO_AAAA-MM.xlsx` | local, luego Drive |
| 3 | Recalcula el libro con LibreOffice (las fórmulas no se calculan solas al generarlo) | mismo archivo, ya con fórmulas resueltas | — |
| 4 | Sube el Excel ya recalculado | mismo archivo | Drive → "07 Vehículos (operaciones)" |
| 5 | `generate_summary_pdf.py` genera un resumen ejecutivo de una página (coste, honorarios, precio todo incluido, semáforo si el precio está por encima de mercado) | `Resumen_MARCA_MODELO_AAAA-MM.pdf` | local, luego Drive |
| 6 | `update_master_list.py inventario` añade una fila al inventario de coches en oferta | actualiza `Inventario_Coches_Oferta.xlsx` | Drive → "07 Vehículos (operaciones)" |
| 7 | `sync_web_data.py coche` traduce los datos al formato del panel y los fusiona (sin duplicar) por `id` | actualiza `coches.json` | Drive → "07 Vehículos (operaciones)" |
| 8 | Te entrego los enlaces de Drive (no archivos sueltos) | — | — |

En cuanto el paso 7 termina, **el coche ya aparece en el panel** — no hace falta hacer nada más ahí salvo, si el panel ya estaba abierto, pulsar "Recargar datos de Drive".

## 3. Flujo 2 — Cliente (demanda)

Se dispara cuando describes un cliente nuevo o pides opciones para uno existente ("tengo un cliente que busca...", "qué le proponemos a Laura").

1. **Ficha del cliente**: se rellena con lo que se sepa (nombre, contacto, cómo llegó, qué busca, presupuesto). No hace falta tenerlo todo — se completa después.
2. `fill_client_template.py` genera `Ficha_Cliente_NOMBRE.xlsx`, se recalcula y se sube a Drive → "06 CRM y clientes".
3. `update_master_list.py clientes` añade la fila a `Clientes.xlsx` en Drive.
4. `sync_web_data.py cliente` actualiza `clientes.json` en Drive → el cliente ya aparece en el panel.
5. Se buscan coincidencias en `Inventario_Coches_Oferta.xlsx` según lo que busca (presupuesto, modelo, km, año) y se te presentan para que decidas si proponérselas — la skill nunca envía nada directamente al cliente, solo prepara la información.
6. Si decides proponer un coche concreto, se anota en la ficha del cliente y se refleja en `clientes.json` (campo `cochesPropuestos`).
7. Cuando el cliente **elige** un coche: se actualiza su ficha, el coche pasa a "Reservado" en el inventario, el cliente pasa a "Operación en curso", y la operación sigue el flujo normal hacia el Registro de operaciones. Se sincronizan `coches.json` (estado, `clienteId`) y `clientes.json` (estado, `cocheElegido`).

## 4. El panel web — qué hace y qué no hace

**Sí hace:**
- Lee `coches.json` / `clientes.json` / `contactos.json` de Drive al abrir, y con el botón "🔄 Recargar datos de Drive" en cualquier momento.
- Permite editar estado, honorarios, checklist de fase, gastos reales vs. estimados, notas — todo se guarda de vuelta en Drive al momento (protocolo: descarga la versión más reciente, fusiona tu cambio, resube).
- Vistas: Lista/Tabla, Kanban (arrastrar tarjetas entre estados), Mapa (distancia desde Madrid), Finanzas (honorarios previstos/cobrados, desviación de gastos reales).
- Calculadora de IEDMT sugerido, honorarios por tramo, transporte por distancia.
- Plantillas de mensaje (ES/EN/DE, profesional/distendido) para contactar vendedor o cliente, listas para copiar.
- Alta rápida de coche/cliente/contacto directamente desde el panel, y "Verificar coche" (pegas texto de un anuncio y una IA ligera extrae los campos — sin investigación real).

**No hace:**
- No investiga fiabilidad, recalls ni precio de mercado — eso siempre se pide en el chat (Fase 2 de arriba).
- No navega webs ni tiene el copy final de un anuncio con datos verificados — solo campos sueltos que tú revisas.

## 5. Archivos en la carpeta del escritorio — qué es cada uno

Estado a día de hoy, para decidir qué conservar:

| Archivo | Qué es | ¿Sigue en uso? |
|---|---|---|
| `Clientes.xlsx`, `Inventario_Coches_Oferta.xlsx`, `Registro_Operaciones_Importacion.xlsx` | Copias locales antiguas (23/07) de las listas maestras | La versión real y actualizada vive en Drive; estas copias de escritorio están desactualizadas |
| `importacion-vehiculos.skill` | Paquete exportado de la skill | Ya está instalada como skill del chat; este .skill es una copia de respaldo |
| `JJ_Panel_Coches.html` | Versión muy temprana del panel (antes de que fuera un artifact de Cowork) | Superada por el panel actual (`jj-panel-operaciones`) |
| `JJ_Centro_Operaciones.html` | El panel de navegador independiente, descartado (ver sección 1) | Descartado según decisión ya tomada |
| `Plan_Mejoras_JJ_Import_Motors.md`, `Plan_Mejoras_JJ_Centro_Operaciones.md`, `Plan_Rediseno_Sistema.md`, `Prompt_Rediseno_Sistema.md` | Planes de mejora para `JJ_Centro_Operaciones.html`, ya completados o atados a un archivo descartado | Ligados a un archivo que ya no se usa |
| `Plan_Migracion_Laravel.md` | Plan para una posible migración a Laravel | Sin relación con el sistema actual (Drive + panel), revisar si sigue vigente |
| `07 Vehículos (para subir a Drive)/` | Carpeta temporal con el Excel y PDF del Mercedes CLA, para subida manual | El PDF ya está en Drive; el Excel seguía pendiente de subida manual |

Este análisis alimenta la propuesta de limpieza que te paso a continuación en el chat.
