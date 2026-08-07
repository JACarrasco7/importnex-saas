<!-- filepath: docs/GUIA_NOTIFICACIONES_2026-08-07.md -->
# 🔔 Guía de Notificaciones — ImportnexCore

> Estado: **Session I completa** (N1-N8 implementados).
> Commit actual: `1c64c5c`. Branch: `master`.

---

## 🎯 Resumen ejecutivo

El sistema de notificaciones de ImportnexCore tiene **5 canales de entrega** y **3 mecanismos de control**:

| Canal | Mecanismo | Cómo se activa | Coste |
|---|---|---|---|
| **In-app badge + toasts** | Polling 30s `/alerts/pending.json` | Siempre activo si hay sesión | Cero |
| **In-app inbox** | `/alerts` con filtros + chips | Siempre activo si hay sesión | Cero |
| **Email digest semanal** | `alerts:send-weekly-digest` (lunes 9 AM) | Auto vía schedule | Bajo (1 mail/semana) |
| **Webhook Slack/Discord/Teams** | HTTP POST al URL configurado | Auto al crear alerta si `notification_webhook_url` configurado | Cero |
| **Push notifications** | Web Push API (browser) | Opt-in del usuario via `Organization/Edit` | Bajo (requiere VAPID) |

---

## 🧭 Cómo navegar el sistema

### 1. Inbox de alertas (privado)

**Ruta:** `/alerts` (requiere auth + org)

**Funcionalidades:**
- **Filtros principales** (pestañas): `Pendientes` · `Pospuestas` · `Todas`
- **Filtros por tipo** (chips arriba): clickable, conteo por tipo
- **Agrupar por tipo** (toggle): agrupa alertas en acordeones colapsables
- **Marcar todas como leídas**: aparece solo en filtro Pendientes si hay > 0
- **Snooze** (botón con icono reloj): 1h · 3h · 24h · 3d · 7d
- **Reintentar verificación** (icono flecha): solo para `verification_failed`
- **Ver recurso** (icono ojo): link al recurso original
- **Silenciar tipo** (🔕 abajo): toggle inline por tipo

### 2. Configuración (Organization)

**Ruta:** `/organization/{id}/edit` (requiere ser owner)

**Sección Notificaciones (al final):**
- **Webhook URL**: pega aquí tu Incoming Webhook (Slack/Discord/Teams). Cifrado en BD.
- **Tipos de alerta**: switches individuales + bulk "Activar todo / Silenciar todo"
- **Notificaciones push en el navegador**: botón "Activar" → pide permiso → registra SW → listo

### 3. Sistema de preferencias (silent kill)

Tres formas de silenciar una alerta:
1. **Tipo completo** → `Organization/Edit > Notifications > switch del tipo` (afecta todos los canales)
2. **Alerta individual** → `/alerts` → botón 🔕 en la línea "Silenciar tipo"
3. **Webhook selective** → `notification_webhook_types` (array) filtra qué tipos llegan al webhook

---

## 🛠️ Implementación

### Arquitectura

```
Alert created (cualquier sitio)
         ↓
AlertObserver::created()
         ↓
         ├── N8: ¿silenciado? → STOP
         │
         ├── N7: webhookEnabledFor(type)?
         │       └── AlertWebhookDispatcher::dispatch() → HTTP POST
         │
         └── N6: PushNotificationDispatcher::dispatch() → log (hook)
                                  ↓ (futuro: minishlink/web-push)
                                  → VAPID-signed Web Push

In-app (independiente):
   Polling 30s → AlertController::pending() → JSON {count, recent}
                  ↓
            useNotificationPolling composable
                  ↓
            AuthenticatedLayout badge + NotificationToaster
```

### Tablas y columnas relevantes

| Tabla | Columna | Tipo | Notas |
|---|---|---|---|
| `alerts` | `snoozed_until` | timestamp nullable | Migration `2026_08_06_212339` |
| `organizations` | `notification_webhook_url` | text encrypted | Migration `2026_08_07_061955` |
| `organizations` | `notification_webhook_types` | json nullable | Whitelist para webhook |
| `organizations` | `notification_preferences` | json nullable | `{alert_type: bool}` |
| `push_subscriptions` | (nueva tabla) | — | Migration `2026_08_07_063229` |

### Endpoints backend

| Ruta | Método | Función |
|---|---|---|
| `/alerts` | GET | Inbox con filtros (pending/snoozed/all + type) |
| `/alerts/{alert}` | GET | Detalle de una alerta |
| `/alerts/{alert}/mark-resolved` | PATCH | Marcar resuelta |
| `/alerts/{alert}` | DELETE | Eliminar |
| `/alerts/pending.json` | GET | Polling (count + 5 recientes) |
| `/alerts/mark-all-read` | POST | Marca todas activas como resueltas |
| `/alerts/{alert}/snooze` | POST | Pospone `hours` (1-168) |
| `/alerts/{alert}/snooze` | DELETE | Quita posposición |
| `/alerts/preferences/{type}` | POST | Toggle de preferencia por tipo |
| `/push/subscribe` | POST | Registra suscripción Web Push |
| `/push/subscribe` | DELETE | Elimina suscripción |
| `/push/vapid-public-key` | GET | Devuelve clave pública VAPID (o `enabled:false`) |

