<?php

namespace Tests\Feature;

use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Tests\TestCase;

class RateLimiterTest extends TestCase
{
    public function test_api_read_limit_is_60_per_minute(): void
    {
        $limit = RateLimiter::limiter('api-read')(Request::create('/test', 'GET'));

        $this->assertInstanceOf(Limit::class, $limit);
        $this->assertSame(60, $limit->maxAttempts);
    }

    public function test_api_write_limit_is_20_per_minute(): void
    {
        $limit = RateLimiter::limiter('api-write')(Request::create('/test', 'POST'));

        $this->assertInstanceOf(Limit::class, $limit);
        $this->assertSame(20, $limit->maxAttempts);
    }

    public function test_api_heavy_limit_is_5_per_10_minutes(): void
    {
        $limit = RateLimiter::limiter('api-heavy')(Request::create('/test', 'POST'));

        $this->assertInstanceOf(Limit::class, $limit);
        $this->assertSame(5, $limit->maxAttempts);
    }

    public function test_auth_limit_is_6_per_minute(): void
    {
        $limit = RateLimiter::limiter('auth')(Request::create('/login', 'POST'));

        $this->assertInstanceOf(Limit::class, $limit);
        $this->assertSame(6, $limit->maxAttempts);
    }

    public function test_public_form_limit_is_10_per_minute(): void
    {
        $limit = RateLimiter::limiter('public-form')(Request::create('/newsletter/subscribe', 'POST'));

        $this->assertInstanceOf(Limit::class, $limit);
        $this->assertSame(10, $limit->maxAttempts);
    }

    public function test_routes_use_named_throttle_aliases(): void
    {
        $routeFiles = [
            base_path('routes/web.php'),
            base_path('routes/api.php'),
            base_path('routes/auth.php'),
        ];

        foreach ($routeFiles as $file) {
            $content = file_get_contents($file);
            // No raw numeric throttle:N,M should remain (they should be named aliases)
            $hasNumeric = preg_match('/throttle:\d+,\d+/', $content);
            $this->assertSame(
                0,
                $hasNumeric,
                "File {$file} still has numeric throttle:N,M — should use named alias"
            );
        }
    }

    public function test_throttle_alias_resolves_to_correct_limiter(): void
    {
        // Verify the limiter callback works
        $request = Request::create('/test', 'GET');
        $request->server->set('REMOTE_ADDR', '192.168.1.1');

        $limit = RateLimiter::limiter('api-read')($request);

        $this->assertNotNull($limit);
        $this->assertNotEmpty($limit->key);
    }
}
