# 11 — Sistema de notificaciones: por dentro y por fuera

## La visión de 30 segundos

```
ALGO PASA EN LA APP (ej: la IA no pudo verificar un coche)
                    ↓
        Se crea una fila en la tabla `alerts`
                    ↓
        AlertObserver::created()  ← el "repartidor" automático
                    ↓
   ┌────────┬──────────────┬─────────────┐
   In-app    Email digest   Webhook       Push
  (badge+   (1/semana)    (Slack/       (navegador)
   toasts)                 Discord)
```

Una sola alerta puede salir por **4 canales**. El usuario decide cuáles.

---

## 🧑 NIVEL USUARIO (por fuera)

### Lo que ve en la app

1. **Badge rojo en la campana del topbar** con el número de pendientes. Hace una animación "wiggle" cuando llega una nueva.
2. **Toasts** (esquina): aviso emergente de 8 segundos con el mensaje. Si hay varias, se apilan (máx. 5).
3. **El inbox `/alerts`** — la "bandeja de entrada":

```
 [Pendientes] [Pospuestas] [Todas]          ← pestañas de estado

 Chips: 🚗 car_request (3)  ⚠️ verification_failed (1) ...  ← filtrar por tipo
 [✓] Agrupar por tipo                                     ← acordeones

 ┌────────────────────────────────────────────────┐
 │ ⚠️ Verificación fallida: BMW Serie 3           │
 │ hace 2h                                        │
 │ [Reintentar] [🕐 Posponer] [👁 Ver] [🔕] [🗑] │  ← acciones inline
 └────────────────────────────────────────────────┘
```

### Las 3 acciones que preguntaste

#### 🕐 Snooze (N3) — "recuérdamelo luego"

El usuario clicka el reloj → elige **1h / 3h / 24h / 3d / 7d** → la alerta desaparece de "Pendientes" y pasa a "Pospuestas". Cuando pasa ese tiempo, **vuelve sola** a pendientes.

**Por qué existe:** sin snooze, el usuario que no puede resolver algo HOY solo tiene 2 opciones: dejarla ahí molestando (fatiga de notificaciones) o borrarla (pierde la tarea). Snooze es la tercera vía: "no ahora, pero no olvidar".

#### 📧 Email digest semanal (N5)

Cada **lunes a las 9:00**, el dueño de la organización recibe UN email:

```
Resumen semanal — JJ Import Motors
━━━━━━━━━━━━━━━━━━━━━
🆕 5 nuevas  ·  ✅ 3 resueltas  ·  ⏳ 2 pendientes

Top alertas:
• Verificación fallida: BMW Serie 3...
• Cliente sin contacto hace 7 días: Juan P....
```

**Por qué semanal y no por cada alerta:** un email por alerta = bandeja de spam → el usuario desactiva los emails o, peor, te marca como spam (mata la reputación de tu dominio). El digest semanal es el equilibrio: el owner que no abre la app a diario sigue enterado.

#### 🔗 Webhook Slack/Discord (N7)

El admin pega una URL de webhook en `Organization → Editar → Notificaciones`. A partir de ahí, cada alerta nueva aparece TAMBIÉN en el canal de Slack/Discord del equipo:

```
🔔 ImportnexCore — Verificación fallida
Coche: BMW Serie 3 (ref 42)
Hace 2 minutos | [Ver en la app]
```

**Por qué:** el equipo YA vive en Slack/Discord. Llevar la alerta a su herramienta elimina la fricción de "entrar a la app a mirar".

---

## ⚙️ NIVEL TÉCNICO (por dentro)

### 1. El origen: cualquier código puede crear una alerta

```php
Alert::create([
    'organization_id' => $org->id,
    'alert_type'      => 'verification_failed',
    'reference_type'  => 'car',
    'reference_id'    => $car->id,
    'message'         => 'AI verification failed: timeout',
]);
```

### 2. El repartidor: `AlertObserver::created()`

