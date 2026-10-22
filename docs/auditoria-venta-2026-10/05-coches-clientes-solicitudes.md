# 05 · Coches, clientes y solicitudes

**Estado:** 🟢

| Comprobación | Resultado |
| --- | --- |
| Aislamiento por empresa (coches, clientes, contactos, fotos, documentos, gastos) | ✅ scope global |
| Solicitudes: listado, detalle, cambio de estado, borrado | ✅ filtro + `authorize()` por empresa |
| Asignar solicitud a coche | ✅ comprueba que coche y solicitud son de la misma empresa |
| Límites de plan al crear coches, clientes y contactos | ✅ `plan.limit` |
| Páginas Vue sin usar | ✅ ninguna (las 14 «sueltas» son parciales importados) |
| `console.log` en el frontend | ✅ 0 |
| Tests de la suite | ✅ en verde |

| ID | Prio | Hallazgo | Estado |
| --- | --- | --- | --- |
| 5.3 | P1 | El estado del coche y el del encargo podían divergir: el kanban y la edición cambiaban el estado de un coche con encargo en curso (el cliente veía una cosa en el portal y el equipo otra) | ✅ Con encargo activo, el estado se cambia desde el encargo · test `FlowIntegrityTest` |
| 5.4 | P2 | La tabla `cars` tiene 82 columnas (ficha, investigación, IA, marketplace) | Aparcado |
| 5.5 | P2 | El comentario de «Publicar en marketplace» dice que exige estado `Delivered`, pero el código excluye precisamente `Delivered` | Corregir el comentario |
| 5.1 | P2 | Textos de interfaz que mezclan español e inglés en algunas pantallas antiguas | Aparcado: revisar al traducir a inglés |
| 5.2 | P2 | Ficha del coche muy grande (`Cars/Show.vue`) | Aparcado: funciona; refactor solo si se amplía |
