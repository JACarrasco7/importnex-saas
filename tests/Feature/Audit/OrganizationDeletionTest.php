<?php

namespace Tests\Feature\Audit;

use App\Models\Car;
use App\Models\Client;
use App\Models\ImportOrder;
use App\Models\Organization;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * Auditoría de venta (1.14): una empresa puede darse de baja y borrar todos
 * sus datos, y el último propietario no puede dejarla huérfana.
 */
class OrganizationDeletionTest extends TestCase
{
    use RefreshDatabase;

    public function test_owner_deletes_the_company_and_all_its_data(): void
    {
        Storage::fake('local');
        Storage::fake('public');

        $org = Organization::factory()->create(['name' => 'Borrame SL']);
        $owner = User::factory()->create(['organization_id' => $org->id, 'role' => 'owner', 'password' => bcrypt('secreta-123')]);
        $operator = User::factory()->create(['organization_id' => $org->id, 'role' => 'operator']);
        $car = Car::factory()->create(['organization_id' => $org->id]);
        $client = Client::factory()->create(['organization_id' => $org->id]);
        $order = ImportOrder::factory()->create(['organization_id' => $org->id]);
        Storage::disk('public')->put("cars/{$car->id}/photo.jpg", 'x');
        Storage::disk('local')->put("cars/{$car->id}/documents/dni.pdf", 'x');
        Storage::disk('local')->put("orders/{$org->id}/{$order->id}/foto.jpg", 'x');

        $other = Organization::factory()->create();
        $otherCar = Car::factory()->create(['organization_id' => $other->id]);

        $this->actingAs($owner)
            ->delete(route('organization.destroy', $org), ['confirm_name' => 'Borrame SL', 'password' => 'secreta-123'])
            ->assertRedirect('/');

        $this->assertGuest();
        $this->assertDatabaseMissing('organizations', ['id' => $org->id]);
        $this->assertDatabaseMissing('users', ['id' => $owner->id]);
        $this->assertDatabaseMissing('users', ['id' => $operator->id]);
        $this->assertDatabaseMissing('cars', ['id' => $car->id]);
        $this->assertDatabaseMissing('clients', ['id' => $client->id]);
        $this->assertDatabaseMissing('import_orders', ['id' => $order->id]);
        $this->assertSame(0, DB::table('car_documents')->where('car_id', $car->id)->count());
        $this->assertSame(0, DB::table('car_checklists')->where('car_id', $car->id)->count());
        Storage::disk('public')->assertMissing("cars/{$car->id}/photo.jpg");
        Storage::disk('local')->assertMissing("cars/{$car->id}/documents/dni.pdf");
        Storage::disk('local')->assertMissing("orders/{$org->id}/{$order->id}/foto.jpg");

        // Las demás empresas no se tocan.
        $this->assertDatabaseHas('organizations', ['id' => $other->id]);
        $this->assertDatabaseHas('cars', ['id' => $otherCar->id]);
        $this->assertGreaterThan(0, DB::table('car_documents')->where('car_id', $otherCar->id)->count());
    }

    public function test_deletion_needs_the_exact_name_and_password(): void
    {
        $org = Organization::factory()->create(['name' => 'Borrame SL']);
        $owner = User::factory()->create(['organization_id' => $org->id, 'role' => 'owner', 'password' => bcrypt('secreta-123')]);

        $this->actingAs($owner)
            ->delete(route('organization.destroy', $org), ['confirm_name' => 'borrame', 'password' => 'secreta-123'])
            ->assertSessionHasErrors('confirm_name');
        $this->actingAs($owner)
            ->delete(route('organization.destroy', $org), ['confirm_name' => 'Borrame SL', 'password' => 'mala'])
            ->assertSessionHasErrors('password');

        $this->assertDatabaseHas('organizations', ['id' => $org->id]);
    }

    public function test_operators_and_other_companies_cannot_delete_it(): void
    {
        $org = Organization::factory()->create(['name' => 'Borrame SL']);
        $operator = User::factory()->create(['organization_id' => $org->id, 'role' => 'operator', 'password' => bcrypt('secreta-123')]);
        $stranger = User::factory()->create(['organization_id' => Organization::factory()->create()->id, 'role' => 'owner', 'password' => bcrypt('secreta-123')]);

        $this->actingAs($operator)
            ->delete(route('organization.destroy', $org), ['confirm_name' => 'Borrame SL', 'password' => 'secreta-123'])
            ->assertForbidden();
        $this->actingAs($stranger)
            ->delete(route('organization.destroy', $org), ['confirm_name' => 'Borrame SL', 'password' => 'secreta-123'])
            ->assertForbidden();

        $this->assertDatabaseHas('organizations', ['id' => $org->id]);
    }

    public function test_platform_organization_cannot_be_deleted(): void
    {
        $org = Organization::factory()->create(['name' => 'Operadora', 'is_owner' => true]);
        $owner = User::factory()->create(['organization_id' => $org->id, 'role' => 'owner', 'password' => bcrypt('secreta-123')]);

        $this->actingAs($owner)
            ->delete(route('organization.destroy', $org), ['confirm_name' => 'Operadora', 'password' => 'secreta-123'])
            ->assertSessionHasErrors('confirm_name');

        $this->assertDatabaseHas('organizations', ['id' => $org->id]);
    }

    public function test_last_owner_cannot_delete_their_account_and_orphan_the_company(): void
    {
        $org = Organization::factory()->create();
        $owner = User::factory()->create(['organization_id' => $org->id, 'role' => 'owner', 'password' => bcrypt('secreta-123')]);

        $this->actingAs($owner)
            ->delete(route('profile.destroy'), ['password' => 'secreta-123'])
            ->assertSessionHasErrors('password');

        $this->assertDatabaseHas('users', ['id' => $owner->id]);
    }

    public function test_an_owner_can_leave_when_another_owner_remains(): void
    {
        $org = Organization::factory()->create();
        $owner = User::factory()->create(['organization_id' => $org->id, 'role' => 'owner', 'password' => bcrypt('secreta-123')]);
        User::factory()->create(['organization_id' => $org->id, 'role' => 'owner']);

        $this->actingAs($owner)
            ->delete(route('profile.destroy'), ['password' => 'secreta-123'])
            ->assertRedirect('/');

        $this->assertDatabaseMissing('users', ['id' => $owner->id]);
    }
}
