<?php

namespace App\Http\Controllers\ImportOrders;

use App\Models\ImportOrder;
use App\Services\ImportOrders\OrderFlowException;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\RedirectResponse;

/**
 * Tenancy explícita (ImportOrder no tiene global scope) + traducción de las
 * reglas de negocio incumplidas a errores/flash para el panel.
 */
trait AuthorizesImportOrder
{
    protected function authorizeOrder(ImportOrder $order): void
    {
        abort_unless((int) $order->organization_id === (int) auth()->user()->organization_id, 404);
    }

    /** Comprueba que un hijo (pago, aprobación, media) pertenece al encargo. */
    protected function authorizeChild(ImportOrder $order, Model $child): void
    {
        $this->authorizeOrder($order);
        abort_unless((int) $child->getAttribute('import_order_id') === (int) $order->id, 404);
    }

    protected function flowAction(callable $action, string $success): RedirectResponse
    {
        try {
            $action();
        } catch (OrderFlowException $e) {
            return back()->withErrors([$e->field => $e->getMessage()])->with('error', $e->getMessage());
        }

        return back()->with('success', $success);
    }
}
