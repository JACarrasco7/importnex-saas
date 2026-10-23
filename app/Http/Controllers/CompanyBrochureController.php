<?php

namespace App\Http\Controllers;

use App\Services\BrochureRenderer;
use App\Support\PublicSite;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Throwable;

/** Folleto PDF de la empresa dueña de la web pública /web/{site}. */
class CompanyBrochureController extends Controller
{
    public function show(string $site, BrochureRenderer $renderer): BinaryFileResponse
    {
        $resolved = PublicSite::forSlug($site);
        abort_if($resolved === null || $resolved->organization === null, 404);

        try {
            $path = $renderer->path($resolved->organization);
        } catch (Throwable $e) {
            Log::error('brochure.failed', ['organization_id' => $resolved->organization->id, 'message' => $e->getMessage()]);
            abort(503, 'El folleto no está disponible ahora mismo.');
        }

        return response()->file($path, ['Content-Type' => 'application/pdf', 'Content-Disposition' => 'inline; filename="folleto-'.$site.'.pdf"']);
    }
}
