# Deploy — ImportnexCore

## Servidor

| Dato | Valor |
|---|---|
| URL | https://dev.aktive.cloud/importnexcore |
| Servidor | VPS dev.aktive.cloud |
| Directorio | `/var/www/importnex-saas` |
| Stack | PHP 8.3, MySQL 8.0, Apache, Redis |
| Entorno | DEV |

## Desplegar cambios

```powershell
# Subir archivos PHP al servidor
scp -i "~\.ssh\id_ed25519_nopass" archivo.php root@dev.aktive.cloud:/var/www/importnex-saas/ruta/

# Regenerar autoload (si hay clases nuevas)
ssh root@dev.aktive.cloud "cd /var/www/importnex-saas; composer dump-autoload"

# Limpiar caché
ssh root@dev.aktive.cloud "cd /var/www/importnex-saas; php artisan optimize:clear; php -r 'opcache_reset();'; apachectl graceful"

# Build frontend (si cambian archivos Vue/JS)
ssh root@dev.aktive.cloud "cd /var/www/importnex-saas; npm run build"
```

## Archivos clave

| Archivo | Rol |
|---|---|
| `app/Services/ValuationImporter.php` | Importa JSON → coche en BBDD |
| `app/Services/ValuationPackageIngestor.php` | Importa ZIP → coche + docs + fotos |
| `app/Http/Controllers/ValuationImportController.php` | Controlador web (formulario) |
| `app/Http/Controllers/Api/ImportValuationApiController.php` | Endpoint API (token) |
| `resources/js/Pages/Cars/ImportValuation.vue` | Frontend (pestañas pegar/subir/zip/server) |

## Scripts

| Script | Uso |
|---|---|
| `subir-informe.ps1` | Subir JSONs desde PowerShell |
| `subir-informe.bat` | Arrastrar JSON para subir |
| `scripts/setup-server.php` | Validar entorno en servidor |
