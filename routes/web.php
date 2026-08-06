<?php

use App\Http\Controllers\OrganizationController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\SitemapController;
use App\Http\Controllers\StripeWebhookController;
use App\Http\Controllers\PublicCarRequestController;
use App\Models\Organization;
use Illuminate\Foundation\Application;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

// Landing — public marketing page (Welcome.vue, branded, hero + features)
// When guest → renders the marketing landing. When authenticated → redirects to /dashboard.
Route::get('/', function () {
    if (Auth::check()) {
        return redirect('/dashboard');
    }
    $org = Organization::orderBy('id')->first();

    return Inertia::render('Welcome', [
        'canLogin' => Route::has('login'),
        'canRegister' => Route::has('register'),
        'laravelVersion' => Application::VERSION,
        'phpVersion' => PHP_VERSION,
        'organizationName' => $org?->name,
    ]);
})->name('home');

// Public pricing page (SEO + lead capture, no auth required)
Route::get('/pricing', function () {
    return Inertia::render('Public/PricingPublic', [
        'plans' => config('subscription.plans'),
        'currency' => config('subscription.default_currency', 'eur'),
    ]);
})->name('pricing');

// Sitemap.xml (SEO — referenced by robots.txt)
Route::get('/sitemap.xml', [SitemapController::class, 'index'])->name('sitemap');

// Legacy admin landing (kept for backwards-compat, redirects to /)
Route::get('/admin', function () {
    return Auth::check() ? redirect('/dashboard') : redirect('/');
})->name('admin');

// Stripe webhook (must be outside auth/csrf middleware)
Route::post('stripe/webhook', [StripeWebhookController::class, 'handleWebhook']);

// Public car request form
// URL: /request/{slug} — public form for clients to send car preferences
Route::prefix('request/{slug}')->name('public.car-request.')->group(function () {
    Route::get('/', [PublicCarRequestController::class, 'index'])->name('index');
    Route::post('/', [PublicCarRequestController::class, 'store'])
        ->middleware('throttle:5,10')
        ->name('store');
    Route::get('/success', [PublicCarRequestController::class, 'success'])->name('success');
});

// Public marketplace for vetted cars
// URL: /marketplace — public browsable marketplace of verified cars
Route::prefix('marketplace')->name('marketplace.')->group(function () {
    Route::get('/', [\App\Http\Controllers\PublicMarketplaceController::class, 'index'])->name('index');
    Route::get('/{car}', [\App\Http\Controllers\PublicMarketplaceController::class, 'show'])->name('show');
});

Route::middleware('auth', 'has.organization')->group(function () {
    Route::get('organization/create', [OrganizationController::class, 'create'])
        ->name('organization.create');
    Route::post('organization', [OrganizationController::class, 'store'])
        ->name('organization.store');
});

