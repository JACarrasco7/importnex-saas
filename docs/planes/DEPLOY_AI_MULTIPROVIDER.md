# Deploy — IA multi-proveedor + chat flotante (31/07/2026)

## Resumen

Cada organización puede elegir su proveedor de IA, su clave API y opcionalmente su modelo. Funciona en:

- **Verificación de coches** (Claude → IA configurable)
- **Scraping de URLs** (mobile.de, autoscout24, etc.) → IA configurable
- **Chat IA flotante** (esquina inferior derecha) → IA configurable

Proveedores soportados: Anthropic, OpenAI, Mistral, Google Gemini, DeepSeek, MiniMax (M3), GLM (Z.AI).

## Pasos en el VPS (dev.aktive.cloud)

### 1. Subir archivos

```bash
scp -i "C:\Users\jacar\.ssh\id_ed25519_nopass" -r \
    c:\laragon\www\importnexcore\app\Services\Ai \
    root@dev.aktive.cloud:/var/www/importnex-saas/app/Services/

scp -i "C:\Users\jacar\.ssh\id_ed25519_nopass" \
    c:\laragon\www\importnexcore\app\Providers\AiServiceProvider.php \
    root@dev.aktive.cloud:/var/www/importnex-saas/app/Providers/

scp -i "C:\Users\jacar\.ssh\id_ed25519_nopass" \
    c:\laragon\www\importnexcore\app\Http\Controllers\AiChatController.php \
    root@dev.aktive.cloud:/var/www/importnex-saas/app/Http/Controllers/

scp -i "C:\Users\jacar\.ssh\id_ed25519_nopass" \
    c:\laragon\www\importnexcore\app\Http\Middleware\HandleInertiaRequests.php \
    root@dev.aktive.cloud:/var/www/importnex-saas/app/Http/Middleware/

scp -i "C:\Users\jacar\.ssh\id_ed25519_nopass" \
    c:\laragon\www\importnexcore\app\Models\Organization.php \
    root@dev.aktive.cloud:/var/www/importnex-saas/app/Models/

scp -i "C:\Users\jacar\.ssh\id_ed25519_nopass" \
    c:\laragon\www\importnexcore\app\Http\Controllers\OrganizationController.php \
    root@dev.aktive.cloud:/var/www/importnex-saas/app/Http/Controllers/

scp -i "C:\Users\jacar\.ssh\id_ed25519_nopass" \
    c:\laragon\www\importnexcore\app\Http\Controllers\CarController.php \
    root@dev.aktive.cloud:/var/www/importnex-saas/app/Http/Controllers/

scp -i "C:\Users\jacar\.ssh\id_ed25519_nopass" \
    c:\laragon\www\importnexcore\app\Services\CarVerificationService.php \
    root@dev.aktive.cloud:/var/www/importnex-saas/app/Services/

# Borra los viejos extractores del VPS
ssh -i "C:\Users\jacar\.ssh\id_ed25519_nopass" root@dev.aktive.cloud \
    "cd /var/www/importnex-saas/app/Services/Scraping && rm -f MistralExtractor.php MiniMaxExtractor.php GlmExtractor.php"

# Sube el nuevo
scp -i "C:\Users\jacar\.ssh\id_ed25519_nopass" \
    c:\laragon\www\importnexcore\app\Services\Scraping\CarScrapingService.php \
    root@dev.aktive.cloud:/var/www/importnex-saas/app/Services/Scraping/

scp -i "C:\Users\jacar\.ssh\id_ed25519_nopass" \
    c:\laragon\www\importnexcore\app\Services\Scraping\AiExtractorInterface.php \
    root@dev.aktive.cloud:/var/www/importnex-saas/app/Services/Scraping/

scp -i "C:\Users\jacar\.ssh\id_ed25519_nopass" \
    c:\laragon\www\importnexcore\app\Services\Scraping\GenericAiExtractor.php \
    root@dev.aktive.cloud:/var/www/importnex-saas/app/Services/Scraping/

# Migration + factories
scp -i "C:\Users\jacar\.ssh\id_ed25519_nopass" \
    c:\laragon\www\importnexcore\database\migrations\2026_07_31_000001_add_ai_settings_to_organizations.php \
    root@dev.aktive.cloud:/var/www/importnex-saas/database/migrations/

scp -i "C:\Users\jacar\.ssh\id_ed25519_nopass" \
    c:\laragon\www\importnexcore\database\factories\OrganizationFactory.php \
    root@dev.aktive.cloud:/var/www/importnex-saas/database/factories/

# Providers + bootstrap
scp -i "C:\Users\jacar\.ssh\id_ed25519_nopass" \
    c:\laragon\www\importnexcore\bootstrap\providers.php \
    root@dev.aktive.cloud:/var/www/importnex-saas/bootstrap/

# Routes
scp -i "C:\Users\jacar\.ssh\id_ed25519_nopass" \
    c:\laragon\www\importnexcore\routes\web.php \
    root@dev.aktive.cloud:/var/www/importnex-saas/routes/

# Frontend (Organization/Edit.vue, AIChatWidget.vue, Ai/Chat.vue, AuthLayout, lang)
scp -i "C:\Users\jacar\.ssh\id_ed25519_nopass" -r \
    c:\laragon\www\importnexcore\resources\js\Components\AIChatWidget.vue \
    root@dev.aktive.cloud:/var/www/importnex-saas/resources/js/Components/

scp -i "C:\Users\jacar\.ssh\id_ed25519_nopass" -r \
    c:\laragon\www\importnexcore\resources\js\Pages\Organization\Edit.vue \
    root@dev.aktive.cloud:/var/www/importnex-saas/resources/js/Pages/Organization/

scp -i "C:\Users\jacar\.ssh\id_ed25519_nopass" -r \
    c:\laragon\www\importnex-saas\resources\js\Pages\Ai \
    root@dev.aktive.cloud:/var/www/importnex-saas/resources/js/Pages/
# (recuerda el mkdir primero si Ai/ no existe)

scp -i "C:\Users\jacar\.ssh\id_ed25519_nopass" \
    c:\laragon\www\importnexcore\resources\js\Layouts\AuthenticatedLayout.vue \
    root@dev.aktive.cloud:/var/www/importnex-saas/resources/js/Layouts/

scp -i "C:\Users\jacar\.ssh\id_ed25519_nopass" \
    c:\laragon\www\importnexcore\resources\lang\en\nav.php \
    root@dev.aktive.cloud:/var/www/importnex-saas/resources/lang/en/

scp -i "C:\Users\jacar\.ssh\id_ed25519_nopass" \
    c:\laragon\www\importnexcore\resources\lang\es\nav.php \
    root@dev.aktive.cloud:/var/www/importnex-saas/resources/lang/es/
```

