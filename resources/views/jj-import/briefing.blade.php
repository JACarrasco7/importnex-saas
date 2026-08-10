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
                    ->size(240)
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

    $price = $car->purchase_price ?? 0;
    $marketAvg = $car->market_avg ?? $price;
    $saving = $car->estimated_saving ?? ($marketAvg - $price > 0 ? $marketAvg - $price : 0);
    $description = $car->description
        ?? $car->recommendation
        ?? ($car->brand . ' ' . $car->model . ' importado y matriculado por JJ Import Motors. Servicio llave en mano: inspección, transporte, trámites y entrega a tu nombre.');

    $specs = [
        'Año' => $car->year ?? '—',
        'Km' => isset($car->mileage) ? number_format($car->mileage, 0, ',', '.') . ' km' : '—',
        'Combustible' => $car->fuel ?? '—',
        'Cambio' => $car->transmission ?? '—',
        'CV' => isset($car->cv) ? $car->cv : '—',
        'Color' => $car->color ?? '—',
        'Puertas' => $car->doors ?? '—',
        'Plazas' => $car->seats ?? '—',
        'Norma Euro' => $car->euro_norm ?? '—',
        'Ciudad' => $car->city ?? '—',
    ];

    $equipment = collect($car->equipment ?? [])->take(6)->values();
