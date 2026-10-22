<?php

namespace Tests\Feature\ImportOrders;

use App\Enums\ContractStatus;
use App\Enums\ImportOrderState;
use App\Mail\OrderUpdateMail;
use App\Mail\PortalOtpMail;
use App\Models\ContractAcceptance;
use App\Models\ImportOrder;
use App\Models\OrderMedia;
use App\Models\OrderMessage;
use App\Models\Organization;
use App\Models\User;
use App\Services\ImportOrders\OrderContractService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

/** Portal del cliente: acceso por token, firma con código, mensajes y privacidad. */
class PortalTest extends TestCase
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
        return ImportOrder::factory()->withPortal()->create(['organization_id' => $this->org->id, ...$attributes])->fresh();
    }

    public function test_unknown_token_is_not_found(): void
    {
        $this->get('/mi-encargo/'.str_repeat('a', 48))->assertNotFound();
    }

    public function test_portal_shows_whitelisted_data_only(): void
    {
        $order = $this->order(['state' => ImportOrderState::InTransit, 'internal_notes' => 'MARGEN SECRETO']);
        OrderMessage::create([
            'organization_id' => $order->organization_id, 'import_order_id' => $order->id,
            'author_type' => 'team', 'user_id' => $this->user->id, 'body' => 'Nota interna privada', 'visibility' => 'internal',
        ]);

        $response = $this->get(route('portal.show', $order->portal_token))->assertOk();

        $response->assertInertia(fn (Assert $page) => $page
            ->component('Public/Portal', false)
            ->where('order.reference', $order->reference)
            ->where('order.is_tracking_visible', true)
            ->where('order.phases.3.status', 'current')
            ->missing('order.internal_notes')
            ->missing('vehicle.purchase_price'));

        $this->assertStringNotContainsString('MARGEN SECRETO', $response->getContent());
        $this->assertStringNotContainsString('Nota interna privada', $response->getContent());
        $this->assertSame(1, $order->fresh()->portal_views);
        $this->assertStringContainsString(e($this->org->name), $response->getContent());
    }

    public function test_client_signs_the_contract_from_the_portal_with_a_code(): void
    {
        $order = $this->order();
        app(OrderContractService::class)->issue($order, $this->user);
        $token = $order->portal_token;

        $this->get(route('portal.show', $token))
            ->assertInertia(fn (Assert $page) => $page
                ->where('contract.is_signable', true)
                ->where('contract.status', 'viewed')
                ->where('client.masked_email', fn ($masked) => str_contains($masked, '*')));

        $this->assertSame(ContractStatus::Viewed, ContractAcceptance::query()->latest('id')->first()->status);

        $this->post(route('portal.contract.code', $token))->assertSessionHas('success');
        $code = null;
        Mail::assertSent(PortalOtpMail::class, function (PortalOtpMail $mail) use (&$code) {
            $code = $mail->code;

            return true;
        });

        $this->post(route('portal.contract.sign', $token), ['name' => 'Ana López', 'dni' => '12345678A', 'code' => $code, 'accept' => '1'])
            ->assertSessionHasErrors('dni');

        $this->post(route('portal.contract.sign', $token), ['name' => 'Ana López', 'dni' => '12345678-z', 'code' => $code, 'accept' => '1'])
            ->assertSessionHasNoErrors()
            ->assertSessionHas('success');

        $order->refresh();
        $this->assertSame(ImportOrderState::AwaitingDeposit, $order->state);
        $this->assertSame('12345678Z', $order->signedContract()->client_dni);

        $this->get(route('portal.contract.pdf', $token))->assertOk();
    }

    public function test_client_can_raise_doubts_instead_of_signing(): void
    {
        $order = $this->order();
        app(OrderContractService::class)->issue($order, $this->user);

        $this->post(route('portal.contract.reject', $order->portal_token), ['reason' => 'No entiendo la cláusula 6'])
            ->assertSessionHas('success');

        $this->assertSame(ImportOrderState::ContractRejected, $order->fresh()->state);
        Mail::assertSent(OrderUpdateMail::class, fn (OrderUpdateMail $mail) => str_contains($mail->subjectLine, 'dudas'));
    }

    public function test_client_message_reaches_the_team(): void
    {
        $order = $this->order(['state' => ImportOrderState::Inspecting]);

        $this->post(route('portal.messages.store', $order->portal_token), ['body' => '¿Cuándo llega?'])
            ->assertSessionHas('success');

        $this->assertDatabaseHas('order_messages', ['import_order_id' => $order->id, 'author_type' => 'client', 'body' => '¿Cuándo llega?']);
        Mail::assertSent(OrderUpdateMail::class, fn (OrderUpdateMail $mail) => str_contains($mail->subjectLine, 'Nuevo mensaje'));
    }

    public function test_internal_media_is_never_served_to_the_portal(): void
    {
        $order = $this->order(['state' => ImportOrderState::Inspecting]);
        Storage::disk('local')->put('orders/x/a.jpg', 'img');
        Storage::disk('local')->put('orders/x/b.jpg', 'img');
        $internal = OrderMedia::create(['organization_id' => $order->organization_id, 'import_order_id' => $order->id, 'path' => 'orders/x/a.jpg', 'mime' => 'image/jpeg', 'visibility' => 'internal']);
        $public = OrderMedia::create(['organization_id' => $order->organization_id, 'import_order_id' => $order->id, 'path' => 'orders/x/b.jpg', 'mime' => 'image/jpeg', 'visibility' => 'client']);

        $this->get(route('portal.media', [$order->portal_token, $internal->id]))->assertNotFound();
        $this->get(route('portal.media', [$order->portal_token, $public->id]))->assertOk();

        $other = $this->order();
        $this->get(route('portal.media', [$other->portal_token, $public->id]))->assertNotFound();
    }

    public function test_cancellation_request_notifies_the_team_once(): void
    {
        $order = $this->order(['state' => ImportOrderState::AwaitingDeposit]);

        $this->post(route('portal.cancellation', $order->portal_token), ['reason' => 'He encontrado otro coche'])->assertSessionHas('success');
        $this->post(route('portal.cancellation', $order->portal_token), ['reason' => 'Otra vez'])->assertSessionHasErrors();

        $this->assertNotNull($order->fresh()->cancellation_requested_at);
        $this->assertSame(ImportOrderState::AwaitingDeposit, $order->fresh()->state);
    }

    public function test_recover_sends_the_link_without_revealing_clients(): void
    {
        $order = $this->order();

        $this->post(route('portal.recover'), ['email' => 'nadie@example.com'])->assertSessionHas('success');
        Mail::assertNothingSent();

        $this->post(route('portal.recover'), ['email' => strtoupper($order->client->email)])->assertSessionHas('success');
        Mail::assertSent(OrderUpdateMail::class, fn (OrderUpdateMail $mail) => $mail->actionUrl === $order->portalUrl());
    }

    public function test_legacy_links_redirect_to_the_portal(): void
    {
        $order = $this->order(['state' => ImportOrderState::InTransit]);
        $order->car->forceFill(['tracking_token' => str_repeat('t', 40), 'status' => 'In_transit'])->save();
        $contract = app(OrderContractService::class)->issue($this->order(), $this->user);

        $this->get('/tracking/'.str_repeat('t', 40))->assertRedirect($order->portalUrl());
        $this->get(route('public.contract.show', $contract->public_token))->assertRedirect($contract->importOrder->portalUrl());
        $this->postJson(route('public.contract.accept', $contract->public_token), ['accept' => true])->assertStatus(409);
    }

    public function test_closed_orders_expire_after_the_grace_period(): void
    {
        $order = $this->order(['state' => ImportOrderState::Delivered]);
        $order->forceFill(['delivered_at' => now()->subDays(120)])->save();

        $this->get(route('portal.show', $order->portal_token))->assertStatus(410);
    }
}
