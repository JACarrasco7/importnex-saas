<?php echo '<?xml version="1.0" encoding="UTF-8"?>'; ?>
<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9"
        xmlns:xhtml="http://www.w3.org/1999/xhtml">
    <!-- Homepage -->
    <url>
        <loc>{{ route('home') }}</loc>
        <lastmod>{{ $lastMod->format('Y-m-d') }}</lastmod>
        <changefreq>daily</changefreq>
        <priority>1.0</priority>
    </url>

    <!-- Marketplace -->
    <url>
        <loc>{{ route('marketplace.index') }}</loc>
        <lastmod>{{ $lastMod->format('Y-m-d') }}</lastmod>
        <changefreq>daily</changefreq>
        <priority>0.9</priority>
    </url>

    <!-- Pricing -->
    <url>
        <loc>{{ route('pricing') }}</loc>
        <lastmod>{{ now()->format('Y-m-d') }}</lastmod>
        <changefreq>monthly</changefreq>
        <priority>0.8</priority>
    </url>

    <!-- Login/Register -->
    <url>
        <loc>{{ route('login') }}</loc>
        <lastmod>{{ now()->format('Y-m-d') }}</lastmod>
        <changefreq>monthly</changefreq>
        <priority>0.5</priority>
    </url>

    <url>
        <loc>{{ route('register') }}</loc>
        <lastmod>{{ now()->format('Y-m-d') }}</lastmod>
        <changefreq>monthly</changefreq>
        <priority>0.5</priority>
    </url>

    <!-- Webs de cada empresa -->
    @foreach($sites as $siteUrl)
        <url>
            <loc>{{ $siteUrl }}</loc>
            <changefreq>daily</changefreq>
            <priority>0.8</priority>
        </url>
    @endforeach

    <!-- Cars -->
    @foreach($cars as $car)
        <url>
            <loc>{{ $car['loc'] }}</loc>
            <lastmod>{{ $car['lastmod']->format('Y-m-d') }}</lastmod>
            <changefreq>weekly</changefreq>
            <priority>0.7</priority>
        </url>
    @endforeach
</urlset>
