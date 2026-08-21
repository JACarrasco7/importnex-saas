<?php

namespace Tests\Feature\PublicContract;

use App\Models\Car;
use App\Models\Client;
use App\Models\ContractAcceptance;
use App\Models\Organization;
use App\Models\User;
use App\Support\ChromePath;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ShowTest extends TestCase
{
    use RefreshDatabase;

    public function test_show_renders_for_valid_token(): void
    {
        [$contract] = $this->makeContract();
        $this->get(route('public.contract.show', $contract->public_token))
            ->assertOk()
            ->assertSee('CONTRATO')
            ->assertSee(config('contracts.prestador.razon_social'));
    }

    public function test_show_404_for_unknown_token(): void
    {
        $this->get(route('public.contract.show', 'NONEXISTENT_TOKEN_LONG_ENOUGH_TO_PASS_ROUTING'))
            ->assertNotFound();
    }

    public function test_accept_records_hash_ip_ua(): void
    {
        [$contract] = $this->makeContract();

        $response = $this->withHeaders([
            'Accept' => 'application/json',
            'User-Agent' => 'Mozilla/5.0 test',
        ])->post(route('public.contract.accept', $contract->public_token), [
            'accept' => '1',
            'client_name' => 'Juan Pérez',
            'client_dni' => '12345678A',
        ]);

        $response->assertOk()->assertJson(['ok' => true]);

        $contract->refresh();
        $this->assertNotNull($contract->accepted_at);
        $this->assertSame('Juan Pérez', $contract->client_name);
        $this->assertSame('12345678A', $contract->client_dni);
        $this->assertSame('127.0.0.1', $contract->accepted_ip);
        $this->assertSame('Mozilla/5.0 test', $contract->user_agent);
        $this->assertSame(64, strlen($contract->contract_hash));
        $this->assertSame(ContractAcceptance::hashContract($contract->getContractText()), $contract->contract_hash);
        // A1: el snapshot queda congelado con los datos del firmante, y el texto
        // firmado (que genera el hash) contiene ese nombre, no un placeholder '—'.
        $this->assertSame('Juan Pérez', $contract->snapshot['cliente_nombre']);
        $this->assertSame('12345678A', $contract->snapshot['cliente_dni']);
        $this->assertStringContainsString('Juan Pérez', $contract->getContractText());
        // A2: las cláusulas quedan congeladas en el snapshot.
        $this->assertNotEmpty($contract->snapshot['_clausulas']);
    }

    public function test_accept_is_idempotent_409(): void
    {
        [$contract] = $this->makeContract();
        $this->post(route('public.contract.accept', $contract->public_token), ['accept' => '1'])
            ->assertOk();

        $this->post(route('public.contract.accept', $contract->public_token), ['accept' => '1'])
            ->assertStatus(409);
    }

    public function test_accept_requires_checkbox(): void
    {
        [$contract] = $this->makeContract();

        $this->postJson(route('public.contract.accept', $contract->public_token), [])
            ->assertStatus(422);
    }

    public function test_pdf_blocked_until_signed(): void
    {
        [$contract] = $this->makeContract();

        $this->get(route('public.contract.pdf', $contract->public_token))->assertForbidden();
    }

    public function test_pdf_returned_after_signed_skip_in_ci(): void
    {
        // En CI / entorno sin Chrome el Browsershot lanza RuntimeException.
        // Se cubre manualmente en local con `php artisan serve` + curl.
        if (! ChromePath::resolve()) {
            $this->markTestSkipped('Chrome no disponible para generar PDF en este entorno.');
        }

        [$contract] = $this->makeContract();
        $this->post(route('public.contract.accept', $contract->public_token), ['accept' => '1'])
            ->assertOk();

        $this->get(route('public.contract.pdf', $contract->public_token))
            ->assertOk()
            ->assertHeader('content-type', 'application/pdf');
    }

    /**
     * @return array{0: ContractAcceptance}
     */
    private function makeContract(): array
    {
        $org = Organization::factory()->create();
        $user = User::factory()->create(['organization_id' => $org->id, 'role' => 'owner']);
        $client = Client::factory()->create([
            'organization_id' => $org->id,
            'name' => 'Cliente Test',
        ]);
        $car = Car::factory()->create([
            'organization_id' => $org->id,
            'client_id' => $client->id,
            'brand' => 'Audi',
            'model' => 'A3',
            'year' => '2022',
            'purchase_price' => 18000,
        ]);
        $contract = ContractAcceptance::create([
            'organization_id' => $car->organization_id,
            'car_id' => $car->id,
            'public_token' => ContractAcceptance::generateToken(),
            'contract_version' => (string) config('contracts.version'),
            'contract_hash' => '',
            'snapshot' => [
                'cliente_nombre' => $client->name,
                'cliente_email' => 'cliente@example.com',
                'cliente_dni' => '12345678A',
                'vehiculo_marca' => $car->brand,
                'vehiculo_modelo' => $car->model,
                'vehiculo_anio' => $car->year,
                'vehiculo_vin' => '—',
                'precio_total' => number_format((float) $car->purchase_price, 0, ',', '.'),
                'honorarios' => '1.500',
                'fecha_firma' => now()->format('d/m/Y H:i'),
                'contrato_id' => 'JJIM-TEST',
                // Congeladas, igual que ContractService::create().
                '_clausulas' => config('contracts.clausulas'),
                '_prestador' => config('contracts.prestador'),
            ],
            'client_email' => 'cliente@example.com',
            'client_name' => $client->name,
            'client_dni' => '12345678A',
            'accepted_at' => null,
            'accepted_ip' => '0.0.0.0',
            'user_agent' => null,
            'locale' => 'es',
        ]);

        return [$contract];
    }
}
