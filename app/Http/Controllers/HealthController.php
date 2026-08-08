<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;

/**
 * Health check endpoint for uptime monitoring (UptimeRobot, Pingdom, etc.).
 *
 * Returns 200 with JSON body listing each subsystem status.
 * Returns 503 if any critical subsystem (db, cache) is down.
 *
 * Endpoints:
 *   GET /health       — full health report (DB + cache + storage + queue)
 *   GET /health/live  — liveness only (is the process alive?) — 200 always
 *   GET /health/ready — readiness only (DB + cache reachable?) — 503 if degraded
 */
class HealthController extends Controller
{
    /**
     * Full health report.
     */
    public function index(): JsonResponse
    {
        $checks = [
            'app' => $this->checkApp(),
            'database' => $this->checkDatabase(),
            'cache' => $this->checkCache(),
            'storage' => $this->checkStorage(),
            'queue' => $this->checkQueue(),
        ];

        $healthy = collect($checks)->every(fn ($c) => $c['status'] === 'ok');

        return response()->json([
            'status' => $healthy ? 'healthy' : 'degraded',
            'timestamp' => now()->toIso8601String(),
            'app' => config('app.name'),
            'env' => config('app.env'),
            'version' => config('app.version', '1.0.0'),
            'checks' => $checks,
        ], $healthy ? 200 : 503);
    }

    /**
     * Liveness probe — used by Kubernetes / Docker to know if process is alive.
     */
    public function live(): JsonResponse
    {
        return response()->json([
            'status' => 'alive',
            'timestamp' => now()->toIso8601String(),
        ]);
    }

    /**
     * Readiness probe — used by load balancers to know if traffic should route here.
     */
    public function ready(): JsonResponse
    {
        $checks = [
            'database' => $this->checkDatabase(),
            'cache' => $this->checkCache(),
        ];

        $ready = collect($checks)->every(fn ($c) => $c['status'] === 'ok');

        return response()->json([
            'status' => $ready ? 'ready' : 'not_ready',
            'timestamp' => now()->toIso8601String(),
            'checks' => $checks,
        ], $ready ? 200 : 503);
    }

    /**
     * App itself: PHP version, Laravel version, locale.
     */
    private function checkApp(): array
    {
        return [
            'status' => 'ok',
            'php' => PHP_VERSION,
            'laravel' => app()->version(),
            'locale' => app()->getLocale(),
        ];
    }

    /**
     * Database connection.
     */
    private function checkDatabase(): array
    {
        try {
            $start = microtime(true);
            DB::select('select 1');
            $latency = round((microtime(true) - $start) * 1000, 2);

            return [
                'status' => 'ok',
                'driver' => DB::connection()->getDriverName(),
                'latency_ms' => $latency,
            ];
        } catch (\Throwable $e) {
            return [
                'status' => 'error',
                'driver' => DB::connection()->getDriverName(),
                'message' => $e->getMessage(),
            ];
        }
    }

    /**
     * Cache (Redis/database/file).
     */
    private function checkCache(): array
    {
        try {
            $key = 'health-check-'.uniqid('', true);
            $start = microtime(true);
            Cache::put($key, 'ok', 10);
            $value = Cache::get($key);
            Cache::forget($key);
            $latency = round((microtime(true) - $start) * 1000, 2);

            if ($value !== 'ok') {
                return ['status' => 'error', 'message' => 'Cache read/write mismatch'];
            }

            return [
                'status' => 'ok',
                'driver' => config('cache.default'),
                'latency_ms' => $latency,
            ];
        } catch (\Throwable $e) {
            return [
                'status' => 'error',
                'driver' => config('cache.default'),
                'message' => $e->getMessage(),
            ];
        }
    }

    /**
     * Storage disk (default: local).
     */
    private function checkStorage(): array
    {
        // Try the configured default disk first; fall back to 'local' if it
        // throws (e.g., S3 configured but package missing in this env).
        $defaultDisk = config('filesystems.default');

        foreach ([$defaultDisk, 'local'] as $disk) {
            try {
                $start = microtime(true);
                Storage::disk($disk)->put('health-check.txt', 'ok');
                $content = Storage::disk($disk)->get('health-check.txt');
                Storage::disk($disk)->delete('health-check.txt');
                $latency = round((microtime(true) - $start) * 1000, 2);

                if ($content !== 'ok') {
                    continue;
                }

                return [
                    'status' => 'ok',
                    'driver' => $disk,
                    'fallback' => $disk !== $defaultDisk,
                    'latency_ms' => $latency,
                ];
            } catch (\Throwable $e) {
                // Try next disk
                continue;
            }
        }

        return [
            'status' => 'error',
            'driver' => $defaultDisk,
            'message' => 'No working storage disk available (tried: '.$defaultDisk.', local)',
        ];
    }

    /**
     * Queue connection (sync/database/redis/sqs).
     */
    private function checkQueue(): array
    {
        try {
            $driver = config('queue.default');

            // For 'sync' driver, queue runs immediately so it's always healthy.
            if ($driver === 'sync') {
                return [
                    'status' => 'ok',
                    'driver' => 'sync',
                    'note' => 'Sync queue runs in-process (always healthy)',
                ];
            }

            // For other drivers, just verify the connection.
            $start = microtime(true);
            Queue::size();
            $latency = round((microtime(true) - $start) * 1000, 2);

            return [
                'status' => 'ok',
                'driver' => $driver,
                'latency_ms' => $latency,
            ];
        } catch (\Throwable $e) {
            return [
                'status' => 'error',
                'driver' => config('queue.default'),
                'message' => $e->getMessage(),
            ];
        }
    }
}
