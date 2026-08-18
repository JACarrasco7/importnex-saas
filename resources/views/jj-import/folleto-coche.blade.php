{{-- ═══════════════════════════════════════════════════════════════════════
    FOLLETO DEL COCHE (PDF) — TIPO: marketing / venta al cliente
    ─────────────────────────────────────────────────────────────────────────
    · QUIÉN LO GENERA  : Laravel (Blade + Browsershot)
    · DESDE QUÉ ARCHIVO: contenido/ficha-publicitaria.txt  (esqueleto [MARCADOR])
                        o fallback desde los datos del coche
    · RUTA             : GET /cars/{car}/folleto  (autenticado)
    · CONTROLADOR      : PaqueteValoracionController@folleto
    · AUDIENCIA        : CLIENTE final. NO mostrar margen ni honorarios.
    · DIFERENCIA vs ficha: el folleto es más visual/compacto — portada con
      foto grande, precio protagonista, veredicto y CTA. Ideal para imprimir
      o enviar por WhatsApp. La ficha es el documento técnico completo.
    ═══════════════════════════════════════════════════════════════════════ --}}
@php
    $telefono_1 = $telefono_1 ?? '675 70 14 39';
    $telefono_2 = $telefono_2 ?? '691 48 59 27';
    $email = $email ?? 'jjimportmotors@gmail.com';
    $qr_url = $e->uno('QR') ?? 'https://jjimportmotors.on-forge.com/request/jj-import-motors';

    $qr_svg = $qr_svg ?? null;
    if (!$qr_svg) {
        try {
            $qr_svg = \SimpleSoftwareIO\QrCode\Generator::class
                ? (new \SimpleSoftwareIO\QrCode\Generator())->format('svg')
                    ->size(200)->margin(1)->errorCorrection('H')
                    ->backgroundColor(255, 255, 255)->color(6, 16, 31)
                    ->generate($qr_url)
                : null;
        } catch (\Exception $e) {
            $qr_svg = null;
        }
    }

    // ── KPI desde SPEC (Etiqueta | Valor) ──
    $specs = $e->filas('SPEC');
    $spec_val = function ($needle) use ($specs) {
        foreach ($specs as $s) {
            if (stripos((string) ($s[0] ?? ''), $needle) !== false) {
                return $s[1] ?? null;
            }
        }
        return null;
    };
    $kpi_km      = $spec_val('KM') ?? $spec_val('Kilómetros');
    $kpi_anio    = $spec_val('Año');
    $kpi_precio  = $e->uno('PRECIO');
    $kpi_ahorro  = $e->uno('AHORRO');
    $kpi_fuel    = $spec_val('Combustible');
    $kpi_power   = $spec_val('Potencia');
    $kpi_gearbox = $spec_val('Cambio');

    // ── Todas las SPEC (para el grid completo) ──
    $all_specs = [];
    foreach ($specs as $s) {
        $label = trim((string) ($s[0] ?? ''));
        $value = trim((string) ($s[1] ?? ''));
        if ($label !== '' && $value !== '') {
            $all_specs[] = ['k' => $label, 'v' => $value];
        }
    }

    // La ficha técnica NO repite las protagonistas del KPI (Año / KM) ni
    // muestra las emisiones CO2: esas ya van arriba en grande o no aportan
    // al folleto comercial. El resto (color, puertas, VIN...) queda en la tabla.
    $specs_table = [];
    $exclude_specs = ['año', 'km', 'kilometro', 'kilómetro', 'co2', 'emision'];
    foreach ($all_specs as $spec) {
        $l = mb_strtolower($spec['k']);
        $dup = false;
        foreach ($exclude_specs as $ex) {
            if (str_contains($l, $ex)) {
                $dup = true;
                break;
            }
        }
        if (! $dup) {
            $specs_table[] = $spec;
        }
    }

    // ── Texto de valoración PARA EL CLIENTE ──
    // Nada de margen/honorarios/negociación: el folleto es un documento de
    // venta. La skill puede dejar un bloque [VALORACION] presentable en el
    // esqueleto; si no, caemos al `valuation` del coche (valoración de
    // mercado) o a nada. verdict_reasoning/recommendation son INTERNOS y
    // nunca se exponen aquí.
    $val_sales_text = trim((string) ($e->uno('VALORACION') ?: ($car->valuation ?? '')));
    $val_pros = is_array($car->pros ?? null) ? array_filter(array_map('trim', $car->pros)) : [];
    $val_cons = is_array($car->cons ?? null) ? array_filter(array_map('trim', $car->cons)) : [];

    $titulo = $e->uno('TITULO') ?: trim(($car->brand ?? '').' '.($car->model ?? ''));
    $claim  = $e->uno('CLAIM');

    // ── Veredicto / semáforo desde el coche ──
    $tl = strtolower((string) $car->traffic_light);
    $tl_label = ['green' => 'Excelente compra', 'amber' => 'Buena opción', 'red' => 'Con cautela', 'neutral' => 'Sin veredicto'][$tl] ?? 'Sin veredicto';
    $tl_color = ['green' => '#10b981', 'amber' => '#f59e0b', 'red' => '#ef4444', 'neutral' => '#94a3b8'][$tl] ?? '#94a3b8';

    // ── Origen DE/ES (badge) ──
    $pais = strtolower((string) ($car->pais_origen ?? ''));
    $origen = (str_contains($pais, 'alem') || $pais === 'de') ? 'de'
        : ((str_contains($pais, 'espa') || $pais === 'es') ? 'es' : null);

    $fotos = $fotos ?? [];
