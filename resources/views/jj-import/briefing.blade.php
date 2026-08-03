@php
    $precio_honorarios = $precio_honorarios ?? '1.500 €';
    $telefono_1 = $telefono_1 ?? '675 70 14 39';
    $telefono_2 = $telefono_2 ?? '691 48 59 27';
    $email = $email ?? 'jjimportmotors@gmail.com';
    $qr_url = $qr_url ?? 'https://jjimportmotors.on-forge.com/request/jj-import-motors';

    $qr_svg = $qr_svg ?? null;
    if (!$qr_svg) {
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
    }

    // Specs normalized
    $specs = [
        'Año' => $car->year ?? '—',
        'Kilómetros' => isset($car->mileage) ? number_format($car->mileage) . ' km' : '—',
        'Combustible' => $car->fuel ?? '—',
        'Cambio' => $car->transmission ?? '—',
        'Potencia' => isset($car->cv) ? $car->cv . ' CV' : '—',
        'Color' => $car->color ?? '—',
        'Puertas' => $car->doors ?? '—',
        'Plazas' => $car->seats ?? '—',
        'Norma Euro' => $car->euro_norm ?? '—',
        'Ciudad' => $car->city ?? '—',
    ];

    $price = $car->purchase_price ?? 0;
    $marketAvg = $car->market_avg ?? $price;
    $saving = $car->estimated_saving ?? ($marketAvg - $price > 0 ? $marketAvg - $price : 0);
    $description = $car->description
        ?? $car->recommendation
        ?? ($car->brand . ' ' . $car->model . ' importado y matriculado por JJ Import Motors. Servicio llave en mano: inspección, transporte, trámites y entrega a tu nombre.');
    $equipment = collect($car->equipment ?? [])->take(8)->values();
