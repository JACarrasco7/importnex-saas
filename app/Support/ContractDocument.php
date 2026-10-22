<?php

namespace App\Support;

use App\Models\ContractAcceptance;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\View;
use Spatie\Browsershot\Browsershot;

/**
 * Render del contrato: texto → HTML seguro, HTML del documento firmado y PDF.
 *
 * El PDF del contrato firmado se genera UNA vez al firmar y se archiva en el
 * disco privado `local` (contracts/{org}/{id}.pdf) con su propio hash. Las
 * descargas sirven ese fichero; así el documento entregado no cambia aunque
 * cambien las plantillas Blade.
 */
class ContractDocument
{
    /**
     * Markdown mínimo del contrato → HTML. TODO se escapa con e(): nunca se
     * emite contenido del CRM como HTML crudo.
     */
    public static function toHtml(string $markdown): string
    {
        $lines = preg_split('/\r?\n/', $markdown) ?: [];
        $html = [];
        $paragraph = [];

        $flush = function () use (&$html, &$paragraph): void {
            $text = trim(implode("\n", $paragraph));
            $paragraph = [];
            if ($text === '') {
                return;
            }
            $escaped = e($text);
            $escaped = preg_replace('/\*\*(.+?)\*\*/', '<strong>$1</strong>', $escaped);
            $html[] = '<p class="text-sm leading-relaxed text-asphalt-700">'.nl2br((string) $escaped).'</p>';
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

            $paragraph[] = $line;
        }
        $flush();

        return implode("\n", $html);
    }

    /** HTML completo del contrato firmado (plantilla contracts.pdf). */
    public static function signedHtml(ContractAcceptance $contract): string
    {
        return View::make('contracts.pdf', [
            'contract' => $contract,
            'contractTextHtml' => self::toHtml($contract->getContractText()),
            'hash' => $contract->contract_hash,
            'prestador' => $contract->snapshot['_prestador'] ?? config('contracts.prestador'),
            'trackingUrl' => $contract->importOrder?->portalUrl() ?? $contract->car?->tracking_url,
            'acceptedAt' => $contract->accepted_at?->format('d/m/Y H:i:s'),
            'ip' => $contract->accepted_ip,
        ])->render();
    }

    /**
     * Genera el PDF del contrato firmado. Devuelve null si no hay Chrome
     * disponible o Browsershot falla (se registra y se sirve HTML).
     */
    public static function renderPdf(ContractAcceptance $contract): ?string
    {
        $chrome = ChromePath::resolve();
        if (! $chrome) {
            return null;
        }

        $tmpPdf = tempnam(sys_get_temp_dir(), 'contract_').'.pdf';

        try {
            Browsershot::html(self::signedHtml($contract))
                ->noSandbox()
                ->setChromePath($chrome)
                ->format('A4')
                ->showBrowserHeaderAndFooter()
                ->headerHtml('<div style="font-size:9px;color:#6b7280;width:100%;padding:0 14mm;">JJ Import Motors · Contrato #'.$contract->id.'</div>')
                ->footerHtml('<div style="font-size:8px;color:#9ca3af;width:100%;padding:0 14mm;text-align:center;">SHA256 '.substr((string) $contract->contract_hash, 0, 32).'… · Firmado el '.$contract->accepted_at?->format('d/m/Y H:i').' · IP '.$contract->accepted_ip.'</div>')
                ->save($tmpPdf);

            $content = file_get_contents($tmpPdf) ?: null;
        } catch (\Throwable $e) {
            Log::error('contract.pdf: Browsershot falló', [
                'contract_id' => $contract->id,
                'message' => $e->getMessage(),
            ]);
            $content = null;
        } finally {
            @unlink($tmpPdf);
        }

        return $content;
    }

    /**
     * Archiva el contrato firmado (PDF si hay Chrome; si no, HTML) y guarda
     * ruta + hash en el contrato. Idempotente: si ya está archivado no hace nada.
     */
    public static function archive(ContractAcceptance $contract): void
    {
        if ($contract->pdf_path || ! $contract->accepted_at) {
            return;
        }

        $pdf = self::renderPdf($contract);
        $extension = $pdf !== null ? 'pdf' : 'html';
        $content = $pdf ?? self::signedHtml($contract);
        $path = sprintf('contracts/%d/contrato-%d-%s.%s', $contract->organization_id, $contract->id, substr((string) $contract->contract_hash, 0, 8), $extension);

        try {
            Storage::disk('local')->put($path, $content);
            $contract->forceFill([
                'pdf_path' => $path,
                'pdf_hash' => hash('sha256', $content),
            ])->save();
        } catch (\Throwable $e) {
            Log::error('contract.archive: no se pudo guardar el documento firmado', [
                'contract_id' => $contract->id,
                'message' => $e->getMessage(),
            ]);
        }
    }

    public static function downloadName(ContractAcceptance $contract, string $extension = 'pdf'): string
    {
        $car = $contract->car;

        return sprintf(
            'Contrato_%s_%s_%s.%s',
            str((string) ($car?->brand ?? 'coche'))->slug(),
            str((string) ($car?->model ?? 'importado'))->slug(),
            substr((string) $contract->contract_hash, 0, 8),
            $extension,
        );
    }
}
