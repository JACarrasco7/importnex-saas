<?php

namespace Tests\Feature\Audit;

use App\Models\Organization;
use App\Services\BrochureRenderer;
use App\Support\Brand;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * Auditoría de venta (6.2): cada empresa tiene su folleto PDF con su marca,
 * su contacto y sus honorarios, no los de JJ Import Motors.
 */
class CompanyBrochureTest extends TestCase
{
    use RefreshDatabase;

    private Organization $company;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('local');

        Organization::factory()->create(['name' => 'Operadora', 'slug' => 'operadora', 'is_public' => true, 'is_owner' => true]);
        $this->company = Organization::factory()->create([
            'name' => 'Importaciones Sur', 'slug' => 'importaciones-sur', 'is_public' => true,
            'trial_ends_at' => now()->addDays(10),
            'company' => ['phone' => '699888777', 'email' => 'hola@sur.test', 'fee_base' => '900'],
        ]);
    }

    private function fakeRenderer(): void
    {
        $this->app->bind(BrochureRenderer::class, fn () => new class extends BrochureRenderer
        {
            protected function makePdf(string $html, string $target): void
            {
                file_put_contents($target, '%PDF-1.4 '.$html);
            }
        });
    }

    public function test_brochure_html_uses_the_company_data(): void
    {
        $html = app(BrochureRenderer::class)->html($this->company);

        $this->assertStringContainsString('Importaciones Sur', $html);
        $this->assertStringContainsString('699888777', $html);
        $this->assertStringContainsString('hola@sur.test', $html);
        $this->assertStringContainsString('900 €', $html);
        $this->assertStringNotContainsString('1.500', $html);
        $this->assertStringNotContainsString('JJ Import', $html);
    }

    public function test_company_without_fee_label_does_not_inherit_the_default_price_text(): void
    {
        $org = Organization::factory()->create(['company' => []]);

        $this->assertSame('desde 1.000 €', Brand::for($org)->feeLabel());
        $this->assertSame('900 €', Brand::for($this->company)->feeLabel());
    }

    public function test_brochure_is_served_as_pdf_and_cached_until_data_changes(): void
    {
        $this->fakeRenderer();

        $this->get(route('site.brochure', ['site' => 'importaciones-sur']))
            ->assertOk()
            ->assertHeader('Content-Type', 'application/pdf');
        $this->assertCount(1, Storage::disk('local')->files('brochures'));

        $this->get(route('site.brochure', ['site' => 'importaciones-sur']))->assertOk();
        $this->assertCount(1, Storage::disk('local')->files('brochures'));

        $this->company->update(['company' => [...$this->company->company, 'fee_base' => '1200']]);
        $this->get(route('site.brochure', ['site' => 'importaciones-sur']))->assertOk();
        $this->assertCount(1, Storage::disk('local')->files('brochures'));
    }

    public function test_brochure_is_hidden_when_the_site_is_off(): void
    {
        $this->company->update(['is_public' => false]);

        $this->get(route('site.brochure', ['site' => 'importaciones-sur']))->assertNotFound();
    }

    public function test_site_links_to_its_own_brochure(): void
    {
        $this->get('/web/importaciones-sur')
            ->assertOk()
            ->assertInertia(fn ($page) => $page->where('marketplace.brochureUrl', route('site.brochure', ['site' => 'importaciones-sur'])));
    }
}