### Componentes Vue clave

- `resources/js/Pages/Alerts/Index.vue` — inbox con todas las features N1-N4+N8
- `resources/js/Pages/Organization/Edit.vue` — configuración con secciones N7+N8+N6
- `resources/js/Composables/useNotificationPolling.js` — polling 30s
- `resources/js/Composables/usePushNotifications.js` — SW + subscribe
- `resources/js/Components/NotificationToaster.vue` — toasts in-app
- `public/sw.js` — service worker (cache + push handler)

---

## 🧪 Cómo probarlo paso a paso

### Smoke test completo (10 min)

```bash
# 1. Backend
ssh forge@jjimportmotors.on-forge.com
cd /home/forge/jjimportmotors.on-forge.com/current
php artisan migrate:status | head -10   # ver migrations aplicadas
php artisan schedule:list               # ver schedule activo
php artisan alerts:send-weekly-digest --dry-run

# 2. Frontend
curl -sI https://jjimportmotors.on-forge.com/sw.js | head -1  # 200
curl -sI https://jjimportmotors.on-forge.com/push/vapid-public-key | head -1  # 401 (auth required)

# 3. Logs
tail -f storage/logs/laravel.log | grep -i 'alert\|push\|webhook'
```

### Test manual UI

1. **Login** en https://jjimportmotors.on-forge.com/login
2. **Ir a /alerts** → ver inbox (si no hay alertas, ver `Alerts/Show` mockeando)
3. **Probar filtros**: click en chips de tipo, ver cómo cambia la lista
4. **Toggle agrupar**: ver acordeones
5. **Snooze una alerta**: click en icono reloj → elegir 1h → ver badge cambia a "pospuesta"
6. **/organization/{id}/edit**: sección Notificaciones
   - Pega una URL de webhook Slack de prueba (`https://webhook.site/{tu-uuid}`) → guardar
   - Toggle un switch de preferencia → guardar
7. **Push**: click "Activar" en push → el browser pide permiso → Aceptar → verificado

### Test programático de webhooks

```bash
# Local: usar webhook.site
# 1. Crear URL de prueba en https://webhook.site
# 2. Pegarla en Organization/Edit > Webhook URL
# 3. Generar una alerta:
php artisan tinker
> $org = Organization::first();
> Alert::create([
>   'organization_id' => $org->id,
>   'alert_type' => 'verification_failed',
>   'reference_type' => 'car',
>   'reference_id' => 1,
>   'message' => 'Test webhook',
> ]);
# 4. Revisar webhook.site → debería llegar POST JSON

# Verificar en logs
tail storage/logs/laravel.log | grep -i webhook
```

### Test del digest semanal

```bash
# Local
php artisan alerts:send-weekly-digest --dry-run

# Producción (envía de verdad)
php artisan alerts:send-weekly-digest
```

Debería:
- Listar cada org con `new=X resolved=Y pending=Z -> owner@email`
- Si una org tiene `new=0 && resolved=0 && pending=0` → skip sin enviar
- Enviar 1 mail por org con stats + 10 alertas recientes

Para ver el email renderizado: usar Mailtrap o Mailpit en local (`php artisan mail:serve`).

### Tests automatizados (28 verdes)

```bash
php artisan test --compact tests/Feature/AlertControllerTest.php \
                            tests/Feature/PushSubscriptionTest.php \
                            tests/Feature/WeeklyAlertDigestCommandTest.php
```

Coverage:
- 12 originales + 7 de N7/N8 + 5 de N6 + 4 de N5 = **28 tests, 94 assertions**

---

## 📚 Cómo añadir un nuevo `alert_type`

Cuando agregues un nuevo tipo de alerta al sistema:

1. **Backend** — crear la alerta en el código que la origina:
   ```php
   Alert::create([
       'organization_id' => $org->id,
       'alert_type' => 'mi_nuevo_tipo',
       'reference_type' => 'car',
       'reference_id' => $car->id,
       'message' => 'Algo pasó',
   ]);
   ```

2. **i18n** — añadir clave en `resources/js/i18n/{es,en}.js`:
   ```js
   // es.js
   alerts: {
       alert_types: {
           mi_nuevo_tipo: 'Mi nuevo tipo',
       },
   }
   ```

3. **Filtros** — añadir a `alertTypes` en `Alerts/Index.vue`:
   ```js
   const alertTypes = [
       'car_request', 'car_stale', 'client_no_contact',
       'verification_failed', 'verification_completed',
       'mi_nuevo_tipo',  // ← aquí
   ];
   ```

