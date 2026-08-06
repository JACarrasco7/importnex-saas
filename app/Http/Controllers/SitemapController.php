<?php

namespace App\Http\Controllers;

use App\Models\Car;
use Illuminate\Http\Response;

class SitemapController extends Controller
{
    public function index(): Response
    {
        $cars = Car::query()
            ->where('is_marketplace', true)
            ->orderBy('updated_at', 'desc')
            ->limit(500)
            ->get(['id', 'updated_at']);

        return response()->view('sitemap', [
            'cars' => $cars,
            'lastMod' => $cars->first()?->updated_at ?? now(),
        ])->header('Content-Type', 'text/xml');
    }
}
