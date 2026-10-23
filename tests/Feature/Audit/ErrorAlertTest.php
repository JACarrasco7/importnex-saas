<?php

namespace Tests\Feature\Audit;

use App\Support\ErrorAlert;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Route;
use RuntimeException;
use Tests\TestCase;

/**
 * Auditoría de venta (8.9): un error 500 en producción avisa por email,
 * sin repetirse y sin provocar otro error si el correo falla.
 */
class ErrorAlertTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        Cache::flush();
    }

    public function test_no_alert_without_a_configured_recipient(): void
    {
        config(['logging.error_alert_email' => null]);
        Mail::fake();

        ErrorAlert::notify(new RuntimeException('boom'));

        Mail::assertNothingSent();
    }

    public function test_alert_is_sent_once_per_error_within_the_window(): void
    {
        config(['logging.error_alert_email' => 'avisos@example.com']);
        Mail::fake();

        $error = new RuntimeException('boom');
        ErrorAlert::notify($error);
        ErrorAlert::notify($error);

        Mail::assertSentCount(1);
    }

    public function test_invalid_recipient_is_ignored(): void
    {
        config(['logging.error_alert_email' => 'no-es-un-email']);

        $this->assertNull(ErrorAlert::recipient());
    }

    public function test_unhandled_exception_triggers_the_alert(): void
    {
        config(['logging.error_alert_email' => 'avisos@example.com']);
        Mail::fake();
        Route::get('/__boom', fn () => throw new RuntimeException('fallo de prueba'));

        $this->get('/__boom')->assertStatus(500);

        Mail::assertSentCount(1);
    }
}
