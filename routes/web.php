<?php

use App\Http\Controllers\AiChatController;
use App\Http\Controllers\AlertController;
use App\Http\Controllers\BillingController;
use App\Http\Controllers\CarChecklistController;
use App\Http\Controllers\CarController;
use App\Http\Controllers\CarDocumentController;
use App\Http\Controllers\CarKanbanController;
use App\Http\Controllers\CarMapController;
use App\Http\Controllers\CarMarketingController;
use App\Http\Controllers\CarPhotoController;
use App\Http\Controllers\CarRequestController;
use App\Http\Controllers\CarVerificationController;
use App\Http\Controllers\ClientContactLogController;
use App\Http\Controllers\ClientController;
use App\Http\Controllers\ContactController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\FinanceController;
use App\Http\Controllers\JJImportFolletoController;
use App\Http\Controllers\LocaleController;
use App\Http\Controllers\MarketingController;
use App\Http\Controllers\MessageTemplateController;
use App\Http\Controllers\OnboardingController;
use App\Http\Controllers\OrganizationController;
use App\Http\Controllers\PaqueteValoracionController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\PublicCarRequestController;
use App\Http\Controllers\PublicMarketplaceController;
use App\Http\Controllers\SitemapController;
use App\Http\Controllers\StripeWebhookController;
use App\Http\Controllers\PushSubscriptionController;
use App\Http\Controllers\SubscriptionController;
use App\Http\Controllers\TripPlannerController;
use App\Http\Controllers\ValuationImportController;
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
    Route::get('/', [PublicMarketplaceController::class, 'index'])->name('index');
    Route::get('/{car}', [PublicMarketplaceController::class, 'show'])->name('show');
});

Route::middleware('auth', 'has.organization')->group(function () {
    Route::get('organization/create', [OrganizationController::class, 'create'])
        ->name('organization.create');
    Route::post('organization', [OrganizationController::class, 'store'])
        ->name('organization.store');
});

