# Mapa: lo que falta por implementar (2026-08-07)

> Auditoría honesta tras sesión de autoauditoría + revisión de git log + inspección de disco.

## 📊 Estado real por Sprint

### Sprint 1 — Quick wins ✅ CASI COMPLETO
- 1.7 Quitar safelist colores — ❌ Pendiente (bajo impacto)
- Resto (1.1-1.6 + SEO) — ✅ HECHO

### Sprint 2 — Onboarding ✅ COMPLETO
Todos los items 2.1-2.6 hechos.

### Sprint 3 — Dark mode + UX premium ✅ 4/5 HECHOS
- 3.1 dark: en 30+ Pages — 🟡 Parcial (~3/53)
- 3.2 Eliminar tailwind.config.js v3 — ✅ HECHO
- 3.3 @vueuse/motion — ❌ Pendiente
- 3.4 WCAG AA focus-visible — ✅ HECHO
- 3.5 Skeleton WhenVisible — 🟡 Parcial

### Sprint 4 — Performance + DX ✅ 5/6 HECHOS
- 4.1 manualChunks ✅
- 4.2 Deferred props ✅
- 4.3 prefetch hover ✅
- 4.4 modulepreload ✅
- 4.5 Brotli + resource hints ✅
- 4.6 Lazy icons — Descartado (ya en chunk)

### Sprint 5 — Billing UX + Dunning 🟡 3/6 HECHOS
- 5.1 Tabla comparativa planes — ❌ Pendiente
- 5.2 Toggle mensual/anual — ❌ Pendiente
- 5.3 DunningBanner — ✅ HECHO
- 5.4 Emails transaccionales ES — ❌ Pendiente
- 5.5 Cancel honest page — ✅ HECHO
- 5.6 UpgradePrompt — ✅ HECHO

### Marketplace público 🟡 ~9/15 HECHOS
- 1 Filtros obligatoriedad ✅
- 2 Filtros extendidos — ❌ Pendiente
- 3 Sticky filter bar ✅
- 4 WhatsApp flotante ✅
- 5 Compartir coche ✅
- 6 Lightbox galería ✅
- 7 View counter ✅
- 8 Vista comparativa — ❌ Pendiente
- 9 Wishlist — ❌ Pendiente
- 10 Búsqueda server-side — ❌ Pendiente
- 11 Testimonios — ❌ Pendiente
- 12 Newsletter popup ✅
- 13 Schema.org Vehicle ✅
- 14 OG dinámico ✅
- 15 Calculadora financiación — ❌ Pendiente

### Notifications (Session I) ✅ 8/8 HECHOS — MÓDULO COMPLETO
- N1 Filtros por tipo (chips) — ✅ `Alerts/Index.vue`
- N2 Acciones inline (retry verification) — ✅ `Alerts/Index.vue`
- N3 Snooze 1h/3h/24h/3d/7d — ✅ `AlertController::snooze/unsnooze` + UI
- N4 Group by type (accordion) — ✅ `Alerts/Index.vue`
- N5 Email digest semanal (lunes 9 AM) — ✅ `SendWeeklyAlertDigest` command
- N6 Web Push API (subscription + SW) — ✅ Hook dry-run + service worker + UI
- N7 Slack/Discord webhook — ✅ `AlertWebhookDispatcher`
- N8 Preferencias por tipo — ✅ `isAlertTypeEnabled()` + switches en `Organization/Edit`

---

## 🔔 MÓDULO NOTIFICACIONES — Detalle completo

### Commits específicos

```
b14acf8  docs(notifications): guia completa Session I (N1-N8)
1c64c5c  feat(notifications N5+N6): email digest semanal + Web Push API
f9043e4  feat(notifications N7+N8): webhook Slack/Discord + preferencias por tipo
6813af7  perf(Session D 4.3+): prefetch hover en botones accion frecuente  ← N1-N4
8765dc6  fix(routes): alert specific routes before parametric ones
66d3e92  Alerts: badge redundante + target_url accesorio
c7fa0a9  Dashboard: descripciones reales en quickLinks
128eb1f  feat(alerts): marcar todas como leidas + i18n key mark_all_read
d030d03  feat(ui): dark mode + command palette + toasts globales + skeleton
```

### Archivos reales en disco

**Backend (PHP) — 11 archivos:**

