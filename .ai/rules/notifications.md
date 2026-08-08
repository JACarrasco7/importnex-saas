---
glob: 'app/Models/Alert.php,app/Observers/AlertObserver.php,app/Services/Alert*Dispatcher.php,app/Mail/*'
title: 'Sistema de notificaciones multi-canal (3 canales independientes)'
---

## Arquitectura (2026-08-07)

Sistema de alertas con **3 canales independientes** que se disparan en paralelo desde `AlertObserver::created()`:

1. **Webhook** (`AlertWebhookDispatcher`) — Slack/Discord/Teams/n8n via JSON POST. Falla silenciosa con `Log::warning`.
2. **Email** (`AlertEmailDispatcher`) — Laravel Mail con `AlertNotification` mailable. I18n por locale de organización.
3. **Push** (`PushNotificationDispatcher`) — OneSignal API REST. Per-org credentials (encrypted).

## Reglas

- **Cada canal es independiente**: fallo en uno no afecta a los otros. Try/catch por canal en `AlertObserver`.
- **Respetar `notification_preferences`** en 3 niveles:
  - `Organization::isAlertTypeEnabled($type)` → aborta TODO si silenciado.
  - `Organization::webhookEnabledFor($type)` → filtra webhook.
  - `User::isChannelEnabled('email')` + `User::isAlertTypeEnabled($type)` → filtra email per user.
- **Push** broadcast: `PushNotificationDispatcher` consulta `Organization` prefs pero NO user prefs (broadcast).
- **Email Mailable**: `app/Mail/AlertNotification.php` con locale (`$org->locale ?? 'es'`).
- **NO usar global scopes** en `Alert` model (ver [multitenancy.md](multitenancy.md)). Filtrar con `authorizeAlertAccess` o `where('organization_id', $orgId)` explícito.
- **AlertObserver** es el ÚNICO entry point. Cualquier código que cree Alerts debe disparar el observer (`Alert::create()` → `created()`).

## Migraciones relacionadas

- `2026_08_07_061955` añade `notification_webhook_url` (encrypted), `notification_webhook_types` (json), `notification_preferences` (json) en `organizations`.
- `2026_08_07_063229` crea `push_subscriptions` (Web Push API subscriptions).
- `2026_08_07_075222` añade `onesignal_app_id` y `onesignal_api_key` (encrypted) en `organizations`.
- `2026_08_07_111226` añade `notification_preferences` y `notification_channels` (json) en `users`.

## Tests obligatorios (104 passing)

- `tests/Unit/AlertEmailDispatcherTest.php` (4 tests)
- `tests/Unit/AlertWebhookDispatcherTest.php` (4 tests)
- `tests/Unit/AlertObserverTest.php` (5 tests)
- `tests/Unit/PushNotificationDispatcherTest.php` (4 tests)
- `tests/Unit/UserNotificationPreferencesTest.php` (5 tests)
- `tests/Feature/AlertControllerTest.php` (19 tests)

Comando: `php artisan test --filter="Alert|UserNotification" --compact`.
