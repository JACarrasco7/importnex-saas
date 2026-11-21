{{-- Datos de la empresa para buscadores. 21-nov-2026: "ProfessionalService"
     (no somos concesionario, A31) y una descripcion honesta; antes salia
     "AutoDealer", "verificacion AI" y un telefono de relleno. --}}
@php
    $schemaBrand = \App\Support\Brand::platform();
    $schema = array_filter([
        '@context' => 'https://schema.org',
        '@type' => 'ProfessionalService',
        'name' => $schemaBrand->name(),
        'description' => $schemaBrand->get('tagline')
            ?: 'Servicio de búsqueda, revisión e importación de coches, con honorarios fijos. No somos concesionario: el coche se compra al vendedor y se matricula a nombre del cliente.',
        'url' => config('app.url'),
        'logo' => $schemaBrand->get('logo_url'),
        'image' => config('app.url').'/img/og-image.jpg',
        'telephone' => $schemaBrand->get('phone'),
        'email' => $schemaBrand->get('email'),
        'areaServed' => 'ES',
        'address' => array_filter([
            '@type' => 'PostalAddress',
            'streetAddress' => $schemaBrand->get('address'),
            'addressLocality' => $schemaBrand->get('city'),
            'addressCountry' => 'ES',
        ]),
    ]);
@endphp
<script type="application/ld+json">{!! json_encode($schema, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_HEX_TAG) !!}</script>
