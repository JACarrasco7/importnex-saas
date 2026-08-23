<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use SimpleSoftwareIO\QrCode\Facades\QrCode;
use Spatie\Browsershot\Browsershot;

/**
 * Folleto institucional de JJ Import Motors (marketing, público).
 *
 * TIPO DE DOCUMENTO: folleto de servicio (estático, no depende de esqueletos
 * del ZIP). Es UNO de los 4 PDFs que genera LARAVEL — los otros son:
 *   - ficha-coche    → contenido/ficha-publicitaria.txt  (PaqueteValoracionController@ficha)
 *   - informe-interno→ contenido/informe-interno.txt     (PaqueteValoracionController@interno)
 *   - dossier        → contenido/dossier-cliente.txt      (documento del cliente)
 * Los PDFs de INVESTIGACIÓN (informe_busqueda / informe_unidad) los genera CLAUDE
 * con plantilla_pdf_marca.html, no Laravel.
 */
class JJImportFolletoController extends Controller
{
    public function download(Request $request)
    {
        $publicPath = public_path('jj-import-folleto.pdf');

        // Serve static PDF directly (PDF is generated locally and uploaded via SCP)
        if (file_exists($publicPath) && filesize($publicPath) > 100000) {
            return response()->download($publicPath, 'JJ_Import_Motors_Folleto.pdf');
        }

        // Fallback: try to generate on the fly (requires Chrome/Chromium on server)
        try {
            $cachePath = storage_path('app/public/jj-import-folleto.pdf');

            $logoPath = public_path('images/jj-import/logo-horizontal-blanco.png');
            $logoBase64 = 'data:image/png;base64,'.base64_encode(file_get_contents($logoPath));

            $qrUrl = route('public.car-request.index', ['slug' => 'jj-import-motors']);
            $qrSvg = QrCode::format('svg')
                ->size(280)
                ->margin(1)
                ->errorCorrection('H')
                ->backgroundColor(255, 255, 255)
                ->color(6, 16, 31)
                ->generate($qrUrl);

            $html = view('jj-import.folleto', [
                'logo_base64' => $logoBase64,
                'qr_svg' => $qrSvg,
                'qr_url' => $qrUrl,
                'precio_honorarios' => '1.500 €',
                'telefono_1' => '675 70 14 39',
                'telefono_2' => '691 48 59 27',
                'email' => 'jjimportmotors@gmail.com',
            ])->render();

            Browsershot::html($html)
                ->noSandbox()
                ->format('A4')
                ->landscape(false)
                ->margins(0, 0, 0, 0)
                ->showBackground()
                ->waitUntilNetworkIdle()
                ->deviceScaleFactor(2)
                ->scale(1)
                ->savePdf($cachePath);

            if (file_exists($cachePath)) {
                copy($cachePath, $publicPath);

                return response()->download($cachePath, 'JJ_Import_Motors_Folleto.pdf');
            }
        } catch (\Exception $e) {
            Log::error('Error generando PDF JJ Import Motors: '.$e->getMessage());
        }

        return response()->json([
            'error' => 'No se pudo generar el PDF. Configura Chrome/Chromium en el servidor.',
        ], 500);
    }
}
