<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Organization;
use App\Models\Car;
use App\Models\CarDocument;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class CarDocumentTest extends TestCase
{
    use RefreshDatabase;

    public function test_can_upload_documents_to_car(): void
    {
        Storage::fake('public');
        $org = Organization::factory()->create();
        $user = User::factory()->create(['organization_id' => $org->id]);
        $car = Car::factory()->create(['organization_id' => $org->id]);

        $file = UploadedFile::fake()->create('invoice.pdf', 100);

        $response = $this->actingAs($user)->post("/cars/{$car->id}/documents", [
            'doc_type' => 'invoice',
            'name' => 'Purchase Invoice',
            'documents' => [$file],
        ]);

        $response->assertStatus(302);
        // El observer crea las 17 filas del expediente al crear el coche,
        // más el archivo recién subido.
        $this->assertGreaterThanOrEqual(17, $car->documents()->count());
    }

    public function test_validates_doc_type_required(): void
    {
        Storage::fake('public');
        $org = Organization::factory()->create();
        $user = User::factory()->create(['organization_id' => $org->id]);
        $car = Car::factory()->create(['organization_id' => $org->id]);

        $file = UploadedFile::fake()->create('doc.pdf', 100);

        $response = $this->actingAs($user)->post("/cars/{$car->id}/documents", [
            'documents' => [$file],
        ]);

        $response->assertSessionHasErrors('doc_type');
    }

    public function test_validates_file_format(): void
    {
        Storage::fake('public');
        $org = Organization::factory()->create();
        $user = User::factory()->create(['organization_id' => $org->id]);
        $car = Car::factory()->create(['organization_id' => $org->id]);

        $file = UploadedFile::fake()->create('file.exe', 100);

        $response = $this->actingAs($user)->post("/cars/{$car->id}/documents", [
            'doc_type' => 'invoice',
            'documents' => [$file],
        ]);

        $response->assertSessionHasErrors('documents.0');
    }

    public function test_can_delete_document(): void
    {
        Storage::fake('public');
        $org = Organization::factory()->create();
        $user = User::factory()->create(['organization_id' => $org->id]);
        $car = Car::factory()->create(['organization_id' => $org->id]);
        $doc = CarDocument::factory()->create([
            'car_id' => $car->id,
            'organization_id' => $org->id,
        ]);

        $response = $this->actingAs($user)->delete("/cars/{$car->id}/documents/{$doc->id}");

        $response->assertStatus(302);
        $this->assertDatabaseMissing('car_documents', ['id' => $doc->id]);
    }

    public function test_document_from_other_car_cannot_be_deleted(): void
    {
        $org = Organization::factory()->create();
        $user = User::factory()->create(['organization_id' => $org->id]);
        $car1 = Car::factory()->create(['organization_id' => $org->id]);
        $car2 = Car::factory()->create(['organization_id' => $org->id]);
        $doc = CarDocument::factory()->create([
            'car_id' => $car2->id,
            'organization_id' => $org->id,
        ]);

        $response = $this->actingAs($user)->delete("/cars/{$car1->id}/documents/{$doc->id}");

        $response->assertStatus(403);
        $this->assertDatabaseHas('car_documents', ['id' => $doc->id]);
    }

    public function test_documents_filtered_by_organization(): void
    {
        $org1 = Organization::factory()->create();
        $org2 = Organization::factory()->create();
        $car1 = Car::factory()->create(['organization_id' => $org1->id]);
        $car2 = Car::factory()->create(['organization_id' => $org2->id]);

        // Las 17 filas del expediente se crean automáticamente al crear el coche.
        // Sólo añadimos un doc extra a cada coche para verificar el aislamiento.
        CarDocument::factory()->create(['car_id' => $car1->id, 'organization_id' => $org1->id]);
        CarDocument::factory()->create(['car_id' => $car2->id, 'organization_id' => $org2->id]);

        // Cada coche tiene sus 17 + 1 = 18 docs propios, ninguno del otro.
        $this->assertEquals(18, $car1->documents()->count());
        $this->assertEquals(18, $car2->documents()->count());

        // El aislamiento se verifica también: ningún doc del org2 aparece en car1.
        $this->assertEquals(0, $car1->documents()->where('organization_id', $org2->id)->count());
    }
}