@endphp

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>JJ Import Motors - {{ $car->brand }} {{ $car->model }}</title>
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800;900&display=swap');

        @page { size: A4; margin: 0; }

        * { margin: 0; padding: 0; box-sizing: border-box; }

        html, body {
            font-family: 'Inter', 'Segoe UI', Arial, sans-serif;
            background: #0f1d42;
            color: #e5e7eb;
            width: 210mm;
            height: 297mm;
            overflow: hidden;
            -webkit-font-smoothing: antialiased;
            -webkit-print-color-adjust: exact;
            print-color-adjust: exact;
        }

        body {
            position: relative;
            padding: 18px 28px 20px 28px;
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
            width: 100%;
            height: 100%;
            display: flex;
            flex-direction: column;
        }

        /* ============ HEADER ============ */
        .header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding-bottom: 10px;
            border-bottom: 1px solid rgba(143, 163, 217, 0.2);
            margin-bottom: 10px;
            flex-shrink: 0;
        }

        .logo { height: 40px; width: auto; }

        .badge-llave {
            display: inline-flex;
            align-items: center;
            gap: 7px;
            background: linear-gradient(135deg, #1A306D 0%, #2a3d87 100%);
            color: #fff;
            padding: 7px 14px;
            border-radius: 100px;
            font-size: 9px;
            font-weight: 700;
            letter-spacing: 1.2px;
            box-shadow: 0 4px 14px rgba(26, 48, 109, 0.4);
        }

        /* ============ TITLE ============ */
        .title-row {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 16px;
            margin-bottom: 10px;
            flex-shrink: 0;
        }

        .title-left { min-width: 0; }

        .title-eyebrow {
            color: #8fa3d9;
            font-size: 8.5px;
            font-weight: 700;
            letter-spacing: 1.8px;
            text-transform: uppercase;
            margin-bottom: 2px;
        }

        .h1-title {
            font-size: 24px;
            font-weight: 800;
            line-height: 1.1;
            color: #ffffff;
            letter-spacing: -0.5px;
        }

        .h1-title .accent { color: #8fa3d9; }

        .title-meta { font-size: 11px; color: #94a3b8; margin-top: 3px; }

        .title-price {
            text-align: right;
            flex-shrink: 0;
            background: linear-gradient(135deg, rgba(26, 48, 109, 0.4) 0%, rgba(26, 48, 109, 0.15) 100%);
            border: 1px solid rgba(143, 163, 217, 0.45);
            border-radius: 12px;
            padding: 8px 16px;
        }

        .title-price .label { font-size: 8px; color: #8fa3d9; text-transform: uppercase; letter-spacing: 1px; font-weight: 700; }
        .title-price .value { font-size: 22px; font-weight: 900; color: #fff; line-height: 1.1; }

        /* ============ MAIN GRID ============ */
        .main-grid {
            display: grid;
            grid-template-columns: 1fr 1.05fr;
            gap: 12px;
            flex: 1;
            min-height: 0;
        }

        /* Left: photo */
        .photo-box {
            border-radius: 12px;
            overflow: hidden;
            border: 1px solid rgba(143, 163, 217, 0.25);
            background: #14265a;
            display: flex;
            align-items: center;
            justify-content: center;
            min-height: 0;
        }

        .photo-box img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        .photo-box.no-photo span { font-size: 64px; color: #8fa3d9; }

        /* Right: content column */
        .right-col {
            display: flex;
            flex-direction: column;
            gap: 8px;
            min-height: 0;
        }

        .price-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 8px;
        }

        .price-card {
            background: rgba(15, 23, 42, 0.6);
            border: 1px solid rgba(143, 163, 217, 0.2);
            border-radius: 10px;
            padding: 8px 12px;
            text-align: center;
        }

        .price-card .label { font-size: 8px; color: #64748b; text-transform: uppercase; letter-spacing: 0.8px; font-weight: 600; }
        .price-card .value { font-size: 15px; font-weight: 800; color: #f1f5f9; }
        .price-card .value.saving { color: #4ade80; }

        .desc-box {
            background: rgba(15, 23, 42, 0.45);
            border: 1px solid rgba(143, 163, 217, 0.15);
            border-radius: 10px;
            padding: 10px 14px;
            flex: 1;
            min-height: 0;
        }

        .desc-label {
            color: #8fa3d9;
            font-size: 8.5px;
            font-weight: 700;
            letter-spacing: 1.8px;
            text-transform: uppercase;
            margin-bottom: 4px;
        }

        .desc-text {
            font-size: 10.5px;
            color: #cbd5e1;
            line-height: 1.5;
            display: -webkit-box;
            -webkit-line-clamp: 7;
            -webkit-box-orient: vertical;
            overflow: hidden;
            white-space: pre-line;
        }

        .verdict-line {
            display: flex; align-items: center; gap: 8px;
            background: linear-gradient(135deg, rgba(34, 197, 94, 0.12) 0%, rgba(26, 48, 109, 0.12) 100%);
            border: 1px solid rgba(74, 222, 128, 0.3);
            border-radius: 8px;
            padding: 7px 12px;
            font-size: 10px;
            color: #cbd5e1;
        }
        .verdict-line .dot { width: 8px; height: 8px; border-radius: 50%; background: #4ade80; box-shadow: 0 0 8px #4ade80; flex-shrink: 0; }
        .verdict-line strong { color: #f1f5f9; }

        /* ============ BOTTOM: specs + equipment + qr ============ */
        .bottom-grid {
            display: grid;
            grid-template-columns: 1.4fr 1fr;
            gap: 12px;
            margin-top: 12px;
            flex-shrink: 0;
        }

        .specs-grid {
            display: grid;
            grid-template-columns: repeat(5, 1fr);
            gap: 6px;
        }

        .spec-cell {
            background: rgba(15, 23, 42, 0.5);
            border: 1px solid rgba(143, 163, 217, 0.12);
            border-radius: 8px;
            padding: 6px 8px;
        }
        .spec-cell .k { font-size: 7.5px; color: #64748b; text-transform: uppercase; letter-spacing: 0.6px; font-weight: 600; }
        .spec-cell .v { font-size: 11.5px; font-weight: 700; color: #f1f5f9; margin-top: 1px; }

        .equip-row {
            display: flex;
            flex-wrap: wrap;
            gap: 5px;
            margin-top: 8px;
        }

        .chip {
            background: rgba(26, 48, 109, 0.25);
            border: 1px solid rgba(143, 163, 217, 0.25);
            color: #cbd5e1;
            padding: 3px 9px;
            border-radius: 100px;
            font-size: 8.5px;
            font-weight: 500;
        }

        .qr-box-wrap {
            display: flex;
            align-items: center;
            gap: 12px;
            background: linear-gradient(180deg, rgba(15, 23, 42, 0.75) 0%, rgba(15, 23, 42, 0.45) 100%);
            border: 1px solid rgba(143, 163, 217, 0.3);
            border-radius: 12px;
            padding: 10px 14px;
        }

        .qr-box {
            flex-shrink: 0;
            width: 78px; height: 78px;
            background: #fff; border-radius: 8px; padding: 4px;
            display: flex; align-items: center; justify-content: center;
            box-shadow: 0 4px 14px rgba(143, 163, 217, 0.3);
        }
        .qr-box svg { width: 100%; height: 100%; display: block; }

        .qr-tag { color: #8fa3d9; font-size: 8px; font-weight: 700; letter-spacing: 1.5px; margin-bottom: 3px; text-transform: uppercase; }
        .qr-title { font-size: 12px; font-weight: 800; color: #f8fafc; margin-bottom: 3px; line-height: 1.15; }
        .qr-desc { font-size: 8.5px; color: #94a3b8; line-height: 1.35; }

        /* ============ FOOTER ============ */
        .footer {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 12px;
            margin-top: 12px;
            padding-top: 10px;
            border-top: 1px solid rgba(143, 163, 217, 0.2);
            flex-shrink: 0;
        }

        .contact-row { display: flex; gap: 16px; }
        .contact-item { display: flex; align-items: center; gap: 7px; font-size: 10px; color: #cbd5e1; }
        .contact-item .label { font-size: 7.5px; color: #64748b; text-transform: uppercase; letter-spacing: 0.5px; font-weight: 600; }
        .contact-item .value { font-weight: 700; color: #f1f5f9; font-size: 10.5px; }

        .honorarios {
            font-size: 9px;
            color: #94a3b8;
            text-align: right;
        }
        .honorarios strong { color: #8fa3d9; }

        .disclaimer {
            text-align: center;
            font-size: 7.5px;
            color: #64748b;
            margin-top: 6px;
            font-style: italic;
            flex-shrink: 0;
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

        <!-- Title + price -->
        <div class="title-row">
            <div class="title-left">
                <div class="title-eyebrow">Importación Premium · Alemania → España</div>
                <h1 class="h1-title">{{ $car->brand }} {{ $car->model }} <span class="accent">@if($car->version ?? null){{ $car->version }}@endif</span></h1>
                <div class="title-meta">{{ $car->year ?? '' }} · {{ number_format($car->mileage, 0, ',', '.') }} km · {{ $car->fuel ?? '' }} · {{ $car->transmission ?? '' }}</div>
            </div>
            <div class="title-price">
                <div class="label">Precio</div>
                <div class="value">{{ number_format($price, 0, ',', '.') }} €</div>
            </div>
        </div>

        <!-- Main grid: photo + content -->
        <div class="main-grid">
            <div class="photo-box {{ $car_photo_base64 ? '' : 'no-photo' }}">
                @if($car_photo_base64)
                    <img src="{{ $car_photo_base64 }}" alt="{{ $car->brand }} {{ $car->model }}">
                @else
                    <span>🚗</span>
                @endif
            </div>

            <div class="right-col">
                <div class="price-row">
                    <div class="price-card">
                        <div class="label">Valor de mercado</div>
                        <div class="value">{{ number_format($marketAvg, 0, ',', '.') }} €</div>
                    </div>
                    <div class="price-card">
                        <div class="label">Ahorro estimado</div>
                        <div class="value saving">{{ $saving > 0 ? number_format($saving, 0, ',', '.') . ' €' : '—' }}</div>
                    </div>
                </div>

                <div class="desc-box">
                    <div class="desc-label">Sobre este vehículo</div>
                    <div class="desc-text">{{ $description }}</div>
                </div>

                @if(($car->verdict ?? null) || ($car->traffic_light ?? null))
                <div class="verdict-line">
                    <span class="dot"></span>
                    <span><strong>Veredicto:</strong> {{ $car->verdict ?? 'Verificado' }}@if($car->verdict_confidence ?? null) · confianza {{ $car->verdict_confidence }}@endif — Inspeccionado por JJ Import Motors.</span>
                </div>
                @endif
            </div>
        </div>

        <!-- Bottom: specs + equipment + QR -->
        <div class="bottom-grid">
            <div>
                <div class="desc-label">Ficha técnica</div>
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
                @if($equipment->count())
                <div class="desc-label" style="margin-top:8px;">Equipamiento</div>
                <div class="equip-row">
                    @foreach($equipment as $item)
                        <span class="chip">{{ $item }}</span>
                    @endforeach
                </div>
                @endif
            </div>

            <div class="qr-box-wrap">
                <div class="qr-box">
                    {!! $qr_svg !!}
                </div>
                <div>
                    <div class="qr-tag">Escanea para verlo</div>
                    <div class="qr-title">Este vehículo, en la web</div>
                    <div class="qr-desc">Ficha completa, fotos e informe técnico desde tu móvil.</div>
                </div>
            </div>
        </div>

        <!-- Footer -->
        <div class="footer">
            <div class="contact-row">
                <div class="contact-item">
                    <span class="label">📞 Tel 1</span>
                    <span class="value">{{ $telefono_1 }}</span>
                </div>
                <div class="contact-item">
                    <span class="label">📞 Tel 2</span>
                    <span class="value">{{ $telefono_2 }}</span>
                </div>
                <div class="contact-item">
                    <span class="label">✉️ Email</span>
                    <span class="value">{{ $email }}</span>
                </div>
            </div>
            <div class="honorarios">
                <strong>Honorarios: {{ $precio_honorarios }}</strong> + coste e impuestos
            </div>
        </div>

        <p class="disclaimer">*Precio final orientativo. Puede variar según especificaciones, impuestos, logística y transporte. · JJ Import Motors — Servicio llave en mano.</p>

    </div>
</body>
</html>
