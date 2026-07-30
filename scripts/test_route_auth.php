<?php
// Login real + GET a la ruta protegida
require __DIR__.'/../vendor/autoload.php';
$app = require __DIR__.'/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);

// 1) Login via /login
$request = Illuminate\Http\Request::create('/login', 'POST', [
    'email'    => 'carra@jjimportmotors.com',
    'password' => 'joselete7',
    '_token'   => csrf_token(),
]);

// CSRF se valida en middleware; lo saltamos para test
$kernel->bootstrap();
$response = $kernel->handle($request);
echo "Login: " . $response->getStatusCode() . "\n";

// 2) GET a /cars/import-valuation
$request2 = Illuminate\Http\Request::create('/cars/import-valuation', 'GET');
$request2->setLaravelSession(session()->driver());
$response2 = $kernel->handle($request2);
echo "Import-valuation: " . $response2->getStatusCode() . "\n";
echo "URL: " . $response2->headers->get('Location') . "\n";