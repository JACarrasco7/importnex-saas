<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Organization;
use App\Models\Contact;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ContactCrudTest extends TestCase
{
    use RefreshDatabase;

    private $user;

    protected function setUp(): void
    {
        parent::setUp();
        $org = Organization::factory()->create();
        $this->user = User::factory()->create([
            'organization_id' => $org->id,
            'role' => 'owner',
        ]);
        $this->actingAs($this->user);
    }

    public function test_can_create_contact(): void
    {
        $response = $this->post('/contacts', [
            'name' => 'Autohaus Müller',
            'phone' => '+49 30 12345678',
            'email' => 'info@autohaus.de',
            'city' => 'Berlin',
            'tags' => ['seller', 'verified'],
        ]);

        $response->assertRedirect('/contacts');
        $this->assertDatabaseHas('contacts', [
            'name' => 'Autohaus Müller',
            'organization_id' => $this->user->organization_id,
        ]);
    }

    public function test_can_view_contacts_list(): void
    {
        Contact::factory()->create([
            'organization_id' => $this->user->organization_id,
        ]);

        $response = $this->get('/contacts');

        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => $page
            ->component('Contacts/Index')
            ->has('contacts')
        );
    }

    public function test_can_update_contact(): void
    {
        $contact = Contact::factory()->create([
            'organization_id' => $this->user->organization_id,
        ]);

        $response = $this->patch("/contacts/{$contact->id}", [
            'name' => 'Updated Name',
            'phone' => '+49 30 87654321',
            'email' => 'new@example.com',
            'city' => 'Munich',
        ]);

        $response->assertRedirect('/contacts');
        $this->assertDatabaseHas('contacts', [
            'id' => $contact->id,
            'name' => 'Updated Name',
        ]);
    }

    public function test_can_delete_contact(): void
    {
        $contact = Contact::factory()->create([
            'organization_id' => $this->user->organization_id,
        ]);

        $response = $this->delete("/contacts/{$contact->id}");

        $response->assertRedirect('/contacts');
        $this->assertSoftDeleted('contacts', ['id' => $contact->id]);
    }

    public function test_validates_required_name(): void
    {
        $response = $this->post('/contacts', [
            'phone' => '+34 600',
        ]);

        $response->assertSessionHasErrors('name');
    }
}