### 2. Ejecutar migration

```bash
ssh -i "C:\Users\jacar\.ssh\id_ed25519_nopass" root@dev.aktive.cloud \
    "cd /var/www/importnex-saas && php artisan migrate --force"
```

Comprueba que añade columnas:

```sql
SHOW COLUMNS FROM organizations LIKE 'ai_%';
-- ai_provider, ai_model, ai_api_key
```

### 3. Limpiar cachés

```bash
ssh -i "C:\Users\jacar\.ssh\id_ed25519_nopass" root@dev.aktive.cloud \
    "cd /var/www/importnex-saas && php artisan route:clear && php artisan config:clear && php artisan cache:clear && php artisan view:clear"
```

### 4. Build assets (manual del usuario)

```bash
ssh -i "C:\Users\jacar\.ssh\id_ed25519_nopass" root@dev.aktive.cloud \
    "cd /var/www/importnex-saas && npm run build"
```

### 5. Smoke test

1. Login con `carra@admin.com`
2. Ir a **Organización → Edit** → debe aparecer el selector de IA con las 7 opciones
3. Seleccionar **Anthropic** → pegar `sk-ant-...` → Save
4. En cualquier página debe aparecer el **botón flotante** con icono `Sparkles` en la esquina inferior derecha
5. Pulsarlo → escribir → enviar → debe responder Claude

## Rollback

Si algo falla:

1. `cd /var/www/importnex-saas && git checkout HEAD~1 -- app/ database/ resources/`
2. `php artisan migrate:rollback --step=1` (deshace ai_*)
3. `php artisan route:clear && php artisan config:clear && cache:clear`
4. `npm run build`

## SQL manual alternativo (si la migration da problemas)

```sql
ALTER TABLE organizations
    ADD COLUMN ai_provider VARCHAR(32) NULL AFTER subscribed_at,
    ADD COLUMN ai_model VARCHAR(128) NULL AFTER ai_provider,
    ADD COLUMN ai_api_key TEXT NULL AFTER ai_model;
```
