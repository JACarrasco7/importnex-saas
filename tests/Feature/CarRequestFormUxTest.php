<?php

namespace Tests\Feature;

use App\Models\Organization;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Verifica que el formulario público de solicitud de coche responde
 * con la página Inertia correcta y entrega la organización esperada.
 *
 * La persistencia en localStorage, el honeypot, el progress indicator
 * y el error summary son lógica JS-only que se valida con tests E2E /
 * navegador; aquí solo garantizamos el contrato server-side.
 */
class CarRequestFormUxTest extends TestCase
{
    use RefreshDatabase;

    public function test_formulario_renderiza_pagina_inertia_correcta(): void
    {
        $org = Organization::factory()->create(['slug' => 'jjimport-test', 'is_public' => true]);

        $this->get(route('public.car-request.index', $org->slug))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('Public/CarRequestForm')
                ->has('organization')
                ->where('organization.slug', 'jjimport-test')
            );
    }

    public function test_organizacion_inexistente_devuelve_404(): void
    {
        // Garantiza que la ruta por slug responde 404 si la organización
        // no existe.
        $this->get(route('public.car-request.index', 'no-existe'))
            ->assertNotFound();
    }
}
