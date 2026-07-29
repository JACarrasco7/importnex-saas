<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Organization;
use App\Models\Car;
use App\Models\CarPhoto;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class CarPhotoTest extends TestCase
{
    use RefreshDatabase;

    public function test_can_list_car_photos(): void
    {
        $org = Organization::factory()->create();
        $user = User::factory()->create(['organization_id' => $org->id]);
        $car = Car::factory()->create(['organization_id' => $org->id]);

        CarPhoto::factory()->count(3)->create([
            'car_id' => $car->id,
            'organization_id' => $org->id,
        ]);

        $this->assertEquals(3, $car->photos()->count());
    }

    public function test_can_upload_photos_to_car(): void
    {
        Storage::fake('public');
        $org = Organization::factory()->create();
        $user = User::factory()->create(['organization_id' => $org->id]);
        $car = Car::factory()->create(['organization_id' => $org->id]);

        $file = UploadedFile::fake()->image('car.jpg');

        $response = $this->actingAs($user)->post("/cars/{$car->id}/photos", [
            'photo_type' => 'exterior',
            'photos' => [$file],
        ]);

        $response->assertStatus(302);
        $this->assertDatabaseCount('car_photos', 1);
    }

    public function test_validates_photo_type_required(): void
    {
        Storage::fake('public');
        $org = Organization::factory()->create();
        $user = User::factory()->create(['organization_id' => $org->id]);
        $car = Car::factory()->create(['organization_id' => $org->id]);

        $file = UploadedFile::fake()->image('car.jpg');

        $response = $this->actingAs($user)->post("/cars/{$car->id}/photos", [
            'photos' => [$file],
        ]);

        $response->assertSessionHasErrors('photo_type');
    }

    public function test_validates_files_are_images(): void
    {
        Storage::fake('public');
        $org = Organization::factory()->create();
        $user = User::factory()->create(['organization_id' => $org->id]);
        $car = Car::factory()->create(['organization_id' => $org->id]);

        $file = UploadedFile::fake()->create('document.pdf', 100);

        $response = $this->actingAs($user)->post("/cars/{$car->id}/photos", [
            'photo_type' => 'exterior',
            'photos' => [$file],
        ]);

        $response->assertSessionHasErrors('photos.0');
    }

    public function test_can_delete_photo(): void
    {
        Storage::fake('public');
        $org = Organization::factory()->create();
        $user = User::factory()->create(['organization_id' => $org->id]);
        $car = Car::factory()->create(['organization_id' => $org->id]);
        $photo = CarPhoto::factory()->create([
            'car_id' => $car->id,
            'organization_id' => $org->id,
        ]);

        $response = $this->actingAs($user)->delete("/cars/{$car->id}/photos/{$photo->id}");

        $response->assertStatus(302);
        $this->assertDatabaseMissing('car_photos', ['id' => $photo->id]);
    }

    public function test_photo_from_other_car_cannot_be_deleted(): void
    {
        $org = Organization::factory()->create();
        $user = User::factory()->create(['organization_id' => $org->id]);
        $car1 = Car::factory()->create(['organization_id' => $org->id]);
        $car2 = Car::factory()->create(['organization_id' => $org->id]);
        $photo = CarPhoto::factory()->create([
            'car_id' => $car2->id,
            'organization_id' => $org->id,
        ]);

        $response = $this->actingAs($user)->delete("/cars/{$car1->id}/photos/{$photo->id}");

        $response->assertStatus(403);
        $this->assertDatabaseHas('car_photos', ['id' => $photo->id]);
    }

    public function test_photos_filtered_by_organization(): void
    {
        $org1 = Organization::factory()->create();
        $org2 = Organization::factory()->create();
        $car1 = Car::factory()->create(['organization_id' => $org1->id]);
        $car2 = Car::factory()->create(['organization_id' => $org2->id]);

        CarPhoto::factory()->create(['car_id' => $car1->id, 'organization_id' => $org1->id]);
        CarPhoto::factory()->create(['car_id' => $car2->id, 'organization_id' => $org2->id]);

        $this->assertEquals(1, $car1->photos()->count());
        $this->assertEquals(1, $car2->photos()->count());
    }
}