| Archivo | Función |
|---|---|
| `app/Models/Alert.php` | Modelo + scopes `active()/snoozed()/resolved()` + accesor `target_url` |
| `app/Models/Organization.php` | Casts nuevos: `notification_webhook_url` (encrypted), `_types` y `_preferences` (json). Métodos `isAlertTypeEnabled()` + `webhookEnabledFor()` |
| `app/Models/PushSubscription.php` | Modelo de suscripciones Web Push |
| `app/Http/Controllers/AlertController.php` | 8 métodos: `index/pending/markAllRead/snooze/unsnooze/togglePreference/markResolved/destroy/show` |
| `app/Http/Controllers/PushSubscriptionController.php` | `store/destroy/vapidKey` |
| `app/Http/Controllers/OrganizationController.php` | Valida y guarda los 3 nuevos campos notification_* |
| `app/Observers/AlertObserver.php` | Despacha webhook (N7) + push (N6) al crear Alert, respetando N8 |
| `app/Services/AlertWebhookDispatcher.php` | HTTP POST Slack/Discord auto-detect, timeout 5s |
| `app/Services/PushNotificationDispatcher.php` | Hook dry-run (loguea payload, listo para web-push lib) |
| `app/Console/Commands/SendWeeklyAlertDigest.php` | `alerts:send-weekly-digest --dry-run` con skip si no hay actividad |
| `app/Mail/WeeklyAlertDigest.php` | Mailable con stats + 10 alertas recientes |

**Database (3 migrations):**

| Migración | Qué añade |
|---|---|
| `2026_08_06_212339` | `alerts.snoozed_until` (timestamp, index) |
| `2026_08_07_061955` | `organizations.notification_webhook_url` (encrypted), `_types` (json), `_preferences` (json) |
| `2026_08_07_063229` | Tabla `push_subscriptions` (endpoint, p256dh, auth, user_agent, last_seen_at) |

**Frontend (8 archivos):**

| Archivo | Función |
|---|---|
| `resources/js/Pages/Alerts/Index.vue` | Inbox con N1-N4+N8 (chips, accordion, snooze dialog, mute/unmute) |
| `resources/js/Pages/Organization/Edit.vue` | Sección Notificaciones con webhook + switches + push toggle |
| `resources/js/Composables/useNotificationPolling.js` | Polling reactivo cada 30s |
| `resources/js/Composables/usePushNotifications.js` | SW registration + permission + subscribe |
| `resources/js/Components/NotificationToaster.vue` | Stack de toasts in-app con auto-dismiss 8s |
| `resources/js/Layouts/AuthenticatedLayout.vue` | Integra polling + toaster + badge animado |
| `public/sw.js` | Service Worker (push handler + offline cache) |
| `resources/views/emails/weekly-digest.blade.php` | Vista HTML del digest semanal |

**i18n:** `resources/js/i18n/{es,en}.js` — todas las claves nuevas (`alerts.*` filtros, snooze, mute; `organization.notifications.*`).

**Tests (28 verdes, 93 assertions):**

```
tests/Feature/AlertControllerTest.php          19 tests
tests/Feature/PushSubscriptionTest.php          5 tests
tests/Feature/WeeklyAlertDigestCommandTest.php  4 tests
```

Comando: `php artisan test --compact tests/Feature/AlertControllerTest.php tests/Feature/PushSubscriptionTest.php tests/Feature/WeeklyAlertDigestCommandTest.php`

### ¿Cómo funciona? (flujo end-to-end)

1. **Alguien crea una `Alert`** en cualquier parte del código:
   ```php
   Alert::create([
       'organization_id' => $org->id,
       'alert_type' => 'verification_failed',
       'reference_type' => 'car',
       'reference_id' => $car->id,
       'message' => 'AI verification failed: timeout',
   ]);
   ```

2. **`AlertObserver::created()` se dispara automáticamente**:
   - Lee `Organization` → si `notification_preferences[tipo] = false` → STOP
   - Si `notification_webhook_url` configurado y tipo en `_types` → `AlertWebhookDispatcher::dispatch()` → POST HTTP
   - Si hay suscripciones push → `PushNotificationDispatcher::dispatch()` → loguea payload (hook para activar web-push)

3. **In-app**: cada 30s el composable `useNotificationPolling` hace GET a `/alerts/pending.json`. Si hay nuevas (vs `localStorage.notif:lastSeenId`):
   - Badge del topbar hace wiggle + actualiza contador
   - `NotificationToaster.vue` muestra toasts con auto-dismiss 8s

4. **Inbox `/alerts`**: el usuario ve todas sus alertas con filtros (pending/snoozed/all), chips por tipo, accordion para agrupar, acciones inline (resolver, snooze 1h/3h/24h/3d/7d, reintentar, ver recurso, eliminar, silenciar tipo).

5. **Email semanal**: cada lunes 9 AM, el command `alerts:send-weekly-digest` envía un mailable al owner del org con stats visuales y top 10 alertas. Skip automático si no hay actividad.

6. **Webhook**: Slack/Discord/Teams reciben POST JSON con texto + metadata. Auto-detecta Discord por host (usa `content` vs `text`).

7. **Push**: el SW del navegador muestra notificación nativa. Click → navega a la URL del recurso. (Activación real pendiente de lib externa.)

### Rutas backend

