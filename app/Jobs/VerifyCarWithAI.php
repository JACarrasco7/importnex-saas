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

    public function __construct(public Car $car) {}

    public function handle(CarVerificationService $service): void
    {
        $result = $service->verify($this->car);

        if (! $result['success']) {
            Alert::create([
                'organization_id' => $this->car->organization_id,
                'alert_type' => 'verification_failed',
                'reference_type' => 'car',
                'reference_id' => $this->car->id,
                'message' => 'AI verification failed: '.($result['error'] ?? 'unknown'),
                'resolved' => false,
            ]);

            return;
        }

        $analysis = $result['analysis'];
        $analysisFull = $result['analysis_full'] ?? $analysis;

        // No modificamos ningún campo existente del coche: solo guardamos la
        // investigación (ai_analysis_json + ai_verified_at). El usuario aplica
        // campo por campo desde el modal (CarVerificationController@apply).
        $this->car->update([
            'ai_analysis_json' => $analysisFull,
            'ai_verified_at' => now(),
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
        Alert::create([
            'organization_id' => $this->car->organization_id,
            'alert_type' => 'verification_failed',
            'reference_type' => 'car',
            'reference_id' => $this->car->id,
            'message' => 'AI verification job failed: '.$exception->getMessage(),
            'resolved' => false,
        ]);
    }
}