@endphp

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>JJ Import Motors - {{ $titulo }}</title>
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800;900&display=swap');
        @page { size: A4; margin: 0; }
        * { margin: 0; padding: 0; box-sizing: border-box; }

        html, body {
            font-family: 'Inter', 'Segoe UI', Arial, sans-serif;
            color: #e5e7eb;
            -webkit-font-smoothing: antialiased;
            -webkit-print-color-adjust: exact;
            print-color-adjust: exact;
        }

        body {
            position: relative;
            padding: 26px 30px 50px 30px;
            background:
                radial-gradient(ellipse at 100% 0%, rgba(143, 163, 217, 0.12) 0%, transparent 45%),
                linear-gradient(180deg, #0f1d42 0%, #14265a 50%, #0f1d42 100%);
        }

        .container { max-width: 1080px; margin: 0 auto; position: relative; z-index: 1; }

        .header {
            display: flex; justify-content: space-between; align-items: center;
            padding-bottom: 14px; border-bottom: 1px solid rgba(143, 163, 217, 0.2);
            margin-bottom: 16px;
        }
        .logo { height: 44px; width: auto; }
        .badge-llave {
            display: inline-flex; align-items: center; gap: 7px;
            background: linear-gradient(135deg, #1A306D 0%, #2a3d87 100%);
            color: #fff; padding: 8px 15px; border-radius: 100px;
            font-size: 10px; font-weight: 700; letter-spacing: 1.3px;
            box-shadow: 0 4px 14px rgba(26, 48, 109, 0.4);
        }
        .header-right { display: flex; align-items: center; gap: 8px; }
        .badge-folleto {
            display: inline-flex; align-items: center;
            background: linear-gradient(135deg, rgba(232,89,12,0.25) 0%, rgba(232,89,12,0.12) 100%);
            border: 1px solid rgba(232,89,12,0.45); color: #f0a06b;
            padding: 6px 12px; border-radius: 100px;
            font-size: 9px; font-weight: 800; letter-spacing: 1.4px;
        }

        .badge-origen { display: inline-block; padding: 2px 9px; border-radius: 6px; font-size: 9px; font-weight: 800; letter-spacing: 0.6px; vertical-align: middle; margin-left: 8px; }
        .badge-origen.de { background: #1A306D; color: #c7d4f5; border: 1px solid rgba(143,163,217,0.4); }
        .badge-origen.es { background: rgba(16,185,129,0.15); color: #34d399; border: 1px solid rgba(16,185,129,0.4); }

        .badge-dgt {
            display: inline-block; background: #1a5fb4; color: #fff; font-weight: 800;
            padding: 2px 8px; border-radius: 4px; font-size: 11px; margin-left: 8px; vertical-align: middle;
        }

        /* ── PORTADA / HERO ─────────────────────────────────────── */
        .hero { margin-bottom: 14px; }
        .hero-eyebrow {
            display: inline-flex; align-items: center; gap: 8px;
            background: rgba(26, 48, 109, 0.15); border: 1px solid rgba(143, 163, 217, 0.3);
            color: #8fa3d9; padding: 5px 14px; border-radius: 100px;
            font-size: 9.5px; font-weight: 600; letter-spacing: 1.5px; text-transform: uppercase;
            margin-bottom: 8px;
        }
        .h1-title { font-size: 30px; font-weight: 900; line-height: 1.1; color: #fff; letter-spacing: -0.5px; }
        .h1-title .accent { color: #8fa3d9; }
        .claim { font-size: 13px; color: #94a3b8; margin-top: 6px; }

        /* ── FOTO GRANDE ───────────────────────────────────────── */
        .hero-photo {
            position: relative; border-radius: 16px; overflow: hidden;
            border: 1px solid rgba(143,163,217,0.3); background: #14265a;
            aspect-ratio: 16/9; margin-bottom: 14px;
            box-shadow: 0 12px 32px rgba(0,0,0,0.35);
        }
        .hero-photo img { width: 100%; height: 100%; object-fit: cover; display: block; }
        .hero-photo .photo-overlay {
            position: absolute; inset: 0;
            background: linear-gradient(180deg, transparent 40%, rgba(15,29,66,0.85) 100%);
        }
        .hero-photo .price-float {
            position: absolute; right: 16px; bottom: 14px; text-align: right;
        }
        .hero-photo .price-float .label { font-size: 9px; color: #c7d4f5; text-transform: uppercase; letter-spacing: 1px; font-weight: 700; }
        .hero-photo .price-float .value { font-size: 30px; font-weight: 900; color: #E8590C; line-height: 1.05; text-shadow: 0 2px 10px rgba(0,0,0,0.6); }
        .hero-photo .price-float .caption { font-size: 9.5px; color: #cbd5e1; }

        /* ── GALERÍA (adaptativa, hasta 5 fotos, todas iguales) ──── */
        .gallery { display: grid; grid-template-columns: repeat(4, 1fr); gap: 8px; margin-bottom: 14px; }
        .gallery .shot {
            border-radius: 10px; overflow: hidden; border: 1px solid rgba(143,163,217,0.25);
            background: #14265a; aspect-ratio: 4/3;
        }
        .gallery .shot img { width: 100%; height: 100%; object-fit: cover; display: block; }
        /* 2 fotos en grid → una ancha a lo ancho */
        .gallery.one .shot { grid-column: span 4; }
        /* 3 fotos → dos medianas lado a lado */
        .gallery.two .shot { grid-column: span 2; }
        /* 4-5 fotos → las 4 del grid son IGUALES (solo la hero es grande) */

        /* ── KPI GRID (4 protagonistas) ─────────────────────────── */
        .kpi-grid { display: grid; grid-template-columns: repeat(4, 1fr); gap: 8px; margin-bottom: 14px; }
        .kpi-card {
            background: linear-gradient(180deg, rgba(15,23,42,0.85) 0%, rgba(15,23,42,0.55) 100%);
            border: 1px solid rgba(143,163,217,0.22); border-radius: 12px; padding: 12px 14px; text-align: center;
        }
        .kpi-card .k { font-size: 8px; color: #64748b; text-transform: uppercase; letter-spacing: 0.9px; font-weight: 600; }
        .kpi-card .v { font-size: 16px; font-weight: 800; color: #f1f5f9; margin-top: 2px; }
        .kpi-card .v.accent { color: #4ade80; }
        .kpi-card .s { font-size: 8px; color: #64748b; margin-top: 1px; }

        /* ── VEREDICTO ─────────────────────────────────────────── */
        .verdict-band {
            display: flex; align-items: center; gap: 10px;
            background: rgba(26,48,109,0.2); border: 1px solid rgba(143,163,217,0.25);
            border-left: 4px solid {{ $tl_color }};
            border-radius: 10px; padding: 10px 16px; margin-bottom: 14px;
        }
        .verdict-dot { width: 12px; height: 12px; border-radius: 50%; background: {{ $tl_color }}; box-shadow: 0 0 12px {{ $tl_color }}; flex-shrink: 0; }
        .verdict-label { font-size: 11px; font-weight: 800; color: #f1f5f9; letter-spacing: 0.3px; }
        .verdict-sub { font-size: 10px; color: #94a3b8; margin-left: auto; text-align: right; }

        /* ── HIGHLIGHTS ────────────────────────────────────────── */
        .section { margin-bottom: 14px; }
        .h2 {
            color: #9fb4e8; font-size: 11px; font-weight: 800; letter-spacing: 1.6px; text-transform: uppercase;
            padding-bottom: 6px; border-bottom: 1px solid rgba(143,163,217,0.15); margin-bottom: 8px;
            display: flex; align-items: center; gap: 8px;
        }
        .h2::before { content: ''; width: 4px; height: 13px; border-radius: 2px; background: linear-gradient(180deg, #E8590C, #f07c3a); }
        .list { list-style: none; }
        .list li {
            display: flex; align-items: flex-start; gap: 8px;
            font-size: 11.5px; color: #cbd5e1; line-height: 1.5; padding: 4px 0;
        }
        .list li::before { content: ''; width: 6px; height: 6px; border-radius: 50%; background: #8fa3d9; margin-top: 5px; flex-shrink: 0; }
        .list li strong { color: #f1f5f9; }

        /* ── SPECS COMPLETAS (grid clave-valor) ────────────────── */
        .specs-grid {
            display: grid; grid-template-columns: repeat(3, 1fr); gap: 6px 18px;
            background: rgba(15,23,42,0.5); border: 1px solid rgba(143,163,217,0.18);
            border-radius: 12px; padding: 14px 16px; margin-bottom: 16px;
        }
        .spec-row { display: flex; align-items: baseline; justify-content: space-between; gap: 8px; padding: 3px 0; border-bottom: 1px dashed rgba(143,163,217,0.12); }
        .spec-row .k { font-size: 9px; color: #64748b; text-transform: uppercase; letter-spacing: 0.6px; font-weight: 600; flex-shrink: 0; }
        .spec-row .v { font-size: 12px; color: #e2e8f0; font-weight: 600; text-align: right; }

        /* ── VALORACIÓN (razonamiento + pros/cons) ──────────────── */
        .reasoning-card {
            background: linear-gradient(135deg, rgba(26,48,109,0.3) 0%, rgba(15,23,42,0.5) 100%);
            border: 1px solid rgba(143,163,217,0.25); border-left: 4px solid {{ $tl_color }};
            border-radius: 12px; padding: 14px 16px; margin-bottom: 12px;
        }
        .reasoning-card .rt { font-size: 11px; font-weight: 800; color: #f1f5f9; }
        .reasoning-card .rd { font-size: 11.5px; color: #cbd5e1; line-height: 1.55; margin-top: 4px; }

        .pc-grid { display: grid; grid-template-columns: repeat(2, 1fr); gap: 8px; margin-bottom: 12px; }
        .pc-col { border-radius: 12px; padding: 12px 14px; }
        .pc-col .pt { font-size: 9px; font-weight: 800; letter-spacing: 1px; text-transform: uppercase; margin-bottom: 6px; }
        .pc-col.pros { background: rgba(16,185,129,0.1); border: 1px solid rgba(16,185,129,0.3); }
        .pc-col.pros .pt { color: #4ade80; }
        .pc-col.cons { background: rgba(239,68,68,0.08); border: 1px solid rgba(239,68,68,0.28); }
        .pc-col.cons .pt { color: #f87171; }
        .pc-item { display: flex; align-items: flex-start; gap: 8px; font-size: 11px; color: #e2e8f0; line-height: 1.4; padding: 3px 0; }
        .pc-item::before { font-weight: 800; flex-shrink: 0; }
        .pc-col.pros .pc-item::before { content: '✓'; color: #4ade80; }
        .pc-col.cons .pc-item::before { content: '✕'; color: #f87171; }

        .note-card {
            display: flex; align-items: flex-start; gap: 10px;
            background: rgba(232,89,12,0.1); border: 1px solid rgba(232,89,12,0.3);
            border-radius: 12px; padding: 12px 14px; margin-bottom: 16px;
        }
        .note-card .nt { font-size: 11.5px; font-weight: 700; color: #f0a06b; }
        .note-card .nd { font-size: 11px; color: #cbd5e1; line-height: 1.5; margin-top: 2px; }

        /* ── EQUIPAMIENTO (lista con check, 2 col) ─────────────── */
        .equip-grid { display: grid; grid-template-columns: repeat(2, 1fr); gap: 5px 16px; margin-bottom: 16px; }
        .equip-item { display: flex; align-items: flex-start; gap: 8px; font-size: 11px; color: #cbd5e1; line-height: 1.35; padding: 3px 0; }
        .equip-item::before { content: '✓'; color: #4ade80; font-weight: 800; flex-shrink: 0; font-size: 11px; line-height: 1.35; }

        /* ── CTA + QR ──────────────────────────────────────────── */
        .cta-row { display: grid; grid-template-columns: 1.6fr 1fr; gap: 12px; }
        .cta-card {
            background: linear-gradient(135deg, rgba(232,89,12,0.14) 0%, rgba(26,48,109,0.15) 100%);
            border: 1px solid rgba(232,89,12,0.35); border-radius: 14px; padding: 15px 18px;
        }
        .cta-text { font-size: 14px; font-weight: 800; color: #fff; margin-bottom: 4px; }
        .contact { font-size: 11px; color: #cbd5e1; line-height: 1.6; }
        .contact strong { color: #f1f5f9; }

        .qr-card {
            background: linear-gradient(180deg, rgba(15,23,42,0.75) 0%, rgba(15,23,42,0.45) 100%);
            border: 1px solid rgba(143,163,217,0.3); border-radius: 14px; padding: 12px;
            display: flex; align-items: center; gap: 12px;
        }
        .qr-box { flex-shrink: 0; width: 74px; height: 74px; background: #fff; border-radius: 8px; padding: 4px; display: flex; align-items: center; justify-content: center; box-shadow: 0 4px 14px rgba(143,163,217,0.3); }
        .qr-box svg { width: 100%; height: 100%; display: block; }
        .qr-tag { color: #8fa3d9; font-size: 8px; font-weight: 700; letter-spacing: 1.5px; margin-bottom: 3px; text-transform: uppercase; }
        .qr-title { font-size: 12px; font-weight: 800; color: #f8fafc; margin-bottom: 3px; line-height: 1.15; }
        .qr-desc { font-size: 8.5px; color: #94a3b8; line-height: 1.35; }

        .legal {
            margin-top: 14px; padding-top: 10px; border-top: 1px solid rgba(143,163,217,0.15);
            font-size: 8px; color: #64748b; line-height: 1.5; font-style: italic; text-align: center;
        }
    </style>
</head>
<body>
    <div class="container">

        <div class="header">
            <img src="{{ $logo_base64 }}" alt="JJ Import Motors" class="logo">
            <div class="header-right">
                <span class="badge-folleto">FOLLETO DEL COCHE</span>
                <span class="badge-llave">SERVICIO LLAVE EN MANO</span>
            </div>
        </div>

        <div class="hero">
            <span class="hero-eyebrow">Importación Premium · {{ $origen === 'de' ? 'Alemania' : ($origen === 'es' ? 'España' : 'Europa') }} → España</span>
            <h1 class="h1-title">
                {{ $titulo }}
                @if($e->uno('ETIQUETA_DGT'))<span class="badge-dgt">{{ $e->uno('ETIQUETA_DGT') }}</span>@endif
            </h1>
            @if($claim)<p class="claim">{{ $claim }}</p>@endif
        </div>

        {{-- ── FOTO PROTAGONISTA + PRECIO ─────────────────────────── --}}
        @if(!empty($fotos[0]))
        <div class="hero-photo">
            <img src="{{ $fotos[0] }}" alt="{{ $titulo }}">
            <div class="photo-overlay"></div>
            <div class="price-float">
                @if($kpi_precio)
                    <div class="label">Precio final llave en mano</div>
                    <div class="value">{{ $kpi_precio }}</div>
                @endif
                @if($kpi_ahorro)
                    <div class="caption">Ahorro {{ $kpi_ahorro }} vs. mercado español</div>
                @endif
            </div>
        </div>
        @endif

        {{-- ── GALERÍA (hasta 5 fotos: 1 hero + 4 grid) ─────────── --}}
        @php
            $grid_fotos = array_slice($fotos, 1, 4);
            $grid_count = count($grid_fotos);
            $gallery_class = $grid_count === 1 ? 'one' : ($grid_count === 2 ? 'two' : ($grid_count === 3 ? 'three' : 'four'));
        @endphp
        @if($grid_count > 0)
        <div class="gallery {{ $gallery_class }}">
            @foreach($grid_fotos as $foto)
                <div class="shot"><img src="{{ $foto }}" alt="{{ $titulo }}"></div>
            @endforeach
        </div>
        @endif

        {{-- ── KPI GRID (4 protagonistas, sin duplicar ficha) ────── --}}
        <div class="kpi-grid">
            @if($kpi_precio)
                <div class="kpi-card">
                    <div class="k">Precio final</div>
                    <div class="v"><span style="color:#E8590C;">{{ $kpi_precio }}</span></div>
                    <div class="s">Llave en mano</div>
                </div>
            @endif
            @if($kpi_ahorro)
                <div class="kpi-card">
                    <div class="k">Ahorro</div>
                    <div class="v" style="color:#4ade80;">{{ $kpi_ahorro }}</div>
                    <div class="s">vs. mercado español</div>
                </div>
            @endif
            @if($kpi_km)
                <div class="kpi-card">
                    <div class="k">Kilómetros</div>
                    <div class="v">{{ $kpi_km }}</div>
                    <div class="s">Odómetro verificado</div>
                </div>
            @endif
            @if($kpi_anio)
                <div class="kpi-card">
                    <div class="k">Año</div>
                    <div class="v">{{ $kpi_anio }}</div>
                    <div class="s">Primera matriculación</div>
                </div>
            @endif
        </div>

        {{-- ── VEREDICTO ──────────────────────────────────────────── --}}
        <div class="verdict-band">
            <span class="verdict-dot"></span>
            <span class="verdict-label">Veredicto: {{ $tl_label }}</span>
            <span class="verdict-sub">Verificado por JJ Import Motors</span>
        </div>

        {{-- ── SPECS COMPLETAS (sin repetir Año/KM del KPI) ───────── --}}
        @if(count($specs_table))
        <div class="section">
            <div class="h2">Ficha técnica</div>
            <div class="specs-grid">
                @foreach($specs_table as $spec)
                    <div class="spec-row"><span class="k">{{ $spec['k'] }}</span><span class="v">{{ $spec['v'] }}</span></div>
                @endforeach
            </div>
        </div>
        @endif

        {{-- ── VALORACIÓN (para el cliente, sin datos internos) ───── --}}
        @if($val_sales_text || count($val_pros) || count($val_cons))
        <div class="section">
            <div class="h2">Nuestra valoración</div>

            @if($val_sales_text)
                <div class="reasoning-card">
                    <div class="rt">Por qué este coche</div>
                    <div class="rd">{{ $val_sales_text }}</div>
                </div>
            @endif

            @if(count($val_pros) || count($val_cons))
            <div class="pc-grid">
                @if(count($val_pros))
                <div class="pc-col pros">
                    <div class="pt">A favor</div>
                    @foreach($val_pros as $pro)
                        <div class="pc-item">{{ $pro }}</div>
                    @endforeach
                </div>
                @endif
                @if(count($val_cons))
                <div class="pc-col cons">
                    <div class="pt">En contra</div>
                    @foreach($val_cons as $con)
                        <div class="pc-item">{{ $con }}</div>
                    @endforeach
                </div>
                @endif
            </div>
            @endif
        </div>
        @endif

        {{-- ── EQUIPAMIENTO (lista con check) ─────────────────────── --}}
        @if(count($e->filas('EQUIPAMIENTO')))
        <div class="section">
            <div class="h2">Equipamiento</div>
            <div class="equip-grid">
                @foreach($e->filas('EQUIPAMIENTO') as $eq)
                    <div class="equip-item">{{ $eq[0] ?? '' }}</div>
                @endforeach
            </div>
        </div>
        @endif

        {{-- ── CTA + QR ───────────────────────────────────────────── --}}
        <div class="cta-row">
            <div class="cta-card">
                <div class="cta-text">¿Te interesa este coche?</div>
                <div class="contact">
                    Escríbenos y lo gestionamos todo: importación, ITV, matriculación.<br>
                    <strong>Tel:</strong> {{ $telefono_1 }} · {{ $telefono_2 }}<br>
                    <strong>Email:</strong> {{ $email }}
                </div>
            </div>
            <div class="qr-card">
                <div class="qr-box">{!! $qr_svg !!}</div>
                <div>
                    <div class="qr-tag">Escanea</div>
                    <div class="qr-title">Solicita tu vehículo</div>
                    <div class="qr-desc">Cuéntanos qué buscas y te lo buscamos</div>
                </div>
            </div>
        </div>

        <div class="legal">
            JJ Import Motors · Servicio de búsqueda e importación de vehículos desde Alemania y España.
            El vehículo se adquiere a nombre del cliente; nosotros gestionamos todo el proceso.
        </div>

    </div>
</body>
</html>
