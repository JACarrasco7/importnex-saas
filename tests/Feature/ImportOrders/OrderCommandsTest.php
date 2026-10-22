<?php

namespace Tests\Feature\ImportOrders;

use App\Enums\ApprovalStatus;
use App\Enums\ContractStatus;
use App\Enums\ImportOrderState;
use App\Mail\OrderUpdateMail;
use App\Models\Car;
use App\Models\Client;
use App\Models\ContractAcceptance;
use App\Models\ImportOrder;
use App\Models\Organization;
use App\Models\User;
use App\Services\ImportOrders\OrderApprovalService;
use App\Services\ImportOrders\OrderContractService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class OrderCommandsTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();
        Mail::fake();
        Storage::fake('local');
        $org = Organization::factory()->create();
        $this->user = User::factory()->create(['organization_id' => $org->id, 'role' => 'owner']);
    }

    public function test_maintenance_reminds_then_expires_unsigned_contracts(): void
    {
        $order = ImportOrder::factory()->withPortal()->create(['organization_id' => $this->user->organization_id]);
        $contract = app(OrderContractService::class)->issue($order, $this->user);
        Mail::fake();

        $this->travel(25)->hours();
        Artisan::call('orders:maintenance');
        Mail::assertSent(OrderUpdateMail::class, fn (OrderUpdateMail $mail) => str_contains($mail->subjectLine, 'Recordatorio'));
        $this->assertSame(1, $contract->fresh()->reminders_sent);

        $this->travel(7)->days();
        Artisan::call('orders:maintenance');
        $this->assertSame(ContractStatus::Expired, $contract->fresh()->status);
        $this->assertFalse($contract->fresh()->isSignable());
    }

    public function test_maintenance_expires_unanswered_approvals(): void
    {
        $order = ImportOrder::factory()->withPortal()->create([
            'organization_id' => $this->user->organization_id,
            'state' => ImportOrderState::Inspecting,
        ]);
        $approval = app(OrderApprovalService::class)->request($order, ['kind' => 'extra_cost', 'title' => 'Neumáticos', 'amount' => 400, 'due_hours' => 2], $this->user);

        $this->travel(3)->hours();
        Artisan::call('orders:maintenance');

        $this->assertSame(ApprovalStatus::Expired, $approval->fresh()->status);
    }

    public function test_backfill_is_dry_run_by_default_and_maps_states(): void
    {
        $client = Client::factory()->create(['organization_id' => $this->user->organization_id]);
        $inTransit = Car::factory()->create(['organization_id' => $this->user->organization_id, 'client_id' => $client->id, 'status' => 'In_transit']);
        $signedCar = Car::factory()->create(['organization_id' => $this->user->organization_id, 'client_id' => $client->id, 'status' => 'Reserved']);
        ContractAcceptance::create([
            'organization_id' => $this->user->organization_id, 'car_id' => $signedCar->id, 'public_token' => str_repeat('k', 48),
            'contract_version' => '1', 'contract_hash' => str_repeat('a', 64), 'snapshot' => [], 'client_email' => 'x@y.z',
            'accepted_at' => now(), 'status' => 'signed',
        ]);

        Artisan::call('orders:backfill');
        $this->assertSame(0, ImportOrder::count());

        Artisan::call('orders:backfill', ['--apply' => true]);
        $this->assertSame(ImportOrderState::InTransit, ImportOrder::where('car_id', $inTransit->id)->first()->state);
        $this->assertSame('In_transit', $inTransit->fresh()->status);

        $signedOrder = ImportOrder::where('car_id', $signedCar->id)->first();
        $this->assertSame(ImportOrderState::AwaitingDeposit, $signedOrder->state);
        $this->assertSame(2, $signedOrder->payments()->count());
        $this->assertSame($signedOrder->id, ContractAcceptance::where('car_id', $signedCar->id)->value('import_order_id'));
    }
}
