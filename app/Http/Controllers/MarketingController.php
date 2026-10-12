<?php

namespace App\Http\Controllers;

use App\Models\Car;
use App\Models\CarMarketingContent;
use Inertia\Inertia;
use Inertia\Response;

class MarketingController extends Controller
{
    public function index(): Response
    {
        // Convención de listados de vehículos: precio más bajo primero.
        // La relación se llama marketingContents en PHP y se serializa como
        // marketing_contents en las props de Inertia (ver regla pages.md).
        // No usamos ->select() para reducir columnas porque ya está limitado
        // a las 6 claves útiles para los chips del overview.
        $cars = Car::with(['marketingContents' => function ($q) {
            $q->select('id', 'car_id', 'channel', 'status', 'source', 'published_at');
        }])
            ->orderBy('purchase_price', 'asc')
            ->paginate(24)
            ->withQueryString();

        $stats = [
            'total_cars' => Car::count(),
            'with_content' => Car::has('marketingContents')->count(),
            'published' => CarMarketingContent::where('status', 'published')->count(),
            'drafts' => CarMarketingContent::where('status', 'draft')->count(),
            'from_zip' => CarMarketingContent::where('source', 'zip')->count(),
        ];

        return Inertia::render('Marketing/Index', [
            'cars' => $cars,
            'stats' => $stats,
        ]);
    }
}
