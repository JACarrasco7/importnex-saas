# Deploy Guía Rápida — Importnex SaaS

## 🚀 Sistema DESPLEGADO y funcional

**URL:** https://dev.aktive.cloud/importnexcore
**Estado:** ✅ ONLINE - Listo para uso

---

## 🔑 Acceso directo

| Cosa | Credenciales |
|------|--------------|
| **URL** | https://dev.aktive.cloud/importnexcore |
| **Login** | carra@admin.com |
| **Password** | demo1234 |
| **Organización** | JJ Import Motors |
| **Plan** | Pro (trial activo) |

---

## 🔗 Enlaces útiles

| Función | URL |
|---------|-----|
| Dashboard | /dashboard |
| Coches (lista) | /cars |
| Kanban | /cars-kanban |
| Mapa | /cars-map |
| Finanzas | /finance |
| Clientes | /clients |
| Contactos | /contacts |
| Suscripciones | /subscriptions |
| Billing | /billing |

---

## 🖥️ Comandos de desarrollo

### Acceso SSH
```bash
ssh -i "C:\Users\jacar\.ssh\id_ed25519_nopass" root@dev.aktive.cloud
```

### Directorio del proyecto
```bash
cd /var/www/importnex-saas
```

### Desplegar cambios desde local
```bash
# Copiar archivos modificados
scp -i "C:\Users\jacar\.ssh\id_ed25519_nopass" archivo.php root@dev.aktive.cloud:/var/www/importnex-saas/path/

# Limpiar cachés
ssh -i "C:\Users\jacar\.ssh\id_ed25519_nopass" root@dev.aktive.cloud "cd /var/www/importnex-saas; php artisan route:clear && php artisan config:clear && php artisan cache:clear"

# Rebuild frontend
ssh -i "C:\Users\jacar\.ssh\id_ed25519_nopass" root@dev.aktive.cloud "cd /var/www/importnex-saas; npm run build"
```

### Verificar logs
```bash
# Logs de aplicación
tail -f /var/www/importnex-saas/storage/logs/laravel.log

# Logs de Apache
tail -f /var/log/apache2/error.log

# Logs de MySQL
tail -f /var/log/mysql/error.log
```

### Base de datos
```bash
# Acceso directo
mysql -uimportnex -p'Importnex2026Saas#' importnex_saas

# Query rápida
mysql -uimportnex -p'Importnex2026Saas#' importnex_saas -e "SELECT COUNT(*) FROM cars;"
```

---

## 🔧 Configuración del servidor

### Stack técnico
- **PHP:** 8.3 con Composer 2.8.4
- **MySQL:** 8.0.46
- **Web server:** Apache con PHP-FPM
- **Cache:** Redis Server
- **Frontend:** Node.js 20 + Vite

### Configuración Apache (subpath)
**Archivo:** `/etc/apache2/sites-enabled/000-default-le-ssl.conf`

```apache
Alias /importnexcore /var/www/importnex-saas/public

<Directory /var/www/importnex-saas/public>
    Options +FollowSymLinks
    AllowOverride All
    Require all granted

    RewriteEngine On
    RewriteBase /importnexcore
    RewriteRule ^index\.php$ - [L]
    RewriteCond %{REQUEST_FILENAME} !-f
    RewriteCond %{REQUEST_FILENAME} !-d
    RewriteRule . /importnexcore/index.php [L]
</Directory>
```

### Variables de entorno clave
```bash
APP_URL=https://dev.aktive.cloud/importnexcore
APP_NAME=Importnex
APP_KEY=base64:TeBtlinZCiVikQxDfcel0eAkBMURmZpXu7br2v/yAoE=
DB_HOST=127.0.0.1
DB_DATABASE=importnex_saas
DB_USERNAME=importnex
DB_PASSWORD="Importnex2026Saas#"
```

---

## ✅ Funcionalidades verificadas

| Función | Estado | Notas |
|---------|--------|-------|
| Login | ✅ | carra@admin.com / demo1234 |
| Dashboard | ✅ | KPIs funcionan |
| CRUD coches | ✅ | 11 estados válidos |
| Kanban | ✅ | 8 columnas |
| Mapa Leaflet | ✅ | Iconos cargan |
| Dashboard | ✅ | Sin errores 404 |
| Auth | ✅ | Sesiones funcionan |
| Multi-tenancy | ✅ | Global scopes activos |
| Deploy | ✅ | Subpath configurado |

---

## 🐛 Troubleshooting

### Problema: 500 error en login
**Solución:** Verificar que `.env` existe y tiene `APP_KEY` correcto
```bash
cd /var/www/importnex-saas
php artisan config:clear
php artisan cache:clear
```

### Problema: Iconos de mapa 404
**Solución:** Copiar iconos de Leaflet
```bash
cd /var/www/importnex-saas
cp node_modules/leaflet/dist/images/*.png public/build/assets/
```

### Problema: URLs sin /importnexcore
**Solución:** Verificar middleware ForceRootUrl
```bash
# En resources/js/Components/MapaLeaflet.vue
# Asegurar rutas con prefijo /importnexcore
iconUrl: '/importnexcore/build/assets/marker-icon.png'
```

### Problema: Caché stale
**Solución:** Limpiar todos los cachés
```bash
php artisan route:clear
php artisan config:clear
php artisan cache:clear
php artisan view:clear
```

---

## 📋 Checklist para commits

Antes de hacer `git commit`:
- [ ] Tests pasan localmente (`php artisan test`)
- [ ] No hay errores de linting
- [ ] Cambios probados en dev
- [ ] Logs de application revisados
- [ ] README actualizado si necesario

Después de deploy:
- [ ] Verificar que la app funciona
- [ ] Probar login con credenciales demo
- [ ] Revisar logs en producción
- [ ] Documentar cambios en PROGRESO.md

---

## 📚 Documentación relacionada

- **[PROGRESO.md](PROGRESO.md)** - Estado del proyecto y deployment
- **[LAUNCH.md](LAUNCH.md)** - Checklist producción completo
- **[PLAN_IMPLEMENTACION_COMPLETO.md](PLAN_IMPLEMENTACION_COMPLETO.md)** - Plan original
- **[inconsistencias-corregidas.md](../memories/repo/inconsistencias-corregidas.md)** - Auditorías

---

**Última actualización:** 30 julio 2026
**Estado:** Sistema funcional y listo para pruebas
