<?php

namespace App\Http\Controllers\Portal;

use App\Http\Controllers\Controller;
use App\Mail\OrderUpdateMail;
use App\Models\CarPhoto;
use App\Models\Client;
use App\Models\ImportOrder;
use App\Models\OrderMedia;
use App\Services\ImportOrders\ImportOrderService;
use App\Services\ImportOrders\OrderContractService;
use App\Services\ImportOrders\OrderMessageService;
use App\Services\ImportOrders\PortalPresenter;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Portal del cliente: un único enlace privado por encargo (/mi-encargo/{token})
 * con contrato, pagos, aprobaciones, mensajes y seguimiento.
 */
class PortalController extends Controller
{
    use ResolvesPortalOrder;

    public function __construct(
        private PortalPresenter $presenter,
        private OrderContractService $contracts,
        private OrderMessageService $messages,
        private ImportOrderService $orders,
    ) {}

    public function show(Request $request, string $token): Response
    {
        $order = $this->portalOrder($token);

        if (! $this->looksLikeBot($request->userAgent())) {
            $order->forceFill([
                'portal_views' => $order->portal_views + 1,
                'portal_last_seen_at' => now(),
            ])->saveQuietly();

            $contract = $this->presenter->portalContract($order);
            if ($contract && $contract->isSignable()) {
                $this->contracts->markViewed($order, $contract);
            }
            $this->messages->markReadByClient($order);
        }

        return Inertia::render('Public/Portal', $this->presenter->present($order, $token))
            ->withViewData(['ogMeta' => $this->presenter->ogMeta($order, $token)]);
    }

    public function requestCancellation(Request $request, string $token): RedirectResponse
    {
        $order = $this->portalOrder($token);
        $data = $request->validate(['reason' => ['required', 'string', 'min:5', 'max:2000']]);

        return $this->portalAction(
            fn () => $this->orders->requestCancellation($order, $data['reason'], $request->ip()),
            'Hemos recibido tu solicitud. Te llamamos en cuanto la revisemos.',
        );
    }

    /** Foto del coche (solo exterior/interior/motor del coche del encargo). */
    public function carPhoto(string $token, int $photo): StreamedResponse
    {
        $order = $this->portalOrder($token);
        $carPhoto = CarPhoto::withoutGlobalScopes()
            ->where('car_id', $order->car_id)
            ->whereIn('photo_type', ['exterior', 'interior', 'engine'])
            ->findOrFail($photo);

        $relative = str_starts_with($carPhoto->url, '/storage/')
            ? substr($carPhoto->url, strlen('/storage/'))
            : ltrim($carPhoto->url, '/');

        abort_unless(Storage::disk('public')->exists($relative), 404);

        return Storage::disk('public')->response($relative, null, [
            'Cache-Control' => 'private, max-age=86400',
            'X-Robots-Tag' => 'noindex',
        ]);
    }

    /** Foto o documento del encargo visible para el cliente. */
    public function media(string $token, int $media): StreamedResponse
    {
        $order = $this->portalOrder($token);
        $item = OrderMedia::query()
            ->where('import_order_id', $order->id)
            ->where(fn ($q) => $q->where('visibility', OrderMedia::VISIBILITY_CLIENT)->orWhereNotNull('order_approval_id'))
            ->findOrFail($media);

        abort_unless(Storage::disk($item->disk)->exists($item->path), 404);

        $disposition = $item->isImage() || $item->mime === 'application/pdf' ? 'inline' : 'attachment';

        return Storage::disk($item->disk)->response($item->path, $item->original_name, [
            'Cache-Control' => 'private, max-age=3600',
            'X-Robots-Tag' => 'noindex',
        ], $disposition);
    }

    public function recoverForm(): Response
    {
        return Inertia::render('Public/PortalRecover');
    }

    /**
     * Reenvía el enlace del portal al email del cliente. La respuesta es la
     * misma exista o no el email (no revela quién es cliente).
     */
    public function recover(Request $request): RedirectResponse
    {
        $data = $request->validate(['email' => ['required', 'email', 'max:191']]);

        $clients = Client::withoutGlobalScopes()->where('email', strtolower($data['email']))->get();
        foreach ($clients as $client) {
            $orders = ImportOrder::query()
                ->where('client_id', $client->id)
                ->where(fn ($q) => $q->active()->orWhere('updated_at', '>=', now()->subDays((int) config('import_orders.portal_days_after_close', 90))))
                ->get();

            foreach ($orders as $order) {
                $url = $order->portalUrl();
                if (! $url) {
                    continue;
                }
                try {
                    Mail::to($client->email)->send(new OrderUpdateMail(
                        subjectLine: __('orders.mail.recover_subject'),
                        heading: __('orders.mail.recover_heading'),
                        lines: [__('orders.mail.recover_line1', ['reference' => $order->reference, 'vehicle' => $order->vehicleLabel()])],
                        actionLabel: __('orders.mail.button_portal'),
                        actionUrl: $url,
                        greeting: __('orders.mail.greeting', ['name' => explode(' ', (string) $client->name)[0]]),
                    ));
                } catch (\Throwable $e) {
                    Log::warning('portal.recover_mail_failed', ['order_id' => $order->id, 'message' => $e->getMessage()]);
                }
            }
        }

        return back()->with('success', 'Si ese email tiene un encargo con nosotros, te acabamos de enviar el enlace. Revisa también la carpeta de spam.');
    }

    private function looksLikeBot(?string $userAgent): bool
    {
        return $userAgent !== null
            && (bool) preg_match('/bot|crawl|spider|slurp|curl|wget|python-requests|headless|whatsapp|facebookexternalhit|telegram/i', $userAgent);
    }
}
