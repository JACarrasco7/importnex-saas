<?php

namespace Tests\Feature\Audit;

use App\Models\Car;
use App\Models\CarDocument;
use App\Models\Organization;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * Auditoría de venta (2.8): los documentos del coche (DNI, contratos,
 * facturas, informes internos) no pueden tener URL pública.
 */
class PrivateDocumentsTest extends TestCase
{
    use RefreshDatabase;

    private Organization $org;

    private User $user;

    private Car $car;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('local');
        Storage::fake('public');

        $this->org = Organization::factory()->create();
        $this->user = User::factory()->create(['organization_id' => $this->org->id, 'role' => 'owner']);
        $this->car = Car::factory()->create(['organization_id' => $this->org->id]);
    }

    public function test_uploaded_documents_go_to_the_private_disk(): void
    {
        $this->actingAs($this->user)->post(route('cars.documents.store', $this->car), [
            'doc_key' => 'cliente_dni',
            'documents' => [UploadedFile::fake()->image('dni.jpg')],
        ])->assertRedirect();

        $document = CarDocument::query()->where('car_id', $this->car->id)->whereNotNull('url')->latest('id')->firstOrFail();

        Storage::disk('local')->assertExists($document->url);
        Storage::disk('public')->assertMissing($document->url);
        $this->assertSame('local', $document->storageDisk());
    }

    public function test_documents_are_served_only_to_the_same_company(): void
    {
        Storage::disk('local')->put('cars/'.$this->car->id.'/documents/dni.pdf', '%PDF-1.4 dni');
        $document = CarDocument::factory()->create([
            'car_id' => $this->car->id, 'organization_id' => $this->org->id, 'url' => 'cars/'.$this->car->id.'/documents/dni.pdf',
        ]);

        $this->get(route('cars.documents.show', [$this->car, $document]))->assertRedirect(route('login'));

        $this->actingAs($this->user)->get(route('cars.documents.show', [$this->car, $document]))->assertOk();

        $stranger = User::factory()->create(['organization_id' => Organization::factory()->create()->id]);
        $status = $this->actingAs($stranger)->get(route('cars.documents.show', [$this->car, $document]))->status();
        $this->assertContains($status, [403, 404]);
    }

    public function test_legacy_public_documents_are_still_served_and_moved_by_the_migration(): void
    {
        $path = 'cars/'.$this->car->id.'/documents/informe-interno.pdf';
        Storage::disk('public')->put($path, '%PDF-1.4 interno');
        $document = CarDocument::factory()->create(['car_id' => $this->car->id, 'organization_id' => $this->org->id, 'url' => $path]);

        $this->assertSame('public', $document->storageDisk());
        $this->actingAs($this->user)->get(route('cars.documents.show', [$this->car, $document]))->assertOk();

        (require database_path('migrations/2026_10_23_100100_move_car_documents_to_private_disk.php'))->up();

        Storage::disk('public')->assertMissing($path);
        Storage::disk('local')->assertExists($path);
        $this->assertSame('local', $document->fresh()->storageDisk());
    }

    public function test_deleting_a_document_removes_the_private_file(): void
    {
        $path = 'cars/'.$this->car->id.'/documents/factura.pdf';
        Storage::disk('local')->put($path, '%PDF-1.4');
        $document = CarDocument::factory()->create(['car_id' => $this->car->id, 'organization_id' => $this->org->id, 'url' => $path]);

        $this->actingAs($this->user)->delete(route('cars.documents.destroy', [$this->car, $document]))->assertRedirect();

        Storage::disk('local')->assertMissing($path);
    }
}
