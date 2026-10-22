<?php

namespace App\Http\Controllers;

use App\Models\Organization;
use App\Support\Brand;
use Illuminate\Http\Request;

/**
 * Pagina publica unica de aviso legal / privacidad / terminos.
 *
 * Se llego a esta pagina con 3 enlaces del footer (Privacidad, Terminos,
 * Legal) que apuntaban a /marketplace#legal, ancla que no existia.
 * Ahora los 3 enlaces llevan a esta pagina con su seccion correspondiente
 * (#privacidad, #terminos, #aviso-legal).
 *
 * URL: GET /legal
 */
class PublicLegalController extends Controller
{
    public function show(Request $request)
    {
        // ?org=<slug> muestra el aviso legal de esa organización; sin él, el
        // de la organización que opera la web pública.
        $organization = $request->filled('org')
            ? Organization::query()->where('slug', (string) $request->query('org'))->where('is_public', true)->first()
            : null;

        return view('public.legal', [
            'brand' => $organization ? Brand::for($organization) : Brand::platform(),
        ]);
    }
}
