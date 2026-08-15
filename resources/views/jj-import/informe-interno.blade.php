{{-- ═══════════════════════════════════════════════════════════════════════
    INFORME INTERNO (PDF) — TIPO: investigación interna
    ─────────────────────────────────────────────────────────────────────────
    · QUIÉN LO GENERA  : Laravel (Blade + Browsershot)
    · DESDE QUÉ ARCHIVO: contenido/informe-interno.txt  (esqueleto [MARCADOR])
    · RUTA             : GET /cars/{car}/informe-interno (solo owner/operator)
    · CONTROLADOR      : PaqueteValoracionController@interno
    · AUDIENCIA        : JJ Import Motors (EQUIPO). NUNCA al cliente:
                         margen, honorarios desglosados, URLs de comparables.
    · Bloques que renderiza: SCORE_GLOBAL, RECOMENDACION, COBERTURA, SCORE_DIM,
      CAND_*, MARGEN, VENDIBILIDAD_FACTOR, VENTA, RIESGO, BANDERA_ROJA/AMARILLA,
      ACCION, COSTE/TOTAL, A_FAVOR/EN_CONTRA, ASPECTO, COMPARABLE, FUENTE_LISTA,
      CHECK, SEMAFORO, DICTAMEN, CONFIANZA, RESUMEN, PRECIO_OBJETIVO.
    ═══════════════════════════════════════════════════════════════════════ --}}
@php
    // DOCUMENTO INTERNO. No debe servirse por ninguna ruta pública.
    $telefono_1 = $telefono_1 ?? '675 70 14 39';
    $telefono_2 = $telefono_2 ?? '691 48 59 27';
    $email = $email ?? 'jjimportmotors@gmail.com';

    $semaforo = strtolower($e->uno('SEMAFORO') ?? 'gris');
    $semaforo_color = match ($semaforo) {
        'verde', 'green'  => '#10B981',
        'ambar', 'amber'  => '#F59E0B',
        'rojo', 'red'     => '#EF4444',
        default           => '#5A6472',
    };

    // Bloques financieros en orden real (COSTE, TOTAL, COSTE, DESTACADO, MERCADO, AHORRO, NOTA...)
    $financiero = [];
    $balance = ['A_FAVOR' => [], 'EN_CONTRA' => []];
    $auditoria = $e->grupos('ASPECTO');
    $comparables = $e->filas('COMPARABLE');
    $fuentes = $e->filas('FUENTE_LISTA');
    $checks = $e->todos('CHECK');

    foreach ($e->orden as $bloque) {
        $n = $bloque['nombre'];
        if (in_array($n, ['COSTE', 'TOTAL', 'DESTACADO', 'MERCADO', 'AHORRO'], true)) {
            $campos = array_map('trim', explode('|', $bloque['texto']));
            $financiero[] = ['tipo' => $n, 'concepto' => $campos[0] ?? '', 'importe' => $campos[1] ?? ''];
        } elseif ($n === 'NOTA') {
            $campos = array_map('trim', explode('|', $bloque['texto']));
            $financiero[] = ['tipo' => 'NOTA', 'concepto' => $campos[0] ?? 'Nota', 'importe' => $campos[1] ?? ''];
        } elseif ($n === 'A_FAVOR') {
            $campos = array_map('trim', explode('|', $bloque['texto']));
            $balance['A_FAVOR'][] = ['texto' => $campos[0] ?? '', 'peso' => $campos[1] ?? 'medio'];
        } elseif ($n === 'EN_CONTRA') {
            $campos = array_map('trim', explode('|', $bloque['texto']));
            $balance['EN_CONTRA'][] = ['texto' => $campos[0] ?? '', 'peso' => $campos[1] ?? 'medio'];
        }
    }

    $peso = fn ($p) => match (strtolower($p)) {
        'alto', 'alta', '3' => '3', 'medio', 'media', '2' => '2', default => '1',
    };

    $valoracion_clase = fn ($v) => match (strtolower($v)) {
        'favorable' => 'fav', 'desfavorable' => 'des', 'neutro' => 'neu', default => 'sin',
    };

    // ── Datos premium: score, cobertura, dimensiones, candidato, venta, riesgo ──
    $score_global  = $e->uno('SCORE_GLOBAL');
    if ($score_global !== null && str_contains($score_global, '/')) {
        $score_global = explode('/', $score_global)[0];
    }
    $recomendacion = $e->uno('RECOMENDACION') ?? $e->uno('VEREDICTO');
    $veredicto     = $e->uno('VEREDICTO') ?? $recomendacion;

    $cobertura_raw = $e->filas('COBERTURA');      // [fuente, estado, score, detalle]
    $cobertura_ok  = count(array_filter($cobertura_raw, fn ($c) => strtoupper(trim($c[1] ?? '')) === 'OK'));
    $cobertura_total = count($cobertura_raw);
    $cobertura_pct  = $cobertura_total > 0 ? round($cobertura_ok * 100 / $cobertura_total) : 0;
    $cov_clase = fn ($estado) => match (strtoupper(trim((string) $estado))) {
        'OK'                      => 'ok',
        'DEGRADADA', 'FALLBACK', 'PARCIAL' => 'deg',
        default                   => 'omit',
    };

    $score_dims    = $e->filas('SCORE_DIM');      // [nombre, max, score]
    $vendibilidad  = $e->filas('VENDIBILIDAD_FACTOR'); // [nombre, max, score, nota]
    $vend_total    = $e->uno('VENDIBILIDAD_TOTAL');
    $margenes      = $e->filas('MARGEN');         // [concepto, margen€, %, color]
    $ventas        = $e->filas('VENTA');          // [escenario, precio, días, margen€, %]
    $venta_rec     = strtoupper(trim((string) $e->uno('VENTA_RECOMENDADA')));
    $riesgos       = $e->filas('RIESGO');         // [desc, prob, impacto, mitigación]
    $banderas_rojas    = $e->todos('BANDERA_ROJA');
    $banderas_amarillas = $e->todos('BANDERA_AMARILLA');
    $acciones      = $e->todos('ACCION');
    $accion_plazo  = $e->uno('ACCION_PLAZO');

    $cand_url        = $e->uno('CAND_URL');
    $cand_vendedor   = $e->uno('CAND_VENDEDOR');
    $cand_tipo       = $e->uno('CAND_VENDEDOR_TIPO');
    $cand_rating     = $e->uno('CAND_VENDEDOR_RATING');
    $cand_ciudad     = $e->uno('CAND_CIUDAD');
    $cand_precio     = $e->uno('CAND_PRECIO');
    $cand_precio_obj = $e->uno('PRECIO_OBJETIVO') ?? $e->uno('CAND_PRECIO_OBJ');
    $cand_dias       = $e->uno('CAND_DIAS');
    $cand_cambio     = $e->uno('CAND_CAMBIO_PRECIO');

    $mediana_es = $e->uno('MERCADO_ES_MEDIANA');
    $mediana_de = $e->uno('MERCADO_DE_MEDIANA');
    $numero_informe = $e->uno('COCHE_ID');

    // Origen DE/ES para cada comparable + marcar el elegido (pick)
    $origen_de = fn ($url) => preg_match('#(mobile\.de|autoscout24\.de|autouncle\.de|kleinanzeigen\.de)#i', (string) $url) === 1;
    $comparables = array_map(function ($c) use ($origen_de, $cand_url) {
        $url = $c[3] ?? '';
        return [
            'titulo' => $c[0] ?? '', 'km' => $c[1] ?? '', 'precio' => $c[2] ?? '',
            'url' => $url, 'origen' => $origen_de($url) ? 'de' : 'es',
            'pick' => $cand_url !== null && $url !== '' && str_contains($url, trim((string) $cand_url)),
        ];
    }, $comparables);

    $fecha = $e->uno('FECHA_INFORME') ?? $e->uno('GENERADO');
