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
        .pageinfo { font-size: 12px; color: #8fa3d9; font-variant-numeric: tabular-nums; white-space: nowrap; }
        .stage {
            flex: 1; min-height: 0;
            overflow: auto;
            background: #0b1533;
            display: flex; flex-direction: column; align-items: center;
            gap: 18px; padding: 24px;
        }
        .page-slot {
            position: relative;
            background: #fff;
            box-shadow: 0 12px 40px rgba(0, 0, 0, 0.5);
            border-radius: 4px;
            overflow: hidden;
            flex-shrink: 0;
        }
        .page-slot canvas { display: block; width: 100%; height: 100%; }
        .loading {
            padding: 60px 20px; text-align: center; color: #8fa3d9; font-size: 13px;
        }
        .spinner {
            width: 36px; height: 36px; margin: 0 auto 14px;
            border: 3px solid rgba(143, 163, 217, 0.25); border-top-color: #8fa3d9;
            border-radius: 50%; animation: spin 0.9s linear infinite;
        }
        @keyframes spin { to { transform: rotate(360deg); } }
        .error-box {
            max-width: 460px; margin: 60px auto; padding: 22px 24px;
            background: rgba(232, 89, 12, 0.1); border: 1px solid rgba(232, 89, 12, 0.35);
            border-radius: 12px; color: #fecaca; font-size: 13px; line-height: 1.5;
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
            <span class="pageinfo" id="pageinfo"></span>
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
    <div class="stage" id="stage">
        <div class="loading">
            <div class="spinner"></div>
            Generando documento…
        </div>
    </div>

    <script type="importmap">
        {
            "imports": {
                "pdfjs-dist": "https://cdn.jsdelivr.net/npm/pdfjs-dist@4.7.76/build/pdf.min.mjs",
                "pdfjs-dist/build/pdf.worker.min.mjs": "https://cdn.jsdelivr.net/npm/pdfjs-dist@4.7.76/build/pdf.worker.min.mjs"
            }
        }
    </script>
    <script type="module">
        import * as pdfjsLib from 'pdfjs-dist';

        pdfjsLib.GlobalWorkerOptions.workerSrc =
            'https://cdn.jsdelivr.net/npm/pdfjs-dist@4.7.76/build/pdf.worker.min.mjs';

        const pdfSrc = @json($pdfSrc);
        const stage = document.getElementById('stage');
        const pageinfo = document.getElementById('pageinfo');

        async function load() {
            try {
                const loadingTask = pdfjsLib.getDocument({ url: pdfSrc, isEvalSupported: false });
                const pdf = await loadingTask.promise;

                const slotWidth = Math.min(900, stage.clientWidth - 48);

                for (let i = 1; i <= pdf.numPages; i++) {
                    const page = await pdf.getPage(i);
                    const base = page.getViewport({ scale: 1 });
                    const scale = slotWidth / base.width;
                    const viewport = page.getViewport({ scale });

                    const slot = document.createElement('div');
                    slot.className = 'page-slot';
                    slot.style.width = viewport.width + 'px';
                    slot.style.height = viewport.height + 'px';

                    const canvas = document.createElement('canvas');
                    canvas.width = Math.floor(viewport.width * devicePixelRatio);
                    canvas.height = Math.floor(viewport.height * devicePixelRatio);
                    canvas.style.width = viewport.width + 'px';
                    canvas.style.height = viewport.height + 'px';

                    slot.appendChild(canvas);
                    stage.appendChild(slot);

                    const ctx = canvas.getContext('2d');
                    const renderTask = page.render({
                        canvasContext: ctx,
                        viewport,
                        transform: devicePixelRatio !== 1
                            ? [devicePixelRatio, 0, 0, devicePixelRatio, 0, 0]
                            : undefined,
                    });
                    await renderTask.promise;
                    page.cleanup();
                }

                pageinfo.textContent = pdf.numPages + ' páginas';
            } catch (err) {
                console.error(err);
                stage.innerHTML = '';
                const box = document.createElement('div');
                box.className = 'error-box';
                box.innerHTML = '<strong>No se pudo mostrar el documento.</strong><br><br>'
                    + 'Puede que tu sesión haya caducado. Vuelve a la ficha y pulsa '
                    + '<strong>Ficha cliente</strong> de nuevo. Si el problema continúa, '
                    + 'usa el botón <strong>Descargar</strong> para abrirlo en tu lector PDF.';
                stage.appendChild(box);
            }
        }

        load();
    </script>
</body>
</html>
