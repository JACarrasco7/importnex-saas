# 04 — Emails de bienvenida y recordatorios

## ¿Qué recordatorios hay? (estado real, verificado en disco)

| Email | Cuándo se envía | Estado | Código |
|---|---|---|---|
| **Bienvenida (D0)** | Inmediatamente tras registrarse | ✅ Implementado | `app/Listeners/SendWelcomeEmail.php` + `app/Mail/WelcomeMail.php` |
| **Recordatorio D3** | 3 días después si NO completó el onboarding | ⚠️ Planificado (Sprint 2.5, pendiente) | — |
| **Recordatorio D7** | 7 días después si sigue sin completar | ⚠️ Planificado (pendiente) | — |

**Ojo:** en el plan (Sprint 2.5) figuraban los 3 como "HECHOS", pero al verificar el disco solo existe el de bienvenida. Los recordatorios D3/D7 están diseñados pero sin implementar. Esta guía te enseña cómo funcionan los dos patrones para que sepas montarlos.

## Cómo funciona el email de bienvenida (patrón Event → Listener → Mailable)

Es el flujo estándar de Laravel en 3 piezas:

```
Usuario se registra
      ↓
Laravel dispara evento: Registered($user)      ← lo lanza Breeze automáticamente
      ↓
Listener: SendWelcomeEmail (escucha ese evento)
      ↓
Mailable: WelcomeMail (construye el email)
      ↓
Cola → Mailer (Mailgun/SMTP/log según .env)
```

### 1. El Listener ([app/Listeners/SendWelcomeEmail.php](../../app/Listeners/SendWelcomeEmail.php))

```php
class SendWelcomeEmail implements ShouldQueue   // ← ShouldQueue = se envía en background
{
    public function handle(Registered $event): void
    {
        if (app()->environment('testing')) {
            return;                             // ← nunca spamear en tests
        }
        try {
            Mail::to($user->email)->send(new WelcomeMail(...));
        } catch (\Throwable $e) {
            Log::warning('Welcome email failed', [...]);  // ← el registro NO falla aunque el mail falle
        }
    }
}
```

**3 lecciones aquí:**

1. **`ShouldQueue`** → el email se envía en un job de cola. El usuario ve su dashboard al instante, no espera a que el SMTP responda. *Regla: todo email en cola, siempre.*
2. **Guard de testing** → sin esto, cada `php artisan test` que registra usuarios mandaría emails reales.
3. **`try/catch` con Log** → si el servidor de correo cae, el registro del usuario NO debe romperse. El email es "best effort", el negocio es sagrado.

### 2. El Mailable ([app/Mail/WelcomeMail.php](../../app/Mail/WelcomeMail.php))

Es la clase que define asunto, vista Blade y datos. Recibe `userName`, `organizationName`, `appUrl`, `locale` → **email en el idioma del usuario**.

## Cómo funcionarían los recordatorios D3/D7 (el patrón Scheduled Job)

El patrón es distinto: no hay evento, hay un **cron que revisa cada día**:

```php
// routes/console.php (Laravel 11)
Schedule::command('onboarding:send-reminders')->daily();
```

```php
// Pseudo-implementación del command
UserOnboardingProgress::whereNull('completed_at')
    ->whereNull('skipped_at')
    ->where('created_at', '<=', now()->subDays(3))
    ->whereNull('reminder_d3_sent_at')     // ← idempotencia: no enviar 2 veces
    ->each(fn ($p) => Mail::to($p->user)->send(new OnboardingReminderD3(...)));
```

**La clave es la columna `reminder_d3_sent_at`:** marca cuándo se envió. Sin ella, el cron diario mandaría el recordatorio TODOS los días. Este es el mismo principio de idempotencia que usamos en los webhooks de Stripe.

## Por qué D0 + D3 + D7 y no otra cadencia

Es la cadencia estándar de SaaS (la usan Notion, Linear, Airtable):

- **D0** → el usuario está "caliente": acaba de registrarse. Máxima apertura (~60%).
- **D3** → si no volvió, aún se acuerda de ti. Recordatorio suave.
- **D7** → última llamada. Después de 7 días sin activarse, la probabilidad de que vuelva cae a <10%. Más emails = spam y baja reputación de dominio.

> **Regla reutilizable:** Emails automáticos = eventos (reacción inmediata) + cron (seguimiento por inactividad). Todo email: en cola, con try/catch, con guard de testing y con marca de "ya enviado" para idempotencia. Y pocos: 3 toques en 7 días, no 10.
