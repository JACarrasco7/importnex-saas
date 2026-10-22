# 02 · Aislamiento entre empresas y seguridad

**Estado:** 🟢 P0 resueltos

Revisión hecha modelo a modelo: los que tienen `organization_id` y el modo en que
cada controlador los filtra (scope global, filtro explícito o comprobación
`abort_unless` al recibir el modelo por la ruta).

| Modelo | Cómo se aísla | Estado |
| --- | --- | --- |
| Car, Client, Contact, CarPhoto, CarDocument, CarExpense, CarChecklist, ClientContactLog | Scope global por organización | ✅ |
| CarRequest, Alert, Cierre, MarketModel, MarketLead, ScoutingMercado, InvestigationCache | Filtro explícito + comprobación en show/update/destroy | ✅ |
| ImportOrder y tablas del encargo | `AuthorizesImportOrder` + hijos validados contra el encargo | ✅ |
| CarMarketingContent | Por el coche | ✅ estadísticas corregidas (P0-5) |
| MessageTemplate | Global (plantillas del sistema, solo lectura) | P2: plantillas por empresa |
| MarketModel/MarketLead públicos (`organization_id` nulo) | Catálogo compartido de la plataforma, intencionado | ✅ |

## Hallazgos

| ID | Prio | Hallazgo | Estado |
| --- | --- | --- | --- |
| P0-3 | P0 | Token de API global: con `?org=` se accedía a cualquier empresa | ✅ Token por empresa (hash SHA-256, se muestra una vez, rotable y revocable) |
| P0-4 | P0 | Importar valoración desde el panel escribía en la empresa «JJ Import Motors» | ✅ Empresa del usuario |
| P0-5 | P0 | Estadísticas de Marketing de todas las empresas | ✅ |
| P0-7 | P0 | Contraseñas reales en `DatabaseSeeder` | ✅ Eliminadas. **Rotar en producción** (siguen en el historial de git) |
| P0-10 | P0 | Un contacto se podía vincular a un cliente de otra empresa (`exists:clients,id` sin filtrar por organización) | ✅ Validación por empresa · test |
| P0-13 | P0 | No hay condiciones del servicio, política de privacidad de la plataforma ni contrato de encargado del tratamiento (art. 28 RGPD). Las empresas guardan DNI, teléfonos y emails de sus clientes | ⏳ Abogado. Después: página `/condiciones` y casilla de aceptación en el alta (S) |
| 2.8 | P1 | Documentos del coche (contratos, facturas, permisos con datos personales) en el disco **público** (`storage/cars/{id}/documents/…`). El nombre es aleatorio, pero quien tenga el enlace los abre sin iniciar sesión | Pendiente (M): disco privado + comando que mueva los existentes |
| 2.9 | P1 | No se puede borrar una empresa ni exportar sus datos (derecho de supresión/portabilidad) | Pendiente (M), junto con 1.14 |
| 2.5 | P1 | La portada pública enviaba la versión de Laravel y PHP al navegador | ✅ Quitado |
| 2.10 | P2 | Tablas sin clave foránea a `organizations` aunque tienen la columna: `car_documents`, `car_photos` (se borran en cascada por el coche) | Aparcado |
| 2.11 | P2 | La regla del repo dice «no usar scopes globales» y 8 modelos los usan. Funcionan (en consola no hay usuario y se ven todas las empresas, que es lo que necesitan los comandos), pero la regla y el código se contradicen | Actualizar la regla |
| 2.6 | P2 | Sin políticas (`Policies`) de Laravel: la autorización está repartida en controladores | Aparcado: funciona y está cubierto por tests |
| 2.7 | P2 | Telescope solo para rol «Super Admin» que no existe en el alta | Aparcado (desactivado en producción) |