Route::middleware(['auth', 'verified', 'organization'])->group(function () {
    Route::get('/dashboard', [DashboardController::class, '__invoke'])
        ->name('dashboard');

    // Onboarding wizard (redirige a dashboard si ya está completado)
    Route::get('/onboarding', [OnboardingController::class, 'index'])
        ->name('onboarding.index');
    Route::post('/onboarding', [OnboardingController::class, 'update'])
        ->name('onboarding.update');
    Route::post('/onboarding/skip', [OnboardingController::class, 'skip'])
        ->name('onboarding.skip');

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
    Route::get('/cars', [CarController::class, 'index'])->name('cars.index');
    Route::get('/cars/create', [CarController::class, 'create'])->name('cars.create');
    Route::post('/cars', [CarController::class, 'store'])
        ->middleware('plan.limit:cars')
        ->name('cars.store');
    Route::post('/cars/import', [CarController::class, 'import'])->name('cars.import');
    Route::post('/cars/scrape-url', [CarController::class, 'scrapeUrl'])
        ->middleware('throttle:30,1')
        ->name('cars.scrape-url');
    Route::get('/cars/{car}', [CarController::class, 'show'])->name('cars.show');
    Route::get('/cars/{car}/edit', [CarController::class, 'edit'])->name('cars.edit');
    Route::patch('/cars/{car}', [CarController::class, 'update'])->name('cars.update');
    Route::delete('/cars/{car}', [CarController::class, 'destroy'])->name('cars.destroy');

    // Car Photos
    Route::post('/cars/{car}/photos', [CarPhotoController::class, 'store'])->name('cars.photos.store');
    Route::delete('/cars/{car}/photos/{photo}', [CarPhotoController::class, 'destroy'])->name('cars.photos.destroy');
    Route::post('/cars/{car}/photos/reorder', [CarPhotoController::class, 'reorder'])->name('cars.photos.reorder');

    // Car Documents
    Route::post('/cars/{car}/documents', [CarDocumentController::class, 'store'])->name('cars.documents.store');
    Route::get('/cars/{car}/documents/{document}', [CarDocumentController::class, 'show'])->name('cars.documents.show');
    Route::delete('/cars/{car}/documents/{document}', [CarDocumentController::class, 'destroy'])->name('cars.documents.destroy');

    // Valuation import (from chat report ZIP)
    Route::get('/cars/import-valuation', [ValuationImportController::class, 'create'])
        ->name('cars.import-valuation.create');
    Route::post('/cars/import-valuation', [ValuationImportController::class, 'store'])
        ->name('cars.import-valuation.store');

    // Car Checklist (toggle complete)
    Route::post('/cars/{car}/checklists/{checklist}/toggle', [CarChecklistController::class, 'toggle'])
        ->name('cars.checklists.toggle');

    // AI Car Verification
    Route::get('/cars/{car}/verify', [CarVerificationController::class, 'show'])->name('cars.verify.show');
    Route::post('/cars/{car}/verify', [CarVerificationController::class, 'verify'])->name('cars.verify');
    Route::post('/cars/{car}/verify-sync', [CarVerificationController::class, 'verifySync'])->name('cars.verify-sync');
    Route::post('/cars/{car}/verify/apply', [CarVerificationController::class, 'apply'])->name('cars.verify.apply');
    Route::post('/cars/{car}/verify/discard', [CarVerificationController::class, 'discard'])->name('cars.verify.discard');

    // Car Marketing (AI-generated content for sales channels)
    Route::get('/cars/{car}/marketing', [CarMarketingController::class, 'show'])->name('cars.marketing');
    Route::post('/cars/{car}/marketing/generate', [CarMarketingController::class, 'generate'])->name('cars.marketing.generate');
    Route::post('/cars/{car}/marketing/save', [CarMarketingController::class, 'save'])->name('cars.marketing.save');
    Route::post('/cars/{car}/marketing/publish', [CarMarketingController::class, 'publish'])->name('cars.marketing.publish');
    Route::get('/cars/{car}/marketing/briefing', [CarMarketingController::class, 'briefing'])->name('cars.marketing.briefing');

    // Paquete de valoración (esqueletos .txt → PDF con Blade + Browsershot)
    // Ficha del cliente: cuelga del expediente (autenticado).
    Route::get('/cars/{car}/ficha', [PaqueteValoracionController::class, 'ficha'])->name('cars.ficha');
    // Informe interno: SOLO equipo, nunca expuesto al cliente.
    Route::get('/cars/{car}/informe-interno', [PaqueteValoracionController::class, 'interno'])->name('cars.informe-interno');

    // Marketing overview
    Route::get('/marketing', [MarketingController::class, 'index'])->name('marketing.index');

    // Car Kanban
    Route::get('/cars-kanban', [CarKanbanController::class, 'index'])->name('cars.kanban');
    Route::post('/cars-kanban/{car}/move', [CarKanbanController::class, 'move'])->name('cars.kanban.move');

    // Cars Map
    Route::get('/cars-map', [CarMapController::class, 'index'])->name('cars.map');

    // Finance
    Route::get('/finance', [FinanceController::class, 'index'])->name('finance.index');

    // Trip Planner
    Route::get('/trips', [TripPlannerController::class, 'index'])->name('trips.index');

    // Clients CRUD
    Route::get('/clients', [ClientController::class, 'index'])->name('clients.index');
    Route::get('/clients/create', [ClientController::class, 'create'])->name('clients.create');
    Route::post('/clients', [ClientController::class, 'store'])
        ->middleware('plan.limit:clients')
        ->name('clients.store');
    Route::get('/clients/{client}', [ClientController::class, 'show'])->name('clients.show');
    Route::get('/clients/{client}/edit', [ClientController::class, 'edit'])->name('clients.edit');
    Route::patch('/clients/{client}', [ClientController::class, 'update'])->name('clients.update');
    Route::delete('/clients/{client}', [ClientController::class, 'destroy'])->name('clients.destroy');

    // Contacts CRUD
    Route::get('/contacts', [ContactController::class, 'index'])->name('contacts.index');
    Route::get('/contacts/create', [ContactController::class, 'create'])->name('contacts.create');
    Route::post('/contacts', [ContactController::class, 'store'])
        ->middleware('plan.limit:contacts')
        ->name('contacts.store');
    Route::get('/contacts/{contact}', [ContactController::class, 'show'])->name('contacts.show');
    Route::get('/contacts/{contact}/edit', [ContactController::class, 'edit'])->name('contacts.edit');
    Route::patch('/contacts/{contact}', [ContactController::class, 'update'])->name('contacts.update');
    Route::delete('/contacts/{contact}', [ContactController::class, 'destroy'])->name('contacts.destroy');

    // Client Contact Logs
    Route::get('/clients/{client}/contact-logs', [ClientContactLogController::class, 'index'])->name('clients.contact-logs.index');
    Route::post('/clients/{client}/contact-logs', [ClientContactLogController::class, 'store'])->name('clients.contact-logs.store');
    Route::delete('/clients/{client}/contact-logs/{log}', [ClientContactLogController::class, 'destroy'])->name('clients.contact-logs.destroy');

    // AI generic chat
    Route::get('/ai/chat', [AiChatController::class, 'index'])->name('ai.chat');
    Route::post('/ai/chat', [AiChatController::class, 'send'])->name('ai.chat.send');

    // Alerts — específicas primero para no ser interceptadas por {alert}
    Route::get('/alerts', [AlertController::class, 'index'])->name('alerts.index');
    Route::get('/alerts/pending.json', [AlertController::class, 'pending'])->name('alerts.pending');
    Route::post('/alerts/mark-all-read', [AlertController::class, 'markAllRead'])->name('alerts.mark-all-read');
    Route::get('/alerts/{alert}', [AlertController::class, 'show'])->name('alerts.show');
    Route::patch('/alerts/{alert}/mark-resolved', [AlertController::class, 'markResolved'])->name('alerts.mark-resolved');
    Route::post('/alerts/{alert}/snooze', [AlertController::class, 'snooze'])->name('alerts.snooze');
    Route::delete('/alerts/{alert}/snooze', [AlertController::class, 'unsnooze'])->name('alerts.unsnooze');
    Route::post('/alerts/preferences/{alertType}', [AlertController::class, 'togglePreference'])->name('alerts.toggle-preference');
    Route::delete('/alerts/{alert}', [AlertController::class, 'destroy'])->name('alerts.destroy');

    // Message Templates
    Route::get('/message-templates', [MessageTemplateController::class, 'index'])->name('message-templates.index');
    Route::get('/message-templates/{messageTemplate}', [MessageTemplateController::class, 'show'])->name('message-templates.show');

    // Subscriptions
    Route::get('/subscriptions', [SubscriptionController::class, 'index'])->name('subscriptions.index');
    Route::get('/subscriptions/cancel-page', [SubscriptionController::class, 'cancelPage'])->name('subscriptions.cancel-page');
    Route::get('/subscriptions/{plan}', [SubscriptionController::class, 'show'])->name('subscriptions.show');
    Route::post('/subscriptions/{plan}/create', [SubscriptionController::class, 'create'])->name('subscriptions.create');
    Route::post('/subscriptions/{plan}/swap', [SubscriptionController::class, 'swap'])->name('subscriptions.swap');
    Route::post('/subscriptions/cancel', [SubscriptionController::class, 'cancel'])->name('subscriptions.cancel');
    Route::post('/subscriptions/resume', [SubscriptionController::class, 'resume'])->name('subscriptions.resume');

    // Billing
    Route::get('/billing', [BillingController::class, 'index'])->name('billing.index');
    Route::get('/billing/{invoiceId}', [BillingController::class, 'show'])->name('billing.show');
    Route::get('/billing/{invoiceId}/download', [BillingController::class, 'download'])->name('billing.download');
    Route::get('/billing/portal/redirect', [BillingController::class, 'portal'])->name('billing.portal');

    // Car Requests (internal management)
    Route::get('/car-requests', [CarRequestController::class, 'index'])->name('car-requests.index');
    Route::get('/car-requests/{carRequest}', [CarRequestController::class, 'show'])->name('car-requests.show');
    Route::patch('/car-requests/{carRequest}/status', [CarRequestController::class, 'updateStatus'])->name('car-requests.update-status');
    Route::delete('/car-requests/{carRequest}', [CarRequestController::class, 'destroy'])->name('car-requests.destroy');
});

// Locale update (available for all users, including guests)
Route::put('/locale', [LocaleController::class, 'update'])->name('locale.update');

// N6: Push subscriptions (Web Push API). Requiere auth.
Route::middleware(['auth', 'verified'])->group(function () {
    Route::post('/push/subscribe', [PushSubscriptionController::class, 'store'])->name('push.subscribe');
    Route::delete('/push/subscribe', [PushSubscriptionController::class, 'destroy'])->name('push.unsubscribe');
    Route::get('/push/vapid-public-key', [PushSubscriptionController::class, 'vapidKey'])->name('push.vapid-key');
});

// JJ Import Motors folleto PDF
Route::get('/jj-import/folleto', [JJImportFolletoController::class, 'download'])->name('jj-import.folleto');

require __DIR__.'/auth.php';
