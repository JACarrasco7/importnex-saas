<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Car;
use App\Models\CarMarketingContent;
use Inertia\Inertia;
use Inertia\Response;

class MarketingController extends Controller
{
    public function index(): Response
    {
        $cars = Car::with(['marketingContents' => function ($q) {
            $q->select('car_id', 'channel', 'status', 'published_at');
        }])->get();

        $stats = [
            'total_cars' => $cars->count(),
            'with_content' => $cars->filter(fn ($c) => $c->marketingContents->isNotEmpty())->count(),
            'published' => CarMarketingContent::where('status', 'published')->count(),
            'drafts' => CarMarketingContent::where('status', 'draft')->count(),
        ];

        return Inertia::render('Marketing/Index', [
            'cars' => $cars,
            'stats' => $stats,
        ]);
    }
}
