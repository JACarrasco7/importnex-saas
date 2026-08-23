<?php

namespace App\Http\Controllers;

use App\Models\ContractAcceptance;
use App\Support\ChromePath;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response as HttpResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\View;
use Illuminate\Support\Str;
use Spatie\Browsershot\Browsershot;

/**
 * Flujo público del contrato de prestación de servicios.
 *
 *   GET  /contrato/{token}            → vista HTML con el texto + botón firmar
 *   POST /contrato/{token}/aceptar    → registra la aceptación (IP, UA, hash)
 *   GET  /contrato/{token}/pdf        → PDF firmado (con hash al pie)
 *
 * Sin auth. Rate limit por IP. Una vez aceptado, las llamadas a /aceptar
 * devuelven 409 Conflict (idempotencia: no se duplica la firma).
 */
class PublicContractController extends Controller
{
    public function show(string $token): HttpResponse
    {
        $contract = $this->findContract($token);
        $car = $contract->car;

        $this->rateLimit('show:'.request()->ip().':'.substr($token, 0, 8));

        $text = $contract->getContractText();
        $hash = ContractAcceptance::hashContract($text);

        return response()->view('contracts.show', [
            'contract' => $contract,
            'car' => $car,
            'contractTextHtml' => $this->markdownToHtml($text),
            'contractHash' => $hash,
            'ui' => config('contracts.ui'),
            'prestador' => config('contracts.prestador'),
            'accepted' => $contract->accepted_at !== null,
            'accepted_at' => $contract->accepted_at?->format('d/m/Y H:i:s'),
        ]);
    }

    public function accept(Request $request, string $token): JsonResponse
    {
        $this->rateLimit('accept:'.$request->ip().':'.substr($token, 0, 8), 10, 60);

        $data = $request->validate([
            'accept' => ['required', 'accepted'],
            'client_name' => ['nullable', 'string', 'max:191'],
            'client_dni' => ['nullable', 'string', 'max:32'],
        ]);

        try {
            // Transacción con lock: evita doble firma por requests concurrentes (M5).
            $contract = DB::transaction(function () use ($token, $data, $request) {
                $contract = $this->findContract($token);

                if ($contract->accepted_at) {
                    return null; // ya firmado
                }

                // Actualizar snapshot con los datos que tecleó el firmante, para que
                // el texto firmado (y su hash) coincidan EXACTAMENTE con lo que ve.
                $name = $data['client_name'] ?? $contract->client_name;
                $dni = $data['client_dni'] ?? $contract->client_dni;

                $snapshot = $contract->snapshot ?? [];
                if ($name) {
                    $snapshot['cliente_nombre'] = $name;
                }
                if ($dni) {
                    $snapshot['cliente_dni'] = $dni;
                }

                $contract->snapshot = $snapshot;
                $contract->client_name = $name;
                $contract->client_dni = $dni;

                // Recalcular el texto y el hash con los datos finales del firmante.
                $text = $contract->getContractText();
                $contract->contract_hash = ContractAcceptance::hashContract($text);
                $contract->accepted_at = now();
                $contract->accepted_ip = $request->ip();
                $contract->user_agent = substr((string) $request->userAgent(), 0, 191);
                $contract->locale = substr(app()->getLocale(), 0, 8);
                $contract->save();

                return $contract;
            });
        } catch (\Throwable $e) {
            Log::error('contract.accept: exception', [
                'token_prefix' => substr($token, 0, 8),
                'message' => $e->getMessage(),
                'class' => get_class($e),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'ok' => false,
                'message' => 'No se pudo procesar la firma. Inténtalo de nuevo.',
            ], 500);
        }

        if (! $contract) {
            return response()->json(['ok' => false, 'error' => 'ya_firmado'], 409);
        }

        Log::info('contract.accepted', [
            'contract_id' => $contract->id,
            'car_id' => $contract->car_id,
            'ip' => $request->ip(),
        ]);

        return response()->json([
            'ok' => true,
            'pdf_url' => route('public.contract.pdf', $token),
        ]);
    }

