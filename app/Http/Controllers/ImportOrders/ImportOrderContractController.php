<?php

namespace App\Http\Controllers\ImportOrders;

use App\Http\Controllers\Controller;
use App\Models\ContractAcceptance;
use App\Models\ImportOrder;
use App\Services\ImportOrders\OrderContractService;
use App\Support\ContractDocument;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\Response;

class ImportOrderContractController extends Controller
{
    use AuthorizesImportOrder;

    public function __construct(private OrderContractService $contracts) {}

    public function issue(Request $request, ImportOrder $importOrder): RedirectResponse
    {
        $this->authorizeOrder($importOrder);

        return $this->flowAction(
            fn () => $this->contracts->issue($importOrder, $request->user()),
            'Contrato enviado. El cliente lo ha recibido por email; puedes avisarle también por WhatsApp.',
        );
    }

    public function void(Request $request, ImportOrder $importOrder): RedirectResponse
    {
        $this->authorizeOrder($importOrder);
        $data = $request->validate(['reason' => ['required', 'string', 'max:255']]);

        $count = $this->contracts->void($importOrder, $data['reason'], $request->user());

        return back()->with($count ? 'success' : 'error', $count ? 'Contrato anulado. Reemítelo cuando tengas los datos correctos.' : 'No hay ningún contrato abierto que anular.');
    }

    public function download(ImportOrder $importOrder, ContractAcceptance $contract): Response
    {
        $this->authorizeChild($importOrder, $contract);
        abort_unless($contract->accepted_at !== null, 404);

        ContractDocument::archive($contract);
        $contract->refresh();

        if ($contract->pdf_path && Storage::disk('local')->exists($contract->pdf_path)) {
            $isPdf = str_ends_with($contract->pdf_path, '.pdf');

            return Storage::disk('local')->download($contract->pdf_path, ContractDocument::downloadName($contract, $isPdf ? 'pdf' : 'html'));
        }

        return response(ContractDocument::signedHtml($contract), 200, ['Content-Type' => 'text/html; charset=UTF-8']);
    }
}
