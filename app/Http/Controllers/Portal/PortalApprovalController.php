<?php

namespace App\Http\Controllers\Portal;

use App\Http\Controllers\Controller;
use App\Models\OrderApproval;
use App\Services\ImportOrders\OrderApprovalService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

/** Respuesta del cliente a costes extra y a la decisión tras la inspección. */
class PortalApprovalController extends Controller
{
    use ResolvesPortalOrder;

    public function __construct(private OrderApprovalService $approvals) {}

    public function sendCode(Request $request, string $token, int $approval): RedirectResponse
    {
        $item = $this->approval($token, $approval);

        return $this->portalAction(
            fn () => $this->approvals->sendCode($item, $request->ip()),
            'Te hemos enviado un código de 6 dígitos a tu email.',
        );
    }

    public function resolve(Request $request, string $token, int $approval): RedirectResponse
    {
        $item = $this->approval($token, $approval);
        $data = $request->validate([
            'approved' => ['nullable', 'boolean'],
            'decision' => ['nullable', Rule::in(['continue', 'replace', 'cancel'])],
            'comment' => ['nullable', 'string', 'max:2000'],
            'code' => ['nullable', 'string', 'max:12'],
        ]);

        return $this->portalAction(
            fn () => $this->approvals->resolve($item, $data, $request->ip()),
            'Respuesta registrada. ¡Gracias!',
        );
    }

    private function approval(string $token, int $approvalId): OrderApproval
    {
        $order = $this->portalOrder($token);

        return OrderApproval::query()->where('import_order_id', $order->id)->findOrFail($approvalId);
    }
}
