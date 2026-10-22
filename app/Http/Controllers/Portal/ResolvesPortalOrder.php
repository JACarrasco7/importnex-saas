<?php

namespace App\Http\Controllers\Portal;

use App\Models\ImportOrder;
use App\Services\ImportOrders\OrderFlowException;
use Illuminate\Http\RedirectResponse;

/**
 * Localiza el encargo a partir del token del portal (se busca por hash) y
 * convierte las reglas de negocio incumplidas en errores de formulario.
 */
trait ResolvesPortalOrder
{
    protected function portalOrder(string $token): ImportOrder
    {
        $order = ImportOrder::findByPortalToken($token);
        if (! $order) {
            abort(404);
        }

        $closedAt = $order->delivered_at ?? $order->cancelled_at;
        if ($order->state->isTerminal() && $closedAt
            && $closedAt->lt(now()->subDays((int) config('import_orders.portal_days_after_close', 90)))) {
            abort(410, 'Este enlace ha caducado. Escríbenos si necesitas algún documento.');
        }

        return $order;
    }

    protected function portalAction(callable $action, string $success): RedirectResponse
    {
        try {
            $action();
        } catch (OrderFlowException $e) {
            return back()->withErrors([$e->field => $e->getMessage()])->with('error', $e->getMessage());
        }

        return back()->with('success', $success);
    }
}
