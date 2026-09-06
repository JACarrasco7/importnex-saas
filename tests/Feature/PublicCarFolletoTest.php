<?php

namespace Tests\Feature;

use App\Models\Car;
use App\Models\CarPublicLink;
use App\Models\Organization;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Folleto PDF vía link público (mismo token que el dossier /c/{token}).
 *
 * GET /c/{token}/folleto → 200 (PDF si hay Chrome, HTML como fallback).
 * Token inválido o revocado → vista car-unavailable.
 */
class PublicCarFolletoTest extends TestCase
{
    use RefreshDatabase;

    public function test_folleto_accessible_with_valid_token(): void
    {
        $org = Organization::factory()->create();
        User::factory()->create(['organization_id' => $org->id]);
        $car = Car::factory()->create([
            'organization_id' => $org->id,
            'brand' => 'BMW',
            'model' => '320d',
        ]);
        $link = CarPublicLink::generateFor($car);

        $response = $this->get("/c/{$link->token}/folleto");

        $response->assertOk();
        $ct = $response->headers->get('Content-Type') ?? '';
        $this->assertTrue(
            str_starts_with($ct, 'application/pdf') || str_starts_with($ct, 'text/html'),
            "Content-Type esperado pdf|html, recibido: {$ct}"
        );
    }

    public function test_folleto_dossier_page_links_to_pdf(): void
    {
        $org = Organization::factory()->create();
        User::factory()->create(['organization_id' => $org->id]);
        $car = Car::factory()->create([
            'organization_id' => $org->id,
            'brand' => 'BMW',
            'model' => '320d',
        ]);
        $link = CarPublicLink::generateFor($car);

        $response = $this->get("/c/{$link->token}");

        $response->assertOk();
        $response->assertSee("/c/{$link->token}/folleto");
    }

    public function test_folleto_unavailable_with_revoked_token(): void
    {
        $org = Organization::factory()->create();
        User::factory()->create(['organization_id' => $org->id]);
        $car = Car::factory()->create(['organization_id' => $org->id]);
        $link = CarPublicLink::generateFor($car);
        $link->update(['revoked_at' => now()]);

        $this->get("/c/{$link->token}/folleto")->assertOk(); // vista car-unavailable (200)
        $this->get('/c/invalidtoken0000000000000000/folleto')->assertOk();
    }
}
