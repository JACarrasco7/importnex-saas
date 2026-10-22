<?php

namespace App\Http\Controllers\ImportOrders;

use App\Enums\PaymentKind;
use App\Enums\PaymentMethod;
use App\Http\Controllers\Controller;
use App\Http\Requests\ImportOrders\StoreOrderPaymentRequest;
use App\Models\ImportOrder;
use App\Models\OrderPayment;
use App\Services\ImportOrders\OrderPaymentService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

/** Pagos del encargo: alta, edición (enlace Stripe), marcar pagado/pendiente, anular. */
class ImportOrderPaymentController extends Controller
{
    use AuthorizesImportOrder;

    public function __construct(private OrderPaymentService $payments) {}

    public function store(StoreOrderPaymentRequest $request, ImportOrder $importOrder): RedirectResponse
    {
        $this->authorizeOrder($importOrder);
        $this->payments->create($importOrder, [...$request->validated(), 'kind' => $request->validated('kind') ?? PaymentKind::Other->value], $request->user());

        return back()->with('success', 'Pago añadido como pendiente.');
    }

    public function update(StoreOrderPaymentRequest $request, ImportOrder $importOrder, OrderPayment $payment): RedirectResponse
    {
        $this->authorizeChild($importOrder, $payment);

        return $this->flowAction(fn () => $this->payments->update($payment, $request->validated()), 'Pago actualizado.');
    }

    public function markPaid(Request $request, ImportOrder $importOrder, OrderPayment $payment): RedirectResponse
    {
        $this->authorizeChild($importOrder, $payment);
        $data = $request->validate([
            'method' => ['required', Rule::enum(PaymentMethod::class)],
            'reference' => ['nullable', 'string', 'max:191'],
            'paid_at' => ['nullable', 'date', 'before_or_equal:today'],
        ]);

        return $this->flowAction(
            fn () => $this->payments->markPaid($payment, PaymentMethod::from($data['method']), $request->user(), $data['reference'] ?? null, $data['paid_at'] ?? null),
            'Pago registrado. El cliente recibe el justificante por email.',
        );
    }

    public function markPending(Request $request, ImportOrder $importOrder, OrderPayment $payment): RedirectResponse
    {
        $this->authorizeChild($importOrder, $payment);
        $this->payments->markPending($payment, $request->user());

        return back()->with('success', 'Pago marcado de nuevo como pendiente.');
    }

    public function cancel(Request $request, ImportOrder $importOrder, OrderPayment $payment): RedirectResponse
    {
        $this->authorizeChild($importOrder, $payment);
        $this->payments->cancel($payment, $request->user());

        return back()->with('success', 'Pago anulado.');
    }
}
