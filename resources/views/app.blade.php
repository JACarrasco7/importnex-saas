<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">

        <title inertia>{{ config('app.name', 'Laravel') }}</title>

        <!-- Critical CSS inline (above-the-fold styles) — improves FCP/LCP.
             Envuelto en @layer base para que las utilidades de Tailwind v4
             (que viven en @layer utilities) puedan pisarlo; sin capa,
             reglas como mx-auto no funcionan en h1/p. -->
        <style>
            @layer base {
                *,*::before,*::after{box-sizing:border-box}
                html{-webkit-text-size-adjust:100%;tab-size:4;font-family:Figtree,ui-sans-serif,system-ui,sans-serif;line-height:1.5}
                body{margin:0;line-height:inherit;background:#fff;color:#111827}
                h1,h2,h3,h4,h5,h6,p,figure,blockquote,dl,dd{margin:0}
                .sr-only{position:absolute;width:1px;height:1px;padding:0;margin:-1px;overflow:hidden;clip:rect(0,0,0,0);white-space:nowrap;border-width:0}
                .min-h-screen{min-height:100vh}
            }
        </style>

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

        <!-- PWA -->
        <link rel="manifest" href="/manifest.json">
        <meta name="theme-color" content="#1A306D">
        <meta name="mobile-web-app-capable" content="yes">
        <meta name="apple-mobile-web-app-capable" content="yes">
        <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
        <meta name="apple-mobile-web-app-title" content="JJ Imports">
        <link rel="apple-touch-icon" href="/img/apple-touch-icon.png">

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

        <!-- Service Worker registration -->
        <script>
            if ('serviceWorker' in navigator) {
                window.addEventListener('load', () => {
                    navigator.serviceWorker.register('/sw.js?v=3')
                        .then(reg => console.log('[PWA] Service Worker registered:', reg.scope))
                        .catch(err => console.warn('[PWA] Service Worker registration failed:', err));
                });
            }
        </script>

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
