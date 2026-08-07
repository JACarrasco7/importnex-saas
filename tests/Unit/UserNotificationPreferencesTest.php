<?php

namespace Tests\Unit;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UserNotificationPreferencesTest extends TestCase
{
    use RefreshDatabase;

    public function test_is_alert_type_enabled_returns_true_by_default(): void
    {
        $user = User::factory()->create();

        $this->assertTrue($user->isAlertTypeEnabled('car_stale'));
        $this->assertTrue($user->isAlertTypeEnabled('verification_failed'));
    }

    public function test_is_alert_type_enabled_returns_false_when_silenced(): void
    {
        $user = User::factory()->create([
            'notification_preferences' => ['car_stale' => false],
        ]);

        $this->assertFalse($user->isAlertTypeEnabled('car_stale'));
        $this->assertTrue($user->isAlertTypeEnabled('verification_failed'));
    }

    public function test_is_channel_enabled_defaults_to_email_and_push(): void
    {
        $user = User::factory()->create();

        $this->assertTrue($user->isChannelEnabled('email'));
        $this->assertTrue($user->isChannelEnabled('push'));
        $this->assertFalse($user->isChannelEnabled('webhook'));
    }

    public function test_is_channel_enabled_returns_false_when_disabled(): void
    {
        $user = User::factory()->create([
            'notification_channels' => ['email'],
        ]);

        $this->assertTrue($user->isChannelEnabled('email'));
        $this->assertFalse($user->isChannelEnabled('push'));
    }

    public function test_notification_preferences_are_cast_to_array(): void
    {
        $user = User::factory()->create([
            'notification_preferences' => ['car_stale' => false, 'verification_failed' => true],
        ]);

        $this->assertIsArray($user->notification_preferences);
        $this->assertSame(['car_stale' => false, 'verification_failed' => true], $user->notification_preferences);
    }
}
