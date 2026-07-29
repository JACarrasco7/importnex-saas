<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;
use Inertia\Response;

class BillingController extends Controller
{
    public function index(Request $request): Response
    {
        $user = $request->user();
        $organization = $user->organization;

        $invoices = collect();
        $subscription = null;
        $paymentMethod = null;
        $upcomingInvoice = null;
        $hasStripeId = (bool) ($organization?->stripe_id);
        $stripeConfigured = $this->stripeIsConfigured();

        if ($hasStripeId && $stripeConfigured) {
            try {
                $subscription = $organization->subscription();
            } catch (\Throwable $e) {
                Log::warning('Billing: subscription load failed', ['err' => $e->getMessage()]);
            }

            try {
                $invoices = $organization->invoices(true)->take(50)->map(fn ($invoice) => [
                    'id' => $invoice->id,
                    'number' => $invoice->number,
                    'date' => $invoice->date()?->toDateTimeString(),
                    'total' => $invoice->total(),
                    'currency' => $invoice->currency,
                    'status' => $invoice->paid ? 'paid' : 'open',
                    'pdf_url' => $invoice->invoice_pdf,
                    'hosted_url' => $invoice->hosted_invoice_url,
                ])->values();
            } catch (\Throwable $e) {
                Log::warning('Billing: invoices load failed', ['err' => $e->getMessage()]);
            }

            try {
                if ($subscription?->active()) {
                    $upcomingInvoice = $organization->upcomingInvoice();
                }
            } catch (\Throwable $e) {
                $upcomingInvoice = null;
            }

            try {
                $paymentMethod = $organization->defaultPaymentMethod();
            } catch (\Throwable $e) {
                $paymentMethod = null;
            }
        }

        $stats = [
            'total_paid' => $invoices->where('status', 'paid')->sum(fn ($i) => $i['total'] / 100),
            'invoice_count' => $invoices->count(),
            'last_payment' => $invoices->where('status', 'paid')->first()['date'] ?? null,
        ];

        return Inertia::render('Billing/Index', [
            'invoices' => $invoices->values(),
            'subscription' => $subscription ? [
                'name' => $subscription->type,
                'stripe_id' => $subscription->stripe_id,
                'stripe_status' => $subscription->stripe_status,
                'on_trial' => $subscription->onTrial(),
                'ends_at' => $subscription->ends_at?->toDateTimeString(),
                'current_period_end' => $subscription->asStripeSubscription()?->current_period_end,
            ] : null,
            'paymentMethod' => $paymentMethod ? [
                'brand' => $paymentMethod->card?->brand,
                'last4' => $paymentMethod->card?->last4,
                'exp_month' => $paymentMethod->card?->exp_month,
                'exp_year' => $paymentMethod->card?->exp_year,
            ] : null,
            'upcomingInvoice' => $upcomingInvoice ? [
                'total' => $upcomingInvoice->total(),
                'currency' => $upcomingInvoice->currency,
                'date' => $upcomingInvoice->date()?->toDateTimeString(),
            ] : null,
            'stats' => $stats,
            'hasStripeId' => $hasStripeId,
            'stripeConfigured' => $stripeConfigured,
            'stripePortalUrl' => ($hasStripeId && $stripeConfigured) ? route('billing.portal') : null,
        ]);
    }

    public function portal(Request $request)
    {
        $organization = $request->user()->organization;

        abort_unless($organization?->stripe_id && $this->stripeIsConfigured(), 404);

        return $organization->redirectToBillingPortal(route('billing.index'));
    }

    public function download(Request $request, string $invoiceId)
    {
        $organization = $request->user()->organization;

        abort_unless($organization?->stripe_id && $this->stripeIsConfigured(), 404);

        try {
            $invoice = $organization->invoices(true)->firstWhere('id', $invoiceId);
        } catch (\Throwable $e) {
            abort(404);
        }

        abort_unless($invoice, 404);

        return $invoice->download();
    }

    public function show(Request $request, string $invoiceId)
    {
        $organization = $request->user()->organization;

        abort_unless($organization?->stripe_id && $this->stripeIsConfigured(), 404);

        try {
            $invoice = $organization->invoices(true)->firstWhere('id', $invoiceId);
        } catch (\Throwable $e) {
            abort(404);
        }

        abort_unless($invoice, 404);

        return Inertia::render('Billing/Show', [
            'invoice' => [
                'id' => $invoice->id,
                'number' => $invoice->number,
                'date' => $invoice->date()?->toDateTimeString(),
                'total' => $invoice->total(),
                'subtotal' => $invoice->subtotal(),
                'tax' => $invoice->tax(),
                'currency' => $invoice->currency,
                'status' => $invoice->paid ? 'paid' : 'open',
                'pdf_url' => $invoice->invoice_pdf,
                'hosted_url' => $invoice->hosted_invoice_url,
                'lines' => $invoice->lines()->map(fn ($line) => [
                    'description' => $line->description,
                    'amount' => $line->amount,
                    'currency' => $line->currency,
                    'quantity' => $line->quantity,
                    'period' => $line->period,
                ])->values(),
            ],
        ]);
    }

    private function stripeIsConfigured(): bool
    {
        $secret = config('cashier.secret') ?? config('services.stripe.secret');

        return ! empty($secret) && ! str_contains((string) $secret, 'your-stripe-secret');
    }
}