4. **Acciones inline** (opcional) — `inlineActions(alert)` en `Alerts/Index.vue`:
   ```js
   if (alert.alert_type === 'mi_nuevo_tipo') {
       actions.push({ key: 'special', label: 'Acción custom', icon: SomeIcon, handler: () => ... });
   }
   ```

5. **Test** — añadir caso en `AlertControllerTest` si tiene comportamiento especial.

---

## 🔌 Activar Web Push real (N6 completo)

El push actual loguea payload en `storage/logs/laravel.log`. Para activar envío real:

### 1. Instalar librería

```bash
composer require minishlink/web-push
php artisan vendor:publish --tag=laravel-notifications
```

### 2. Generar claves VAPID

```bash
php artisan web-push:vapid
# Copiar VAPID_PUBLIC_KEY y VAPID_PRIVATE_KEY a .env
```

### 3. Añadir a `.env`

```env
VAPID_PUBLIC_KEY=BPdX...
VAPID_PRIVATE_KEY=abc123...
VAPID_SUBJECT=mailto:admin@jjimportmotors.com
```

### 4. Modificar `PushNotificationDispatcher`

```php
private static function sendToSubscription(PushSubscription $sub, array $payload, Alert $alert): void
{
    try {
        \Minishlink\WebPush\WebPush::sendNotification(
            $sub->endpoint,
            json_encode($payload),
            $sub->p256dh,
            $sub->auth,
        );
    } catch (\Throwable $e) {
        Log::warning('WebPush failed', ['sub' => $sub->id, 'error' => $e->getMessage()]);
        if (str_contains($e->getMessage(), '410') || str_contains($e->getMessage(), '404')) {
            $sub->delete(); // subscription expirada
        }
    }
}
```

### 5. Verificar

```bash
# Local: usar https://web-push-testing.firebaseapp.com o similar
# Activar push en /organization/{id}/edit → debe mostrar "Recibirás un aviso..."
# Generar alerta → debe aparecer notificación nativa del browser
```

---

## 🐛 Troubleshooting

| Problema | Causa probable | Solución |
|---|---|---|
| Badge no se actualiza | Polling fallando | `tail -f storage/logs/laravel.log` mientras navegas |
| Webhook no llega | URL malformada o Discord bloqueado | Test con `https://webhook.site` primero |
| Mail digest no llega | Mail driver = `log` en `.env` | Configurar SMTP real (Mailgun/Postmark/Resend) |
| Push "No configurado en servidor" | Falta `VAPID_PUBLIC_KEY` en `.env` | Ver sección "Activar Web Push real" arriba |
| Snooze no se aplica | `snoozed_until` en pasado | Verificar que `pending()` filtra `snoozed_until <= now()` |
| Preferencias no se respetan | Cache de BD stale | `php artisan optimize:clear` |
| 404 en `/alerts/pending.json` | Ruta mal ordenada en `web.php` | Las rutas `/alerts/pending.json` y `/alerts/mark-all-read` deben ir ANTES de `/alerts/{alert}` |

---

## 📊 Métricas y monitoreo

```bash
# Alertas por org (última semana)
php artisan tinker --execute='
$orgs = App\Models\Organization::all();
foreach ($orgs as $o) {
    $new = $o->alerts()->where("created_at", ">=", now()->subWeek())->count();
    echo "$o->name: $new nuevas" . PHP_EOL;
}
'

# Suscripciones push activas
SELECT COUNT(*) FROM push_subscriptions;

# Webhooks configurados
SELECT id, name, notification_webhook_url IS NOT NULL as has_webhook
FROM organizations;
```

---

## ✅ Checklist de despliegue

Para replicar esto en otro entorno:

- [ ] `php artisan migrate` (aplica 3 migrations nuevas: alerts.snoozed_until, organizations.*, push_subscriptions)
- [ ] `npm run build` (assets compilados)
- [ ] `php artisan schedule:list` (ver `alerts:send-weekly-digest` weekly Monday 09:00)
- [ ] `php artisan optimize:clear` (limpiar cache de routes/config)
- [ ] Verificar `/sw.js` accesible (200)
- [ ] Verificar `/alerts/pending.json` requiere auth (401 si no logged)
- [ ] Configurar `MAIL_*` en `.env` si quieres que el digest envíe de verdad
- [ ] (Opcional) Instalar `minishlink/web-push` para push reales

---

## 📅 Próximos pasos sugeridos

| Feature | Esfuerzo | Valor | Bloquea |
|---|---|---|---|
| Email transaccional por alerta crítica (no solo digest) | 4h | Alto | Decisión sobre driver mail |
| Snooze con motivos custom ("revisar tras import") | 2h | Medio | — |
| Push notifications reales (VAPID) | 4h | Alto | Aprobar dep `minishlink/web-push` |
| Notification preferences por usuario (no org) | 6h | Medio | Migration + UI en Profile |
| Vista de "actividad reciente" de la org | 8h | Alto | Refactor de Audit Log |