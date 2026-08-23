{{-- ═══════════════════════════════════════════════════════════════════════
    FICHA DEL COCHE (PDF) — TIPO: marketing / venta al cliente
    ─────────────────────────────────────────────────────────────────────────
    · QUIÉN LO GENERA  : Laravel (Blade + Browsershot)
    · DESDE QUÉ ARCHIVO: contenido/ficha-publicitaria.txt  (esqueleto [MARCADOR])
    · RUTA             : GET /cars/{car}/ficha  (autenticado)
    · CONTROLADOR      : PaqueteValoracionController@ficha
    · AUDIENCIA        : CLIENTE final. NO mostrar margen ni honorarios.
    · Bloques que renderiza: TITULO, CLAIM, ETIQUETA_DGT, SPEC, PRECIO,
      PRECIO_CAPTION, PLAZO, PRECIO_NOTA, H2+INCLUYE/ARGUMENTO/EQUIPAMIENTO,
      CTA, CONTACTO, QR, QR_TEXTO, LEGAL, FOTOS.
    ═══════════════════════════════════════════════════════════════════════ --}}
@php
    $telefono_1 = $telefono_1 ?? '675 70 14 39';
    $telefono_2 = $telefono_2 ?? '691 48 59 27';
    $email = $email ?? 'jjimportmotors@gmail.com';
    $qr_url = $e->uno('QR') ?? route('public.car-request.index', ['slug' => 'jj-import-motors']);

    $qr_svg = $qr_svg ?? null;
    if (!$qr_svg) {
        try {
            $qr_svg = \SimpleSoftwareIO\QrCode\Generator::class
                ? (new \SimpleSoftwareIO\QrCode\Generator())->format('svg')
                    ->size(220)
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

    // ── KPI: KM y Año se derivan de la ficha técnica (SPEC Etiqueta | Valor) ──
    $specs = $e->filas('SPEC');
    $spec_val = function ($needle) use ($specs) {
        foreach ($specs as $s) {
            if (stripos((string) ($s[0] ?? ''), $needle) !== false) {
                return $s[1] ?? null;
            }
        }
        return null;
    };
    $kpi_km   = $spec_val('KM') ?? $spec_val('Kilómetros');
    $kpi_anio = $spec_val('Año');
    $kpi_precio = $e->uno('PRECIO');
    $kpi_ahorro = $e->uno('AHORRO');

    // ── Origen DE/ES (badge) — desde el país de origen del coche ──
    $pais = strtolower((string) ($car->pais_origen ?? ''));
    $origen = (str_contains($pais, 'alem') || $pais === 'de') ? 'de'
        : ((str_contains($pais, 'espa') || $pais === 'es') ? 'es' : null);

    // ── Contenido de IA del modelo Car (enriquece la ficha aunque el
    //    esqueleto .txt no traiga esas secciones). Nunca margen/honorarios. ──
    $ia_desc   = trim((string) ($e->uno('DESCRIPCION') ?: ($car->description ?? '')));
    $ia_val    = trim((string) ($e->uno('VALORACION') ?: ($car->valuation ?? '')));
    $ia_porque = trim((string) ($e->uno('POR_QUE') ?: ($car->recommendation ?? '')));
    $ia_pros   = is_array($car->pros ?? null) ? array_filter(array_map('trim', (array) $car->pros)) : [];
    $ia_cons   = is_array($car->cons ?? null) ? array_filter(array_map('trim', (array) $car->cons)) : [];
    $ia_tips   = is_array($car->tips ?? null) ? array_filter(array_map('trim', (array) $car->tips)) : [];
    $ia_redfl  = is_array($car->red_flags ?? null) ? array_filter(array_map('trim', (array) $car->red_flags)) : [];

    // Comparativa de mercado (€ formateado en español).
    $fmt = fn ($n) => $n !== null ? number_format((float) $n, 0, ',', '.') : null;
    $ia_market_min  = $fmt($car->market_min ?? null);
    $ia_market_avg  = $fmt($car->market_avg ?? null);
    $ia_market_max  = $fmt($car->market_max ?? null);
    $ia_ahorro      = $fmt($car->estimated_saving ?? null);
    $ia_mercado     = ($ia_market_min || $ia_market_max || $ia_market_avg);

    // Veredicto del experto presentable al cliente (green/amber/red/neutral).
    $tl = strtolower((string) ($car->traffic_light ?? ''));
    $veredicto_cliente = ['green' => 'Excelente compra', 'amber' => 'Buena opción', 'red' => 'Con cautela', 'neutral' => 'Sin veredicto'][$tl] ?? null;
    $veredicto_color = ['green' => '#10b981', 'amber' => '#f59e0b', 'red' => '#ef4444', 'neutral' => '#94a3b8'][$tl] ?? null;
@endphp

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>JJ Import Motors - {{ $e->uno('TITULO') }}</title>
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800;900&display=swap');
        @page { size: A4; margin: 0; }
        * { margin: 0; padding: 0; box-sizing: border-box; }

        html, body {
            font-family: 'Inter', 'Segoe UI', Arial, sans-serif;
            background: #0f1d42;
            color: #e5e7eb;
            -webkit-font-smoothing: antialiased;
            -webkit-print-color-adjust: exact;
            print-color-adjust: exact;
        }

        body {
            position: relative;
            padding: 30px 34px 60px 34px;
            background:
                radial-gradient(ellipse at 100% 0%, rgba(143, 163, 217, 0.12) 0%, transparent 45%),
                linear-gradient(180deg, #0f1d42 0%, #14265a 50%, #0f1d42 100%);
            /* Repite el degradado en CADA página impresa (Chrome/headless):
               sin esto, el 50% cae a mitad del documento y la última página
               sale con otro tono. */
            background-attachment: fixed;
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

        .container { position: relative; z-index: 1; max-width: 1060px; margin: 0 auto; }

        /* Evita que las secciones se partan a mitad de página */
        .kpi-grid, .specs-strip, .price-band, .section, .cta-row, .legal,
        .verdict-card, .proscons, .mercado-card, .tips-card, .ia-porque {
            break-inside: avoid;
            page-break-inside: avoid;
        }

        .header {
            display: flex; justify-content: space-between; align-items: center;
            padding-bottom: 16px; border-bottom: 1px solid rgba(143, 163, 217, 0.2);
            margin-bottom: 22px;
        }
        .logo { height: 46px; width: auto; }
        .badge-llave {
            display: inline-flex; align-items: center; gap: 7px;
            background: linear-gradient(135deg, #1A306D 0%, #2a3d87 100%);
            color: #fff; padding: 8px 16px; border-radius: 100px;
            font-size: 10px; font-weight: 700; letter-spacing: 1.3px;
            box-shadow: 0 4px 14px rgba(26, 48, 109, 0.4);
        }

        .badge-pick { display: inline-block; background: rgba(232,89,12,0.2); color: #E8590C; border: 1px solid rgba(232,89,12,0.4); font-size: 7.5px; font-weight: 800; letter-spacing: 1px; padding: 1px 6px; border-radius: 100px; margin-left: 6px; }
        .badge-pick.picked { background: rgba(232,89,12,0.3); border-color: #E8590C; color: #E8590C; }

        .badge-origen { display: inline-block; padding: 2px 9px; border-radius: 6px; font-size: 9px; font-weight: 800; letter-spacing: 0.6px; vertical-align: middle; margin-left: 8px; }
        .badge-origen.de { background: #1A306D; color: #c7d4f5; border: 1px solid rgba(143,163,217,0.4); }
        .badge-origen.es { background: rgba(16,185,129,0.15); color: #34d399; border: 1px solid rgba(16,185,129,0.4); }

        .hero { text-align: center; margin-bottom: 20px; }
        .hero-eyebrow {
            display: inline-flex; align-items: center; gap: 8px;
            background: rgba(26, 48, 109, 0.15); border: 1px solid rgba(143, 163, 217, 0.3);
            color: #8fa3d9; padding: 5px 15px; border-radius: 100px;
            font-size: 9.5px; font-weight: 600; letter-spacing: 1.5px; text-transform: uppercase;
            margin-bottom: 10px;
        }
        .h1-title { font-size: 28px; font-weight: 800; line-height: 1.12; color: #fff; letter-spacing: -0.5px; }
        .h1-title .accent { color: #8fa3d9; }
        .claim { font-size: 13px; color: #94a3b8; margin-top: 8px; }

        /* ── KPI GRID ──────────────────────────────────────── */
        @if($kpi_precio || $kpi_ahorro || $kpi_km || $kpi_anio)
        <div class="kpi-grid">
            @if($kpi_precio)
                <div class="kpi-card">
                    <div class="k">Precio final</div>
                    <div class="v"><span class="accent">{{ $kpi_precio }}</span></div>
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
        @endif

        /* Galería */
        .gallery { display: grid; grid-template-columns: repeat(3, 1fr); gap: 8px; margin-bottom: 18px; }
        .gallery .shot {
            border-radius: 10px; overflow: hidden; border: 1px solid rgba(143, 163, 217, 0.2);
            background: #14265a; aspect-ratio: 4/3;
        }
        .gallery .shot img { width: 100%; height: 100%; object-fit: cover; }
        .gallery .shot:first-child { grid-column: span 2; grid-row: span 2; }

        /* Specs strip */
        .specs-strip { display: grid; grid-template-columns: repeat(4, 1fr); gap: 8px; margin-bottom: 18px; }
        .spec-box {
            background: linear-gradient(180deg, rgba(15,23,42,0.8) 0%, rgba(15,23,42,0.5) 100%);
            border: 1px solid rgba(143,163,217,0.2); border-radius: 10px; padding: 10px 12px; text-align: center;
        }
        .spec-box .k { font-size: 8px; color: #64748b; text-transform: uppercase; letter-spacing: 0.8px; font-weight: 600; }
        .spec-box .v { font-size: 14px; font-weight: 800; color: #f1f5f9; margin-top: 2px; }

        /* Price */
        .price-band {
            display: flex; align-items: center; justify-content: space-between; gap: 14px;
            background: linear-gradient(135deg, rgba(232,89,12,0.18) 0%, rgba(26,48,109,0.15) 100%);
            border: 1px solid rgba(232,89,12,0.4); border-radius: 14px; padding: 16px 22px; margin-bottom: 18px;
        }
        .price-main .label { font-size: 9px; color: #8fa3d9; text-transform: uppercase; letter-spacing: 1px; font-weight: 700; }
        .price-main .value { font-size: 30px; font-weight: 900; color: #E8590C; line-height: 1.1; }
        .price-main .caption { font-size: 10.5px; color: #cbd5e1; margin-top: 2px; }
        .price-side { text-align: right; font-size: 10px; color: #94a3b8; line-height: 1.5; }

        /* Sections */
        .section { margin-bottom: 16px; }
        .h2 {
            color: #9fb4e8; font-size: 12px; font-weight: 800; letter-spacing: 1.6px; text-transform: uppercase;
            padding-bottom: 6px; border-bottom: 1px solid rgba(143,163,217,0.15); margin-bottom: 10px;
            display: flex; align-items: center; gap: 8px;
        }
        .h2::before { content: ''; width: 4px; height: 14px; border-radius: 2px; background: linear-gradient(180deg, #E8590C, #f07c3a); }

        /* ── BADGE ORIGEN (DE/ES) CON PICK ──────────────────── */
        .badge-pick { display: inline-block; background: rgba(232,89,12,0.2); color: #E8590C; border: 1px solid rgba(232,89,12,0.4); font-size: 7.5px; font-weight: 800; letter-spacing: 1px; padding: 1px 6px; border-radius: 100px; margin-left: 6px; }
        .badge-pick.picked { background: rgba(232,89,12,0.3); border-color: #E8590C; color: #E8590C; }

        /* ── BADGE ORIGEN (DE/ES) ──────────────────────────── */
        .badge-origen { display: inline-block; padding: 2px 9px; border-radius: 6px; font-size: 9px; font-weight: 800; letter-spacing: 0.6px; vertical-align: middle; margin-left: 8px; }
        .badge-origen.de { background: #1A306D; color: #c7d4f5; border: 1px solid rgba(143,163,217,0.4); }
        .badge-origen.es { background: rgba(16,185,129,0.15); color: #34d399; border: 1px solid rgba(16,185,129,0.4); }
        .list { list-style: none; }
        .list li {
            display: flex; align-items: flex-start; gap: 8px;
            font-size: 11.5px; color: #cbd5e1; line-height: 1.5; padding: 4px 0;
        }
        .list li::before { content: ''; width: 6px; height: 6px; border-radius: 50%; background: #8fa3d9; margin-top: 5px; flex-shrink: 0; }
        .list li strong { color: #f1f5f9; }

        .equip { display: flex; flex-wrap: wrap; gap: 6px; }
        .chip {
            background: rgba(26,48,109,0.25); border: 1px solid rgba(143,163,217,0.25);
            color: #cbd5e1; padding: 4px 10px; border-radius: 100px; font-size: 9.5px; font-weight: 500;
        }

        /* CTA + QR */
        .cta-row { display: grid; grid-template-columns: 1.6fr 1fr; gap: 14px; margin-top: 4px; }
        .cta-card {
            background: linear-gradient(135deg, rgba(232,89,12,0.12) 0%, rgba(26,48,109,0.15) 100%);
            border: 1px solid rgba(232,89,12,0.35); border-radius: 14px; padding: 16px 20px;
        }
        .cta-text { font-size: 14px; font-weight: 800; color: #fff; margin-bottom: 4px; }
        .contact { font-size: 11px; color: #cbd5e1; line-height: 1.6; }

        .qr-card {
            background: linear-gradient(180deg, rgba(15,23,42,0.75) 0%, rgba(15,23,42,0.45) 100%);
            border: 1px solid rgba(143,163,217,0.3); border-radius: 14px; padding: 12px;
            display: flex; align-items: center; gap: 12px;
        }
        .qr-box { flex-shrink: 0; width: 82px; height: 82px; background: #fff; border-radius: 8px; padding: 4px; display: flex; align-items: center; justify-content: center; box-shadow: 0 4px 14px rgba(143,163,217,0.3); }
        .qr-box svg { width: 100%; height: 100%; display: block; }
        .qr-tag { color: #8fa3d9; font-size: 8px; font-weight: 700; letter-spacing: 1.5px; margin-bottom: 3px; text-transform: uppercase; }
        .qr-title { font-size: 12px; font-weight: 800; color: #f8fafc; margin-bottom: 3px; line-height: 1.15; }
        .qr-desc { font-size: 8.5px; color: #94a3b8; line-height: 1.35; }

        .legal {
            margin-top: 16px; padding-top: 10px; border-top: 1px solid rgba(143,163,217,0.15);
            font-size: 8px; color: #64748b; line-height: 1.5; font-style: italic; text-align: center;
        }
        .badge-dgt {
            display: inline-block; background: #1a5fb4; color: #fff; font-weight: 800;
            padding: 2px 8px; border-radius: 4px; font-size: 11px; margin-left: 8px; vertical-align: middle;
        }

        /* ── SECCIONES IA (enriquecen la ficha desde el modelo Car) ── */
        .ia-desc { font-size: 11px; color: #cbd5e1; line-height: 1.6; text-align: justify; }

        .verdict-card {
            display: flex; align-items: center; gap: 12px;
            background: linear-gradient(135deg, rgba(16,185,129,0.10) 0%, rgba(26,48,109,0.12) 100%);
            border: 1px solid rgba(143,163,217,0.25); border-radius: 12px; padding: 12px 16px; margin-bottom: 16px;
        }
        .verdict-dot { width: 10px; height: 10px; border-radius: 50%; flex-shrink: 0; }
        .verdict-t { font-size: 10px; color: #8fa3d9; text-transform: uppercase; letter-spacing: 1px; font-weight: 700; }
        .verdict-v { font-size: 15px; font-weight: 800; color: #f8fafc; }

        .proscons { display: grid; grid-template-columns: 1fr 1fr; gap: 10px; margin-bottom: 16px; }
        .pc-col { border-radius: 12px; padding: 12px 14px; }
        .pc-col.pros { background: rgba(16,185,129,0.07); border: 1px solid rgba(16,185,129,0.25); }
        .pc-col.cons { background: rgba(239,68,68,0.06); border: 1px solid rgba(239,68,68,0.22); }
        .pc-title { font-size: 10px; font-weight: 800; text-transform: uppercase; letter-spacing: 1px; margin-bottom: 6px; }
        .pc-col.pros .pc-title { color: #34d399; }
        .pc-col.cons .pc-title { color: #f87171; }
        .pc-list { list-style: none; }
        .pc-list li { font-size: 10px; color: #cbd5e1; line-height: 1.5; padding: 3px 0; display: flex; gap: 6px; }
        .pc-list li::before { content: '✓'; color: #34d399; flex-shrink: 0; }
        .pc-col.cons .pc-list li::before { content: '✗'; color: #f87171; }

        .mercado-card {
            display: grid; grid-template-columns: repeat(3, 1fr) auto; gap: 10px; align-items: center;
            background: rgba(15,23,42,0.6); border: 1px solid rgba(143,163,217,0.25);
            border-radius: 12px; padding: 12px 16px; margin-bottom: 16px;
        }
        .mercado-card .m-label { font-size: 8px; color: #64748b; text-transform: uppercase; letter-spacing: 0.8px; font-weight: 700; }
        .mercado-card .m-val { font-size: 15px; font-weight: 800; color: #f1f5f9; }
        .mercado-card .m-ahorro { background: rgba(16,185,129,0.12); border: 1px solid rgba(16,185,129,0.3); border-radius: 10px; padding: 8px 12px; text-align: center; }
        .mercado-card .m-ahorro .m-val { color: #4ade80; }

        .tips-card { background: rgba(26,48,109,0.2); border: 1px solid rgba(143,163,217,0.2); border-radius: 12px; padding: 12px 16px; margin-bottom: 16px; }
        .tips-card ul { list-style: none; }
        .tips-card li { font-size: 10px; color: #cbd5e1; line-height: 1.5; padding: 3px 0; display: flex; gap: 6px; }
        .tips-card li::before { content: '💡'; flex-shrink: 0; }
    </style>
</head>
<body>
    <div class="container">

        <div class="header">
            <img src="{{ $logo_base64 }}" alt="JJ Import Motors" class="logo">
            <span class="badge-llave">SERVICIO LLAVE EN MANO</span>
        </div>

        <div class="hero">
            <span class="hero-eyebrow">Importación Premium · Alemania → España</span>
            <h1 class="h1-title">{{ $e->uno('TITULO') }}@if($e->uno('ETIQUETA_DGT'))<span class="badge-dgt">{{ $e->uno('ETIQUETA_DGT') }}</span>@endif</h1>
            @if($origen)
                <p class="claim" style="margin-top:8px;">
                    <span class="badge-origen {{ $origen }}">Origen: {{ $origen === 'de' ? 'Alemania' : 'España' }}</span>
                </p>
            @endif
            @if($e->uno('CLAIM'))
                <p class="claim">{{ $e->uno('CLAIM') }}</p>
            @endif
        </div>

        {{-- ── KPI CARDS ─────────────────────────────────────────────── --}}
        @if($kpi_precio || $kpi_ahorro || $kpi_km || $kpi_anio)
        <div class="kpi-grid">
            @if($kpi_precio)
                <div class="kpi-card">
                    <div class="k">Precio final</div>
                    <div class="v"><span class="accent">{{ $kpi_precio }}</span></div>
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
        @endif

        @if(!empty($fotos))
        <div class="gallery">
            @foreach(array_slice($fotos, 0, 6) as $foto)
                <div class="shot">
                    <img src="{{ $foto }}" alt="Foto">
                </div>
            @endforeach
        </div>
        @endif

        @if(count($e->filas('SPEC')) > 0)
        <div class="specs-strip">
            @foreach($e->filas('SPEC') as [$etiqueta, $valor])
                <div class="spec-box">
                    <div class="k">{{ $etiqueta }}</div>
                    <div class="v">{{ $valor }}</div>
                </div>
            @endforeach
        </div>
        @endif

        @if($e->uno('PRECIO'))
        <div class="price-band">
            <div class="price-main">
                <div class="label">Precio final</div>
                <div class="value">{{ $e->uno('PRECIO') }}</div>
                @if($e->uno('PRECIO_CAPTION'))
                    <div class="caption">{{ $e->uno('PRECIO_CAPTION') }}</div>
                @endif
            </div>
            <div class="price-side">
                @if($e->uno('PLAZO'))<div>⏱ {{ $e->uno('PLAZO') }}</div>@endif
                @if($e->uno('PRECIO_NOTA'))<div>{{ $e->uno('PRECIO_NOTA') }}</div>@endif
            </div>
        </div>
        @endif

        @php $secciones = []; $actual = null; @endphp
        @foreach($e->orden as $bloque)
            @if($bloque['nombre'] === 'H2')
                @php
                    $secciones[$bloque['texto']] = [];
                    $actual = $bloque['texto'];
                @endphp
            @elseif($actual && in_array($bloque['nombre'], ['INCLUYE', 'ARGUMENTO', 'EQUIPAMIENTO']))
                @php $secciones[$actual][$bloque['nombre']][] = $bloque['texto']; @endphp
            @endif
        @endforeach

        @foreach($secciones as $titulo => $bloques)
            @if(!empty($bloques))
            <div class="section">
                <div class="h2">{{ $titulo }}</div>

                @if(isset($bloques['INCLUYE']))
                    <ul class="list">
                        @foreach($bloques['INCLUYE'] as $item)
                            <li>{{ $item }}</li>
                        @endforeach
                    </ul>
                @endif

                @if(isset($bloques['ARGUMENTO']))
                    <ul class="list">
                        @foreach($bloques['ARGUMENTO'] as $punto)
                            <li>{!! \App\Support\Esqueleto::negrita($punto) !!}</li>
                        @endforeach
                    </ul>
                @endif

                @if(isset($bloques['EQUIPAMIENTO']))
                    <div class="equip">
                        @foreach($bloques['EQUIPAMIENTO'] as $item)
                            <span class="chip">{{ $item }}</span>
                        @endforeach
                    </div>
                @endif
            </div>
            @endif
        @endforeach

        {{-- ═══ SECCIONES DE IA (enriquecen la ficha desde el modelo Car) ═══ --}}

        @if($ia_desc)
            <div class="section">
                <div class="h2">Descripción</div>
                <p class="ia-desc">{{ $ia_desc }}</p>
            </div>
        @endif

        @if($veredicto_cliente)
            <div class="verdict-card">
                <span class="verdict-dot" style="background:{{ $veredicto_color }};"></span>
                <div>
                    <div class="verdict-t">Valoración del experto</div>
                    <div class="verdict-v">{{ $veredicto_cliente }}</div>
                </div>
            </div>
        @endif

        @if($ia_porque)
            <div class="section ia-porque">
                <div class="h2">¿Por qué este coche?</div>
                <p class="ia-desc">{!! \App\Support\Esqueleto::negrita($ia_porque) !!}</p>
            </div>
        @endif

        @if($ia_val)
            <div class="section">
                <div class="h2">Nuestra valoración</div>
                <p class="ia-desc">{!! \App\Support\Esqueleto::negrita($ia_val) !!}</p>
            </div>
        @endif

        @if($ia_pros || $ia_cons)
            <div class="proscons">
                @if($ia_pros)
                    <div class="pc-col pros">
                        <div class="pc-title">A favor</div>
                        <ul class="pc-list">
                            @foreach($ia_pros as $p)<li>{{ $p }}</li>@endforeach
                        </ul>
                    </div>
                @endif
                @if($ia_cons)
                    <div class="pc-col cons">
                        <div class="pc-title">A tener en cuenta</div>
                        <ul class="pc-list">
                            @foreach($ia_cons as $c)<li>{{ $c }}</li>@endforeach
                        </ul>
                    </div>
                @endif
            </div>
        @endif

        @if($ia_mercado)
            <div class="mercado-card">
                <div><div class="m-label">Mínimo mercado</div><div class="m-val">{{ $ia_market_min ?? '—' }}</div></div>
                <div><div class="m-label">Precio medio</div><div class="m-val">{{ $ia_market_avg ?? '—' }}</div></div>
                <div><div class="m-label">Máximo mercado</div><div class="m-val">{{ $ia_market_max ?? '—' }}</div></div>
                @if($ia_ahorro)
                    <div class="m-ahorro"><div class="m-label">Ahorro estimado</div><div class="m-val">€{{ $ia_ahorro }}</div></div>
                @endif
            </div>
        @endif

        @if($ia_tips)
            <div class="tips-card">
                <div class="h2">Consejos del experto</div>
                <ul>
                    @foreach($ia_tips as $tip)<li>{{ $tip }}</li>@endforeach
                </ul>
            </div>
        @endif

        @if($e->uno('CTA'))
        <div class="cta-row">
            <div class="cta-card">
                <div class="cta-text">{{ $e->uno('CTA') }}</div>
                <div class="contact">{{ $e->uno('CONTACTO') }}</div>
            </div>
            <div class="qr-card">
                <div class="qr-box">{!! $qr_svg !!}</div>
                <div>
                    <div class="qr-tag">Escanea</div>
                    <div class="qr-title">{{ $e->uno('QR_TEXTO') ?? '¿Otro vehículo? Escanea aquí' }}</div>
                    <div class="qr-desc">Formulario de solicitud directo desde tu móvil.</div>
                </div>
            </div>
        </div>
        @endif

        @if($e->uno('LEGAL'))
        <p class="legal">{{ $e->uno('LEGAL') }}</p>
        @endif

    </div>
</body>
</html>
