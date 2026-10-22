<?php

namespace App\Http\Controllers\Portal;

use App\Http\Controllers\Controller;
use App\Services\ImportOrders\OrderMessageService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class PortalMessageController extends Controller
{
    use ResolvesPortalOrder;

    public function __construct(private OrderMessageService $messages) {}

    public function store(Request $request, string $token): RedirectResponse
    {
        $order = $this->portalOrder($token);
        $data = $request->validate(['body' => ['required', 'string', 'min:2', 'max:3000']]);

        $this->messages->postFromClient($order, $data['body'], $request->ip());

        return back()->with('success', 'Mensaje enviado. Te respondemos lo antes posible.');
    }
}
