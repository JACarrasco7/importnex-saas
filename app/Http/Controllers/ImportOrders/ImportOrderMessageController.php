<?php

namespace App\Http\Controllers\ImportOrders;

use App\Http\Controllers\Controller;
use App\Models\ImportOrder;
use App\Services\ImportOrders\OrderMessageService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class ImportOrderMessageController extends Controller
{
    use AuthorizesImportOrder;

    public function __construct(private OrderMessageService $messages) {}

    public function store(Request $request, ImportOrder $importOrder): RedirectResponse
    {
        $this->authorizeOrder($importOrder);
        $data = $request->validate([
            'body' => ['required', 'string', 'min:2', 'max:5000'],
            'internal' => ['nullable', 'boolean'],
        ]);

        $internal = (bool) ($data['internal'] ?? false);
        $this->messages->postFromTeam($importOrder, $data['body'], $request->user(), $internal);

        return back()->with('success', $internal ? 'Nota interna guardada.' : 'Mensaje enviado al cliente (le llega aviso por email).');
    }
}
