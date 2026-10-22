<?php

namespace Tests\Feature\ImportOrders;

use App\Enums\ImportOrderState;
use App\Enums\PaymentStatus;
use App\Models\Car;
use App\Models\Client;
use App\Models\ImportOrder;
use App\Models\OrderMedia;
use App\Models\Organization;
use App\Models\User;
use App\Services\ImportOrders\OrderPaymentService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

/** Panel interno de encargos: tenancy, alta, ficha y acciones principales. */
class ImportOrderPanelTest extends TestCase
{
    use RefreshDatabase;

    private Organization $org;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();
        Mail::fake();
        Storage::fake('local');

        $this->org = Organization::factory()->create();
        $this->user = User::factory()->create(['organization_id' => $this->org->id, 'role' => 'owner']);
    }

    private function order(array $attributes = []): ImportOrder
    {
        return ImportOrder::factory()->withPortal()->create(['organization_id' => $this->org->id, ...$attributes]);
    }

    public function test_store_creates_an_order_from_a_car_with_client(): void
    {
        $client = Client::factory()->create(['organization_id' => $this->org->id, 'email' => 'a@b.com']);
        $car = Car::factory()->create(['organization_id' => $this->org->id, 'client_id' => $client->id]);

        $response = $this->actingAs($this->user)->post(route('import-orders.store'), ['car_id' => $car->id]);

        $order = ImportOrder::query()->where('car_id', $car->id)->firstOrFail();
        $response->assertRedirect(route('import-orders.show', $order->id));
        $this->assertSame($this->user->id, $order->assigned_to);
    }

    public function test_store_rejects_cars_from_another_organization(): void
    {
        $other = Organization::factory()->create();
        $car = Car::factory()->create(['organization_id' => $other->id]);

        $this->actingAs($this->user)->post(route('import-orders.store'), ['car_id' => $car->id])
            ->assertSessionHasErrors('car_id');
    }

    public function test_orders_of_another_organization_are_not_found(): void
    {
        $foreign = ImportOrder::factory()->create();

        $this->actingAs($this->user)->get(route('import-orders.show', $foreign->id))->assertNotFound();
        $this->actingAs($this->user)->post(route('import-orders.transition', $foreign->id), ['to' => 'cancelled', 'reason' => 'x'])->assertNotFound();
    }

    public function test_index_lists_orders_of_the_organization_only(): void
    {
        $mine = $this->order();
        ImportOrder::factory()->create();

        $this->actingAs($this->user)->get(route('import-orders.index', ['tab' => 'all']))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('ImportOrders/Index', false)
                ->has('orders.data', 1)
                ->where('orders.data.0.reference', $mine->reference)
                ->where('counts.all', 1));
    }

    public function test_show_exposes_next_action_transitions_and_whatsapp_links(): void
    {
        $order = $this->order();

        $this->actingAs($this->user)->get(route('import-orders.show', $order->id))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('ImportOrders/Show', false)
                ->where('order.reference', $order->reference)
                ->where('order.state', 'draft')
                ->where('next_action.key', 'issue_contract')
                ->where('transitions.0.value', 'cancelled')
                ->where('whatsapp.has_phone', true)
                ->where('order.portal.url', $order->portalUrl()));
    }

    public function test_issue_contract_endpoint_moves_order_to_awaiting_signature(): void
    {
        $order = $this->order();

        $this->actingAs($this->user)->post(route('import-orders.contracts.issue', $order->id))
            ->assertSessionHas('success');

        $this->assertSame(ImportOrderState::AwaitingSignature, $order->fresh()->state);
    }

    public function test_transition_errors_are_returned_as_form_errors(): void
    {
        $order = $this->order();

        $this->actingAs($this->user)->post(route('import-orders.transition', $order->id), ['to' => 'cancelled'])
            ->assertSessionHasErrors('reason');

        $this->actingAs($this->user)->post(route('import-orders.transition', $order->id), ['to' => 'cancelled', 'reason' => 'Cliente desiste'])
            ->assertSessionHas('success');
        $this->assertSame(ImportOrderState::Cancelled, $order->fresh()->state);
    }

    public function test_fees_are_locked_once_the_contract_is_sent(): void
    {
        $order = $this->order(['state' => ImportOrderState::AwaitingSignature]);

        $this->actingAs($this->user)->patch(route('import-orders.update', $order->id), ['fee_amount' => 900])
            ->assertSessionHasErrors('fee_amount');

        $this->actingAs($this->user)->patch(route('import-orders.update', $order->id), ['expected_delivery_date' => '2026-12-01'])
            ->assertSessionHasNoErrors();
        $this->assertSame('2026-12-01', $order->fresh()->expected_delivery_date->toDateString());
    }

    public function test_marking_a_payment_paid_requires_a_method_and_validates_stripe_links(): void
    {
        $order = $this->order(['state' => ImportOrderState::AwaitingDeposit]);
        app(OrderPaymentService::class)->scheduleFees($order);
        $deposit = $order->depositPayment();

        $this->actingAs($this->user)->patch(route('import-orders.payments.update', [$order->id, $deposit->id]), [
            'stripe_payment_link' => 'https://evil.example.com/pay',
        ])->assertSessionHasErrors('stripe_payment_link');

        $this->actingAs($this->user)->patch(route('import-orders.payments.update', [$order->id, $deposit->id]), [
            'stripe_payment_link' => 'https://buy.stripe.com/test_123',
        ])->assertSessionHasNoErrors();

        $this->actingAs($this->user)->post(route('import-orders.payments.paid', [$order->id, $deposit->id]), [])
            ->assertSessionHasErrors('method');

        $this->actingAs($this->user)->post(route('import-orders.payments.paid', [$order->id, $deposit->id]), ['method' => 'transfer'])
            ->assertSessionHas('success');

        $this->assertSame(PaymentStatus::Paid, $deposit->fresh()->status);
        $this->assertSame(ImportOrderState::Inspecting, $order->fresh()->state);
    }

    public function test_payment_of_another_order_cannot_be_touched(): void
    {
        $order = $this->order(['state' => ImportOrderState::AwaitingDeposit]);
        $other = $this->order(['state' => ImportOrderState::AwaitingDeposit]);
        app(OrderPaymentService::class)->scheduleFees($other);

        $this->actingAs($this->user)
            ->post(route('import-orders.payments.paid', [$order->id, $other->depositPayment()->id]), ['method' => 'cash'])
            ->assertNotFound();
    }

    public function test_media_upload_is_internal_unless_published(): void
    {
        $order = $this->order(['state' => ImportOrderState::Inspecting]);

        $this->actingAs($this->user)->post(route('import-orders.media.store', $order->id), [
            'files' => [UploadedFile::fake()->image('puerta.jpg')],
            'collection' => 'inspection',
            'visibility' => 'internal',
        ])->assertSessionHasNoErrors();

        $media = OrderMedia::query()->firstOrFail();
        $this->assertSame('internal', $media->visibility);
        Storage::disk('local')->assertExists($media->path);

        $this->actingAs($this->user)->patch(route('import-orders.media.update', [$order->id, $media->id]), ['visibility' => 'client'])
            ->assertSessionHasNoErrors();
        $this->assertDatabaseHas('order_events', ['import_order_id' => $order->id, 'type' => 'media_published', 'visible_to_client' => true]);
    }

    public function test_team_message_notifies_the_client_but_internal_notes_do_not(): void
    {
        $order = $this->order(['state' => ImportOrderState::InTransit]);

        $this->actingAs($this->user)->post(route('import-orders.messages.store', $order->id), ['body' => 'Nota solo equipo', 'internal' => true]);
        Mail::assertNothingSent();

        $this->actingAs($this->user)->post(route('import-orders.messages.store', $order->id), ['body' => 'El camión sale mañana']);
        Mail::assertSent(\App\Mail\OrderUpdateMail::class, fn ($mail) => $mail->hasTo($order->client->email));
    }

    public function test_car_show_offers_the_order_summary(): void
    {
        $order = $this->order();

        $this->actingAs($this->user)->get(route('cars.show', $order->car_id))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->where('derived.import_order.reference', $order->reference)
                ->where('derived.import_order.url', route('import-orders.show', $order->id)));
    }
}