@endphp

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>JJ Import Motors - {{ $car->brand }} {{ $car->model }}</title>
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800;900&display=swap');

        * { margin: 0; padding: 0; box-sizing: border-box; }

        html, body {
            font-family: 'Inter', 'Segoe UI', Arial, sans-serif;
            background: #0f1d42;
            color: #e5e7eb;
            width: 100%;
            min-height: 100vh;
            -webkit-font-smoothing: antialiased;
            -webkit-print-color-adjust: exact;
            print-color-adjust: exact;
        }

        body {
            position: relative;
            padding: 36px 36px 70px 36px;
            min-height: 100vh;
            background:
                radial-gradient(ellipse at 100% 0%, rgba(143, 163, 217, 0.12) 0%, transparent 45%),
                radial-gradient(ellipse at 0% 100%, rgba(190, 192, 195, 0.08) 0%, transparent 45%),
                linear-gradient(180deg, #0f1d42 0%, #14265a 50%, #0f1d42 100%);
        }

        body::before {
            content: '';
            position: absolute;
            inset: 0;
            background-image:
                linear-gradient(rgba(143, 163, 217, 0.04) 1px, transparent 1px),
                linear-gradient(90deg, rgba(143, 163, 217, 0.04) 1px, transparent 1px);
            background-size: 28px 28px;
            pointer-events: none;
            z-index: 0;
        }

        .container {
            position: relative;
            z-index: 1;
            max-width: 1060px;
            margin: 0 auto;
        }

        /* ============ HEADER ============ */
        .header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding-bottom: 18px;
            border-bottom: 1px solid rgba(143, 163, 217, 0.2);
            margin-bottom: 24px;
        }

        .logo { height: 50px; width: auto; }

        .badge-llave {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            background: linear-gradient(135deg, #1A306D 0%, #2a3d87 100%);
            color: #fff;
            padding: 9px 18px;
            border-radius: 100px;
            font-size: 10.5px;
            font-weight: 700;
            letter-spacing: 1.4px;
            box-shadow: 0 4px 14px rgba(26, 48, 109, 0.4);
        }

        /* ============ HERO ============ */
        .hero { margin-bottom: 20px; text-align: center; }

        .hero-eyebrow {
            display: inline-flex;
            align-items: center;
            gap: 9px;
            background: rgba(26, 48, 109, 0.15);
            border: 1px solid rgba(143, 163, 217, 0.3);
            color: #8fa3d9;
            padding: 6px 16px;
            border-radius: 100px;
            font-size: 10px;
            font-weight: 600;
            letter-spacing: 1.6px;
            text-transform: uppercase;
            margin-bottom: 12px;
        }

        .hero-eyebrow .pulse {
            width: 6px; height: 6px;
            background: #8fa3d9; border-radius: 50%;
            box-shadow: 0 0 10px #8fa3d9;
        }

        .h1-title {
            font-size: 30px;
            font-weight: 800;
            line-height: 1.12;
            color: #ffffff;
            letter-spacing: -0.6px;
        }

        .h1-title .accent { color: #8fa3d9; }

        .hero-subtitle {
            font-size: 13.5px;
            color: #94a3b8;
            margin-top: 8px;
            letter-spacing: 0.3px;
        }

        /* ============ PHOTO ============ */
        .car-photo {
            width: 100%;
            border-radius: 16px;
            overflow: hidden;
            border: 1px solid rgba(143, 163, 217, 0.25);
            margin-bottom: 20px;
            box-shadow: 0 10px 36px rgba(0, 0, 0, 0.5);
            background: #14265a;
        }

        .car-photo img {
            display: block;
            width: 100%;
            height: auto;
            max-height: 360px;
            object-fit: cover;
        }

        .car-photo.no-photo {
            display: flex; align-items: center; justify-content: center;
            min-height: 210px;
        }
        .car-photo.no-photo span { font-size: 58px; color: #8fa3d9; }

        /* ============ PRICE ============ */
        .price-row {
            display: grid;
            grid-template-columns: 1.5fr 1fr 1fr;
            gap: 12px;
            margin-bottom: 20px;
        }

        .price-card {
            background: linear-gradient(180deg, rgba(15, 23, 42, 0.85) 0%, rgba(15, 23, 42, 0.55) 100%);
            border-radius: 14px;
            padding: 16px 18px;
            text-align: center;
            border: 1px solid rgba(143, 163, 217, 0.2);
        }

        .price-card.main {
            border-color: rgba(143, 163, 217, 0.55);
            background: linear-gradient(135deg, rgba(26, 48, 109, 0.35) 0%, rgba(26, 48, 109, 0.15) 100%);
        }

        .price-card .label {
            font-size: 9px; color: #64748b;
            text-transform: uppercase; letter-spacing: 1px; font-weight: 600;
            margin-bottom: 4px;
        }
        .price-card .value { font-size: 18px; font-weight: 800; color: #f8fafc; line-height: 1.2; }
        .price-card.main .value { font-size: 26px; color: #8fa3d9; }
        .price-card .value.saving { color: #4ade80; }

        /* ============ DESCRIPTION ============ */
        .section {
            background: linear-gradient(180deg, rgba(15, 23, 42, 0.6) 0%, rgba(15, 23, 42, 0.35) 100%);
            border: 1px solid rgba(143, 163, 217, 0.15);
            border-radius: 14px;
            padding: 18px 22px;
            margin-bottom: 16px;
        }

        .section-tag {
            color: #8fa3d9;
            font-size: 10px; font-weight: 700; letter-spacing: 2.4px;
            text-transform: uppercase; display: block; margin-bottom: 6px;
        }

        .section-title {
            font-size: 16px; font-weight: 800; color: #f8fafc;
            margin-bottom: 10px; letter-spacing: -0.2px;
        }

        .description-text {
            font-size: 12.5px;
            color: #cbd5e1;
            line-height: 1.65;
            white-space: pre-line;
        }

        /* Equipment chips */
        .chips { display: flex; flex-wrap: wrap; gap: 6px; margin-top: 4px; }
        .chip {
            background: rgba(26, 48, 109, 0.25);
            border: 1px solid rgba(143, 163, 217, 0.25);
            color: #cbd5e1;
            padding: 4px 10px;
            border-radius: 100px;
            font-size: 10px;
            font-weight: 500;
        }

        /* ============ SPECS ============ */
        .specs-grid {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 8px;
        }

        .spec-cell {
            background: rgba(15, 23, 42, 0.5);
            border: 1px solid rgba(143, 163, 217, 0.12);
            border-radius: 10px;
            padding: 10px 12px;
        }

        .spec-cell .k {
            font-size: 9px; color: #64748b;
            text-transform: uppercase; letter-spacing: 0.8px; font-weight: 600;
            margin-bottom: 3px;
        }
        .spec-cell .v { font-size: 13px; font-weight: 700; color: #f1f5f9; }

        /* ============ VERDICT ============ */
        .verdict-strip {
            display: flex; align-items: center; gap: 12px;
            background: linear-gradient(135deg, rgba(34, 197, 94, 0.12) 0%, rgba(26, 48, 109, 0.12) 100%);
            border: 1px solid rgba(74, 222, 128, 0.3);
            border-radius: 12px;
            padding: 12px 18px;
            margin-bottom: 16px;
        }
        .verdict-dot { width: 10px; height: 10px; border-radius: 50%; background: #4ade80; box-shadow: 0 0 10px #4ade80; flex-shrink: 0; }
        .verdict-text { font-size: 12px; color: #cbd5e1; }
        .verdict-text strong { color: #f1f5f9; }

        /* ============ QR + CONTACT ============ */
        .bottom-row {
            display: grid;
            grid-template-columns: 1fr 1.3fr;
            gap: 14px;
            margin-bottom: 18px;
        }

        .qr-card {
            background: linear-gradient(180deg, rgba(15, 23, 42, 0.75) 0%, rgba(15, 23, 42, 0.45) 100%);
            border: 1px solid rgba(143, 163, 217, 0.3);
            border-radius: 14px;
            padding: 14px;
            display: flex; align-items: center; gap: 14px;
        }

        .qr-box {
            flex-shrink: 0;
            width: 96px; height: 96px;
            background: #fff; border-radius: 9px; padding: 5px;
            display: flex; align-items: center; justify-content: center;
            box-shadow: 0 4px 18px rgba(143, 163, 217, 0.3);
        }
        .qr-box svg { width: 100%; height: 100%; display: block; }

        .qr-tag { color: #8fa3d9; font-size: 9px; font-weight: 700; letter-spacing: 1.8px; margin-bottom: 4px; text-transform: uppercase; }
        .qr-title { font-size: 14px; font-weight: 800; color: #f8fafc; margin-bottom: 4px; line-height: 1.15; }
        .qr-desc { font-size: 10px; color: #94a3b8; line-height: 1.4; }

        .contact-card {
            background: linear-gradient(180deg, rgba(15, 23, 42, 0.6) 0%, rgba(15, 23, 42, 0.35) 100%);
            border: 1px solid rgba(143, 163, 217, 0.15);
            border-radius: 14px;
            padding: 14px 18px;
            display: flex; flex-direction: column; justify-content: center; gap: 8px;
        }

        .contact-item { display: flex; align-items: center; gap: 10px; font-size: 12px; color: #cbd5e1; }
        .contact-item .icon {
            width: 30px; height: 30px; border-radius: 8px;
            background: rgba(26, 48, 109, 0.2); color: #8fa3d9;
            display: flex; align-items: center; justify-content: center; font-size: 13px; flex-shrink: 0;
        }
        .contact-item .label { font-size: 9px; color: #64748b; text-transform: uppercase; letter-spacing: 0.6px; font-weight: 600; }
        .contact-item .value { font-weight: 700; color: #f1f5f9; font-size: 12.5px; }

        .honorarios {
            text-align: center;
            font-size: 11.5px;
            color: #94a3b8;
            background: linear-gradient(135deg, rgba(26, 48, 109, 0.1) 0%, rgba(190, 192, 195, 0.1) 100%);
            border: 1px solid rgba(143, 163, 217, 0.2);
            border-radius: 12px;
            padding: 10px 16px;
            margin-bottom: 16px;
        }
        .honorarios strong { color: #8fa3d9; }

        .disclaimer {
            text-align: center; font-size: 9.5px; color: #94a3b8;
            margin-top: 16px; line-height: 1.5; padding: 0 18px; font-style: italic;
        }
    </style>
</head>
<body>
    <div class="container">

        <!-- Header -->
        <div class="header">
            <img src="{{ $logo_base64 }}" alt="JJ Import Motors" class="logo">
            <span class="badge-llave">SERVICIO LLAVE EN MANO</span>
        </div>

        <!-- Hero -->
        <div class="hero">
            <span class="hero-eyebrow"><span class="pulse"></span>Importación Premium · Alemania → España</span>
            <h1 class="h1-title">{{ $car->brand }} {{ $car->model }} @if($car->version ?? null)<span class="accent">{{ $car->version }}</span>@endif</h1>
            <p class="hero-subtitle">{{ $car->year ?? '' }} · {{ isset($car->mileage) ? number_format($car->mileage) . ' km' : '' }} · {{ $car->fuel ?? '' }} · {{ $car->transmission ?? '' }}</p>
        </div>

        <!-- Photo -->
        <div class="car-photo {{ $car_photo_base64 ? '' : 'no-photo' }}">
            @if($car_photo_base64)
                <img src="{{ $car_photo_base64 }}" alt="{{ $car->brand }} {{ $car->model }}">
            @else
                <span>🚗</span>
            @endif
        </div>

        <!-- Price -->
        <div class="price-row">
            <div class="price-card main">
                <div class="label">Precio</div>
                <div class="value">{{ number_format($price, 0, ',', '.') }} €</div>
            </div>
            <div class="price-card">
                <div class="label">Valor de mercado</div>
                <div class="value">{{ number_format($marketAvg, 0, ',', '.') }} €</div>
            </div>
            <div class="price-card">
                <div class="label">Ahorro estimado</div>
                <div class="value saving">{{ $saving > 0 ? number_format($saving, 0, ',', '.') . ' €' : '—' }}</div>
            </div>
        </div>

        <!-- Verdict -->
        @if(($car->verdict ?? null) || ($car->traffic_light ?? null))
        <div class="verdict-strip">
            <span class="verdict-dot"></span>
            <div class="verdict-text">
                <strong>Veredicto del informe técnico:</strong>
                {{ $car->verdict ?? 'Verificado' }}
                @if($car->verdict_confidence ?? null) · Confianza {{ $car->verdict_confidence }}@endif
                — Inspeccionado y validado por JJ Import Motors.
            </div>
        </div>
        @endif

        <!-- Description -->
        @if($description)
        <div class="section">
            <span class="section-tag">Sobre este coche</span>
            <div class="description-text">{{ $description }}</div>
        </div>
        @endif

        <!-- Equipment -->
        @if($equipment->count())
        <div class="section">
            <span class="section-tag">Equipamiento destacado</span>
            <div class="chips">
                @foreach($equipment as $item)
                    <span class="chip">{{ $item }}</span>
                @endforeach
            </div>
        </div>
        @endif

        <!-- Specs -->
        <div class="section">
            <span class="section-tag">Ficha técnica</span>
            <div class="specs-grid">
                @foreach($specs as $k => $v)
                    @if($v && $v !== '—')
                    <div class="spec-cell">
                        <div class="k">{{ $k }}</div>
                        <div class="v">{{ $v }}</div>
                    </div>
                    @endif
                @endforeach
            </div>
        </div>

        <!-- QR + Contact -->
        <div class="bottom-row">
            <div class="qr-card">
                <div class="qr-box">
                    {!! $qr_svg !!}
                </div>
                <div class="qr-content">
                    <div class="qr-tag">Escanea para verlo</div>
                    <div class="qr-title">Este coche,<br>en la web</div>
                    <div class="qr-desc">Accede a la ficha completa, fotos e informe técnico desde tu móvil.</div>
                </div>
            </div>
            <div class="contact-card">
                <div class="contact-item">
                    <div class="icon">📞</div>
                    <div><div class="label">Teléfono 1</div><div class="value">{{ $telefono_1 }}</div></div>
                </div>
                <div class="contact-item">
                    <div class="icon">📞</div>
                    <div><div class="label">Teléfono 2</div><div class="value">{{ $telefono_2 }}</div></div>
                </div>
                <div class="contact-item">
                    <div class="icon">✉️</div>
                    <div><div class="label">Email</div><div class="value">{{ $email }}</div></div>
                </div>
            </div>
        </div>

        <!-- Honorarios -->
        <div class="honorarios">
            <strong>Honorarios de gestión: {{ $precio_honorarios }}</strong> + coste del vehículo e impuestos · Contacta sin compromiso
        </div>

        <p class="disclaimer">*El precio final puede variar según las especificaciones del vehículo, impuestos aplicables, logística y transporte.</p>

    </div>
</body>
</html>
