<?php

namespace Tests\Feature;

use App\Models\Organization;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Guías internas del panel (sección "Guía" del menú lateral).
 *
 * El sidebar traía esta sección comentada porque la ruta `guide.index` no existía
 * (Ziggy lanzaba error). Estos tests cubren la ruta nueva.
 */
class GuideControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_un_invitado_no_puede_ver_las_guias(): void
    {
        $this->get('/guias')->assertRedirect('/login');
    }

    public function test_el_indice_lista_las_guias_y_abre_la_primera(): void
    {
        $response = $this->actingAs($this->usuario())->get('/guias');

        $response->assertOk();
        // El número se cuenta del array GUIAS en GuideController — si añades una
        // guía, este test obliga a actualizarlo. La alternativa sería ->
        // has('guias', count(GuideController::GUIAS)) pero PHP no expone
        // constantes privadas a tests, así que el literal es lo más estable.
        $response->assertInertia(fn ($page) => $page
            ->component('Guide/Index')
            ->has('guias', 11)
            ->has('actual.slug')
            ->has('actual.titulo')
            ->has('actual.html'));
    }

    public function test_abre_una_guia_concreta_por_su_slug(): void
    {
        $response = $this->actingAs($this->usuario())->get('/guias/flujo-c-mercado');

        $response->assertOk();
        $response->assertInertia(fn ($page) => $page
            ->component('Guide/Index')
            ->where('actual.slug', 'flujo-c-mercado'));
    }

    public function test_un_slug_desconocido_da_404(): void
    {
        $this->actingAs($this->usuario())->get('/guias/no-existe-esta-guia')->assertNotFound();
    }

    public function test_la_guia_se_convierte_a_html_con_su_titulo(): void
    {
        $response = $this->actingAs($this->usuario())->get('/guias/guia-de-uso');

        $response->assertOk();
        $response->assertInertia(fn ($page) => $page
            ->where('actual.slug', 'guia-de-uso')
            ->where('actual.titulo', fn (string $titulo) => str_contains($titulo, 'JJ Import Motors'))
            // El markdown se convierte en HTML en el servidor.
            ->where('actual.html', fn (string $html) => str_contains($html, '<h2') && str_contains($html, '<strong>')));
    }

    public function test_el_indice_no_permite_salirse_del_whitelist(): void
    {
        // Intento de path traversal: el slug se traduce por whitelist, no a una ruta del disco.
        $this->actingAs($this->usuario())->get('/guias/..%2F.env')->assertNotFound();
    }

    private function usuario(): User
    {
        $organizacion = Organization::factory()->create();

        return User::factory()->create(['organization_id' => $organizacion->id]);
    }
}