    public function pdf(string $token): HttpResponse
    {
        $contract = $this->findContract($token);
        $this->rateLimit('pdf:'.request()->ip().':'.substr($token, 0, 8));

        if (! $contract->accepted_at) {
            abort(403, 'El contrato aún no ha sido firmado.');
        }

        $text = $contract->getContractText();
        $hash = $contract->contract_hash;

        $html = View::make('contracts.pdf', [
            'contract' => $contract,
            'contractTextHtml' => $this->markdownToHtml($text),
            'hash' => $hash,
            'prestador' => config('contracts.prestador'),
            'trackingUrl' => $contract->car?->tracking_url,
            'acceptedAt' => $contract->accepted_at->format('d/m/Y H:i:s'),
            'ip' => $contract->accepted_ip,
        ])->render();

        // Sin Chrome headless no podemos rasterizar el PDF: devolvemos el HTML
        // del contrato firmado (imprimible / exportable a PDF desde el navegador)
        // en lugar de un 500. Mismo patrón que PaqueteValoracionController.
        $chrome = ChromePath::resolve();
        if (! $chrome) {
            Log::warning('contract.pdf: chrome no disponible, devolviendo HTML', [
                'contract_id' => $contract->id,
            ]);

            return response($html, 200)->header('Content-Type', 'text/html');
        }

        $tmpHtml = tempnam(sys_get_temp_dir(), 'contract_').'.html';
        file_put_contents($tmpHtml, $html);

        $tmpPdf = tempnam(sys_get_temp_dir(), 'contract_').'.pdf';

        try {
            Browsershot::html($html)
                ->noSandbox()
                ->setChromePath($chrome)
                ->format('A4')
                ->showBrowserHeaderAndFooter()
                ->headerHtml('<div style="font-size:9px;color:#6b7280;width:100%;padding:0 14mm;">JJ Import Motors · Contrato #'.$contract->id.'</div>')
                ->footerHtml('<div style="font-size:8px;color:#9ca3af;width:100%;padding:0 14mm;text-align:center;">SHA256 '.substr($hash, 0, 32).'… · Firmado el '.$contract->accepted_at->format('d/m/Y H:i').' · IP '.$contract->accepted_ip.'</div>')
                ->save($tmpPdf);
        } catch (\Throwable $e) {
            Log::error('contract.pdf: Browsershot falló, devolviendo HTML', [
                'contract_id' => $contract->id,
                'message' => $e->getMessage(),
                'class' => get_class($e),
            ]);
            @unlink($tmpHtml);
            @unlink($tmpPdf);

            return response($html, 200)->header('Content-Type', 'text/html');
        } finally {
            @unlink($tmpHtml);
        }

        $filename = 'Contrato_'.Str::slug($contract->car?->brand ?? 'coche').'_'.Str::slug($contract->car?->model ?? 'importado').'_'.substr($hash, 0, 8).'.pdf';
        $content = file_get_contents($tmpPdf);
        @unlink($tmpPdf);

        return response($content, 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'attachment; filename="'.$filename.'"',
        ]);
    }

    /** Busca el contrato sin global scopes, con throttle. */
    private function findContract(string $token): ContractAcceptance
    {
        return ContractAcceptance::withoutGlobalScopes()
            ->where('public_token', $token)
            ->firstOr(function () {
                abort(404, (string) config('contracts.ui.errores.token_invalido'));
            });
    }

    private function rateLimit(string $key, int $max = 60, int $decay = 60): void
    {
        if (RateLimiter::tooManyAttempts($key, $max)) {
            abort(429);
        }
        RateLimiter::hit($key, $decay);
    }

    /**
     * Conversión muy básica de Markdown a HTML para el contrato.
     *
     * ⚠️ Seguridad: TODO contenido (incluidas cabeceras) se escapa con e().
     * Nunca se emite contenido del CRM como HTML crudo (A5/XSS).
     */
    /**
     * Convierte el texto del contrato a HTML procesando LÍNEA A LÍNEA, de modo
     * que los títulos (##) y separadores (---) sean bloques propios y nunca se
     * confundan con párrafos (antes quedaban como texto visible dentro de <p>).
     */
    private function markdownToHtml(string $md): string
    {
        $lines = preg_split('/\r?\n/', $md);
        $html = [];
        $para = [];

        $flush = function () use (&$html, &$para) {
            if (! $para) {
                return;
            }
            $text = trim(implode("\n", $para));
            if ($text === '') {
                return;
            }
            $escaped = e($text);
            $escaped = preg_replace('/\*\*(.+?)\*\*/', '<strong>$1</strong>', $escaped);
            $html[] = '<p class="text-sm leading-relaxed text-asphalt-700">'.nl2br($escaped).'</p>';
            $para = [];
        };

        foreach ($lines as $line) {
            $line = rtrim($line);

            if ($line === '') {
                $flush();

                continue;
            }

            if (preg_match('/^# (.+)$/', $line, $m)) {
                $flush();
                $html[] = '<h2 class="mt-6 text-xl font-bold text-asphalt-900">'.e($m[1]).'</h2>';

                continue;
            }
            if (preg_match('/^## (.+)$/', $line, $m)) {
                $flush();
                $html[] = '<h3 class="mt-4 text-base font-semibold text-asphalt-900">'.e($m[1]).'</h3>';

                continue;
            }
            if (preg_match('/^-{3,}$/', $line)) {
                $flush();
                $html[] = '<hr class="my-4 border-asphalt-200" />';

                continue;
            }

            $para[] = $line;
        }
        $flush();

        return implode("\n", $html);
    }
}