@endphp

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>{{ $e->uno('TITULO') }}</title>
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
            padding: 26px 30px 50px 30px;
            background:
                radial-gradient(ellipse at 100% 0%, rgba(143, 163, 217, 0.1) 0%, transparent 45%),
                linear-gradient(180deg, #0f1d42 0%, #14265a 50%, #0f1d42 100%);
        }

        .container { position: relative; z-index: 1; max-width: 1080px; margin: 0 auto; }

        .header {
            display: flex; justify-content: space-between; align-items: center;
            padding-bottom: 14px; border-bottom: 1px solid rgba(143,163,217,0.2); margin-bottom: 16px;
        }
        .logo { height: 42px; width: auto; }
        .confidencial {
            background: rgba(239,68,68,0.15); border: 1px solid rgba(239,68,68,0.4);
            color: #fca5a5; padding: 7px 14px; border-radius: 100px;
            font-size: 9px; font-weight: 700; letter-spacing: 1.2px; text-transform: uppercase;
        }

        .doc-head { margin-bottom: 14px; }
        .doc-eyebrow { color: #8fa3d9; font-size: 8.5px; font-weight: 700; letter-spacing: 1.6px; text-transform: uppercase; margin-bottom: 3px; }
        .doc-title { font-size: 20px; font-weight: 800; color: #fff; line-height: 1.15; }
        .doc-meta { display: flex; flex-wrap: wrap; gap: 6px 18px; margin-top: 6px; font-size: 10px; color: #94a3b8; }
        .doc-meta a { color: #8fa3d9; }

        /* Executive card */
        .exec {
            background: linear-gradient(135deg, rgba(26,48,109,0.35) 0%, rgba(15,23,42,0.6) 100%);
            border: 1px solid rgba(143,163,217,0.25); border-radius: 14px; padding: 16px 18px; margin-bottom: 14px;
        }
        .exec-top { display: flex; align-items: center; gap: 14px; margin-bottom: 10px; }
        .dictamen { font-size: 17px; font-weight: 900; letter-spacing: 0.3px; }
        .semaforo { display: inline-block; width: 12px; height: 12px; border-radius: 50%; flex-shrink: 0; }
        .confianza { margin-left: auto; font-size: 10px; color: #94a3b8; }
        .confianza b { color: #f1f5f9; }
        .resumen { font-size: 12px; color: #cbd5e1; line-height: 1.55; }
        .resumen strong { color: #f1f5f9; }

        .sub-block { margin-top: 10px; }
        .sub-label { color: #8fa3d9; font-size: 9px; font-weight: 700; letter-spacing: 1.4px; text-transform: uppercase; margin-bottom: 4px; }
        .sub-text { font-size: 11px; color: #cbd5e1; line-height: 1.55; }

        /* Sections */
        .section { margin-bottom: 16px; }
        .h2 {
            color: #9fb4e8; font-size: 11.5px; font-weight: 800; letter-spacing: 1.6px; text-transform: uppercase;
            padding-bottom: 5px; border-bottom: 1px solid rgba(143,163,217,0.15); margin-bottom: 10px;
            display: flex; align-items: center; gap: 8px;
        }
        .h2::before { content: ''; width: 4px; height: 13px; border-radius: 2px; background: linear-gradient(180deg, #E8590C, #f07c3a); }

        /* ── KPI CARDS ──────────────────────────────────────── */
        .kpi-grid { display: grid; grid-template-columns: repeat(4, 1fr); gap: 9px; margin-bottom: 16px; }
        .kpi-card {
            background: linear-gradient(180deg, rgba(20,38,90,0.85) 0%, rgba(15,23,42,0.6) 100%);
            border: 1px solid rgba(143,163,217,0.25); border-radius: 12px; padding: 11px 13px;
        }
        .kpi-card .k { font-size: 8px; color: #8fa3d9; text-transform: uppercase; letter-spacing: 1px; font-weight: 700; margin-bottom: 3px; }
        .kpi-card .v { font-size: 17px; font-weight: 800; color: #f1f5f9; line-height: 1.15; }
        .kpi-card .v .accent { color: #E8590C; }
        .kpi-card .s { font-size: 8.5px; color: #64748b; margin-top: 2px; }

        /* ── COVERAGE GRID ──────────────────────────────────── */
        .coverage { display: grid; grid-template-columns: repeat(2, 1fr); gap: 7px; }
        .cov-card {
            display: flex; align-items: center; gap: 10px;
            background: rgba(15,23,42,0.5); border: 1px solid rgba(143,163,217,0.18);
            border-radius: 10px; padding: 8px 12px;
        }
        .cov-dot { width: 9px; height: 9px; border-radius: 50%; flex-shrink: 0; }
        .cov-dot.ok { background: #10B981; box-shadow: 0 0 8px rgba(16,185,129,0.6); }
        .cov-dot.deg { background: #F59E0B; box-shadow: 0 0 8px rgba(245,158,11,0.5); }
        .cov-dot.omit { background: #64748b; }
        .cov-name { font-size: 10px; font-weight: 700; color: #f1f5f9; }
        .cov-desc { font-size: 8.5px; color: #94a3b8; }

        /* ── BARRAS DE PROGRESO (score / vendibilidad) ──────── */
        .bar-row { margin-bottom: 7px; }
        .bar-top { display: flex; justify-content: space-between; align-items: baseline; gap: 10px; font-size: 10px; margin-bottom: 3px; }
        .bar-top .bar-name { font-weight: 700; color: #cbd5e1; }
        .bar-top .bar-val { color: #8fa3d9; font-size: 9px; white-space: nowrap; }
        .bar-track { height: 7px; border-radius: 100px; background: rgba(15,23,42,0.7); border: 1px solid rgba(143,163,217,0.15); overflow: hidden; }
        .bar-fill { height: 100%; border-radius: 100px; background: linear-gradient(90deg, #1A306D, #E8590C); }

        /* ── TABLE PREMIUM (margen / venta / riesgos / comp.) ── */
        table.data { width: 100%; border-collapse: collapse; }
        table.data th {
            text-align: left; font-size: 8px; color: #9fb4e8; text-transform: uppercase;
            letter-spacing: 0.7px; font-weight: 700; padding: 6px 8px;
            border-bottom: 1px solid rgba(143,163,217,0.28);
        }
        table.data td { font-size: 10px; color: #cbd5e1; padding: 6px 8px; border-bottom: 1px solid rgba(143,163,217,0.08); vertical-align: middle; }
        table.data tr:nth-child(even) td { background: rgba(26,48,109,0.12); }
        table.data tr.pick td { background: rgba(232,89,12,0.10); border-left: 3px solid #E8590C; }
        .badge-origen { display: inline-block; padding: 1px 7px; border-radius: 6px; font-size: 8px; font-weight: 800; letter-spacing: 0.5px; }
        .badge-origen.de { background: #1A306D; color: #c7d4f5; border: 1px solid rgba(143,163,217,0.35); }
        .badge-origen.es { background: rgba(16,185,129,0.15); color: #34d399; border: 1px solid rgba(16,185,129,0.35); }
        .sem { display: inline-block; width: 8px; height: 8px; border-radius: 50%; margin-right: 5px; }
        .sem.green { background: #10B981; } .sem.amber { background: #F59E0B; } .sem.red { background: #EF4444; }
        .enlace { color: #8fa3d9; text-decoration: none; font-size: 9px; word-break: break-all; }
        .tag-pick { display: inline-block; background: rgba(232,89,12,0.2); color: #E8590C; border: 1px solid rgba(232,89,12,0.4); font-size: 7.5px; font-weight: 800; letter-spacing: 1px; padding: 1px 6px; border-radius: 100px; margin-left: 6px; }

        /* ── CANDIDATO ──────────────────────────────────────── */
        .cand-card {
            background: linear-gradient(135deg, rgba(26,48,109,0.3) 0%, rgba(15,23,42,0.5) 100%);
            border: 1px solid rgba(143,163,217,0.25); border-radius: 12px; padding: 12px 14px;
        }
        .cand-grid { display: grid; grid-template-columns: repeat(4, 1fr); gap: 8px; }
        .cand-cell .cand-k { font-size: 7.5px; color: #64748b; text-transform: uppercase; letter-spacing: 0.8px; font-weight: 600; }
        .cand-cell .cand-v { font-size: 11px; font-weight: 700; color: #f1f5f9; margin-top: 1px; }

        /* ── RIESGOS / BANDERAS ─────────────────────────────── */
        .bandera { display: flex; gap: 8px; font-size: 10px; line-height: 1.45; padding: 6px 10px; border-radius: 8px; margin-bottom: 6px; }
        .bandera.roja { background: rgba(239,68,68,0.1); border: 1px solid rgba(239,68,68,0.3); color: #fca5a5; }
        .bandera.amarilla { background: rgba(245,158,11,0.1); border: 1px solid rgba(245,158,11,0.3); color: #fbbf24; }

        /* ── ACCIONES ───────────────────────────────────────── */
        .accion-item { display: flex; gap: 8px; font-size: 10.5px; color: #cbd5e1; line-height: 1.5; padding: 5px 0; border-bottom: 1px solid rgba(143,163,217,0.08); }
        .accion-item .n { width: 16px; height: 16px; border-radius: 50%; background: rgba(232,89,12,0.18); color: #E8590C; font-size: 8.5px; font-weight: 800; display: flex; align-items: center; justify-content: center; flex-shrink: 0; margin-top: 1px; }
        .accion-plazo { margin-top: 8px; display: inline-block; background: rgba(232,89,12,0.12); border: 1px dashed rgba(232,89,12,0.4); color: #f07c3a; padding: 4px 10px; border-radius: 100px; font-size: 9px; font-weight: 700; }

        /* ── VERDICT ────────────────────────────────────────── */
        .verdict-card {
            background: linear-gradient(135deg, rgba(232,89,12,0.14) 0%, rgba(26,48,109,0.2) 100%);
            border: 1px solid rgba(232,89,12,0.4); border-radius: 14px; padding: 15px 19px;
        }
        .verdict-title { font-size: 11px; color: #E8590C; font-weight: 800; letter-spacing: 1.2px; text-transform: uppercase; margin-bottom: 6px; }
        .verdict-text { font-size: 12px; color: #e8edf7; line-height: 1.65; }
        .verdict-text strong { color: #fff; }

        /* Financial table */
        .fin-table { width: 100%; border-collapse: collapse; }
        .fin-table td { padding: 5px 8px; font-size: 11px; border-bottom: 1px solid rgba(143,163,217,0.08); }
        .fin-table .concepto { color: #cbd5e1; }
        .fin-table .importe { text-align: right; font-weight: 700; color: #f1f5f9; white-space: nowrap; }
        .fin-table tr.total td { border-top: 2px solid rgba(143,163,217,0.3); font-weight: 800; color: #fff; }
        .fin-table tr.destacado td { background: rgba(232,89,12,0.12); font-weight: 900; color: #E8590C; }
        .fin-table tr.mercado td, .fin-table tr.ahorro td { font-weight: 700; }
        .fin-table tr.ahorro td.importe { color: #4ade80; }
        .fin-table tr.nota td { font-size: 9px; color: #64748b; line-height: 1.4; }
        .fin-table tr.nota .importe { font-weight: 400; color: #64748b; text-align: left; }

        /* Balance */
        .balance-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 10px; }
        .bal-col { border-radius: 10px; padding: 10px 12px; }
        .bal-col.fav { background: rgba(16,185,129,0.08); border: 1px solid rgba(16,185,129,0.25); }
        .bal-col.contra { background: rgba(239,68,68,0.08); border: 1px solid rgba(239,68,68,0.25); }
        .bal-col .bal-title { font-size: 9px; font-weight: 800; letter-spacing: 1.2px; text-transform: uppercase; margin-bottom: 6px; }
        .bal-col.fav .bal-title { color: #10B981; }
        .bal-col.contra .bal-title { color: #EF4444; }
        .bal-item { display: flex; gap: 7px; font-size: 10px; color: #cbd5e1; line-height: 1.45; margin-bottom: 6px; }
        .bal-item .puntos { font-size: 8px; color: #64748b; letter-spacing: 1px; flex-shrink: 0; margin-top: 2px; }

        /* Auditoría */
        .aspecto { border-radius: 10px; padding: 10px 12px; margin-bottom: 8px; border: 1px solid rgba(143,163,217,0.15); background: rgba(15,23,42,0.4); }
        .aspecto .asp-head { display: flex; align-items: center; gap: 8px; margin-bottom: 5px; }
        .aspecto .asp-titulo { font-size: 11.5px; font-weight: 800; color: #f1f5f9; }
        .val-badge { font-size: 7.5px; font-weight: 700; letter-spacing: 1px; text-transform: uppercase; padding: 2px 8px; border-radius: 100px; }
        .val-badge.fav { background: rgba(16,185,129,0.15); color: #4ade80; }
        .val-badge.des { background: rgba(239,68,68,0.15); color: #fca5a5; }
        .val-badge.neu { background: rgba(245,158,11,0.15); color: #fbbf24; }
        .val-badge.sin { background: rgba(90,100,114,0.2); color: #94a3b8; }
        .aspecto .asp-texto { font-size: 10.5px; color: #cbd5e1; line-height: 1.5; }
        .aspecto .asp-fuente { font-size: 8.5px; color: #8fa3d9; margin-top: 4px; word-break: break-all; }

        /* Comparables */
        .comp-table { width: 100%; border-collapse: collapse; }
        .comp-table th { text-align: left; font-size: 8px; color: #64748b; text-transform: uppercase; letter-spacing: 0.8px; padding: 4px 8px; border-bottom: 1px solid rgba(143,163,217,0.2); }
        .comp-table td { padding: 5px 8px; font-size: 10.5px; color: #cbd5e1; border-bottom: 1px solid rgba(143,163,217,0.08); }
        .comp-table td a { color: #8fa3d9; }

        /* Fuentes */
        .fuente-item { display: flex; gap: 7px; font-size: 9.5px; color: #cbd5e1; line-height: 1.4; margin-bottom: 4px; }
        .fuente-item .aspecto-tag { color: #8fa3d9; font-weight: 700; flex-shrink: 0; }
        .fuente-item a { color: #8fa3d9; word-break: break-all; }

        /* Checklist */
        .check-item { display: flex; gap: 8px; font-size: 10.5px; color: #cbd5e1; line-height: 1.5; padding: 5px 0; border-bottom: 1px solid rgba(143,163,217,0.08); }
        .check-item .cb { width: 11px; height: 11px; border: 1.5px solid #8fa3d9; border-radius: 3px; flex-shrink: 0; margin-top: 2px; }

        .pie {
            margin-top: 14px; padding-top: 8px; border-top: 1px solid rgba(143,163,217,0.2);
            font-size: 8px; color: #64748b; line-height: 1.5; text-align: center; font-style: italic;
        }
    </style>
</head>
<body>
    <div class="container">

        <div class="header">
            <img src="{{ $logo_base64 }}" alt="JJ Import Motors" class="logo">
            <span class="confidencial">@if($e->uno('CONFIDENCIAL')){{ $e->uno('CONFIDENCIAL') }}@else CONFIDENCIAL — USO INTERNO @endif</span>
        </div>

        <div class="doc-head">
            <div class="doc-eyebrow">Valoración interna · {{ $fecha }} @if($e->uno('VALIDO_HASTA'))· válido hasta {{ $e->uno('VALIDO_HASTA') }}@endif</div>
            <div class="doc-title">{{ $e->uno('TITULO') }}</div>
            <div class="doc-meta">
                @if($numero_informe)<span>🆔 {{ $numero_informe }}</span>@endif
                @if($e->uno('ORIGEN'))<span>📍 {{ $e->uno('ORIGEN') }}</span>@endif
                @if($e->uno('VIN'))<span>🔩 VIN: {{ $e->uno('VIN') }}</span>@endif
                @if($e->uno('URL_ANUNCIO'))<span><a href="{{ $e->uno('URL_ANUNCIO') }}">Anuncio original</a></span>@endif
            </div>
        </div>

        {{-- ── KPI CARDS ─────────────────────────────────────────────── --}}
        @if($score_global !== null || $recomendacion || $mediana_es || $cobertura_total > 0)
        <div class="kpi-grid">
            @if($score_global !== null)
                <div class="kpi-card">
                    <div class="k">Score global</div>
                    <div class="v"><span class="accent">{{ $score_global }}</span><span style="font-size:11px;color:#64748b;">/100</span></div>
                    <div class="s">Oportunidad de la operación</div>
                </div>
            @endif
            @if($recomendacion)
                <div class="kpi-card">
                    <div class="k">Recomendación</div>
                    <div class="v" style="font-size:13px;">{{ $recomendacion }}</div>
                    <div class="s">Veredicto de la investigación</div>
                </div>
            @endif
            @if($mediana_es)
                <div class="kpi-card">
                    <div class="k">Mediana mercado ES</div>
                    <div class="v">{{ $mediana_es }} €</div>
                    <div class="s">Referencia de reventa en España</div>
                </div>
            @endif
            @if($cobertura_total > 0)
                <div class="kpi-card">
                    <div class="k">Cobertura fuentes</div>
                    <div class="v"><span class="accent">{{ $cobertura_ok }}</span><span style="font-size:11px;color:#64748b;">/{{ $cobertura_total }}</span></div>
                    <div class="s">{{ $cobertura_pct }}% · {{ $cobertura_ok }} fuentes OK</div>
                </div>
            @endif
        </div>
        @endif

        {{-- ── COVERAGE DE FUENTES ───────────────────────────────────── --}}
        @if(count($cobertura_raw) > 0)
        <div class="section">
            <div class="h2">Cobertura de fuentes</div>
            <div class="coverage">
                @foreach($cobertura_raw as [$fuente, $estado, $score, $detalle])
                    <div class="cov-card">
                        <span class="cov-dot {{ $cov_clase($estado) }}"></span>
                        <div>
                            <div class="cov-name">{{ $fuente }}</div>
                            <div class="cov-desc">{{ $estado }} @if($score) · {{ $score }} pts @endif @if($detalle) · {{ $detalle }} @endif</div>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
        @endif

        <!-- Executive card -->
        @if($e->uno('DICTAMEN') || $e->uno('RESUMEN'))
        <div class="exec">
            @if($e->uno('DICTAMEN'))
            <div class="exec-top">
                <span class="semaforo" style="background: {{ $semaforo_color }}; box-shadow: 0 0 10px {{ $semaforo_color }};"></span>
                <span class="dictamen" style="color: {{ $semaforo_color }};">{{ $e->uno('DICTAMEN') }}</span>
                <span class="confianza">Confianza: <b>{{ $e->uno('CONFIANZA') }}</b></span>
            </div>
            @endif
            @if($e->uno('RESUMEN'))
                <p class="resumen">{!! \App\Support\Esqueleto::negrita($e->uno('RESUMEN')) !!}</p>
            @endif
            @if($e->uno('RAZONAMIENTO'))
                <div class="sub-block">
                    <div class="sub-label">Razonamiento</div>
                    <div class="sub-text">{{ $e->uno('RAZONAMIENTO') }}</div>
                </div>
            @endif
            @if($e->uno('QUE_CAMBIARIA'))
                <div class="sub-block">
                    <div class="sub-label">Qué cambiaría la valoración</div>
                    <div class="sub-text">{{ $e->uno('QUE_CAMBIARIA') }}</div>
                </div>
            @endif
            @if($cand_precio_obj)
                <div class="sub-block">
                    <div class="sub-label">Precio objetivo de compra</div>
                    <div class="sub-text">{{ $cand_precio_obj }} €</div>
                </div>
            @endif
        </div>
        @endif

        {{-- ── CANDIDATO ─────────────────────────────────────────────── --}}
        @if($cand_vendedor || $cand_ciudad || $cand_precio)
        <div class="section">
            <div class="h2">Candidato analizado</div>
            <div class="cand-card">
                <div class="cand-grid">
                    @if($cand_vendedor)<div class="cand-cell"><div class="cand-k">Vendedor</div><div class="cand-v">{{ $cand_vendedor }} @if($cand_rating)★ {{ $cand_rating }} @endif</div></div>@endif
                    @if($cand_tipo)<div class="cand-cell"><div class="cand-k">Tipo</div><div class="cand-v">{{ $cand_tipo }}</div></div>@endif
                    @if($cand_ciudad)<div class="cand-cell"><div class="cand-k">Ciudad</div><div class="cand-v">{{ $cand_ciudad }}</div></div>@endif
                    @if($cand_precio)<div class="cand-cell"><div class="cand-k">Precio publicado</div><div class="cand-v" style="color:#E8590C;">{{ $cand_precio }} €</div></div>@endif
                    @if($cand_precio_obj)<div class="cand-cell"><div class="cand-k">Precio objetivo</div><div class="cand-v">{{ $cand_precio_obj }} €</div></div>@endif
                    @if($cand_dias)<div class="cand-cell"><div class="cand-k">Días publicado</div><div class="cand-v">{{ $cand_dias }}</div></div>@endif
                    @if($cand_cambio)<div class="cand-cell"><div class="cand-k">Cambio de precio</div><div class="cand-v">{{ $cand_cambio }}</div></div>@endif
                </div>
                @if($cand_url)
                    <div style="margin-top:8px;"><div class="cand-k">Anuncio</div><div class="cand-v"><a class="enlace" href="{{ $cand_url }}">{{ $cand_url }}</a></div></div>
                @endif
            </div>
        </div>
        @endif

        <!-- Financial breakdown -->
        @if(count($financiero) > 0)
        <div class="section">
            <div class="h2">Desglose de la operación financiera</div>
            <table class="fin-table">
                @foreach($financiero as $fila)
                    @php $clase = match ($fila['tipo']) { 'TOTAL' => 'total', 'DESTACADO' => 'destacado', 'MERCADO' => 'mercado', 'AHORRO' => 'ahorro', 'NOTA' => 'nota', default => '' }; @endphp
                    <tr class="{{ $clase }}">
                        <td class="concepto">{{ $fila['concepto'] }}</td>
                        <td class="importe">{{ $fila['importe'] }}</td>
                    </tr>
                @endforeach
            </table>
        </div>
        @endif

        {{-- ── MARGEN vs MERCADO ─────────────────────────────────────── --}}
        @if(count($margenes) > 0)
        <div class="section">
            <div class="h2">Margen vs. mercado</div>
            <table class="data">
                <tr><th>Referencia</th><th>Margen €</th><th>Margen %</th><th></th></tr>
                @foreach($margenes as [$concepto, $margen_eur, $margen_pct, $color])
                    @php
                        $c = strtolower(trim((string) $color));
                        $sem_clase = in_array($c, ['green', 'verde'], true) ? 'green' : (in_array($c, ['amber', 'ambar'], true) ? 'amber' : 'red');
                    @endphp
                    <tr>
                        <td>{{ $concepto }}</td>
                        <td>{{ $margen_eur }} €</td>
                        <td>{{ $margen_pct }}%</td>
                        <td><span class="sem {{ $sem_clase }}"></span></td>
                    </tr>
                @endforeach
            </table>
        </div>
        @endif

        {{-- ── SCORE POR DIMENSIONES ─────────────────────────────────── --}}
        @if(count($score_dims) > 0)
        <div class="section">
            <div class="h2">Score global desglosado</div>
            @foreach($score_dims as [$nombre, $max, $score])
                @php $pct = ((float) $max > 0) ? round(((float) $score / (float) $max) * 100) : 0; @endphp
                <div class="bar-row">
                    <div class="bar-top"><span class="bar-name">{{ $nombre }}</span><span class="bar-val">{{ $score }}/{{ $max }}</span></div>
                    <div class="bar-track"><div class="bar-fill" style="width: {{ $pct }}%;"></div></div>
                </div>
            @endforeach
        </div>
        @endif

        {{-- ── VENDIBILIDAD ──────────────────────────────────────────── --}}
        @if(count($vendibilidad) > 0)
        <div class="section">
            <div class="h2">Vendibilidad @if($vend_total)· total {{ $vend_total }} @endif</div>
            @foreach($vendibilidad as [$nombre, $max, $score, $nota])
                @php $pct = ((float) $max > 0) ? round(((float) $score / (float) $max) * 100) : 0; @endphp
                <div class="bar-row">
                    <div class="bar-top"><span class="bar-name">{{ $nombre }}</span><span class="bar-val">{{ $score }}/{{ $max }} @if($nota)· {{ $nota }} @endif</span></div>
                    <div class="bar-track"><div class="bar-fill" style="width: {{ $pct }}%;"></div></div>
                </div>
            @endforeach
        </div>
        @endif

        {{-- ── PREDICCIÓN DE VENTA ───────────────────────────────────── --}}
        @if(count($ventas) > 0)
        <div class="section">
            <div class="h2">Predicción de venta en España</div>
            <table class="data">
                <tr><th>Escenario</th><th>Precio</th><th>Días</th><th>Margen €</th><th>Margen %</th></tr>
                @foreach($ventas as [$esc, $precio, $dias, $margen_eur, $margen_pct])
                    <tr class="{{ strtoupper(trim((string) $esc)) === $venta_rec ? 'pick' : '' }}">
                        <td>{{ $esc }} @if(strtoupper(trim((string) $esc)) === $venta_rec)<span class="tag-pick">RECOMENDADA</span>@endif</td>
                        <td>{{ $precio }} €</td>
                        <td>{{ $dias }}</td>
                        <td>{{ $margen_eur }} €</td>
                        <td>{{ $margen_pct }}%</td>
                    </tr>
                @endforeach
            </table>
        </div>
        @endif

        <!-- Balance -->
        @if(count($balance['A_FAVOR']) > 0 || count($balance['EN_CONTRA']) > 0)
        <div class="section">
            <div class="h2">A favor vs. puntos a vigilar</div>
            <div class="balance-grid">
                @if(count($balance['A_FAVOR']) > 0)
                <div class="bal-col fav">
                    <div class="bal-title">▲ A favor</div>
                    @foreach($balance['A_FAVOR'] as $item)
                        <div class="bal-item"><span class="puntos">{{ str_repeat('●', $peso($item['peso'])) }}</span><span>{{ $item['texto'] }}</span></div>
                    @endforeach
                </div>
                @endif
                @if(count($balance['EN_CONTRA']) > 0)
                <div class="bal-col contra">
                    <div class="bal-title">▼ A vigilar</div>
                    @foreach($balance['EN_CONTRA'] as $item)
                        <div class="bal-item"><span class="puntos">{{ str_repeat('●', $peso($item['peso'])) }}</span><span>{{ $item['texto'] }}</span></div>
                    @endforeach
                </div>
                @endif
            </div>
        </div>
        @endif

        <!-- Auditoría -->
        @if(count($auditoria) > 0)
        <div class="section">
            <div class="h2">Auditoría detallada</div>
            @foreach($auditoria as $a)
                <div class="aspecto">
                    <div class="asp-head">
                        <span class="asp-titulo">{{ $a['ASPECTO'] }}</span>
                        <span class="val-badge {{ $valoracion_clase($a['VALORACION'] ?? '') }}">{{ $a['VALORACION'] ?? 'sin valorar' }}</span>
                    </div>
                    @if(!empty($a['TEXTO']))
                        <div class="asp-texto">{!! \App\Support\Esqueleto::negrita($a['TEXTO']) !!}</div>
                    @endif
                    @if(!empty($a['FUENTE']))
                        <div class="asp-fuente">Fuente: {{ $a['FUENTE'] }}</div>
                    @endif
                </div>
            @endforeach
        </div>
        @endif

        {{-- ── COMPARABLES REALES (badges DE/ES + pick) ───────────────── --}}
        @if(count($comparables) > 0)
        <div class="section">
            <div class="h2">Comparables reales</div>
            <table class="data">
                <tr><th>Coche</th><th>Origen</th><th>Km</th><th>Precio</th><th>Anuncio</th></tr>
                @foreach($comparables as $c)
                    <tr class="{{ $c['pick'] ? 'pick' : '' }}">
                        <td>{{ $c['titulo'] }} @if($c['pick'])<span class="tag-pick">ELEGIDO</span>@endif</td>
                        <td><span class="badge-origen {{ $c['origen'] }}">{{ strtoupper($c['origen']) }}</span></td>
                        <td>{{ $c['km'] }}</td>
                        <td>{{ $c['precio'] }}</td>
                        <td>@if($c['url'] && $c['url'] !== '—')<a class="enlace" href="{{ $c['url'] }}">ver</a>@else — @endif</td>
                    </tr>
                @endforeach
            </table>
        </div>
        @endif

        <!-- Fuentes -->
        @if(count($fuentes) > 0)
        <div class="section">
            <div class="h2">Fuentes verificadas</div>
            @foreach($fuentes as [$aspecto, $titulo, $url])
                <div class="fuente-item">
                    <span class="aspecto-tag">{{ $aspecto }}</span>
                    <span>{{ $titulo }} @if($url && $url !== '—')· <a href="{{ $url }}">enlace</a>@endif</span>
                </div>
            @endforeach
        </div>
        @endif

        {{-- ── RIESGOS Y BANDERAS ────────────────────────────────────── --}}
        @if(count($riesgos) > 0 || count($banderas_rojas) > 0 || count($banderas_amarillas) > 0)
        <div class="section">
            <div class="h2">Riesgos y banderas</div>
            @foreach($banderas_rojas as $b)
                <div class="bandera roja">🚩 {{ $b }}</div>
            @endforeach
            @foreach($banderas_amarillas as $b)
                <div class="bandera amarilla">⚠️ {{ $b }}</div>
            @endforeach
            @if(count($riesgos) > 0)
            <table class="data" style="margin-top:6px;">
                <tr><th>Riesgo</th><th>Probabilidad</th><th>Impacto</th><th>Mitigación</th></tr>
                @foreach($riesgos as [$desc, $prob, $impacto, $mitigacion])
                    <tr>
                        <td>{{ $desc }}</td>
                        <td>{{ $prob }}</td>
                        <td>{{ $impacto }}</td>
                        <td>{{ $mitigacion }}</td>
                    </tr>
                @endforeach
            </table>
            @endif
        </div>
        @endif

        <!-- Checklist -->
        @if(count($checks) > 0)
        <div class="section">
            <div class="h2">Pasos siguientes / checklist técnico</div>
            @foreach($checks as $check)
                <div class="check-item"><span class="cb"></span><span>{!! \App\Support\Esqueleto::negrita($check) !!}</span></div>
            @endforeach
        </div>
        @endif

        {{-- ── ACCIONES ──────────────────────────────────────────────── --}}
        @if(count($acciones) > 0)
        <div class="section">
            <div class="h2">Acción inmediata</div>
            @foreach($acciones as $i => $accion)
                <div class="accion-item"><span class="n">{{ $i + 1 }}</span><span>{!! \App\Support\Esqueleto::negrita($accion) !!}</span></div>
            @endforeach
            @if($accion_plazo)
                <span class="accion-plazo">⏱ Plazo: {{ $accion_plazo }}</span>
            @endif
        </div>
        @endif

        {{-- ── VERDICT FINAL ─────────────────────────────────────────── --}}
        @if($veredicto)
        <div class="section">
            <div class="h2">Veredicto final</div>
            <div class="verdict-card">
                <div class="verdict-title">Recomendación · {{ $veredicto }}</div>
                <div class="verdict-text">
                    @if($cand_precio_obj)Compra objetivo: <strong>{{ $cand_precio_obj }} €</strong>.@endif
                    @if($e->uno('CONFIANZA'))Confianza de la valoración: <strong>{{ $e->uno('CONFIANZA') }}</strong>.@endif
                    @if($score_global !== null)Score global de oportunidad: <strong>{{ $score_global }}/100</strong>.@endif
                    @if($mediana_de)Referencia mercado DE: {{ $mediana_de }} €.@endif
                    @if($mediana_es)Referencia mercado ES: {{ $mediana_es }} €.@endif
                </div>
            </div>
        </div>
        @endif

        @if($e->uno('PIE'))
        <p class="pie">{{ $e->uno('PIE') }}</p>
        @endif

    </div>
</body>
</html>
