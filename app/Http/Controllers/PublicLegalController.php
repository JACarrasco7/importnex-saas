<?php

namespace App\Http\Controllers;

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
        return view('public.legal');
    }
}