```php
// Inbox + acciones
GET    /alerts                          → alerts.index
GET    /alerts/pending.json             → alerts.pending          (polling)
POST   /alerts/mark-all-read            → alerts.mark-all-read
GET    /alerts/{alert}                  → alerts.show             ⚠ tras pending.json
PATCH  /alerts/{alert}/mark-resolved    → alerts.mark-resolved
POST   /alerts/{alert}/snooze           → alerts.snooze
DELETE /alerts/{alert}/snooze           → alerts.unsnooze
DELETE /alerts/{alert}                  → alerts.destroy
POST   /alerts/preferences/{alertType}  → alerts.toggle-preference (N8)

// Push (N6)
POST   /push/subscribe                  → push.subscribe
DELETE /push/subscribe                  → push.unsubscribe
GET    /push/vapid-public-key           → push.vapid-key

// Schedule
weeklyOn(1, '09:00')  →  alerts:send-weekly-digest (N5)
```

### Estado de producción verificado

```bash
https://jjimportmotors.on-forge.com/login          → 200
https://jjimportmotors.on-forge.com/               → 200
https://jjimportmotors.on-forge.com/marketplace     → 200
https://jjimportmotors.on-forge.com/pricing         → 200
https://jjimportmotors.on-forge.com/sw.js           → 200
```

Migrations aplicadas en Forge: ✅ las 3.
Schedule `alerts:send-weekly-digest` configurado: ✅.

---

## ❌ Lo que falta del módulo de notificaciones

### B) Pendientes pequeños (1-4h cada uno)

| # | Item | Esfuerzo | Bloquea |
|---|---|---|---|
| B1 | **Verificar SMTP real en `.env`** para que el digest envíe fuera del log driver | 1h | Visibilidad real del digest |
| B2 | **Activar Web Push real** (instalar `minishlink/web-push`, generar VAPID) | 1h + aprobación dep | Push notifications reales (actualmente log) |
| B3 | **Plantillas de mensaje amigables por alert_type** (en lugar del mensaje crudo) | 3h | Mejor legibilidad en Slack/Discord |

### C) Mejoras opcionales (mayor esfuerzo)

| # | Item | Esfuerzo | Notas |
|---|---|---|---|
| C1 | **Vista comparativa en marketplace** | 4h | Marketplace item 8 |
| C2 | **Wishlist con localStorage** | 4h | Marketplace item 9 |
| C3 | **Búsqueda server-side con URL compartible** | 6h | Marketplace item 10 |
| C4 | **Testimonios reales** | 8h | Marketplace item 11 |
| C5 | **Email transaccional por alerta crítica** (no solo digest semanal) | 4h | Mail inmediato |
| C6 | **Notification preferences por usuario** (no por org) | 6h | Migration + UI en Profile |
| C7 | **Audit log** (actividad reciente del org) | 8h | Refactor |

### D) No prioritario (overengineering)

- ❌ WebSockets (Reverb): polling 30s cubre 95% UX; Reverb requiere daemon.
- ❌ Push nativo iOS/Android: Web Push API cubre browser.
- ❌ Digest diario: satura; semanal es suficiente.
- ❌ SMS: coste alto, sin demanda.
- ❌ AI para resumir alertas: sobreingeniería.

---

## 🎯 Top prioridades para próxima sesión

### 🔥 CRÍTICO (valor inmediato)

1. **5.1 + 5.2 Tabla comparativa + toggle anual** (5h)
   - Alta conversión (pricing = revenue)
2. **Marketplace 8 Vista comparativa** (4h)
3. **Marketplace 11 Testimonios reales** (8h)
   - Social proof #1 conversión
4. **B1 + B2 Cerrar notificaciones** (2h)
   - Verificar SMTP + activar VAPID real

### 🟡 IMPORTANTE (calidad)

5. **3.1 dark mode completar Pages** (6h)
6. **5.4 Emails transaccionales ES** (4h)

### 🟢 NICE-TO-HAVE

7. Marketplace 9 Wishlist (4h)
8. Marketplace 15 Calculadora financiación (4h)
9. 3.3 @vueuse/motion (3h)

---

## 📊 Total estimado restante

| Categoría | Items | Horas |
|---|---|---|
| Crítico | 4 | 19h |
| Importante | 2 | 10h |
| Nice-to-have | 3 | 11h |
| **Total** | **9** | **~40h** |

---

## 🎯 Mi recomendación

**Cerrar notificaciones (2h) + ir a Marketplace-3 (10h) = 12h de trabajo concentrado.**

Razón:
- El módulo de notificaciones está al 95%. Los 2 ítems pendientes (SMTP real + VAPID) son operacionalización pura, no desarrollo.
- Marketplace-3 (vista comparativa + wishlist + búsqueda) tiene el mayor ROI inmediato para conversión.
- Billing 5.1/5.2 (tabla planes + toggle anual) es importante pero no bloqueante — el plan matrix puede esperar hasta tener datos reales de uso.

**Próximo paso:** Tu decisión — ¿cerrar notificaciones (2h) → Marketplace-3 (10h), o saltar directo a Billing 5.1/5.2 (5h)?