Route::middleware(['auth', 'verified', 'organization'])->group(function () {
    Route::get('/dashboard', [\App\Http\Controllers\DashboardController::class, '__invoke'])
        ->name('dashboard');

    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    Route::get('/organization/{organization}', [OrganizationController::class, 'show'])
        ->name('organization.show');
    Route::get('/organization/{organization}/edit', [OrganizationController::class, 'edit'])
        ->name('organization.edit');
    Route::patch('/organization/{organization}', [OrganizationController::class, 'update'])
        ->name('organization.update');
    Route::post('/organization/{organization}/ai-models', [OrganizationController::class, 'aiModels'])
        ->name('organization.ai-models');

    // Cars CRUD
    Route::get('/cars', [\App\Http\Controllers\CarController::class, 'index'])->name('cars.index');
    Route::get('/cars/create', [\App\Http\Controllers\CarController::class, 'create'])->name('cars.create');
    Route::post('/cars', [\App\Http\Controllers\CarController::class, 'store'])
        ->middleware('plan.limit:cars')
        ->name('cars.store');
    Route::post('/cars/import', [\App\Http\Controllers\CarController::class, 'import'])->name('cars.import');
    Route::post('/cars/scrape-url', [\App\Http\Controllers\CarController::class, 'scrapeUrl'])
        ->middleware('throttle:30,1')
        ->name('cars.scrape-url');
    Route::get('/cars/{car}', [\App\Http\Controllers\CarController::class, 'show'])->name('cars.show');
    Route::get('/cars/{car}/edit', [\App\Http\Controllers\CarController::class, 'edit'])->name('cars.edit');
    Route::patch('/cars/{car}', [\App\Http\Controllers\CarController::class, 'update'])->name('cars.update');
    Route::delete('/cars/{car}', [\App\Http\Controllers\CarController::class, 'destroy'])->name('cars.destroy');

    // Car Photos
    Route::post('/cars/{car}/photos', [\App\Http\Controllers\CarPhotoController::class, 'store'])->name('cars.photos.store');
    Route::delete('/cars/{car}/photos/{photo}', [\App\Http\Controllers\CarPhotoController::class, 'destroy'])->name('cars.photos.destroy');
    Route::post('/cars/{car}/photos/reorder', [\App\Http\Controllers\CarPhotoController::class, 'reorder'])->name('cars.photos.reorder');

    // Car Documents
    Route::post('/cars/{car}/documents', [\App\Http\Controllers\CarDocumentController::class, 'store'])->name('cars.documents.store');
    Route::get('/cars/{car}/documents/{document}', [\App\Http\Controllers\CarDocumentController::class, 'show'])->name('cars.documents.show');
    Route::delete('/cars/{car}/documents/{document}', [\App\Http\Controllers\CarDocumentController::class, 'destroy'])->name('cars.documents.destroy');

    // Valuation import (from chat report ZIP)
    Route::get('/cars/import-valuation', [\App\Http\Controllers\ValuationImportController::class, 'create'])
        ->name('cars.import-valuation.create');
    Route::post('/cars/import-valuation', [\App\Http\Controllers\ValuationImportController::class, 'store'])
        ->name('cars.import-valuation.store');

    // Car Checklist (toggle complete)
    Route::post('/cars/{car}/checklists/{checklist}/toggle', [\App\Http\Controllers\CarChecklistController::class, 'toggle'])
        ->name('cars.checklists.toggle');

    // AI Car Verification
    Route::get('/cars/{car}/verify', [\App\Http\Controllers\CarVerificationController::class, 'show'])->name('cars.verify.show');
    Route::post('/cars/{car}/verify', [\App\Http\Controllers\CarVerificationController::class, 'verify'])->name('cars.verify');
    Route::post('/cars/{car}/verify-sync', [\App\Http\Controllers\CarVerificationController::class, 'verifySync'])->name('cars.verify-sync');
    Route::post('/cars/{car}/verify/apply', [\App\Http\Controllers\CarVerificationController::class, 'apply'])->name('cars.verify.apply');
    Route::post('/cars/{car}/verify/discard', [\App\Http\Controllers\CarVerificationController::class, 'discard'])->name('cars.verify.discard');

    // Car Marketing (AI-generated content for sales channels)
    Route::get('/cars/{car}/marketing', [\App\Http\Controllers\CarMarketingController::class, 'show'])->name('cars.marketing');
    Route::post('/cars/{car}/marketing/generate', [\App\Http\Controllers\CarMarketingController::class, 'generate'])->name('cars.marketing.generate');
    Route::post('/cars/{car}/marketing/save', [\App\Http\Controllers\CarMarketingController::class, 'save'])->name('cars.marketing.save');
    Route::post('/cars/{car}/marketing/publish', [\App\Http\Controllers\CarMarketingController::class, 'publish'])->name('cars.marketing.publish');
    Route::get('/cars/{car}/marketing/briefing', [\App\Http\Controllers\CarMarketingController::class, 'briefing'])->name('cars.marketing.briefing');

    // Paquete de valoración (esqueletos .txt → PDF con Blade + Browsershot)
    // Ficha del cliente: cuelga del expediente (autenticado).
    Route::get('/cars/{car}/ficha', [\App\Http\Controllers\PaqueteValoracionController::class, 'ficha'])->name('cars.ficha');
    // Informe interno: SOLO equipo, nunca expuesto al cliente.
    Route::get('/cars/{car}/informe-interno', [\App\Http\Controllers\PaqueteValoracionController::class, 'interno'])->name('cars.informe-interno');

    // Marketing overview
    Route::get('/marketing', [\App\Http\Controllers\MarketingController::class, 'index'])->name('marketing.index');

    // Car Kanban
    Route::get('/cars-kanban', [\App\Http\Controllers\CarKanbanController::class, 'index'])->name('cars.kanban');
    Route::post('/cars-kanban/{car}/move', [\App\Http\Controllers\CarKanbanController::class, 'move'])->name('cars.kanban.move');

    // Cars Map
    Route::get('/cars-map', [\App\Http\Controllers\CarMapController::class, 'index'])->name('cars.map');

    // Finance
    Route::get('/finance', [\App\Http\Controllers\FinanceController::class, 'index'])->name('finance.index');

    // Trip Planner
    Route::get('/trips', [\App\Http\Controllers\TripPlannerController::class, 'index'])->name('trips.index');

    // Clients CRUD
    Route::get('/clients', [\App\Http\Controllers\ClientController::class, 'index'])->name('clients.index');
    Route::get('/clients/create', [\App\Http\Controllers\ClientController::class, 'create'])->name('clients.create');
    Route::post('/clients', [\App\Http\Controllers\ClientController::class, 'store'])
        ->middleware('plan.limit:clients')
        ->name('clients.store');
    Route::get('/clients/{client}', [\App\Http\Controllers\ClientController::class, 'show'])->name('clients.show');
    Route::get('/clients/{client}/edit', [\App\Http\Controllers\ClientController::class, 'edit'])->name('clients.edit');
    Route::patch('/clients/{client}', [\App\Http\Controllers\ClientController::class, 'update'])->name('clients.update');
    Route::delete('/clients/{client}', [\App\Http\Controllers\ClientController::class, 'destroy'])->name('clients.destroy');

    // Contacts CRUD
    Route::get('/contacts', [\App\Http\Controllers\ContactController::class, 'index'])->name('contacts.index');
    Route::get('/contacts/create', [\App\Http\Controllers\ContactController::class, 'create'])->name('contacts.create');
    Route::post('/contacts', [\App\Http\Controllers\ContactController::class, 'store'])
        ->middleware('plan.limit:contacts')
        ->name('contacts.store');
    Route::get('/contacts/{contact}', [\App\Http\Controllers\ContactController::class, 'show'])->name('contacts.show');
    Route::get('/contacts/{contact}/edit', [\App\Http\Controllers\ContactController::class, 'edit'])->name('contacts.edit');
    Route::patch('/contacts/{contact}', [\App\Http\Controllers\ContactController::class, 'update'])->name('contacts.update');
    Route::delete('/contacts/{contact}', [\App\Http\Controllers\ContactController::class, 'destroy'])->name('contacts.destroy');

    // Client Contact Logs
    Route::get('/clients/{client}/contact-logs', [\App\Http\Controllers\ClientContactLogController::class, 'index'])->name('clients.contact-logs.index');
    Route::post('/clients/{client}/contact-logs', [\App\Http\Controllers\ClientContactLogController::class, 'store'])->name('clients.contact-logs.store');
    Route::delete('/clients/{client}/contact-logs/{log}', [\App\Http\Controllers\ClientContactLogController::class, 'destroy'])->name('clients.contact-logs.destroy');

    // AI generic chat
    Route::get('/ai/chat', [\App\Http\Controllers\AiChatController::class, 'index'])->name('ai.chat');
    Route::post('/ai/chat', [\App\Http\Controllers\AiChatController::class, 'send'])->name('ai.chat.send');

    // Alerts
    Route::get('/alerts', [\App\Http\Controllers\AlertController::class, 'index'])->name('alerts.index');
    Route::get('/alerts/{alert}', [\App\Http\Controllers\AlertController::class, 'show'])->name('alerts.show');
    Route::patch('/alerts/{alert}/mark-resolved', [\App\Http\Controllers\AlertController::class, 'markResolved'])->name('alerts.mark-resolved');
    Route::delete('/alerts/{alert}', [\App\Http\Controllers\AlertController::class, 'destroy'])->name('alerts.destroy');
    // Polling ligero para badge + toasts in-app (no usa Echo/Reverb, evita BD pesada)
    Route::get('/alerts/pending.json', [\App\Http\Controllers\AlertController::class, 'pending'])->name('alerts.pending');
    Route::post('/alerts/mark-all-read', [\App\Http\Controllers\AlertController::class, 'markAllRead'])->name('alerts.mark-all-read');

    // Message Templates
    Route::get('/message-templates', [\App\Http\Controllers\MessageTemplateController::class, 'index'])->name('message-templates.index');
    Route::get('/message-templates/{messageTemplate}', [\App\Http\Controllers\MessageTemplateController::class, 'show'])->name('message-templates.show');

    // Subscriptions
    Route::get('/subscriptions', [\App\Http\Controllers\SubscriptionController::class, 'index'])->name('subscriptions.index');
    Route::get('/subscriptions/{plan}', [\App\Http\Controllers\SubscriptionController::class, 'show'])->name('subscriptions.show');
    Route::post('/subscriptions/{plan}/create', [\App\Http\Controllers\SubscriptionController::class, 'create'])->name('subscriptions.create');
    Route::post('/subscriptions/{plan}/swap', [\App\Http\Controllers\SubscriptionController::class, 'swap'])->name('subscriptions.swap');
    Route::post('/subscriptions/cancel', [\App\Http\Controllers\SubscriptionController::class, 'cancel'])->name('subscriptions.cancel');
    Route::post('/subscriptions/resume', [\App\Http\Controllers\SubscriptionController::class, 'resume'])->name('subscriptions.resume');

    // Billing
    Route::get('/billing', [\App\Http\Controllers\BillingController::class, 'index'])->name('billing.index');
    Route::get('/billing/{invoiceId}', [\App\Http\Controllers\BillingController::class, 'show'])->name('billing.show');
    Route::get('/billing/{invoiceId}/download', [\App\Http\Controllers\BillingController::class, 'download'])->name('billing.download');
    Route::get('/billing/portal/redirect', [\App\Http\Controllers\BillingController::class, 'portal'])->name('billing.portal');

    // Car Requests (internal management)
    Route::get('/car-requests', [\App\Http\Controllers\CarRequestController::class, 'index'])->name('car-requests.index');
    Route::get('/car-requests/{carRequest}', [\App\Http\Controllers\CarRequestController::class, 'show'])->name('car-requests.show');
    Route::patch('/car-requests/{carRequest}/status', [\App\Http\Controllers\CarRequestController::class, 'updateStatus'])->name('car-requests.update-status');
    Route::delete('/car-requests/{carRequest}', [\App\Http\Controllers\CarRequestController::class, 'destroy'])->name('car-requests.destroy');
});

// Locale update (available for all users, including guests)
Route::put('/locale', [\App\Http\Controllers\LocaleController::class, 'update'])->name('locale.update');

// JJ Import Motors folleto PDF
Route::get('/jj-import/folleto', [\App\Http\Controllers\JJImportFolletoController::class, 'download'])->name('jj-import.folleto');

require __DIR__.'/auth.php';
