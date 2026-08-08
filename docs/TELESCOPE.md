# Laravel Telescope — Observability & Debugging

## Qué es Telescope

Laravel Telescope es un debug assistant para Laravel que provee:
- **Request monitoring**: ver todas las requests HTTP entrantes
- **Query logging**: queries de base de datos con análisis de performance
- **Command logging**: comandos Artisan ejecutados
- **Schedule logging**: jobs scheduleados y su ejecución
- **Exception tracking**: errores y exceptions con stack trace
- **Log viewing**: logs de aplicación en tiempo real
- **Mail tracking**: emails enviados (preview del contenido)
- **Notifications tracking**: notificaciones enviadas
- **Dump debugging**: mejor visualización de `dump()`/`dd()`

## Acceso y Seguridad

**CRÍTICO**: Telescope está **deshabilitado en producción** por seguridad.

### Reglas de acceso

1. **Solo Super Admin** puede acceder a `/telescope`
2. **Solo en non-production** (local, staging, testing)
3. En **production**, Telescope está completamente deshabilitado

### Middleware: `TelescopeAccess`

Ubicación: `app/Http/Middleware/TelescopeAccess.php`

Lógica:
```php
if (app()->environment('production')) {
    abort(403); // Siempre bloquea
}

if (! auth()->check()) {
    abort(401); // Requiere auth
}

if (auth()->user()->role !== 'Super Admin') {
    abort(403); // Solo Super Admin
}
```

### Configuración

Archivo: `config/telescope.php`

```php
'enabled' => env('TELESCOPE_ENABLED', env('APP_ENV', 'production') === 'production' ? false : true),
```

- Por defecto: `true` en local/staging, `false` en production
- Puede sobrescribirse con `TELESCOPE_ENABLED` en `.env`

## Uso en Desarrollo

### Acceso a Telescope

1. Loggear como Super Admin
2. Ir a `/telescope` en tu navegador
3. Ver el dashboard con todos los watches activos

### Watches activos

Telescope registra automáticamente:
- Requests (web y API)
- Queries (DB)
- Commands (Artisan)
- Jobs/Queues
- Exceptions
- Logs
- Mails
- Notifications
- Views
- Events

### Performance

Telescope puede impactar performance si no se configura bien:

**Recomendaciones:**
- Limitar el número de entries en DB (ver `storage` config)
- Usar `telescope:clear` periódicamente
- Deshabilitar watches no usados en `config/telescope.php`

### Commands útiles

```bash
# Limpiar todas las entries de Telescope
php artisan telescope:clear

# Publicar Telescope assets (ya hecho en setup)
php artisan telescope:install

# Ver si Telescope está activo
php artisan tinker
>>> config('telescope.enabled');
```

## CI/CD Integration

### GitHub Actions

El workflow `.github/workflows/ci.yml` ahora usa **SQLite** (más rápido) en lugar de MariaDB:

```yaml
tests:
  runs-on: ubuntu-latest
  # Eliminado servicio MariaDB (innecesario con SQLite)
  steps:
    - name: Prepare environment
      run: |
        cp .env.example .env
        echo "DB_CONNECTION=sqlite" >> .env
        echo "DB_DATABASE=:memory:" >> .env
```

**Mejoras:**
- Faster tests (no container DB overhead)
- Less flaky tests (no race conditions)
- Cost reduction en GitHub Actions

## Tests

### Tests de Telescope

Ubicación: `tests/Feature/TelescopeAccessTest.php`

Tests:
1. `telescope_config_exists` — Verifica que la config existe
2. `telescope_middleware_class_exists` — Verifica el middleware
3. `telescope_middleware_production_block` — Verifica lógica de environment
4. `telescope_middleware_checks_user_role` — Verifica roles de usuarios
5. `super_admin_role_exists` — Verifica que el role existe

### Ejecutar tests

```bash
# Solo tests de Telescope
php artisan test --filter "TelescopeAccess" --compact

# Tests completos
php artisan test --compact
```

## Deployment

### Production

**NO hacer nada** — Telescope está deshabilitado automáticamente.

### Staging/Development

1. Asegurar que el Super Admin tiene acceso al dashboard
2. Configurar `TELESCOPE_ENABLED=true` en `.env` si necesario
3. Considerar límites de retention en DB

## Troubleshooting

### Telescope no se muestra

**Problema**: `/telescope` devuelve 403 o 404

**Soluciones**:
1. Verificar que estás loggeado como Super Admin
2. Verificar que no estás en production: `php artisan env`
3. Verificar config: `php artisan config:show telescope.enabled`
4. Limpiar cache: `php artisan config:clear`

### Telescope no registra queries

**Problema**: No se ven queries en el dashboard

**Solución**:
1. Verificar que los watches están activos en `config/telescope.php`
2. Limpiar cache: `php artisan telescope:clear`
3. Revisar que no estás en production

### Telescope lento

**Problema**: Dashboard tarda en cargar

**Soluciones**:
1. Limpiar entries viejas: `php artisan telescope:clear`
2. Limitar storage en config: `chunk` y `trim` settings
3. Deshabilitar watches no usados

## Security Best Practices

1. **Nunca habilitar Telescope en producción** sin razón crítica
2. **Solo Super Admin** debe tener acceso
3. **Limpiar datos sensibles** del dashboard periódicamente
4. **Usar HTTPS** en staging para proteger el dashboard
5. **Limitar retention** de datos (no guardar todo indefinidamente)

## Referencias

- [Laravel Telescope Documentation](https://laravel.com/docs/telescope)
- [Config file](../config/telescope.php)
- [Middleware](../app/Http/Middleware/TelescopeAccess.php)
- [Tests](../tests/Feature/TelescopeAccessTest.php)

## Future Improvements

- [ ] Add `telescope.access` permission (role + permission system)
- [ ] Configure automatic trimming of old entries
- [ ] Add alerts for critical exceptions
- [ ] Integrate with monitoring tools (Sentry, etc.)