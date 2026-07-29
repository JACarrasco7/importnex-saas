<?php

namespace App\Providers;

use App\Support\CarChecklistDefinitions;
use App\Support\CarDocumentDefinitions;
use Illuminate\Support\ServiceProvider;

class SupportServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(CarChecklistDefinitions::class);
        $this->app->singleton(CarDocumentDefinitions::class);
    }
}
