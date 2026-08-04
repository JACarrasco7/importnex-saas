@php
    // DOCUMENTO INTERNO. No debe servirse por ninguna ruta pública.
    $telefono_1 = $telefono_1 ?? '675 70 14 39';
    $telefono_2 = $telefono_2 ?? '691 48 59 27';
    $email = $email ?? 'jjimportmotors@gmail.com';

    $semaforo = strtolower($e->uno('SEMAFORO') ?? 'gris');
    $semaforo_color = match ($semaforo) {
        'verde'  => '#10B981',
        'ambar'  => '#F59E0B',
        'rojo'   => '#EF4444',
        default  => '#5A6472',
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
        'alto' => '3', 'medio' => '2', default => '1',
    };

    $valoracion_clase = fn ($v) => match (strtolower($v)) {
        'favorable' => 'fav', 'desfavorable' => 'des', 'neutro' => 'neu', default => 'sin',
    };
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

        .container { max-width: 1080px; margin: 0 auto; }

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
            color: #8fa3d9; font-size: 11.5px; font-weight: 800; letter-spacing: 1.6px; text-transform: uppercase;
            padding-bottom: 5px; border-bottom: 1px solid rgba(143,163,217,0.15); margin-bottom: 10px;
        }

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
            <div class="doc-eyebrow">Valoración interna · {{ $e->uno('GENERADO') }} @if($e->uno('VALIDO_HASTA'))· válido hasta {{ $e->uno('VALIDO_HASTA') }}@endif</div>
            <div class="doc-title">{{ $e->uno('TITULO') }}</div>
            <div class="doc-meta">
                @if($e->uno('ORIGEN'))<span>📍 {{ $e->uno('ORIGEN') }}</span>@endif
                @if($e->uno('VIN'))<span>🔩 VIN: {{ $e->uno('VIN') }}</span>@endif
                @if($e->uno('URL_ANUNCIO'))<span><a href="{{ $e->uno('URL_ANUNCIO') }}">Anuncio original</a></span>@endif
            </div>
        </div>

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
            @if($e->uno('PRECIO_OBJETIVO'))
                <div class="sub-block">
                    <div class="sub-label">Precio objetivo</div>
                    <div class="sub-text">{{ $e->uno('PRECIO_OBJETIVO') }}</div>
                </div>
            @endif
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

        <!-- Comparables -->
        @if(count($comparables) > 0)
        <div class="section">
            <div class="h2">Comparables reales en España</div>
            <table class="comp-table">
                <tr><th>Coche</th><th>Km</th><th>Precio</th><th>Anuncio</th></tr>
                @foreach($comparables as [$titulo, $km, $precio, $url])
                    <tr>
                        <td>{{ $titulo }}</td>
                        <td>{{ $km }}</td>
                        <td>{{ $precio }}</td>
                        <td>@if($url && $url !== '—')<a href="{{ $url }}">ver</a>@else — @endif</td>
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

        <!-- Checklist -->
        @if(count($checks) > 0)
        <div class="section">
            <div class="h2">Pasos siguientes / checklist técnico</div>
            @foreach($checks as $check)
                <div class="check-item"><span class="cb"></span><span>{!! \App\Support\Esqueleto::negrita($check) !!}</span></div>
            @endforeach
        </div>
        @endif

        @if($e->uno('PIE'))
        <p class="pie">{{ $e->uno('PIE') }}</p>
        @endif

    </div>
</body>
</html>
