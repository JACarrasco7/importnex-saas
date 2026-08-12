<?php

namespace Tests\Feature;

use App\Models\Car;
use App\Models\Organization;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class BriefingPdfApiTest extends TestCase
{
    use RefreshDatabase;

    protected string $token;

    protected Organization $org;

    protected function setUp(): void
    {
        parent::setUp();

        $this->token = 'test-token-'.bin2hex(random_bytes(16));
        config(['services.importnex_chat.token' => $this->token]);
        $this->org = Organization::factory()->create(['name' => 'JJ Import Motors']);
    }

    // ────────────────────────────────────────────────────────────────────────
    // POST /api/cars/{car}/briefing-pdf
    // ────────────────────────────────────────────────────────────────────────

    public function test_requires_token(): void
    {
        $car = Car::factory()->create(['organization_id' => $this->org->id]);

        $this->postJson("/api/cars/{$car->id}/briefing-pdf")
            ->assertStatus(401);
    }

    public function test_attaches_valid_pdf(): void
    {
        Storage::fake('local');

        $car = Car::factory()->create(['organization_id' => $this->org->id]);

        $file = UploadedFile::fake()->create('briefing.pdf', 200, 'application/pdf');

        $response = $this->withHeader('X-Import-Token', $this->token)
            ->post("/api/cars/{$car->id}/briefing-pdf", [
                'file' => $file,
            ]);

        $response->assertStatus(200)
            ->assertJson([
                'status' => 'attached',
                'car_id' => $car->id,
            ]);

        $path = $response->json('path');
        Storage::disk('local')->assertExists($path);
    }

    public function test_rejects_non_pdf(): void
    {
        $car = Car::factory()->create(['organization_id' => $this->org->id]);

        $file = UploadedFile::fake()->create('briefing.txt', 200, 'text/plain');

        $this->withHeader('X-Import-Token', $this->token)
            ->post("/api/cars/{$car->id}/briefing-pdf", [
                'file' => $file,
            ])
            ->assertStatus(422)
            ->assertJson(['error' => 'Only PDF files are accepted.']);
    }

    public function test_rejects_file_too_large(): void
    {
        $car = Car::factory()->create(['organization_id' => $this->org->id]);

        $file = UploadedFile::fake()->create('briefing.pdf', 11 * 1024, 'application/pdf'); // 11MB

        $this->withHeader('X-Import-Token', $this->token)
            ->post("/api/cars/{$car->id}/briefing-pdf", [
                'file' => $file,
            ])
            ->assertStatus(422)
            ->assertJson(['error' => 'File too large. Maximum size is 10MB.']);
    }

    public function test_rejects_missing_file(): void
    {
        $car = Car::factory()->create(['organization_id' => $this->org->id]);

        $this->withHeader('X-Import-Token', $this->token)
            ->postJson("/api/cars/{$car->id}/briefing-pdf", [])
            ->assertStatus(422)
            ->assertJson(['error' => 'Missing "file" in multipart body.']);
    }
}
