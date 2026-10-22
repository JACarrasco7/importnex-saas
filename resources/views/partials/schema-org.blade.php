@php
    $schemaBrand = \App\Support\Brand::platform();
    $schema = array_filter([
        '@context' => 'https://schema.org',
        '@type' => 'AutoDealer',
        'name' => $schemaBrand->name(),
        'url' => config('app.url'),
        'logo' => $schemaBrand->get('logo_url'),
        'telephone' => $schemaBrand->get('phone'),
        'email' => $schemaBrand->get('email'),
        'address' => array_filter([
            '@type' => 'PostalAddress',
            'streetAddress' => $schemaBrand->get('address'),
            'addressLocality' => $schemaBrand->get('city'),
            'addressCountry' => 'ES',
        ]),
        'potentialAction' => [
            '@type' => 'SearchAction',
            'target' => ['@type' => 'EntryPoint', 'urlTemplate' => config('app.url').'/marketplace?q={search_term_string}'],
            'query-input' => 'required name=search_term_string',
        ],
    ]);
@endphp
<script type="application/ld+json">{!! json_encode($schema, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_HEX_TAG) !!}</script>