El Observer de Laravel se dispara SOLO al crear la fila. Es el único punto de decisión:

```
¿El tipo está silenciado en notification_preferences? → STOP (no molestar)
¿Hay webhook configurado y este tipo permitido?       → POST HTTP a Slack/Discord
¿Hay suscripciones push?                              → dispatch push
```

**Por qué un Observer y no código en cada sitio:** si mañana añades un 5º canal (SMS, Teams...), tocas UN archivo. Los 20 sitios que crean alertas no se enteran.

### 3. Snooze por dentro

- Columna `alerts.snoozed_until` (timestamp nullable, indexada).
- `POST /alerts/{alert}/snooze { hours: 24 }` → guarda `now()->addHours(24)`.
- El endpoint de pendientes filtra: `whereNull('snoozed_until')->orWhere('snoozed_until', '<=', now())`.
- **La "vuelta automática" no existe como proceso:** simplemente la query deja de excluirla cuando pasa la fecha. Cero cron, cero jobs. Elegante por simplicidad.

### 4. Email digest por dentro

- **Command:** `alerts:send-weekly-digest` programado en el scheduler de Laravel (`weeklyOn(1, '09:00')` = lunes 9 AM). El scheduler lo ejecuta el cron del servidor (Forge ya lo configura).
- **Lógica:** para cada organización → cuenta nuevas/resueltas/pendientes de la semana → si TODO es 0, **skip** (no envía email vacío) → si hay actividad, `WeeklyAlertDigest` mailable al owner.
- `--dry-run` para probar sin enviar.

### 5. Webhook por dentro

`AlertWebhookDispatcher`:
- Lee `organizations.notification_webhook_url` (**cifrada en BD**, cast `encrypted`).
- HTTP POST con timeout de **5 segundos** (si Slack está caído, no bloquea tu app).
- **Auto-detección:** si el host es `discord.com`, el payload usa `content`; si es Slack, usa `text`. El usuario no tiene que decirle qué servicio es.
- Whitelist por tipo: `notification_webhook_types` decide qué alertas salen al canal (quizá solo quieres las críticas en Slack, no todas).

### 6. El polling in-app (por qué NO WebSockets)

Cada 30s, el navegador pide `GET /alerts/pending.json` → `{ count: 3, recent: [...] }`. Si el count sube respecto a la última vez (guardada en `localStorage`) → wiggle del badge + toast.

**¿Por qué no WebSockets (Reverb)?** Un servidor de WebSockets es un daemon permanente que mantener (coste, caídas, reconexiones). El polling de 30s da el 95% de la experiencia con el 5% del coste operativo. **Decisión consciente, documentada como anti-overengineering.** Si algún día hay 500+ alertas/día o se necesita <5s de latencia, se reevalúa.

### 7. Idempotencia y seguridad (los detalles invisibles)

- Rutas específicas (`/alerts/pending.json`) declaradas **antes** que `/alerts/{alert}` — si no, Laravel interpreta "pending.json" como un ID.
- Toda alerta filtra por `organization_id` (multi-tenant: nadie ve alertas de otra org).
- 28 tests verdes cubren: polling, snooze, preferencias, digest, push, permisos.

---

## Resumen en una frase por canal

| Canal | Para quién | Coste operativo |
|---|---|---|
| In-app (badge/toasts/inbox) | El usuario que está trabajando ahora | Cero (polling) |
| Snooze | El usuario que dice "luego" | Cero (una columna) |
| Email digest | El owner que no entra a diario | 1 email/semana |
| Webhook | El equipo en Slack/Discord | Cero (POST saliente) |
| Push | El que quiere el aviso nativo del navegador | VAPID (pendiente activar) |

> **Regla reutilizable:** Una notificación = un evento + un repartidor central (Observer) + N canales opcionales + preferencias por usuario/tipo. Empieza con in-app + email digest (los dos baratos), añade webhooks cuando el equipo lo pida, y evita WebSockets hasta que la latencia lo justifique con números.
