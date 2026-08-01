@php
    $precio_honorarios = '1.500 €';
    $telefono_1 = '675 70 14 39';
    $telefono_2 = '691 48 59 27';
    $email = 'jjimportmotors@gmail.com';
    $qr_url = $qr_url ?? 'https://jjimportmotors.on-forge.com/request/jj-import-motors';

    $qr_svg = null;
    try {
        $qr_svg = \SimpleSoftwareIO\QrCode\Generator::class
            ? (new \SimpleSoftwareIO\QrCode\Generator())->format('svg')
                ->size(280)
                ->margin(1)
                ->errorCorrection('H')
                ->backgroundColor(255, 255, 255)
                ->color(6, 16, 31)
                ->generate($qr_url)
            : null;
    } catch (\Exception $e) {
        $qr_svg = null;
    }

    // Get marketing content for each channel
    $milanuncios = $contents->firstWhere('channel', 'milanuncios');
    $cochesNet = $contents->firstWhere('channel', 'coches_net');
    $wallapop = $contents->firstWhere('channel', 'wallapop');
    $tiktok = $contents->firstWhere('channel', 'tiktok');
    $instagram = $contents->firstWhere('channel', 'instagram');
@endphp

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Briefing Marketing - {{ $car->brand }} {{ $car->model }}</title>
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800;900&display=swap');

        * { margin: 0; padding: 0; box-sizing: border-box; }

        html, body {
            font-family: 'Inter', 'Segoe UI', Arial, sans-serif;
            background: #06101f;
            color: #e5e7eb;
            width: 100%;
            min-height: 100vh;
            -webkit-font-smoothing: antialiased;
            -webkit-print-color-adjust: exact;
            print-color-adjust: exact;
        }

        body {
            position: relative;
            padding: 40px 40px 80px 40px;
            min-height: 100vh;
            background:
                radial-gradient(ellipse at 100% 0%, rgba(34, 211, 238, 0.10) 0%, transparent 45%),
                radial-gradient(ellipse at 0% 100%, rgba(168, 85, 247, 0.10) 0%, transparent 45%),
                linear-gradient(180deg, #06101f 0%, #0a1628 50%, #06101f 100%);
        }

        body::before {
            content: '';
            position: absolute;
            inset: 0;
            background-image:
                linear-gradient(rgba(56, 189, 248, 0.035) 1px, transparent 1px),
                linear-gradient(90deg, rgba(56, 189, 248, 0.035) 1px, transparent 1px);
            background-size: 28px 28px;
            pointer-events: none;
            z-index: 0;
        }

        body::after {
            content: '';
            position: absolute;
            top: 35%;
            right: -120px;
            width: 380px;
            height: 380px;
            background: radial-gradient(circle, rgba(34, 211, 238, 0.08) 0%, transparent 60%);
            border-radius: 50%;
            pointer-events: none;
            z-index: 0;
        }

        .container {
            position: relative;
            z-index: 1;
            max-width: 1100px;
            margin: 0 auto;
        }

        /* ============ HEADER ============ */
        .header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding-bottom: 18px;
            border-bottom: 1px solid rgba(56, 189, 248, 0.12);
            margin-bottom: 26px;
        }

        .logo {
            height: 52px;
            width: auto;
            filter: drop-shadow(0 2px 8px rgba(0,0,0,0.4));
        }

        .badge-llave {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            background: linear-gradient(135deg, #15803d 0%, #16a34a 100%);
            color: #fff;
            padding: 9px 18px;
            border-radius: 100px;
            font-size: 11px;
            font-weight: 700;
            letter-spacing: 1.5px;
            box-shadow: 0 4px 14px rgba(22, 163, 74, 0.35);
        }

        .badge-llave svg {
            width: 14px;
            height: 14px;
            fill: #fff;
        }

        /* ============ HERO ============ */
        .hero {
            width: 100%;
            margin-bottom: 30px;
            margin-top: 15px !important;
        }

        .hero-eyebrow {
            display: inline-flex;
            align-items: center;
            gap: 9px;
            background: rgba(34, 211, 238, 0.08);
            border: 1px solid rgba(34, 211, 238, 0.25);
            color: #22d3ee;
            padding: 6px 14px;
            border-radius: 100px;
            font-size: 10.5px;
            font-weight: 600;
            letter-spacing: 1.5px;
            text-transform: uppercase;
            margin-bottom: 12px;
        }

        .hero-eyebrow .pulse {
            width: 6px;
            height: 6px;
            background: #22d3ee;
            border-radius: 50%;
            box-shadow: 0 0 10px #22d3ee;
        }

        .h1-title {
            font-family: 'Inter', sans-serif;
            font-size: 32px;
            font-weight: 800;
            line-height: 1.15;
            color: #ffffff;
            margin-bottom: 8px;
            letter-spacing: -0.8px;
            width: 100%;
        }

        .h1-title .accent {
            color: #22d3ee;
            font-weight: 800;
            display: inline-block;
        }

        .hero-subtitle {
            font-size: 15px;
            color: #94a3b8;
            font-weight: 400;
            margin-bottom: 16px;
            letter-spacing: 0.2px;
        }

        /* ============ CAR INFO CARD ============ */
        .car-info-card {
            background: linear-gradient(180deg, rgba(15, 23, 42, 0.85) 0%, rgba(15, 23, 42, 0.55) 100%);
            border: 1px solid rgba(56, 189, 248, 0.12);
            border-radius: 14px;
            padding: 20px 24px;
            margin-bottom: 24px;
        }

        .car-info-card .section-tag {
            color: #22d3ee;
            font-size: 10px;
            font-weight: 700;
            letter-spacing: 2.5px;
            margin-bottom: 8px;
            text-transform: uppercase;
            display: block;
        }

        .car-info-card .section-title {
            font-size: 18px;
            font-weight: 800;
            color: #f8fafc;
            margin-bottom: 12px;
            letter-spacing: -0.3px;
        }

        .car-specs {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 8px 28px;
        }

        .car-specs .spec-item {
            display: flex;
            align-items: center;
            gap: 10px;
            font-size: 12px;
            color: #cbd5e1;
            padding: 4px 0;
        }

        .car-specs .spec-item .check {
            width: 16px;
            height: 16px;
            background: #22d3ee;
            border-radius: 50%;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
        }

        .car-specs .spec-item .check::after {
            content: '';
            width: 5px;
            height: 3px;
            border-left: 2px solid #06101f;
            border-bottom: 2px solid #06101f;
            transform: rotate(-45deg) translate(0.8px, -0.8px);
        }

        .car-specs .spec-item strong {
            color: #f1f5f9;
            font-weight: 600;
        }

        /* ============ CHANNEL SECTIONS ============ */
        .channel-section {
            background: linear-gradient(180deg, rgba(15, 23, 42, 0.6) 0%, rgba(15, 23, 42, 0.35) 100%);
            border: 1px solid rgba(56, 189, 248, 0.12);
            border-radius: 14px;
            padding: 20px 24px;
            margin-bottom: 24px;
        }

        .channel-section .section-tag {
            color: #22d3ee;
            font-size: 10px;
            font-weight: 700;
            letter-spacing: 2.5px;
            margin-bottom: 4px;
            text-transform: uppercase;
            display: block;
        }

        .channel-section .section-title {
            font-size: 16px;
            font-weight: 800;
            color: #f8fafc;
            margin-bottom: 12px;
            letter-spacing: -0.3px;
        }

        .channel-content {
            font-size: 12px;
            color: #cbd5e1;
            line-height: 1.6;
            white-space: pre-wrap;
        }

        .channel-content .title {
            font-size: 14px;
            font-weight: 700;
            color: #22d3ee;
            margin-bottom: 6px;
        }

        .channel-content .description {
            margin-bottom: 10px;
        }

        .hashtags-list {
            display: flex;
            flex-wrap: gap;
            gap: 6px;
            margin-top: 8px;
        }

        .hashtag {
            background: rgba(34, 211, 238, 0.15);
            color: #22d3ee;
            padding: 3px 8px;
            border-radius: 4px;
            font-size: 10px;
        }

        .tips-list {
            margin-top: 8px;
        }

        .tips-list li {
            display: flex;
            align-items: flex-start;
            gap: 6px;
            margin-bottom: 4px;
        }

        .tips-list .tip-bullet {
            color: #22d3ee;
            font-weight: 700;
        }

        /* ============ HONORARIOS + QR ============ */
        .honorarios-row {
            display: grid;
            grid-template-columns: 1.6fr 1fr;
            gap: 14px;
            margin-bottom: 28px;
        }

        .honorarios-section {
            background: linear-gradient(135deg, rgba(34, 211, 238, 0.10) 0%, rgba(168, 85, 247, 0.10) 100%);
            border: 1px solid rgba(34, 211, 238, 0.25);
            border-radius: 14px;
            padding: 18px 22px;
            text-align: center;
            display: flex;
            flex-direction: column;
            justify-content: center;
        }

        .honorarios-tag {
            color: #22d3ee;
            font-size: 10px;
            font-weight: 700;
            letter-spacing: 2.5px;
            margin-bottom: 6px;
            text-transform: uppercase;
            display: inline-flex;
            align-items: center;
            gap: 6px;
        }

        .honorarios-title {
            font-size: 14px;
            font-weight: 700;
            color: #f8fafc;
            margin-bottom: 6px;
        }

        .honorarios-price {
            font-size: 44px;
            font-weight: 900;
            color: #22d3ee;
            line-height: 1;
            margin-bottom: 5px;
        }

        .honorarios-conditions {
            font-size: 11.5px;
            color: #94a3b8;
            line-height: 1.5;
        }

        .honorarios-conditions strong { color: #e2e8f0; }

        .qr-card {
            background: linear-gradient(180deg, rgba(15, 23, 42, 0.75) 0%, rgba(15, 23, 42, 0.45) 100%);
            border: 1px solid rgba(34, 211, 238, 0.3);
            border-radius: 14px;
            padding: 12px;
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .qr-box {
            flex-shrink: 0;
            width: 95px;
            height: 95px;
            background: #fff;
            border-radius: 9px;
            padding: 4px;
            display: flex;
            align-items: center;
            justify-content: center;
            box-shadow: 0 4px 18px rgba(34, 211, 238, 0.3);
        }

        .qr-box svg {
            width: 100%;
            height: 100%;
            display: block;
        }

        .qr-content {
            flex: 1;
        }

        .qr-tag {
            color: #22d3ee;
            font-size: 9.5px;
            font-weight: 700;
            letter-spacing: 1.8px;
            margin-bottom: 4px;
            text-transform: uppercase;
        }

        .qr-title {
            font-size: 14px;
            font-weight: 800;
            color: #f8fafc;
            margin-bottom: 4px;
            line-height: 1.1;
        }

        .qr-desc {
            font-size: 10px;
            color: #94a3b8;
            line-height: 1.35;
        }

        /* ============ FOOTER ============ */
        .footer {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 10px;
            background: rgba(15, 23, 42, 0.55);
            border-radius: 10px;
            padding: 12px 16px;
            border: 1px solid rgba(56, 189, 248, 0.12);
        }

        .footer-item {
            display: flex;
            align-items: center;
            gap: 10px;
            font-size: 11.5px;
            color: #cbd5e1;
        }

        .footer-item .icon {
            width: 32px;
            height: 32px;
            border-radius: 8px;
            background: rgba(34, 211, 238, 0.14);
            color: #22d3ee;
            display: flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
        }

        .footer-item .label {
            font-size: 9px;
            color: #64748b;
            text-transform: uppercase;
            letter-spacing: 0.6px;
            font-weight: 600;
            margin-bottom: 1px;
        }

        .footer-item .value {
            font-weight: 700;
            color: #f1f5f9;
            font-size: 12px;
            letter-spacing: 0.2px;
        }

        .disclaimer {
            text-align: center;
            font-size: 10px;
            color: #94a3b8;
            margin-top: 20px;
            line-height: 1.5;
            padding: 0 18px;
            font-style: italic;
        }
    </style>
</head>
<body>
    <div class="container">

        <div class="header">
            <img src="{{ $logo_base64 }}" alt="JJ Import Motors" class="logo">
            <span class="badge-llave">BRIEFING DE MARKETING</span>
        </div>

        <div class="hero">
            <span class="hero-eyebrow">Importación Premium · Alemania → España</span>
            <h1 class="h1-title">{{ $car->brand }} {{ $car->model }} <span class="accent">{{ $car->year }}</span></h1>
            <p class="hero-subtitle">{{ $car->mileage }} km · {{ $car->fuel }} · {{ $car->transmission }}</p>
        </div>

        <!-- Car Info -->
        <div class="car-info-card">
            <span class="section-tag">DATOS DEL VEHÍCULO</span>
            <h2 class="section-title">{{ $car->brand }} {{ $car->model }} {{ $car->version }}</h2>
            <div class="car-specs">
                <div class="spec-item">
                    <span class="check"></span>
                    <span><strong>Precio:</strong> €{{ number_format($car->purchase_price, 2) }}</span>
                </div>
                <div class="spec-item">
                    <span class="check"></span>
                    <span><strong>Kilómetros:</strong> {{ number_format($car->mileage) }} km</span>
                </div>
                <div class="spec-item">
                    <span class="check"></span>
                    <span><strong>Combustible:</strong> {{ $car->fuel }}</span>
                </div>
                <div class="spec-item">
                    <span class="check"></span>
                    <span><strong>Cambio:</strong> {{ $car->transmission }}</span>
                </div>
                <div class="spec-item">
                    <span class="check"></span>
                    <span><strong>Potencia:</strong> {{ $car->cv }} CV</span>
                </div>
                <div class="spec-item">
                    <span class="check"></span>
                    <span><strong>Color:</strong> {{ $car->color }}</span>
                </div>
                <div class="spec-item">
                    <span class="check"></span>
                    <span><strong>Veredicto IA:</strong> {{ $car->verdict ?? 'N/A' }}</span>
                </div>
                <div class="spec-item">
                    <span class="check"></span>
                    <span><strong>Precio medio mercado:</strong> €{{ number_format($car->market_avg ?? 0, 2) }}</span>
                </div>
            </div>
        </div>

        <!-- Channel Sections -->
        @if($milanuncios)
        <div class="channel-section">
            <span class="section-tag">Milanuncios</span>
            <h2 class="section-title">Anuncio para Milanuncios</h2>
            <div class="channel-content">
                <div class="title">{{ $milanuncios->title }}</div>
                <div class="description">{{ $milanuncios->description }}</div>
                @if($milanuncios->hashtags)
                <div class="hashtags-list">
                    @foreach($milanuncios->hashtags as $tag)
                    <span class="hashtag">#{{ $tag }}</span>
                    @endforeach
                </div>
                @endif
                @if($milanuncios->photo_tips)
                <div class="tips-list">
                    <ul>
                        @foreach($milanuncios->photo_tips as $tip)
                        <li><span class="tip-bullet">📸</span> {{ $tip }}</li>
                        @endforeach
                    </ul>
                </div>
                @endif
            </div>
        </div>
        @endif

        @if($cochesNet)
        <div class="channel-section">
            <span class="section-tag">Coches.net</span>
            <h2 class="section-title">Anuncio para Coches.net</h2>
            <div class="channel-content">
                <div class="title">{{ $cochesNet->title }}</div>
                <div class="description">{{ $cochesNet->description }}</div>
                @if($cochesNet->hashtags)
                <div class="hashtags-list">
                    @foreach($cochesNet->hashtags as $tag)
                    <span class="hashtag">#{{ $tag }}</span>
                    @endforeach
                </div>
                @endif
                @if($cochesNet->photo_tips)
                <div class="tips-list">
                    <ul>
                        @foreach($cochesNet->photo_tips as $tip)
                        <li><span class="tip-bullet">📸</span> {{ $tip }}</li>
                        @endforeach
                    </ul>
                </div>
                @endif
            </div>
        </div>
        @endif

        @if($wallapop)
        <div class="channel-section">
            <span class="section-tag">Wallapop</span>
            <h2 class="section-title">Anuncio para Wallapop</h2>
            <div class="channel-content">
                <div class="title">{{ $wallapop->title }}</div>
                <div class="description">{{ $wallapop->description }}</div>
                @if($wallapop->hashtags)
                <div class="hashtags-list">
                    @foreach($wallapop->hashtags as $tag)
                    <span class="hashtag">#{{ $tag }}</span>
                    @endforeach
                </div>
                @endif
                @if($wallapop->photo_tips)
                <div class="tips-list">
                    <ul>
                        @foreach($wallapop->photo_tips as $tip)
                        <li><span class="tip-bullet">📸</span> {{ $tip }}</li>
                        @endforeach
                    </ul>
                </div>
                @endif
            </div>
        </div>
        @endif

        @if($tiktok)
        <div class="channel-section">
            <span class="section-tag">TikTok</span>
            <h2 class="section-title">Anuncio para TikTok</h2>
            <div class="channel-content">
                <div class="title">{{ $tiktok->title }}</div>
                <div class="description">{{ $tiktok->description }}</div>
                @if($tiktok->hashtags)
                <div class="hashtags-list">
                    @foreach($tiktok->hashtags as $tag)
                    <span class="hashtag">#{{ $tag }}</span>
                    @endforeach
                </div>
                @endif
                @if($tiktok->photo_tips)
                <div class="tips-list">
                    <ul>
                        @foreach($tiktok->photo_tips as $tip)
                        <li><span class="tip-bullet">📸</span> {{ $tip }}</li>
                        @endforeach
                    </ul>
                </div>
                @endif
            </div>
        </div>
        @endif

        @if($instagram)
        <div class="channel-section">
            <span class="section-tag">Instagram</span>
            <h2 class="section-title">Anuncio para Instagram</h2>
            <div class="channel-content">
                <div class="title">{{ $instagram->title }}</div>
                <div class="description">{{ $instagram->description }}</div>
                @if($instagram->hashtags)
                <div class="hashtags-list">
                    @foreach($instagram->hashtags as $tag)
                    <span class="hashtag">#{{ $tag }}</span>
                    @endforeach
                </div>
                @endif
                @if($instagram->photo_tips)
                <div class="tips-lists">
                    <ul>
                        @foreach($instagram->photo_tips as $tip)
                        <li><span class="tip-bullet">📸</span> {{ $tip }}</li>
                        @endforeach
                    </ul>
                </div>
                @endif
            </div>
        </div>
        @endif

        <!-- Honorarios + QR -->
        <div class="honorarios-row">
            <div class="honorarios-section">
                <div class="honorarios-tag">
                    Nuestros Honorarios de Gestión
                </div>
                <div class="honorarios-price">{{ $precio_honorarios }}</div>
                <div class="honorarios-conditions"><strong>+ coste del vehículo e impuestos</strong><br>Contacta sin compromiso · Ponte en manos de profesionales.</div>
            </div>
            <div class="qr-card">
                <div class="qr-box">
                    {!! $qr_svg !!}
                </div>
                <div class="qr-content">
                    <div class="qr-tag">Ver coche</div>
                    <div class="qr-title">Escanea para<br>ver el coche</div>
                    <div class="qr-desc">Accede al detalle completo del vehículo.</div>
                </div>
            </div>
        </div>

        <!-- Footer -->
        <div class="footer">
            <div class="footer-item">
                <div class="icon">📞</div>
                <div>
                    <div class="label">Teléfono 1</div>
                    <div class="value">{{ $telefono_1 }}</div>
                </div>
            </div>
            <div class="footer-item">
                <div class="icon">📞</div>
                <div>
                    <div class="label">Teléfono 2</div>
                    <div class="value">{{ $telefono_2 }}</div>
                </div>
            </div>
            <div class="footer-item">
                <div class="icon">✉️</div>
                <div>
                    <div class="label">Email</div>
                    <div class="value">{{ $email }}</div>
                </div>
            </div>
        </div>

        <p class="disclaimer">*El precio final puede variar según las especificaciones del vehículo, impuestos aplicables, logística y transporte.</p>

    </div>
</body>
</html>
