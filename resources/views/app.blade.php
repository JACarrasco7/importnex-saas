<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">

        <title inertia>{{ config('app.name', 'Laravel') }}</title>

        <!-- SEO Meta Tags -->
        <meta name="description" content="JJ Import Motors — Plataforma profesional de importación de vehículos con verificación AI. Ahorra tiempo y evita fraudes en la importación de coches de Alemania.">
        <meta name="keywords" content="importar coches, importación vehículos, coches de Alemania, verificación AI, dealer importador, marketplace vehículos">
        <meta name="author" content="JJ Import Motors">

        <!-- Open Graph / Facebook -->
        <meta property="og:type" content="website">
        <meta property="og:url" content="{{ config('app.url') }}">
        <meta property="og:title" content="{{ config('app.name', 'JJ Import Motors') }}">
        <meta property="og:description" content="Plataforma profesional de importación de vehículos con verificación AI. Ahorra tiempo y evita fraudes.">
        <meta property="og:image" content="{{ config('app.url') }}/img/og-image.jpg">
        <meta property="og:locale" content="{{ str_replace('_', '-', app()->getLocale()) }}">

        <!-- Twitter -->
        <meta property="twitter:card" content="summary_large_image">
        <meta property="twitter:url" content="{{ config('app.url') }}">
        <meta property="twitter:title" content="{{ config('app.name', 'JJ Import Motors') }}">
        <meta property="twitter:description" content="Plataforma profesional de importación de vehículos con verificación AI.">
        <meta property="twitter:image" content="{{ config('app.url') }}/img/og-image.jpg">

        <!-- Fonts -->
        <link rel="preconnect" href="https://fonts.bunny.net" crossorigin>
        <link href="https://fonts.bunny.net/css?family=figtree:400,500,600&display=swap" rel="stylesheet" />

        <!-- DNS prefetch para recursos externos (reduce latencia DNS) -->
        <link rel="dns-prefetch" href="//fonts.bunny.net">
        <link rel="dns-prefetch" href="//api.stripe.com">
        <link rel="dns-prefetch" href="//m.stripe.network">
        <link rel="dns-prefetch" href="//cdn.onesignal.com">

        <!-- Preconnect Stripe (pagos, necesario en flujo de checkout) -->
        <link rel="preconnect" href="https://api.stripe.com" crossorigin>
        <link rel="preconnect" href="https://m.stripe.network" crossorigin>

        <!-- Preconnect Unsplash si marketplace usa fotos externas -->
        <link rel="preconnect" href="https://images.unsplash.com" crossorigin>

        <!-- Preconnect OneSignal (notificaciones push) -->
        <link rel="preconnect" href="https://cdn.onesignal.com" crossorigin>

        <!-- OneSignal SDK -->
        <script src="https://cdn.onesignal.com/sdks/OneSignalSDK.js" async></script>

        <!-- Scripts -->
        @routes
        @vite(['resources/js/app.js', "resources/js/Pages/{$page['component']}.vue"])
        @inertiaHead

        <!-- Force Ziggy base URL for subpath deployment -->
        @if(str_contains(config('app.url'), '/importnexcore'))
            <script>
                window.Ziggy.baseUrl = '{{ config('app.url') }}';
            </script>
        @endif

        <!-- Schema.org AutoDealer -->
        @include('partials.schema-org')
    </head>
    <body class="font-sans antialiased">
        @inertia
    </body>
</html>
