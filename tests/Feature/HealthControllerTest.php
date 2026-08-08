<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class HealthControllerTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('local');
    }

    public function test_health_endpoint_returns_200_with_full_report(): void
    {
        $response = $this->get('/health');

        $response->assertOk();
        $response->assertJsonStructure([
            'status',
            'timestamp',
            'app',
            'env',
            'version',
            'checks' => [
                'app',
                'database',
                'cache',
                'storage',
                'queue',
            ],
        ]);
    }

    public function test_health_includes_individual_check_statuses(): void
    {
        $response = $this->get('/health');
        $data = $response->json();

        $this->assertSame('healthy', $data['status']);
        foreach ($data['checks'] as $name => $check) {
            $this->assertSame('ok', $check['status'], "Check '{$name}' should be ok");
        }
    }

    public function test_database_check_reports_driver(): void
    {
        $response = $this->get('/health');
        $data = $response->json();

        $this->assertSame('sqlite', $data['checks']['database']['driver']);
        $this->assertArrayHasKey('latency_ms', $data['checks']['database']);
        $this->assertIsNumeric($data['checks']['database']['latency_ms']);
    }

    public function test_cache_check_writes_and_reads(): void
    {
        $response = $this->get('/health');
        $data = $response->json();

        $this->assertSame('ok', $data['checks']['cache']['status']);
        $this->assertArrayHasKey('driver', $data['checks']['cache']);
    }

    public function test_storage_check_writes_and_reads(): void
    {
        $response = $this->get('/health');
        $data = $response->json();

        $this->assertSame('ok', $data['checks']['storage']['status']);
        $this->assertArrayHasKey('driver', $data['checks']['storage']);
    }

    public function test_queue_check_reports_driver(): void
    {
        $response = $this->get('/health');
        $data = $response->json();

        $this->assertSame('ok', $data['checks']['queue']['status']);
        $this->assertArrayHasKey('driver', $data['checks']['queue']);
    }

    public function test_liveness_endpoint_always_returns_200(): void
    {
        $response = $this->get('/health/live');

        $response->assertOk();
        $this->assertSame('alive', $response->json('status'));
        $this->assertNotEmpty($response->json('timestamp'));
    }

    public function test_readiness_endpoint_returns_200_when_healthy(): void
    {
        $response = $this->get('/health/ready');

        $response->assertOk();
        $this->assertSame('ready', $response->json('status'));
        $this->assertArrayHasKey('database', $response->json('checks'));
        $this->assertArrayHasKey('cache', $response->json('checks'));
    }

    public function test_health_is_idempotent(): void
    {
        $r1 = $this->get('/health')->json();
        $r2 = $this->get('/health')->json();

        $this->assertSame($r1['status'], $r2['status']);
        $this->assertSame('healthy', $r1['status']);
    }

    public function test_health_response_is_fast_under_500ms(): void
    {
        $start = microtime(true);
        $response = $this->get('/health');
        $duration = (microtime(true) - $start) * 1000;

        $response->assertOk();
        $this->assertLessThan(500, $duration, "Health check took {$duration}ms (>500ms limit)");
    }
}
