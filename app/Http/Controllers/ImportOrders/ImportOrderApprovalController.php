<?php

namespace App\Http\Controllers\ImportOrders;

use App\Http\Controllers\Controller;
use App\Http\Requests\ImportOrders\StoreOrderApprovalRequest;
use App\Models\ImportOrder;
use App\Models\OrderApproval;
use App\Services\ImportOrders\OrderApprovalService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class ImportOrderApprovalController extends Controller
{
    use AuthorizesImportOrder;

    public function __construct(private OrderApprovalService $approvals) {}

    public function store(StoreOrderApprovalRequest $request, ImportOrder $importOrder): RedirectResponse
    {
        $this->authorizeOrder($importOrder);

        return $this->flowAction(
            fn () => $this->approvals->request($importOrder, $request->safe()->except('photos'), $request->user(), $request->file('photos', [])),
            'Petición enviada al cliente por email. Puedes avisarle también por WhatsApp.',
        );
    }

    public function withdraw(Request $request, ImportOrder $importOrder, OrderApproval $approval): RedirectResponse
    {
        $this->authorizeChild($importOrder, $approval);

        return $this->flowAction(fn () => $this->approvals->withdraw($approval, $request->user()), 'Petición retirada.');
    }
}
