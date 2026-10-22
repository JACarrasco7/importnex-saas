<?php

namespace Tests\Feature\ImportOrders;

use App\Enums\ApprovalKind;
use App\Enums\ApprovalStatus;
use App\Enums\ContractStatus;
use App\Enums\ImportOrderState;
use App\Enums\PaymentKind;
use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use App\Mail\OrderUpdateMail;
use App\Mail\PortalOtpMail;
use App\Models\Car;
use App\Models\Client;
use App\Models\ContractAcceptance;
use App\Models\ImportOrder;
use App\Models\Organization;
use App\Models\User;
use App\Services\ImportOrders\ImportOrderService;
use App\Services\ImportOrders\OrderApprovalService;
use App\Services\ImportOrders\OrderContractService;
use App\Services\ImportOrders\OrderFlowException;
use App\Services\ImportOrders\OrderPaymentService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/** Flujo completo del encargo a nivel de servicios (reglas de negocio). */
class ImportOrderFlowTest extends TestCase
{
    use RefreshDatabase;

    private Organization $org;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();
        Mail::fake();
        Storage::fake('local');
        config(['import_orders.team_email' => 'equipo@example.com']);

        $this->org = Organization::factory()->create();
        $this->user = User::factory()->create(['organization_id' => $this->org->id, 'role' => 'owner']);
    }

    private function carWithClient(array $clientAttributes = []): Car
    {
        $client = Client::factory()->create([
            'organization_id' => $this->org->id,
            'name' => 'Lucía Pérez Gómez',
            'email' => 'lucia@example.com',
            'phone' => '600 111 222',
            ...$clientAttributes,
        ]);

        return Car::factory()->create([
            'organization_id' => $this->org->id,
            'client_id' => $client->id,
            'brand' => 'Volkswagen',
            'model' => 'Golf',
            'purchase_price' => 15870,
            'professional_fees' => 1500,
            'status' => 'Located',
        ]);
    }

    private function createOrder(?Car $car = null): ImportOrder
    {
        return app(ImportOrderService::class)->createForCar($car ?? $this->carWithClient(), $this->user);
    }

    /** Envía el código y lo recupera del email (en producción solo lo ve el cliente). */
    private function signatureCode(ImportOrder $order, ContractAcceptance $contract): string
    {
        app(OrderContractService::class)->sendSignatureCode($order, $contract, '1.2.3.4');
        $code = null;
        Mail::assertSent(PortalOtpMail::class, function (PortalOtpMail $mail) use (&$code) {
            $code = $mail->code;

            return true;
        });

        return (string) $code;
    }

    private function signedOrder(): ImportOrder
    {
        $order = $this->createOrder();
        $contract = app(OrderContractService::class)->issue($order, $this->user);
        $code = $this->signatureCode($order, $contract);
        app(OrderContractService::class)->sign($order, $contract, ['name' => 'Lucía Pérez Gómez', 'dni' => '12345678Z', 'code' => $code], '1.2.3.4', 'PHPUnit');

        return $order->fresh();
    }

    public function test_creating_an_order_requires_a_client_and_reserves_the_car(): void
    {
        $car = Car::factory()->create(['organization_id' => $this->org->id, 'client_id' => null]);

        try {
            app(ImportOrderService::class)->createForCar($car, $this->user);
            $this->fail('Debía exigir cliente');
        } catch (OrderFlowException $e) {
            $this->assertStringContainsString('cliente', $e->getMessage());
        }

        $order = $this->createOrder($car2 = $this->carWithClient());

        $this->assertSame(ImportOrderState::Draft, $order->state);
        $this->assertMatchesRegularExpression('/^ENC-\d{4}-\d{4}$/', $order->reference);
        $this->assertSame(1500.0, (float) $order->fee_amount);
        $this->assertNotNull($order->portalUrl());
        $this->assertSame('Reserved', $car2->fresh()->status);
        $this->assertDatabaseHas('order_events', ['import_order_id' => $order->id, 'type' => 'created']);
    }

    public function test_a_car_cannot_have_two_active_orders(): void
    {
        $car = $this->carWithClient();
        $this->createOrder($car);

        $this->expectException(OrderFlowException::class);
        $this->createOrder($car);
    }

    public function test_issuing_a_contract_requires_the_client_email(): void
    {
        $order = $this->createOrder($this->carWithClient(['email' => null]));

        $this->expectException(OrderFlowException::class);
        app(OrderContractService::class)->issue($order, $this->user);
    }

    public function test_issuing_a_contract_emails_the_client_and_waits_for_signature(): void
    {
        $order = $this->createOrder();
        $contract = app(OrderContractService::class)->issue($order, $this->user);

        $this->assertSame(ImportOrderState::AwaitingSignature, $order->fresh()->state);
        $this->assertSame(ContractStatus::Sent, $contract->status);
        $this->assertTrue($contract->expires_at->isFuture());
        $this->assertSame('15.870', $contract->snapshot['precio_compra']);
        $this->assertStringContainsString('15.870', $contract->getContractText());
        Mail::assertSent(OrderUpdateMail::class, fn (OrderUpdateMail $mail) => $mail->hasTo('lucia@example.com')
            && str_contains($mail->subjectLine, 'contrato'));
    }

    public function test_reissuing_voids_the_previous_contract(): void
    {
        $order = $this->createOrder();
        $first = app(OrderContractService::class)->issue($order, $this->user);
        $second = app(OrderContractService::class)->issue($order->fresh(), $this->user);

        $this->assertSame(ContractStatus::Voided, $first->fresh()->status);
        $this->assertSame(ContractStatus::Sent, $second->fresh()->status);
    }

    public function test_signing_requires_a_valid_code_and_counts_attempts(): void
    {
        $order = $this->createOrder();
        $contract = app(OrderContractService::class)->issue($order, $this->user);
        $code = $this->signatureCode($order, $contract);
        $wrong = $code === '111111' ? '222222' : '111111';

        try {
            app(OrderContractService::class)->sign($order, $contract, ['name' => 'Lucía', 'dni' => '12345678Z', 'code' => $wrong], null, null);
            $this->fail('Debía rechazar el código');
        } catch (OrderFlowException $e) {
            $this->assertStringContainsString('Te quedan 2', $e->getMessage());
        }

        $this->assertNull($contract->fresh()->accepted_at);
    }

    public function test_signing_records_evidence_schedules_fees_and_awaits_deposit(): void
    {
        $order = $this->signedOrder();
        $contract = $order->signedContract();

        $this->assertSame(ImportOrderState::AwaitingDeposit, $order->state);
        $this->assertSame(ContractStatus::Signed, $contract->status);
        $this->assertSame('1.2.3.4', $contract->accepted_ip);
        $this->assertNotNull($contract->otp_verified_at);
        $this->assertSame('lucia@example.com', $contract->otp_sent_to);
        $this->assertSame(64, strlen($contract->contract_hash));
        $this->assertSame(hash('sha256', $contract->getContractText()), $contract->contract_hash);
        $this->assertNotNull($contract->pdf_path);
        Storage::disk('local')->assertExists($contract->pdf_path);
        $this->assertSame('12345678Z', $order->client->fresh()->dni);

        $this->assertSame(2, $order->payments()->count());
        $deposit = $order->depositPayment();
        $this->assertSame(PaymentKind::FeeDeposit, $deposit->kind);
        $this->assertSame(750.0, (float) $deposit->amount);

        Mail::assertSent(OrderUpdateMail::class, fn (OrderUpdateMail $mail) => $mail->hasTo('lucia@example.com') && count($mail->files) === 1);
        Mail::assertSent(OrderUpdateMail::class, fn (OrderUpdateMail $mail) => $mail->hasTo('equipo@example.com'));
    }

    public function test_recording_the_deposit_moves_the_order_to_inspection(): void
    {
        $order = $this->signedOrder();

        try {
            app(ImportOrderService::class)->transition($order, ImportOrderState::Inspecting, ['user' => $this->user]);
            $this->fail('Sin señal no debe pasar a inspección');
        } catch (OrderFlowException $e) {
            $this->assertStringContainsString('señal', $e->getMessage());
        }

        app(OrderPaymentService::class)->markPaid($order->depositPayment(), PaymentMethod::Transfer, $this->user, 'TRF-1');

        $this->assertSame(ImportOrderState::Inspecting, $order->fresh()->state);
        $this->assertSame(PaymentStatus::Paid, $order->depositPayment()->status);
        $this->assertTrue((bool) $order->car->checklists()->where('item_key', 'deposit_paid')->value('completed'));
    }

    public function test_hidden_defect_flow_lets_the_client_choose_another_car(): void
    {
        $order = $this->signedOrder();
        app(OrderPaymentService::class)->markPaid($order->depositPayment(), PaymentMethod::Cash, $this->user);
        $order->refresh();

        $approval = app(OrderApprovalService::class)->request($order, [
            'kind' => ApprovalKind::UnitDecision->value,
            'title' => 'La inspección ha encontrado repintado en la puerta',
        ], $this->user);
        $this->assertSame(ImportOrderState::AwaitingClientDecision, $order->fresh()->state);

        app(OrderApprovalService::class)->sendCode($approval, null);
        $code = null;
        Mail::assertSent(PortalOtpMail::class, function (PortalOtpMail $mail) use (&$code) {
            $code = $mail->purpose === 'approve' ? $mail->code : $code;

            return true;
        });

        app(OrderApprovalService::class)->resolve($approval->fresh(), ['decision' => 'replace', 'code' => $code], '5.6.7.8');
        $order->refresh();
        $this->assertSame(ImportOrderState::SearchingReplacement, $order->state);
        $this->assertSame(ApprovalStatus::Approved, $approval->fresh()->status);

        $oldCar = $order->car;
        $newCar = Car::factory()->create(['organization_id' => $this->org->id, 'client_id' => null]);
        app(ImportOrderService::class)->replaceVehicle($order, $newCar, $this->user);

        $order->refresh();
        $this->assertSame($newCar->id, $order->car_id);
        $this->assertSame(ImportOrderState::Inspecting, $order->state);
        $this->assertSame($order->client_id, $newCar->fresh()->client_id);
        $this->assertNull($oldCar->fresh()->client_id);
    }

    public function test_extra_cost_approval_creates_a_pending_payment(): void
    {
        $order = $this->signedOrder();
        $approval = app(OrderApprovalService::class)->request($order, [
            'kind' => ApprovalKind::ExtraCost->value,
            'title' => 'Cambio de discos de freno',
            'amount' => 120,
        ], $this->user);

        $this->assertFalse($approval->requiresOtp());
        app(OrderApprovalService::class)->resolve($approval, ['approved' => true], null);

        $this->assertDatabaseHas('order_payments', [
            'import_order_id' => $order->id,
            'kind' => PaymentKind::ExtraCost->value,
            'amount' => 120,
            'status' => 'pending',
        ]);
    }

    public function test_invalid_transitions_and_cancellation_without_reason_are_rejected(): void
    {
        $order = $this->createOrder();

        try {
            app(ImportOrderService::class)->transition($order, ImportOrderState::Delivered);
            $this->fail('Transición inválida');
        } catch (OrderFlowException $e) {
            $this->assertStringContainsString('No se puede pasar', $e->getMessage());
        }

        try {
            app(ImportOrderService::class)->transition($order, ImportOrderState::Cancelled);
            $this->fail('Cancelar exige motivo');
        } catch (OrderFlowException $e) {
            $this->assertSame('reason', $e->field);
        }

        app(ImportOrderService::class)->transition($order, ImportOrderState::Cancelled, ['reason' => 'El cliente compra en otro sitio']);
        $this->assertSame(ImportOrderState::Cancelled, $order->fresh()->state);
        $this->assertNull($order->car->fresh()->client_id);
    }

    public function test_changing_the_car_price_voids_a_pending_contract(): void
    {
        $order = $this->createOrder();
        $contract = app(OrderContractService::class)->issue($order, $this->user);

        $order->car->update(['purchase_price' => 16500]);

        $this->assertSame(ContractStatus::Voided, $contract->fresh()->status);
        $this->assertStringContainsString('precio', (string) $contract->fresh()->void_reason);
        Mail::assertSent(OrderUpdateMail::class, fn (OrderUpdateMail $mail) => $mail->hasTo('equipo@example.com') && str_contains($mail->subjectLine, 'anulado'));
    }

    public function test_courtesy_orders_without_fees_skip_the_deposit(): void
    {
        $car = $this->carWithClient();
        $car->update(['professional_fees' => 0]);
        $order = $this->createOrder($car->fresh());
        $order->update(['fee_amount' => 0]);

        $contract = app(OrderContractService::class)->issue($order->fresh(), $this->user);
        $code = $this->signatureCode($order, $contract);
        app(OrderContractService::class)->sign($order, $contract, ['name' => 'Lucía', 'dni' => 'X1234567L', 'code' => $code], null, null);

        $this->assertSame(ImportOrderState::Inspecting, $order->fresh()->state);
        $this->assertSame(0, $order->payments()->count());
    }
}
