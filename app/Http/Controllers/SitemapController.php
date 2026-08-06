<?php

namespace App\Http\Controllers;

use App\Models\Car;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Route;

class SitemapController extends Controller
{
    public function index(): Response
    {
        $cars = Car::query()
            ->where('published', true)
            ->whereNotNull('published_at')
            ->orderBy('published_at', 'desc')
            ->get(['id', 'published_at', 'updated_at']);

        return response()->view('sitemap', [
            'cars' => $cars,
            'lastMod' => $cars->first()?->updated_at ?? now(),
        ])->header('Content-Type', 'text/xml');
    }
}