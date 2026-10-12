# Deploy — ImportnexCore

## Servidor (Forge)

| Dato | Valor |
|---|---|
| URL | https://jjimportmotors.on-forge.com |
| Servidor | Laravel Forge |
| IP | 168.144.6.105 |
| SSH user | forge |
| Directorio | `/home/forge/jjimportmotors.on-forge.com/current` |
| Stack | PHP, MySQL, Nginx (gestionado por Forge) |
| Entorno | PRODUCCIÓN |

## Documentación de esta carpeta

| Doc | Qué |
|---|---|
| [`HEIDISQL_FORGE.md`](HEIDISQL_FORGE.md) | Conexión HeidiSQL vía SSH tunnel (puerto local 3307) |
| [`DEPLOY_AI_MULTIPROVIDER.md`](DEPLOY_AI_MULTIPROVIDER.md) | Deploy de IA multi-proveedor + chat flotante (31-jul-2026) |

## Cómo desplegar

Forge despliega automáticamente desde GitHub al hacer push a **`master`**:

```powershell
git add .
git commit -m "descripcion de cambios"
git push origin master
```

> ⚠️ **La rama de deploy es `master`, no `main`.** Verificado por SSH el 25-sep-2026:
> `cd /home/forge/jjimportmotors.on-forge.com/current && git rev-parse --abbrev-ref HEAD` → `master`.
> La rama local `main` está **187 commits por detrás** y NO se despliega (residuo histórico).
> El hook `.git/hooks/post-commit` ya lo refleja: recuerda `git push origin master`.

Forge hace: pull → composer install → npm build → migraciones → symlink `current`.

## Despliegue manual (SSH)

```powershell
ssh -i "C:\Users\jacar\.ssh\id_ed25519_nopass" forge@168.144.6.105
cd /home/forge/jjimportmotors.on-forge.com/current
php artisan optimize:clear
npm run build
```

## Acceso MySQL (túnel)

Usa `forge-mysql-tunnel.bat` para abrir un túnel a HeidiSQL (puerto local 3307).

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
