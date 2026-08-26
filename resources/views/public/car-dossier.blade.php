<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $car->brand }} {{ $car->model }} · JJ Import Motors</title>
    <meta name="robots" content="noindex, nofollow">
    <meta property="og:title" content="{{ $car->brand }} {{ $car->model }} · JJ Import Motors">
    @if(count($fotos) > 0)
        <meta property="og:image" content="{{ $fotos[0] }}">
    @endif
    <style>
        :root {
            --estoril: #1A306D;
            --estoril-2: #2a3d87;
            --estoril-50: #f1f4fb;
            --asphalt: #111827;
            --asphalt-2: #1f2937;
            --platinum: #8fa3d9;
            --platinum-2: #c7d4f5;
            --cream: #f5f1ea;
            --orange: #E8590C;
            --green: #10b981;
        }
        * { margin: 0; padding: 0; box-sizing: border-box; }
        html { scroll-behavior: smooth; }
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background: #0a1535;
            color: #e5e7eb;
            line-height: 1.5;
        }
        a { color: var(--platinum); }

        /* ── HERO ─────────────────────────────────────── */
        .hero {
            position: relative;
            min-height: 78vh;
            background:
                linear-gradient(180deg, rgba(10, 21, 53, 0.4) 0%, rgba(10, 21, 53, 0.95) 100%),
                radial-gradient(ellipse at 80% 20%, rgba(143, 163, 217, 0.18) 0%, transparent 50%),
                linear-gradient(135deg, #0f1d42 0%, #14265a 60%, #1A306D 100%);
            display: flex; align-items: flex-end;
            padding: 36px 32px 48px;
            overflow: hidden;
        }
        .hero::before {
            content: ''; position: absolute; inset: 0;
            background: radial-gradient(circle at 50% 100%, rgba(232, 89, 12, 0.12) 0%, transparent 40%);
            pointer-events: none;
        }
        .hero-inner {
            position: relative; z-index: 1;
            max-width: 1200px; margin: 0 auto; width: 100%;
            display: grid; grid-template-columns: 1fr 360px; gap: 48px; align-items: end;
        }
        .hero-left { }
        .brand-logo { height: 44px; margin-bottom: 24px; }
        .eyebrow {
            display: inline-flex; align-items: center; gap: 8px;
            background: rgba(143, 163, 217, 0.15);
            border: 1px solid rgba(143, 163, 217, 0.3);
            color: #c7d4f5;
            padding: 6px 14px; border-radius: 100px;
            font-size: 11px; font-weight: 600; letter-spacing: 1.5px;
            text-transform: uppercase; margin-bottom: 18px;
        }
        .h1 {
            font-size: clamp(36px, 6vw, 72px); font-weight: 800; line-height: 1.04;
            color: #fff; letter-spacing: -1px; margin-bottom: 16px;
        }
        .h1 .accent { color: #c7d4f5; }
        .claim {
            font-size: clamp(15px, 2vw, 19px); color: #c7d4f5;
            max-width: 640px; margin-bottom: 28px;
        }
        .price-card {
            background: rgba(232, 89, 12, 0.18);
            border: 1px solid rgba(232, 89, 12, 0.45);
            border-radius: 16px;
            padding: 18px 24px;
            display: inline-block;
            backdrop-filter: blur(8px);
        }
        .price-label {
            font-size: 10px; text-transform: uppercase; letter-spacing: 1.5px;
            color: #cbd5e1; font-weight: 700; margin-bottom: 4px;
        }
        .price-value {
            font-size: 38px; font-weight: 900; color: #E8590C;
            line-height: 1; text-shadow: 0 2px 16px rgba(0, 0, 0, 0.5);
        }
        .price-caption { font-size: 11px; color: #cbd5e1; margin-top: 6px; }

        /* Hero right: foto principal */
        .hero-photo {
            border-radius: 18px; overflow: hidden;
            box-shadow: 0 30px 80px rgba(0, 0, 0, 0.6);
            aspect-ratio: 4/3;
            background: #14265a;
        }
        .hero-photo img { width: 100%; height: 100%; object-fit: cover; display: block; }

        /* ── KPI BAR ───────────────────────────────────── */
        .kpi-bar {
            background: linear-gradient(180deg, #14265a 0%, #0f1d42 100%);
            border-top: 1px solid rgba(143, 163, 217, 0.18);
            border-bottom: 1px solid rgba(143, 163, 217, 0.18);
            padding: 30px 32px;
        }
        .kpi-grid {
            max-width: 1200px; margin: 0 auto;
            display: grid; grid-template-columns: repeat(auto-fit, minmax(180px, 1fr)); gap: 24px;
        }
        .kpi {
            text-align: center;
        }
        .kpi-k {
            font-size: 10px; text-transform: uppercase; letter-spacing: 1.5px;
            color: #8fa3d9; font-weight: 700; margin-bottom: 8px;
        }
        .kpi-v {
            font-size: 24px; font-weight: 800; color: #fff; line-height: 1.1;
        }
        .kpi-v.green { color: #4ade80; }
        .kpi-v.orange { color: #E8590C; }
        .kpi-s {
            font-size: 11px; color: #94a3b8; margin-top: 4px;
        }

        /* ── CONTENEDOR PRINCIPAL ──────────────────────── */
        .container {
            max-width: 1100px; margin: 0 auto;
            padding: 64px 32px;
        }

        /* ── GALERÍA ───────────────────────────────────── */
        .section-title {
            font-size: 11px; text-transform: uppercase; letter-spacing: 2px;
            color: #8fa3d9; font-weight: 700; margin-bottom: 12px;
        }
        .section-h {
            font-size: clamp(28px, 4vw, 40px); font-weight: 800; color: #fff;
            letter-spacing: -0.5px; margin-bottom: 28px;
        }
        .gallery {
            display: grid; grid-template-columns: repeat(2, 1fr); gap: 14px;
        }
        .gallery .shot {
            border-radius: 14px; overflow: hidden;
            border: 1px solid rgba(143, 163, 217, 0.25);
            background: #14265a;
            aspect-ratio: 16/10;
        }
        .gallery .shot img { width: 100%; height: 100%; object-fit: cover; display: block; }

        /* ── VEREDICTO ────────────────────────────────── */
        .verdict {
            margin-top: 64px;
            background: linear-gradient(135deg, rgba(232, 89, 12, 0.18) 0%, rgba(26, 48, 109, 0.4) 100%);
            border: 1px solid rgba(232, 89, 12, 0.4);
            border-radius: 22px;
            padding: 36px 38px;
        }
        .verdict-eyebrow {
            font-size: 10px; text-transform: uppercase; letter-spacing: 2px;
            color: #E8590C; font-weight: 700; margin-bottom: 10px;
        }
        .verdict-h {
            font-size: clamp(26px, 3.5vw, 36px); font-weight: 800; color: #fff;
            margin-bottom: 16px;
        }
        .verdict-body { color: #c7d4f5; font-size: 16px; line-height: 1.6; }

        /* ── PROS / CONS ──────────────────────────────── */
        .proscons {
            margin-top: 48px;
            display: grid; grid-template-columns: 1fr 1fr; gap: 18px;
        }
        .pc-col {
            border-radius: 16px; padding: 24px 26px;
            border: 1px solid rgba(143, 163, 217, 0.3);
        }
        .pc-col.pros { background: rgba(16, 185, 129, 0.1); border-color: rgba(16, 185, 129, 0.35); }
        .pc-col.cons { background: rgba(232, 89, 12, 0.1); border-color: rgba(232, 89, 12, 0.35); }
        .pc-col h3 {
            font-size: 18px; font-weight: 800; color: #fff;
            margin-bottom: 16px; display: flex; align-items: center; gap: 8px;
        }
        .pc-col.pros h3::before { content: '✓'; color: #4ade80; font-size: 22px; }
        .pc-col.cons h3::before { content: '!'; color: #E8590C; font-size: 22px; }
        .pc-col ul { list-style: none; }
        .pc-col li {
            font-size: 14px; color: #c7d4f5; line-height: 1.5;
            padding: 6px 0; display: flex; gap: 8px;
        }
        .pc-col li::before { content: '•'; color: rgba(255, 255, 255, 0.4); }

        /* ── ESPECIFICACIONES ─────────────────────────── */
        .specs {
            margin-top: 64px;
            background: rgba(143, 163, 217, 0.08);
            border: 1px solid rgba(143, 163, 217, 0.25);
            border-radius: 18px;
            padding: 36px 38px;
        }
        .specs-grid {
            display: grid; grid-template-columns: 1fr 1fr; gap: 12px 36px;
        }
        .spec-row {
            display: flex; justify-content: space-between; align-items: baseline;
            gap: 12px; padding: 10px 0;
            border-bottom: 1px dashed rgba(143, 163, 217, 0.15);
        }
        .spec-row .k {
            font-size: 11px; text-transform: uppercase; letter-spacing: 1px;
            color: #8fa3d9; font-weight: 600;
        }
        .spec-row .v {
            font-size: 14px; color: #fff; font-weight: 600;
            text-align: right;
        }

        /* ── EQUIPAMIENTO ─────────────────────────────── */
        .equip {
            margin-top: 48px;
            display: grid; grid-template-columns: 1fr 1fr; gap: 8px 28px;
        }
        .equip-item {
            display: flex; align-items: flex-start; gap: 8px;
            font-size: 14px; color: #c7d4f5;
            padding: 4px 0;
        }
        .equip-item::before {
            content: '✓'; color: #4ade80; font-weight: 700;
            flex-shrink: 0;
        }

        /* ── TIPS ─────────────────────────────────────── */
        .tips {
            margin-top: 48px;
            background: rgba(26, 48, 109, 0.4);
            border: 1px solid rgba(143, 163, 217, 0.25);
            border-radius: 16px;
            padding: 28px 32px;
        }
        .tips h3 {
            font-size: 18px; font-weight: 800; color: #fff; margin-bottom: 14px;
            display: flex; align-items: center; gap: 8px;
        }
        .tips h3::before { content: '💡'; }
        .tips ul { list-style: none; }
        .tips li {
            font-size: 14px; color: #c7d4f5; line-height: 1.55;
            padding: 5px 0; display: flex; gap: 8px;
        }
        .tips li::before { content: '→'; color: #8fa3d9; }

        /* ── CTA ──────────────────────────────────────── */
        .cta {
            margin-top: 64px;
            background: linear-gradient(135deg, #1A306D 0%, #2a3d87 100%);
            border-radius: 22px;
            padding: 44px 38px;
            text-align: center;
            box-shadow: 0 20px 60px rgba(26, 48, 109, 0.4);
        }
        .cta h2 {
            font-size: clamp(24px, 3vw, 32px); font-weight: 800; color: #fff;
            margin-bottom: 12px;
        }
        .cta p { color: #c7d4f5; font-size: 16px; margin-bottom: 24px; }
        .cta-buttons {
            display: flex; gap: 12px; justify-content: center; flex-wrap: wrap;
        }
        .btn {
            display: inline-flex; align-items: center; gap: 8px;
            padding: 13px 22px; border-radius: 10px;
            font-size: 14px; font-weight: 700;
            text-decoration: none; transition: all 0.15s ease;
            border: 1px solid transparent;
        }
        .btn.primary {
            background: #E8590C; color: #fff;
            box-shadow: 0 6px 20px rgba(232, 89, 12, 0.4);
        }
        .btn.primary:hover { background: #d44a08; transform: translateY(-1px); }
        .btn.ghost {
            background: rgba(255, 255, 255, 0.1);
            color: #fff;
            border-color: rgba(255, 255, 255, 0.2);
        }
        .btn.ghost:hover { background: rgba(255, 255, 255, 0.18); }

        /* ── FOOTER ───────────────────────────────────── */
        footer {
            padding: 36px 32px;
            background: rgba(0, 0, 0, 0.35);
            text-align: center;
            font-size: 12px; color: #94a3b8;
            border-top: 1px solid rgba(143, 163, 217, 0.15);
        }
        footer .brand { display: flex; align-items: center; justify-content: center; gap: 10px; margin-bottom: 8px; }
        footer .brand-text { font-weight: 700; color: #c7d4f5; font-size: 14px; }
        footer .links { display: flex; gap: 16px; justify-content: center; margin-top: 8px; }

        /* ── RESPONSIVE ───────────────────────────────── */
        @media (max-width: 800px) {
            .hero { padding: 24px 20px 36px; min-height: auto; }
            .hero-inner { grid-template-columns: 1fr; gap: 24px; }
            .hero-photo { aspect-ratio: 16/10; }
            .kpi-grid { grid-template-columns: repeat(2, 1fr); gap: 18px; }
            .container { padding: 40px 20px; }
            .gallery { grid-template-columns: 1fr; }
            .proscons { grid-template-columns: 1fr; }
            .specs-grid { grid-template-columns: 1fr; gap: 0; }
            .equip { grid-template-columns: 1fr; }
            .verdict { padding: 26px 22px; }
            .specs { padding: 26px 22px; }
            .tips { padding: 22px; }
            .cta { padding: 32px 22px; }
        }
    </style>
</head>
<body>

    {{-- HERO --}}
    <header class="hero">
        <div class="hero-inner">
            <div class="hero-left">
                @if($logoBase64)
                    <img src="{{ $logoBase64 }}" alt="JJ Import Motors" class="brand-logo">
                @endif
                <div class="eyebrow">Dossier del vehículo</div>
                <h1 class="h1">
                    {{ $car->brand }}<br>
                    <span class="accent">{{ $car->model }}</span>
                </h1>
                @php
                    $anioTxt = $car->year ? ($car->year->format('m/Y')) : null;
                    $potencia = $esqueleto?->uno('POTENCIA');
                    $cambioTxt = $esqueleto?->uno('CAMBIO') ?? $car->transmission;
                    $kmTxt = $car->km ? number_format($car->km, 0, ',', '.').' km' : null;
                    $claimParts = array_filter([
                        $potencia,
                        $cambioTxt ? 'cambio '.strtolower($cambioTxt) : null,
                        $kmTxt,
                    ]);
                @endphp
                @if(count($claimParts) > 0)
                    <p class="claim">{{ implode(' · ', $claimParts) }}</p>
                @endif

                <div class="price-card">
                    <div class="price-label">Precio llave en mano</div>
                    <div class="price-value">{{ number_format($car->sale_price ?? $car->purchase_price ?? 0, 0, ',', '.') }} €</div>
                    <div class="price-caption">IVA incluido · Transferencia · Garantía 12 meses</div>
                </div>
            </div>

            @if(count($fotos) > 0)
                <div class="hero-photo">
                    <img src="{{ $fotos[0] }}" alt="{{ $car->brand }} {{ $car->model }}">
                </div>
            @endif
        </div>
    </header>

    {{-- KPI BAR --}}
    @php
        $kpis = [
            ['k' => 'Año', 'v' => $anioTxt, 's' => $car->year ? 'Primera matriculación' : null, 'class' => ''],
            ['k' => 'Kilómetros', 'v' => $kmTxt ?? '—', 's' => 'Verificados', 'class' => ''],
            ['k' => 'Combustible', 'v' => ucfirst($car->fuel_type ?? '—'), 's' => null, 'class' => ''],
            ['k' => 'Cambio', 'v' => ucfirst($cambioTxt ?? '—'), 's' => null, 'class' => ''],
            ['k' => 'Origen', 'v' => strtoupper($car->origin_country ?? '—'), 's' => 'Historial limpio', 'class' => 'green'],
        ];
        $kpis = array_filter($kpis, fn($x) => !empty($x['v']) && $x['v'] !== '—');
    @endphp
    @if(count($kpis) > 0)
        <section class="kpi-bar">
            <div class="kpi-grid">
                @foreach($kpis as $k)
                    <div class="kpi">
                        <div class="kpi-k">{{ $k['k'] }}</div>
                        <div class="kpi-v {{ $k['class'] }}">{{ $k['v'] }}</div>
                        @if($k['s'])<div class="kpi-s">{{ $k['s'] }}</div>@endif
                    </div>
                @endforeach
            </div>
        </section>
    @endif

    {{-- CONTENIDO --}}
    <main class="container">

        {{-- Galería --}}
        @if(count($fotos) > 1)
            <section>
                <div class="section-title">Galería</div>
                <h2 class="section-h">Fotos reales del vehículo</h2>
                <div class="gallery">
                    @foreach(array_slice($fotos, 1) as $foto)
                        <div class="shot"><img src="{{ $foto }}" alt="Foto"></div>
                    @endforeach
                </div>
            </section>
        @endif

        {{-- Veredicto --}}
        @if($esqueleto && ($v = $esqueleto->uno('DICTAMEN')))
            <section class="verdict">
                <div class="verdict-eyebrow">Veredicto JJ Import Motors</div>
                <h2 class="verdict-h">{{ $esqueleto->uno('RESUMEN') ?? 'Nuestra recomendación' }}</h2>
                <p class="verdict-body">{{ $v }}</p>
            </section>
        @endif

        {{-- Pros / Cons --}}
        @if($esqueleto)
            @php
                $aFavor = $esqueleto->lista('A_FAVOR');
                $enContra = $esqueleto->lista('EN_CONTRA');
            @endphp
            @if(count($aFavor) > 0 || count($enContra) > 0)
                <section class="proscons">
                    <div class="pc-col pros">
                        <h3>A favor</h3>
                        <ul>
                            @foreach($aFavor as $item)
                                <li>{{ $item }}</li>
                            @endforeach
                        </ul>
                    </div>
                    @if(count($enContra) > 0)
                        <div class="pc-col cons">
                            <h3>A tener en cuenta</h3>
                            <ul>
                                @foreach($enContra as $item)
                                    <li>{{ $item }}</li>
                                @endforeach
                            </ul>
                        </div>
                    @endif
                </section>
            @endif
        @endif

        {{-- Especificaciones --}}
        @php
            $specRows = [];
            $specMap = [
                'MARCA' => 'Marca', 'MODELO' => 'Modelo', 'VERSION' => 'Versión',
                'ANIO' => 'Año', 'KM' => 'Kilómetros', 'POTENCIA' => 'Potencia',
                'CAMBIO' => 'Cambio', 'COMBUSTIBLE' => 'Combustible',
                'TRACCION' => 'Tracción', 'COLOR' => 'Color', 'PUERTAS' => 'Puertas',
                'PLAZAS' => 'Plazas', 'ORIGEN' => 'Origen',
            ];
            foreach ($specMap as $key => $label) {
                $val = $esqueleto?->uno($key);
                if ($val) {
                    $specRows[] = ['k' => $label, 'v' => $val];
                }
            }
        @endphp
        @if(count($specRows) > 0)
            <section class="specs">
                <div class="section-title">Ficha técnica</div>
                <h2 class="section-h">Especificaciones</h2>
                <div class="specs-grid">
                    @foreach($specRows as $s)
                        <div class="spec-row">
                            <span class="k">{{ $s['k'] }}</span>
                            <span class="v">{{ $s['v'] }}</span>
                        </div>
                    @endforeach
                </div>
            </section>
        @endif

        {{-- Equipamiento --}}
        @if($esqueleto)
            @php $equipamiento = $esqueleto->lista('EQUIPAMIENTO'); @endphp
            @if(count($equipamiento) > 0)
                <section>
                    <div class="section-title">Equipamiento destacado</div>
                    <h2 class="section-h">Extras y opciones</h2>
                    <div class="equip">
                        @foreach($equipamiento as $item)
                            <div class="equip-item">{{ $item }}</div>
                        @endforeach
                    </div>
                </section>
            @endif

            {{-- Tips / Cosas a saber --}}
            @php $tips = $esqueleto->lista('TIPS'); @endphp
            @if(count($tips) > 0)
                <section class="tips">
                    <h3>Cosas que debes saber</h3>
                    <ul>
                        @foreach($tips as $t)
                            <li>{{ $t }}</li>
                        @endforeach
                    </ul>
                </section>
            @endif
        @endif

        {{-- CTA --}}
        <section class="cta">
            <h2>¿Te interesa este coche?</h2>
            <p>Reservamos la unidad en cuanto confirmes. Sin compromiso y sin coste.</p>
            <div class="cta-buttons">
                <a href="tel:+34675701439" class="btn primary">
                    📞 Llamar: 675 70 14 39
                </a>
                <a href="https://wa.me/34675701439?text={{ urlencode('Hola, me interesa el '.$car->brand.' '.$car->model.' que habéis compartido conmigo.') }}"
                   target="_blank" rel="noopener" class="btn ghost">
                    💬 WhatsApp
                </a>
            </div>
        </section>
    </main>

    <footer>
        <div class="brand">
            @if($logoBase64)
                <img src="{{ $logoBase64 }}" alt="JJ Import Motors" style="height:24px;">
            @endif
            <span class="brand-text">JJ Import Motors</span>
        </div>
        <div>Especialistas en importación de vehículos desde Alemania</div>
        <div class="links">
            <a href="mailto:jjimportmotors@gmail.com">jjimportmotors@gmail.com</a>
            <span>·</span>
            <span>+34 675 70 14 39</span>
            <span>·</span>
            <span>Huelva, España</span>
        </div>
    </footer>

</body>
</html>
