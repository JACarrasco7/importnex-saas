<?php

namespace App\Listeners;

use App\Mail\WelcomeMail;
use Illuminate\Auth\Events\Registered;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class SendWelcomeEmail implements ShouldQueue
{
    /**
     * Send welcome email after user registration.
     * Only fires in non-testing environments to avoid spam during tests.
     */
    public function handle(Registered $event): void
    {
        if (app()->environment('testing')) {
            return;
        }

        $user = $event->user;

        try {
            Mail::to($user->email)->send(new WelcomeMail(
                userName: $user->name,
                organizationName: $user->organization?->name ?? config('app.name', 'JJ Import Motors'),
                appUrl: config('app.url'),
                locale: $user->locale ?? 'es',
            ));
        } catch (\Throwable $e) {
            Log::warning('Welcome email failed', [
                'user_id' => $user->id,
                'email' => $user->email,
                'error' => $e->getMessage(),
            ]);
        }
    }
}
