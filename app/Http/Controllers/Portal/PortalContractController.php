<?php

namespace App\Http\Controllers\Portal;

use App\Http\Controllers\Controller;
use App\Http\Requests\Portal\SignContractRequest;
use App\Services\ImportOrders\OrderContractService;
use App\Services\ImportOrders\OrderFlowException;
use App\Services\ImportOrders\PortalPresenter;
use App\Support\ContractDocument;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\Response;

/** Firma y rechazo del contrato desde el portal del cliente. */
class PortalContractController extends Controller
{
    use ResolvesPortalOrder;

    public function __construct(
        private OrderContractService $contracts,
        private PortalPresenter $presenter,
    ) {}

    public function sendCode(Request $request, string $token): RedirectResponse
    {
        $order = $this->portalOrder($token);
        $contract = $this->presenter->portalContract($order);

        return $this->portalAction(function () use ($order, $contract, $request) {
            if (! $contract) {
                throw new OrderFlowException('No hay contrato pendiente de firma.');
            }
            $this->contracts->sendSignatureCode($order, $contract, $request->ip());
        }, 'Te hemos enviado un código de 6 dígitos a tu email.');
    }

    public function sign(SignContractRequest $request, string $token): RedirectResponse
    {
        $order = $this->portalOrder($token);
        $contract = $this->presenter->portalContract($order);

        return $this->portalAction(function () use ($order, $contract, $request) {
            if (! $contract) {
                throw new OrderFlowException('No hay contrato pendiente de firma.');
            }
            $this->contracts->sign($order, $contract, $request->safe()->only(['name', 'dni', 'code']), $request->ip(), $request->userAgent());
        }, '¡Contrato firmado! Te hemos enviado una copia a tu email.');
    }

    public function reject(Request $request, string $token): RedirectResponse
    {
        $order = $this->portalOrder($token);
        $contract = $this->presenter->portalContract($order);
        $data = $request->validate(['reason' => ['required', 'string', 'min:5', 'max:2000']]);

        return $this->portalAction(function () use ($order, $contract, $data, $request) {
            if (! $contract) {
                throw new OrderFlowException('No hay contrato pendiente de firma.');
            }
            $this->contracts->reject($order, $contract, $data['reason'], $request->ip());
        }, 'Gracias por contárnoslo. Te contactamos para resolver tus dudas.');
    }

    /** Descarga del contrato firmado (fichero archivado al firmar). */
    public function pdf(string $token): Response
    {
        $order = $this->portalOrder($token);
        $contract = $order->signedContract();
        abort_unless($contract, 404);

        ContractDocument::archive($contract);
        $contract->refresh();

        if ($contract->pdf_path && Storage::disk('local')->exists($contract->pdf_path)) {
            $isPdf = str_ends_with($contract->pdf_path, '.pdf');

            return Storage::disk('local')->download(
                $contract->pdf_path,
                ContractDocument::downloadName($contract, $isPdf ? 'pdf' : 'html'),
                ['Content-Type' => $isPdf ? 'application/pdf' : 'text/html; charset=UTF-8'],
            );
        }

        return response(ContractDocument::signedHtml($contract), 200, ['Content-Type' => 'text/html; charset=UTF-8']);
    }
}
