<?php

namespace Tests\Feature\ImportOrders;

use App\Models\Client;
use App\Models\Organization;
use App\Models\User;
use App\Support\SpanishId;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ClientContactFieldsTest extends TestCase
{
    use RefreshDatabase;

    public function test_client_contact_fields_are_saved_normalized_and_dni_is_encrypted(): void
    {
        $org = Organization::factory()->create();
        $user = User::factory()->create(['organization_id' => $org->id]);
        $client = Client::factory()->create(['organization_id' => $org->id]);

        $payload = [
            'name' => 'Pedro Ruiz', 'status' => 'New', 'email' => 'Pedro@Example.COM', 'phone' => '600 123 456',
            'preferred_channel' => 'whatsapp',
        ];

        $this->actingAs($user)->patch(route('clients.update', $client->id), [...$payload, 'dni' => '12345678A'])
            ->assertSessionHasErrors('dni');

        $this->actingAs($user)->patch(route('clients.update', $client->id), [...$payload, 'dni' => '12.345.678-z'])
            ->assertSessionHasNoErrors();

        $client->refresh();
        $this->assertSame('pedro@example.com', $client->email);
        $this->assertSame('12345678Z', $client->dni);
        $this->assertSame('34600123456', $client->whatsappNumber());
        $this->assertSame('*****678Z', $client->maskedDni());
        $this->assertStringNotContainsString('12345678Z', (string) \DB::table('clients')->where('id', $client->id)->value('dni'));
        $this->assertArrayNotHasKey('dni', $client->toArray());
    }

    public function test_spanish_id_validation(): void
    {
        $this->assertTrue(SpanishId::isValid('12345678Z'));
        $this->assertTrue(SpanishId::isValid('x1234567l'));
        $this->assertFalse(SpanishId::isValid('12345678A'));
        $this->assertFalse(SpanishId::isValid('ABC'));
    }
}
