# Deploy Guía Rápida — Importnex SaaS

## 🚀 Sistema DESPLEGADO y funcional

**URL:** https://jjimportmotors.on-forge.com
**Estado:** ✅ ONLINE - Listo para uso

---

## 🔑 Acceso directo

| Cosa | Credenciales |
|------|--------------|
| **URL** | https://jjimportmotors.on-forge.com |
| **Servidor** | Laravel Forge (IP 168.144.6.105, user forge) |
| **Organización** | JJ Import Motors |
| **Plan** | Pro (trial activo) |

---

## 🔗 Enlaces útiles

| Función | URL |
|---------|-----|
| Marketplace público | /marketplace |
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

## 🖥️ Despliegue (Forge)

Forge despliega automáticamente desde GitHub al hacer push a `main`:

```bash
git add .
git commit -m "descripcion"
git push origin main
```

### Despliegue manual (SSH)
```bash
ssh -i "C:\Users\jacar\.ssh\id_ed25519_nopass" forge@168.144.6.105
cd /home/forge/jjimportmotors.on-forge.com/current
php artisan optimize:clear
npm run build
```

### Acceso MySQL
Usa `forge-mysql-tunnel.bat` para abrir túnel a HeidiSQL (puerto local 3307).

### Verificar logs
```bash
# Logs de aplicación
tail -f /home/forge/jjimportmotors.on-forge.com/current/storage/logs/laravel.log

# Logs de Nginx
tail -f /var/log/nginx/error.log
```

### Variables de entorno clave
Gestionadas en el panel de Laravel Forge (Sitio → Environment). Las credenciales de base de datos se generan al crear el sitio.

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
