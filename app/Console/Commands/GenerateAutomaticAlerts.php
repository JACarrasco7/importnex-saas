<?php

namespace App\Console\Commands;

use App\Models\Alert;
use App\Models\Car;
use App\Models\Client;
use App\Models\ClientContactLog;
use Illuminate\Console\Command;

/**
 * Scheduled command that generates automatic alerts for:
 * - Cars stuck in same status for too long
 * - Clients without recent contact
 * - Cars with low ITV date approaching
 */
class GenerateAutomaticAlerts extends Command
{
    protected $signature = 'alerts:generate';
    protected $description = 'Generate automatic alerts for stale cars and clients without contact';

    public function handle(): int
    {
        $this->info('Generating automatic alerts...');

        $carAlerts = $this->alertStaleCars();
        $this->info("Generated {$carAlerts} stale car alerts.");

        $clientAlerts = $this->alertClientsWithoutContact();
        $this->info("Generated {$clientAlerts} client alerts.");

        return self::SUCCESS;
    }

    /**
     * Alert on cars that have been in 'Located' or 'Offered' status for more than 30 days.
     */
    private function alertStaleCars(): int
    {
        $threshold = now()->subDays(30);

        $cars = Car::whereIn('status', ['Located', 'Offered', 'Reserved'])
            ->where('updated_at', '<', $threshold)
            ->get();

        $created = 0;
        foreach ($cars as $car) {
            $existing = Alert::where('reference_type', 'car')
                ->where('reference_id', $car->id)
                ->where('alert_type', 'car_stale')
                ->where('resolved', false)
                ->exists();

            if (! $existing) {
                $daysSinceUpdate = $car->updated_at->diffInDays(now());
                Alert::create([
                    'organization_id' => $car->organization_id,
                    'alert_type' => 'car_stale',
                    'reference_type' => 'car',
                    'reference_id' => $car->id,
                    'message' => "Car {$car->brand} {$car->model} has been in '{$car->status}' status for {$daysSinceUpdate} days.",
                    'resolved' => false,
                ]);
                $created++;
            }
        }

        return $created;
    }

    /**
     * Alert on active clients that have not been contacted in the last 14 days.
     */
    private function alertClientsWithoutContact(): int
    {
        $threshold = now()->subDays(14);

        $clients = Client::whereIn('status', ['New', 'Briefing', 'Quote sent', 'Negotiating'])
            ->get();

        $created = 0;
        foreach ($clients as $client) {
            $lastLog = ClientContactLog::where('client_id', $client->id)
                ->orderBy('contact_date', 'desc')
                ->first();

            $lastContact = $lastLog?->contact_date ?? $client->created_at;

            if ($lastContact->lt($threshold)) {
                $existing = Alert::where('reference_type', 'client')
                    ->where('reference_id', $client->id)
                    ->where('alert_type', 'client_no_contact')
                    ->where('resolved', false)
                    ->exists();

                if (! $existing) {
                    $daysSinceContact = $lastContact->diffInDays(now());
                    Alert::create([
                        'organization_id' => $client->organization_id,
                        'alert_type' => 'client_no_contact',
                        'reference_type' => 'client',
                        'reference_id' => $client->id,
                        'message' => "Client {$client->name} has not been contacted in {$daysSinceContact} days.",
                        'resolved' => false,
                    ]);
                    $created++;
                }
            }
        }

        return $created;
    }
}
