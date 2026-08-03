<?php

namespace App\Jobs;

use App\Models\Alert;
use App\Models\Car;
use App\Services\CarVerificationService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class VerifyCarWithAI implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 2;
    public int $timeout = 120;

    public function __construct(public Car $car)
    {
    }

    public function handle(CarVerificationService $service): void
    {
        $result = $service->verify($this->car);

        if (! $result['success']) {
            $this->car->update(['status' => 'Located']);
            Alert::create([
                'organization_id' => $this->car->organization_id,
                'alert_type' => 'verification_failed',
                'reference_type' => 'car',
                'reference_id' => $this->car->id,
                'message' => 'AI verification failed: ' . ($result['error'] ?? 'unknown'),
                'resolved' => false,
            ]);
            return;
        }

        $analysis = $result['analysis'];
        $analysisFull = $result['analysis_full'] ?? $analysis;

        // Persist the legacy short summary into the dedicated review columns
        // and store the full enriched payload in `ai_analysis_json` so the
        // user can decide per field which suggestions to apply via the modal.
        $this->car->update([
            'status' => 'Pending review',
            'traffic_light' => $analysis['traffic_light'] ?? 'neutral',
            'valuation' => $analysis['valuation'] ?? null,
            'recommendation' => $analysis['recommendation'] ?? null,
            'red_flags' => $analysis['red_flags'] ?? [],
            'tips' => $analysis['tips'] ?? [],
            'ai_analysis_json' => $analysisFull,
        ]);

        Alert::create([
            'organization_id' => $this->car->organization_id,
            'alert_type' => 'verification_completed',
            'reference_type' => 'car',
            'reference_id' => $this->car->id,
            'message' => "AI verification completed: traffic light {$analysis['traffic_light']}",
            'resolved' => false,
        ]);
    }

    public function failed(\Throwable $exception): void
    {
        $this->car->update(['status' => 'Located']);

        Alert::create([
            'organization_id' => $this->car->organization_id,
            'alert_type' => 'verification_failed',
            'reference_type' => 'car',
            'reference_id' => $this->car->id,
            'message' => 'AI verification job failed: ' . $exception->getMessage(),
            'resolved' => false,
        ]);
    }
}
