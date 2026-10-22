# 03 · Marca propia de cada empresa

**Estado:** 🟡 P0 resuelto, quedan P1 menores

Todo lo que ve el cliente final sale ahora de `App\Support\Brand::for($organization)`,
que lee el perfil de la empresa (Ajustes → Datos de empresa: razón social, CIF,
dirección, ciudad, teléfonos, WhatsApp, email, web, IBAN, honorarios, logo).
En Vue: `useBrand()` y las sustituciones `:company`, `:city`, `:brand_phone`,
`:app_name` en las traducciones.

| Superficie | Antes | Ahora |
| --- | --- | --- |
| Contrato (texto, PDF, cabecera) | «JJ Import Motors», CIF por `.env`, «Juzgados de Huelva» | Datos de la empresa congelados en el contrato al emitirlo |
| Portal del cliente y emails del encargo | Marca y contacto fijos | Marca, contacto e IBAN de la empresa |
| Web pública, marketplace, dossier `/c/{token}` | Teléfono, email y «Puesto en Huelva» fijos | De la empresa del coche |
| Fichas, folleto e informe interno (PDF) | Fijos | De la empresa del coche; logo de la empresa |
| Aviso legal | De JJ | Por empresa (`/legal?org=slug`), con CIF y domicilio (LSSI) |
| Emails del SaaS (bienvenida, prueba, pagos) | «JJ Import Motors» | Nombre del producto (`APP_NAME`) |

La migración `2026_10_22_100000` copia los datos de JJ a su organización para que
su web y sus contratos sigan igual tras desplegar.

## Pendiente

| ID | Prio | Hallazgo |
| --- | --- | --- |
| 3.1 | P1 | El folleto PDF institucional es un fichero estático de JJ (`public/jj-import-folleto.pdf`): solo se muestra a la organización operadora. Generarlo por empresa |
| 3.2 | P1 | Colores de marca fijos (paleta «estoril»); el plan Enterprise promete `custom_branding` |
| 3.3 | P2 | Textos de cálculo «Alemania → España» y tablas de costes de importación fijos (`config/importacion.php`): configurables por empresa |
| 3.4 | P2 | Rutas y vistas internas con nombre `jj-import` (no visible para el cliente) |
