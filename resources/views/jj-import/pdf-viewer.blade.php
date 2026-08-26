<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $titulo }} · JJ Import Motors</title>
    <link rel="icon" type="image/png" href="/images/jj-import/logo-icon.png">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        html, body { height: 100%; overflow: hidden; }
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background: #0f1d42;
            color: #c7d4f5;
            display: flex; flex-direction: column;
        }
        .toolbar {
            display: flex; align-items: center; gap: 14px;
            padding: 12px 20px;
            background: linear-gradient(180deg, #14265a 0%, #0f1d42 100%);
            border-bottom: 1px solid rgba(143, 163, 217, 0.25);
            flex-shrink: 0;
            box-shadow: 0 4px 16px rgba(0, 0, 0, 0.3);
        }
        .brand { display: flex; align-items: center; gap: 10px; }
        .brand-logo { height: 32px; width: auto; }
        .brand-text { color: #fff; font-weight: 700; font-size: 14px; letter-spacing: 0.3px; }
        .title {
            flex: 1; min-width: 0;
            color: #c7d4f5; font-size: 13px; font-weight: 500;
            white-space: nowrap; overflow: hidden; text-overflow: ellipsis;
        }
        .actions { display: flex; gap: 8px; flex-shrink: 0; }
        .btn {
            display: inline-flex; align-items: center; gap: 6px;
            padding: 7px 14px; border-radius: 8px;
            font-size: 12px; font-weight: 600; text-decoration: none;
            border: 1px solid rgba(143, 163, 217, 0.3);
            color: #c7d4f5; background: rgba(143, 163, 217, 0.08);
            cursor: pointer; transition: all 0.15s ease;
        }
        .btn:hover { background: rgba(143, 163, 217, 0.18); border-color: #8fa3d9; color: #fff; }
        .btn.primary {
            background: linear-gradient(135deg, #1A306D 0%, #2a3d87 100%);
            border-color: #1A306D; color: #fff;
            box-shadow: 0 4px 14px rgba(26, 48, 109, 0.4);
        }
        .btn.primary:hover { background: linear-gradient(135deg, #2a3d87 0%, #1A306D 100%); }
        .btn svg { width: 14px; height: 14px; }
        .frame-wrap {
            flex: 1; min-height: 0;
            background: #14265a;
        }
        .frame-wrap iframe {
            width: 100%; height: 100%;
            border: 0;
            background: #1e3a8a;
        }
        .frame-wrap embed {
            width: 100%; height: 100%;
        }
    </style>
</head>
<body>
    <div class="toolbar">
        <div class="brand">
            @php
                $logoPath = public_path('images/jj-import/logo-horizontal-blanco.png');
                $logoSrc = file_exists($logoPath)
                    ? 'data:image/png;base64,'.base64_encode(file_get_contents($logoPath))
                    : null;
            @endphp
            @if($logoSrc)
                <img src="{{ $logoSrc }}" alt="JJ Import Motors" class="brand-logo">
            @else
                <span class="brand-text">JJ Import Motors</span>
            @endif
        </div>
        <div class="title">{{ $titulo }}</div>
        <div class="actions">
            <button type="button" class="btn" onclick="window.close(); return false;" title="Cerrar pestaña">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M18 6L6 18M6 6l12 12"/></svg>
                Cerrar
            </button>
            <a href="{{ $downloadUrl }}" class="btn primary" download="{{ $filename }}" title="Descargar PDF">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 15v4a2 2 0 01-2 2H5a2 2 0 01-2-2v-4M7 10l5 5 5-5M12 15V3"/></svg>
                Descargar
            </a>
        </div>
    </div>
    <div class="frame-wrap">
        <iframe src="{{ $pdfSrc }}" title="{{ $titulo }}"></iframe>
    </div>
</body>
</html>
