<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $car->brand }} {{ $car->model }} · JJ Import Motors</title>
    <meta name="robots" content="noindex, nofollow">
    <meta property="og:type" content="website">
    <meta property="og:site_name" content="JJ Import Motors">
    <meta property="og:url" content="{{ url()->current() }}">
    <meta property="og:title" content="{{ $car->brand }} {{ $car->model }} · JJ Import Motors">
    <meta property="og:description" content="{{ number_format(($car->sale_price ?? $car->purchase_price ?? 0), 0, ',', '.') }} € + gastos de gestión · Gestionamos la compra por ti">
    {{-- og:image debe ser URL absoluta (1200x630, <600 KB). WhatsApp NO lee data: URIs. --}}
    @if($fotoPortada)
        <meta property="og:image" content="{{ $fotoPortada }}">
        <meta name="twitter:card" content="summary_large_image">
    @endif
    <style>
        :root {
            --estoril: #1A306D;
            --estoril-2: #2a3d87;
            --asphalt: #0a1535;
            --asphalt-2: #14265a;
            --platinum: #8fa3d9;
            --platinum-2: #c7d4f5;
            --orange: #E8590C;
            --green: #10b981;
            --gold: #f4c542;
        }
        * { margin: 0; padding: 0; box-sizing: border-box; }

        /* ── Revelado al hacer scroll: el contenido SIEMPRE está visible de base ── */
        .reveal { opacity: 1; transform: none; }
        @supports (animation-timeline: view()) {
            @media (prefers-reduced-motion: no-preference) {
                .reveal { animation: reveal-in both; animation-timeline: view(); animation-range: entry 8% cover 26%; }
            }
        }
        @keyframes reveal-in { from { opacity: 0; transform: translateY(18px); } to { opacity: 1; transform: none; } }
        .js-oculto { opacity: 0; transform: translateY(18px); }
        .js-visible { opacity: 1; transform: none; transition: opacity .5s ease, transform .5s cubic-bezier(.2,.7,.3,1); }
        @media (prefers-reduced-motion: reduce) {
            .reveal, .js-oculto, .js-visible { animation: none !important; transition: none !important; opacity: 1 !important; transform: none !important; }
        }
        @media print {
            .reveal, .js-oculto { animation: none !important; opacity: 1 !important; transform: none !important; }
        }

        /* ── Secciones nuevas de la ficha v2 ── */
        .dos-col { display: grid; grid-template-columns: 1fr 1fr; gap: 14px 34px; }
        @media (max-width: 760px) { .dos-col { grid-template-columns: 1fr; } }
        .lista-limpia { list-style: none; margin: 0; padding: 0; }
        .lista-limpia li { position: relative; padding: 7px 0 7px 26px; font-size: 15px; color: #d1d5db; }
        .lista-limpia li::before { position: absolute; left: 0; top: 7px; }
        .lista-ok li::before { content: "✓"; color: var(--green); font-weight: 700; }
        .lista-pend li::before { content: "☐"; color: var(--gold); font-weight: 700; }
        .lista-no li::before { content: "✕"; color: #f87171; font-weight: 700; }
        .aviso-legal {
            border: 1px solid rgba(244, 197, 66, 0.35); background: rgba(244, 197, 66, 0.06);
            border-radius: 16px; padding: 26px; margin: 26px 0;
        }
        .aviso-legal .titular { font-weight: 700; color: var(--gold); font-size: 17px; margin-bottom: 8px; }
        .aviso-legal p { color: #cbd5e1; font-size: 14.5px; }
        .pasos { display: grid; grid-template-columns: repeat(5, 1fr); gap: 10px; }
        .paso { border-top: 3px solid var(--orange); padding-top: 11px; font-size: 14px; color: #d1d5db; }
        .paso b { display: block; font-size: 11.5px; text-transform: uppercase; letter-spacing: .06em; color: var(--orange); margin-bottom: 4px; }
        @media (max-width: 820px) { .pasos { grid-template-columns: 1fr; } }
        .faq details { border-bottom: 1px solid rgba(143,163,217,.18); padding: 12px 0; }
        .faq summary { cursor: pointer; font-weight: 600; color: #e5e7eb; list-style: none; font-size: 15px; }
        .faq summary::-webkit-details-marker { display: none; }
        .faq summary::before { content: "+"; color: var(--orange); font-weight: 800; margin-right: 9px; }
        .faq details[open] summary::before { content: "–"; }
        .faq p { margin: 9px 0 2px 22px; color: #9fb0cf; font-size: 14.5px; }
        .nota-fina { color: #7f8cab; font-size: 12.5px; margin-top: 12px; }
        .ver-todas {
            display: inline-block; margin-top: 16px; border: 1px solid rgba(143,163,217,.35);
            border-radius: 10px; padding: 10px 20px; color: var(--platinum-2); font-weight: 600; font-size: 14px;
        }
        html { scroll-behavior: smooth; }
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', sans-serif;
            background: var(--asphalt);
            color: #e5e7eb;
            line-height: 1.55;
            -webkit-font-smoothing: antialiased;
        }
        a { color: var(--platinum); text-decoration: none; }
        img { display: block; max-width: 100%; }

        /* ── STICKY NAV ───────────────────────────────── */
        .nav {
            position: sticky; top: 0; z-index: 50;
            background: rgba(10, 21, 53, 0.92);
            backdrop-filter: blur(12px);
            -webkit-backdrop-filter: blur(12px);
            border-bottom: 1px solid rgba(143, 163, 217, 0.2);
        }
        .nav-inner {
            max-width: 1200px; margin: 0 auto;
            display: flex; align-items: center; gap: 24px;
            padding: 14px 24px;
        }
        .nav-brand { display: flex; align-items: center; gap: 10px; flex-shrink: 0; }
        .nav-logo { height: 32px; }
        .nav-links {
            display: flex; gap: 4px; flex: 1;
            overflow-x: auto;
            scrollbar-width: none;
        }
        .nav-links::-webkit-scrollbar { display: none; }
        .nav-link {
            padding: 8px 14px; border-radius: 8px;
            font-size: 13px; font-weight: 600;
            color: #c7d4f5;
            white-space: nowrap;
            transition: background 0.15s;
        }
        .nav-link:hover { background: rgba(143, 163, 217, 0.12); color: #fff; }
        .nav-cta {
            display: inline-flex; align-items: center; gap: 6px;
            padding: 9px 16px; border-radius: 8px;
            background: var(--orange); color: #fff;
            font-size: 13px; font-weight: 700;
            box-shadow: 0 4px 14px rgba(232, 89, 12, 0.4);
            flex-shrink: 0;
        }
        .nav-cta:hover { background: #d44a08; transform: translateY(-1px); }

        /* ── HERO ──────────────────────────────────────── */
        .hero {
            position: relative;
            min-height: 90vh;
            padding: 60px 24px 80px;
            overflow: hidden;
            background:
                radial-gradient(ellipse at 20% 100%, rgba(232, 89, 12, 0.18) 0%, transparent 50%),
                radial-gradient(ellipse at 100% 0%, rgba(143, 163, 217, 0.18) 0%, transparent 60%),
                linear-gradient(180deg, #14265a 0%, #0f1d42 50%, #0a1535 100%);
        }
        .hero::before {
            content: ''; position: absolute; inset: 0;
            background-image:
                radial-gradient(circle at 1px 1px, rgba(255,255,255,0.04) 1px, transparent 0);
            background-size: 32px 32px;
            pointer-events: none;
            mask-image: linear-gradient(180deg, black 0%, transparent 100%);
        }
        .hero-inner {
            position: relative; z-index: 1;
            max-width: 1200px; margin: 0 auto;
            display: grid; grid-template-columns: 1.1fr 1fr;
            gap: 60px; align-items: center;
        }
        .hero-eyebrow {
            display: inline-flex; align-items: center; gap: 8px;
            background: rgba(232, 89, 12, 0.15);
            border: 1px solid rgba(232, 89, 12, 0.4);
            color: #fed7aa;
            padding: 7px 14px; border-radius: 100px;
            font-size: 11px; font-weight: 700; letter-spacing: 1.5px;
            text-transform: uppercase;
            margin-bottom: 20px;
            animation: pulse 2.4s ease-in-out infinite;
        }
        @keyframes pulse {
            0%, 100% { box-shadow: 0 0 0 0 rgba(232, 89, 12, 0.4); }
            50% { box-shadow: 0 0 0 8px rgba(232, 89, 12, 0); }
        }
        .h1 {
            font-size: clamp(40px, 6vw, 76px); font-weight: 800; line-height: 1.02;
            color: #fff; letter-spacing: -1.5px; margin-bottom: 20px;
        }
        .h1 .accent {
            background: linear-gradient(135deg, #c7d4f5 0%, #8fa3d9 100%);
            -webkit-background-clip: text; background-clip: text;
            -webkit-text-fill-color: transparent;
        }
        .claim {
            font-size: clamp(15px, 1.6vw, 19px); color: #c7d4f5;
            max-width: 540px; margin-bottom: 32px; line-height: 1.5;
        }
        .price-card {
            display: inline-block;
            background: linear-gradient(135deg, rgba(255, 255, 255, 0.1) 0%, rgba(255, 255, 255, 0.04) 100%);
            border: 1px solid rgba(255, 255, 255, 0.18);
            border-radius: 18px;
            padding: 22px 28px;
            backdrop-filter: blur(10px);
            box-shadow: 0 12px 36px rgba(0, 0, 0, 0.4);
            margin-bottom: 28px;
        }
        .price-label {
            font-size: 10px; text-transform: uppercase; letter-spacing: 1.6px;
            color: #cbd5e1; font-weight: 700; margin-bottom: 6px;
            display: flex; align-items: center; gap: 8px;
        }
        .price-label::before {
            content: ''; width: 8px; height: 8px; border-radius: 50%;
            background: var(--green); box-shadow: 0 0 8px var(--green);
        }
        .price-value {
            font-size: 44px; font-weight: 900;
            background: linear-gradient(135deg, #fed7aa 0%, #E8590C 100%);
            -webkit-background-clip: text; background-clip: text;
            -webkit-text-fill-color: transparent;
            line-height: 1; letter-spacing: -1px;
        }
        .price-caption { font-size: 12px; color: #cbd5e1; margin-top: 8px; }

        .hero-actions { display: flex; gap: 12px; flex-wrap: wrap; }
        .btn {
            display: inline-flex; align-items: center; gap: 8px;
            padding: 13px 22px; border-radius: 10px;
            font-size: 14px; font-weight: 700;
            text-decoration: none; cursor: pointer;
            border: 1px solid transparent;
            transition: all 0.18s ease;
        }
        .btn.primary {
            background: var(--orange); color: #fff;
            box-shadow: 0 6px 20px rgba(232, 89, 12, 0.4);
        }
        .btn.primary:hover { background: #d44a08; transform: translateY(-2px); box-shadow: 0 10px 24px rgba(232, 89, 12, 0.5); }
        .btn.ghost {
            background: rgba(255, 255, 255, 0.08);
            color: #fff; border-color: rgba(255, 255, 255, 0.18);
        }
        .btn.ghost:hover { background: rgba(255, 255, 255, 0.16); }

        .hero-photo {
            position: relative;
            border-radius: 24px; overflow: hidden;
            box-shadow: 0 40px 100px rgba(0, 0, 0, 0.5),
                        0 0 0 1px rgba(255, 255, 255, 0.08);
            aspect-ratio: 4/3;
            background: #14265a;
            transform: rotate(-1.2deg);
        }
        .hero-photo img { width: 100%; height: 100%; object-fit: cover; }
        .hero-photo-badge {
            position: absolute; top: 16px; left: 16px;
            background: rgba(0, 0, 0, 0.7); backdrop-filter: blur(8px);
            color: #fff;
            padding: 8px 14px; border-radius: 100px;
            font-size: 11px; font-weight: 700; letter-spacing: 1px;
            text-transform: uppercase;
        }
        .hero-photo-badge.live {
            background: rgba(16, 185, 129, 0.9);
            display: inline-flex; align-items: center; gap: 6px;
        }
        .hero-photo-badge.live::before {
            content: ''; width: 6px; height: 6px; border-radius: 50%;
            background: #fff; animation: pulse-dot 1.6s ease-in-out infinite;
        }
        @keyframes pulse-dot {
            0%, 100% { opacity: 1; }
            50% { opacity: 0.4; }
        }

        /* ── TRUST BAR ─────────────────────────────────── */
        .trust {
            background: rgba(255, 255, 255, 0.04);
            border-top: 1px solid rgba(143, 163, 217, 0.15);
            border-bottom: 1px solid rgba(143, 163, 217, 0.15);
            padding: 22px 24px;
        }
        .trust-inner {
            max-width: 1200px; margin: 0 auto;
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 28px;
        }
        .trust-item {
            display: flex; align-items: center; gap: 12px;
            justify-content: center;
        }
        .trust-icon {
            width: 36px; height: 36px; border-radius: 8px;
            background: rgba(143, 163, 217, 0.12);
            display: flex; align-items: center; justify-content: center;
            color: var(--platinum); flex-shrink: 0;
        }
        .trust-text {
            font-size: 12px; color: #c7d4f5; line-height: 1.3;
        }
        .trust-text strong { display: block; color: #fff; font-size: 13px; font-weight: 700; margin-bottom: 1px; }

        /* ── KPI BAR ───────────────────────────────────── */
        .kpi-bar {
            background: linear-gradient(180deg, #14265a 0%, #0f1d42 100%);
            padding: 40px 24px;
        }
        .kpi-grid {
            max-width: 1200px; margin: 0 auto;
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
            gap: 24px;
        }
        .kpi { text-align: center; }
        .kpi-k {
            font-size: 10px; text-transform: uppercase; letter-spacing: 1.6px;
            color: var(--platinum); font-weight: 700; margin-bottom: 8px;
        }
        .kpi-v {
            font-size: 28px; font-weight: 800; color: #fff; line-height: 1.05;
            letter-spacing: -0.5px;
        }
        .kpi-v.green { color: #4ade80; }
        .kpi-v.orange { color: #fdba74; }
        .kpi-s { font-size: 11px; color: #94a3b8; margin-top: 6px; }

        /* ── CONTENEDOR PRINCIPAL ──────────────────────── */
        .container {
            max-width: 1100px; margin: 0 auto;
            padding: 80px 24px;
        }

        .section-title {
            font-size: 11px; text-transform: uppercase; letter-spacing: 2.5px;
            color: var(--orange); font-weight: 800; margin-bottom: 12px;
        }
        .section-h {
            font-size: clamp(28px, 4vw, 42px); font-weight: 800; color: #fff;
            letter-spacing: -0.8px; margin-bottom: 36px; line-height: 1.1;
        }

        /* ── VEREDICTO ─────────────────────────────────── */
        .verdict {
            background:
                radial-gradient(ellipse at top right, rgba(232, 89, 12, 0.2) 0%, transparent 50%),
                linear-gradient(135deg, rgba(232, 89, 12, 0.15) 0%, rgba(26, 48, 109, 0.5) 100%);
            border: 1px solid rgba(232, 89, 12, 0.4);
            border-radius: 24px;
            padding: 44px 44px;
            position: relative;
            overflow: hidden;
            margin-bottom: 60px;
        }
        .verdict::before {
            content: '“'; position: absolute;
            top: -20px; left: 16px;
            font-size: 180px; font-weight: 900;
            color: rgba(232, 89, 12, 0.2); line-height: 1;
            font-family: Georgia, serif;
        }
        .verdict-eyebrow {
            font-size: 11px; text-transform: uppercase; letter-spacing: 2.5px;
            color: var(--orange); font-weight: 800; margin-bottom: 12px;
            position: relative;
        }
        .verdict-h {
            font-size: clamp(24px, 3.2vw, 36px); font-weight: 800; color: #fff;
            margin-bottom: 18px; line-height: 1.15;
            position: relative;
        }
        .verdict-body { color: #d1d5db; font-size: 16px; line-height: 1.65; position: relative; }
        .verdict-footer {
            display: flex; align-items: center; gap: 12px;
            margin-top: 28px; padding-top: 22px;
            border-top: 1px solid rgba(255, 255, 255, 0.1);
            font-size: 13px; color: #94a3b8;
            position: relative;
        }

        /* ── INCLUYE ───────────────────────────────────── */
        .incluye {
            background: linear-gradient(135deg, rgba(16, 185, 129, 0.1) 0%, rgba(26, 48, 109, 0.2) 100%);
            border: 1px solid rgba(16, 185, 129, 0.3);
            border-radius: 20px;
            padding: 36px 36px;
            margin-bottom: 60px;
        }
        .incluye h3 {
            font-size: 22px; font-weight: 800; color: #fff;
            margin-bottom: 24px; display: flex; align-items: center; gap: 10px;
        }
        .incluye h3::before {
            content: '✓'; color: var(--green); font-size: 26px;
            background: rgba(16, 185, 129, 0.15);
            width: 40px; height: 40px; border-radius: 50%;
            display: inline-flex; align-items: center; justify-content: center;
        }
        .incluye-grid {
            display: grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
            gap: 18px;
        }
        .incluye-item {
            display: flex; gap: 12px;
            font-size: 14px; color: #e5e7eb;
        }
        .incluye-item strong {
            display: block; color: #fff; font-size: 14px; margin-bottom: 2px;
        }
        .incluye-check {
            width: 22px; height: 22px; border-radius: 50%;
            background: rgba(16, 185, 129, 0.2);
            color: var(--green); flex-shrink: 0;
            display: flex; align-items: center; justify-content: center;
            font-size: 12px; font-weight: 700;
        }

        /* ── GALERÍA ───────────────────────────────────── */
        .gallery-wrap { margin-bottom: 80px; }
        .gallery {
            display: grid; grid-template-columns: repeat(3, 1fr);
            gap: 14px;
        }
        .gallery .shot {
            border-radius: 14px; overflow: hidden;
            border: 1px solid rgba(143, 163, 217, 0.18);
            background: #14265a;
            aspect-ratio: 4/3;
            cursor: pointer;
            transition: transform 0.25s ease, box-shadow 0.25s ease;
        }
        .gallery .shot:hover {
            transform: translateY(-4px);
            box-shadow: 0 16px 40px rgba(0, 0, 0, 0.5);
        }
        .gallery .shot img { width: 100%; height: 100%; object-fit: cover; }
        .gallery .shot:first-child { grid-column: span 2; grid-row: span 2; aspect-ratio: 16/10; }

        /* ── PROS / CONS ──────────────────────────────── */
        .proscons {
            display: grid; grid-template-columns: 1fr 1fr; gap: 18px;
            margin-bottom: 80px;
        }
        .pc-col {
            border-radius: 18px; padding: 30px 32px;
            border: 1px solid rgba(143, 163, 217, 0.25);
            background: rgba(143, 163, 217, 0.05);
        }
        .pc-col.pros {
            background: linear-gradient(135deg, rgba(16, 185, 129, 0.12) 0%, rgba(16, 185, 129, 0.02) 100%);
            border-color: rgba(16, 185, 129, 0.35);
        }
        .pc-col.cons {
            background: linear-gradient(135deg, rgba(232, 89, 12, 0.1) 0%, rgba(232, 89, 12, 0.02) 100%);
            border-color: rgba(232, 89, 12, 0.35);
        }
        .pc-col h3 {
            font-size: 18px; font-weight: 800; color: #fff;
            margin-bottom: 20px; display: flex; align-items: center; gap: 10px;
        }
        .pc-col.pros h3::before {
            content: '✓'; color: #4ade80; font-size: 24px;
            width: 36px; height: 36px; border-radius: 50%;
            background: rgba(16, 185, 129, 0.2);
            display: inline-flex; align-items: center; justify-content: center;
        }
        .pc-col.cons h3::before {
            content: '!'; color: #fdba74; font-size: 20px; font-weight: 900;
            width: 36px; height: 36px; border-radius: 50%;
            background: rgba(232, 89, 12, 0.2);
            display: inline-flex; align-items: center; justify-content: center;
        }
        .pc-col ul { list-style: none; }
        .pc-col li {
            font-size: 14px; color: #e5e7eb; line-height: 1.55;
            padding: 8px 0;
            display: flex; gap: 10px; align-items: flex-start;
            border-bottom: 1px solid rgba(255, 255, 255, 0.05);
        }
        .pc-col li:last-child { border-bottom: none; }
        .pc-col.pros li::before {
            content: '+'; color: var(--green); font-weight: 700;
            font-size: 18px; line-height: 1; margin-top: 1px;
        }
        .pc-col.cons li::before {
            content: '−'; color: var(--orange); font-weight: 700;
            font-size: 18px; line-height: 1; margin-top: 1px;
        }

        /* ── ESPECIFICACIONES ─────────────────────────── */
        .specs { margin-bottom: 80px; }
        .specs-grid {
            background: rgba(143, 163, 217, 0.05);
            border: 1px solid rgba(143, 163, 217, 0.2);
            border-radius: 20px;
            padding: 32px 36px;
            display: grid; grid-template-columns: 1fr 1fr; gap: 0 40px;
        }
        .spec-row {
            display: flex; justify-content: space-between; align-items: baseline;
            gap: 12px; padding: 14px 0;
            border-bottom: 1px dashed rgba(143, 163, 217, 0.15);
        }
        .spec-row:last-child { border-bottom: none; }
        .spec-row .k {
            font-size: 11px; text-transform: uppercase; letter-spacing: 1.2px;
            color: var(--platinum); font-weight: 600;
        }
        .spec-row .v {
            font-size: 14px; color: #fff; font-weight: 700;
            text-align: right;
        }

        /* ── EQUIPAMIENTO ─────────────────────────────── */
        .equip {
            display: grid; grid-template-columns: 1fr 1fr; gap: 6px 32px;
            margin-bottom: 80px;
        }
        .equip-item {
            display: flex; align-items: flex-start; gap: 10px;
            font-size: 14px; color: #e5e7eb;
            padding: 8px 0;
        }
        .equip-item::before {
            content: '✓'; color: var(--green); font-weight: 700;
            flex-shrink: 0;
            background: rgba(16, 185, 129, 0.15);
            width: 18px; height: 18px; border-radius: 50%;
            display: inline-flex; align-items: center; justify-content: center;
            font-size: 11px;
            margin-top: 2px;
        }

        /* ── VEREDICTO: punto de color según semáforo ─── */
        .verdict-dot {
            display: inline-block; width: 13px; height: 13px; border-radius: 50%;
            margin-right: 10px; vertical-align: middle;
            box-shadow: 0 0 12px currentColor;
        }

        /* ── ¿POR QUÉ ESTE COCHE? ──────────────────────── */
        .why {
            background: rgba(143, 163, 217, 0.05);
            border-left: 3px solid var(--orange);
            border-radius: 0 18px 18px 0;
            padding: 32px 36px;
            margin-bottom: 60px;
        }
        .why-body { color: #e5e7eb; font-size: 16px; line-height: 1.65; }

        /* ── COMPARATIVA DE MERCADO ────────────────────── */
        .market { margin-bottom: 80px; }
        .market-grid {
            display: grid; grid-template-columns: repeat(auto-fit, minmax(160px, 1fr));
            gap: 14px;
        }
        .market-box {
            background: rgba(143, 163, 217, 0.05);
            border: 1px solid rgba(143, 163, 217, 0.2);
            border-radius: 14px; padding: 22px; text-align: center;
        }
        .market-box .k {
            font-size: 10px; text-transform: uppercase; letter-spacing: 1px;
            color: var(--platinum); font-weight: 700; margin-bottom: 8px;
        }
        .market-box .v { font-size: 22px; font-weight: 800; color: #fff; }
        .market-box.highlight { background: rgba(16, 185, 129, 0.12); border-color: rgba(16, 185, 129, 0.35); }
        .market-box.highlight .v { color: #4ade80; }

        /* ── INVERSIÓN ESTIMADA (C3 auditoría 09-sep-2026) ── */
        .precio-cliente { margin-bottom: 80px; }
        .precio-grid {
            display: grid; grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
            gap: 14px; margin-bottom: 18px;
        }
        .precio-box {
            background: rgba(143, 163, 217, 0.05);
            border: 1px solid rgba(143, 163, 217, 0.22);
            border-radius: 16px; padding: 22px 24px; text-align: center;
        }
        .precio-box .k {
            font-size: 10px; text-transform: uppercase; letter-spacing: 1.4px;
            color: var(--platinum); font-weight: 700; margin-bottom: 8px;
        }
        .precio-box .v { font-size: 24px; font-weight: 800; color: #fff; }
        .precio-box.precio-total {
            background: linear-gradient(135deg, rgba(16,185,129,0.14) 0%, rgba(16,185,129,0.04) 100%);
            border-color: rgba(16,185,129,0.4);
        }
        .precio-box.precio-total .v { color: #4ade80; }
        .precio-caption {
            font-size: 14px; color: var(--platinum-2);
            margin-top: 6px; font-weight: 500;
        }
        .precio-desglose { margin-top: 16px; }
        .precio-desglose summary {
            cursor: pointer; font-size: 13px; color: var(--platinum-2);
            font-weight: 600; padding: 10px 0; list-style: none;
        }
        .precio-desglose summary::-webkit-details-marker { display: none; }
        .precio-desglose ul {
            list-style: none; margin-top: 10px; padding: 14px 18px;
            background: rgba(143,163,217,0.05); border-radius: 12px;
        }
        .precio-desglose li {
            display: flex; justify-content: space-between;
            padding: 6px 0; border-bottom: 1px solid rgba(255,255,255,0.04);
            font-size: 13px; color: #cbd5e1;
        }
        .precio-desglose li:last-child { border-bottom: none; }

        /* ── TIPS ─────────────────────────────────────── */
        .tips {
            background: rgba(26, 48, 109, 0.4);
            border: 1px solid rgba(143, 163, 217, 0.25);
            border-radius: 18px;
            padding: 32px 36px;
            margin-bottom: 80px;
        }
        .tips h3 {
            font-size: 20px; font-weight: 800; color: #fff; margin-bottom: 20px;
            display: flex; align-items: center; gap: 10px;
        }
        .tips h3::before { content: '💡'; font-size: 24px; }
        .tips ul { list-style: none; }
        .tips li {
            font-size: 14px; color: #e5e7eb; line-height: 1.6;
            padding: 8px 0;
            display: flex; gap: 12px; align-items: flex-start;
            border-bottom: 1px solid rgba(255, 255, 255, 0.05);
        }
        .tips li:last-child { border-bottom: none; }
        .tips li::before {
            content: '→'; color: var(--orange); font-weight: 700; margin-top: 2px;
        }

        /* ── CTA FINAL ────────────────────────────────── */
        .cta-final {
            background:
                radial-gradient(ellipse at top, rgba(232, 89, 12, 0.25) 0%, transparent 50%),
                linear-gradient(180deg, #0f1d42 0%, #0a1535 100%);
            border-radius: 28px;
            padding: 60px 44px;
            text-align: center;
            border: 1px solid rgba(255, 255, 255, 0.08);
            position: relative;
            overflow: hidden;
            margin-bottom: 80px;
        }
        .cta-final::before {
            content: ''; position: absolute; inset: 0;
            background: radial-gradient(circle at 50% 0%, rgba(232, 89, 12, 0.15) 0%, transparent 40%);
        }
        .cta-final > { position: relative; }
        .cta-eyebrow {
            font-size: 11px; text-transform: uppercase; letter-spacing: 2.5px;
            color: var(--orange); font-weight: 800;
            margin-bottom: 14px;
            position: relative;
        }
        .cta-final h2 {
            font-size: clamp(28px, 4vw, 40px); font-weight: 800; color: #fff;
            margin-bottom: 14px; line-height: 1.15;
            position: relative;
        }
        .cta-final p {
            color: #c7d4f5; font-size: 16px; margin-bottom: 32px;
            max-width: 540px; margin-left: auto; margin-right: auto;
            line-height: 1.55;
            position: relative;
        }
        .cta-buttons {
            display: flex; gap: 14px; justify-content: center; flex-wrap: wrap;
            position: relative;
        }
        .btn.big { padding: 16px 28px; font-size: 15px; }

        /* ── FOOTER ───────────────────────────────────── */
        footer {
            padding: 50px 24px 36px;
            background: linear-gradient(180deg, transparent 0%, rgba(0, 0, 0, 0.4) 100%);
            text-align: center;
            font-size: 13px; color: #94a3b8;
            border-top: 1px solid rgba(143, 163, 217, 0.15);
        }
        footer .brand { display: flex; align-items: center; justify-content: center; gap: 12px; margin-bottom: 12px; }
        footer .brand-text { font-weight: 800; color: #fff; font-size: 15px; letter-spacing: 0.3px; }
        footer .slogan { font-size: 12px; color: var(--platinum); margin-bottom: 22px; letter-spacing: 0.5px; }
        footer .links {
            display: flex; gap: 20px; justify-content: center; flex-wrap: wrap;
            font-size: 13px; color: #c7d4f5;
        }
        footer .links a { color: #c7d4f5; }
        footer .links a:hover { color: #fff; }
        footer .copy { margin-top: 28px; padding-top: 22px; border-top: 1px solid rgba(143, 163, 217, 0.1); font-size: 11px; color: #64748b; }

        /* ── LIGHTBOX ─────────────────────────────────── */
        .lightbox {
            position: fixed; inset: 0; z-index: 100;
            background: rgba(0, 0, 0, 0.92); backdrop-filter: blur(8px);
            display: none; align-items: center; justify-content: center;
            padding: 24px;
        }
        .lightbox.open { display: flex; }
        .lightbox img {
            max-width: 92%; max-height: 88vh;
            border-radius: 12px;
            box-shadow: 0 30px 80px rgba(0, 0, 0, 0.6);
        }
        .lightbox-close, .lightbox-prev, .lightbox-next {
            position: absolute; background: rgba(255, 255, 255, 0.1);
            border: none; color: #fff;
            width: 44px; height: 44px; border-radius: 50%;
            cursor: pointer; font-size: 20px;
            display: flex; align-items: center; justify-content: center;
            transition: background 0.15s;
        }
        .lightbox-close:hover, .lightbox-prev:hover, .lightbox-next:hover {
            background: rgba(255, 255, 255, 0.2);
        }
        .lightbox-close { top: 24px; right: 24px; }
        .lightbox-prev { left: 24px; top: 50%; transform: translateY(-50%); }
        .lightbox-next { right: 24px; top: 50%; transform: translateY(-50%); }
        .lightbox-counter {
            position: absolute; bottom: 24px; left: 50%; transform: translateX(-50%);
            background: rgba(255, 255, 255, 0.1);
            padding: 8px 18px; border-radius: 100px;
            font-size: 13px; color: #fff;
        }

        /* ── RESPONSIVE ───────────────────────────────── */
        @media (max-width: 800px) {
            .nav-links { display: none; }
            .nav-cta span.long { display: none; }
            .hero { min-height: auto; padding: 40px 20px 56px; }
            .hero-inner { grid-template-columns: 1fr; gap: 32px; }
            .hero-photo { aspect-ratio: 16/10; transform: rotate(0); }
            .trust-inner { grid-template-columns: repeat(2, 1fr); gap: 16px; }
            .container { padding: 50px 20px; }
            .kpi-bar { padding: 32px 20px; }
            .gallery { grid-template-columns: repeat(2, 1fr); }
            .gallery .shot:first-child { grid-column: span 2; grid-row: span 1; aspect-ratio: 16/10; }
            .proscons { grid-template-columns: 1fr; }
            .specs-grid { grid-template-columns: 1fr; padding: 22px; }
            .equip { grid-template-columns: 1fr; }
            .verdict { padding: 28px 22px; }
            .incluye { padding: 26px 22px; }
            .why { padding: 22px 20px; }
            .market-grid { grid-template-columns: repeat(2, 1fr); }
            .tips { padding: 24px 22px; }
            .cta-final { padding: 40px 22px; }
            .price-value { font-size: 36px; }
            .h1 { font-size: 38px; }
        }
        @media (max-width: 480px) {
            .gallery { grid-template-columns: 1fr; }
            .gallery .shot:first-child { grid-column: span 1; }
            .trust-inner { grid-template-columns: 1fr; }
        }
    </style>
</head>
<body>

    {{-- ── STICKY NAV ─────────────────────────────────── --}}
    <nav class="nav">
        <div class="nav-inner">
            <div class="nav-brand">
                @if($logoBase64)
                    <img src="{{ $logoBase64 }}" alt="JJ Import Motors" class="nav-logo">
                @endif
            </div>
            <div class="nav-links">
                <a href="#galeria" class="nav-link">Galería</a>
                <a href="#veredicto" class="nav-link">Veredicto</a>
                <a href="#detalles" class="nav-link">Detalles</a>
            </div>
            <a href="tel:+34675701439" class="nav-cta">
                📞 <span class="long">675 70 14 39</span>
            </a>
        </div>
    </nav>

    {{-- ── HERO ────────────────────────────────────────── --}}
    @php
        $precio = $car->sale_price ?? $car->purchase_price ?? 0;
        $potencia = $esqueleto?->uno('POTENCIA');
        // ── Cambio: limpio, sin coletillas técnicas (NO mostramos "doble embrague", "DSG", "6 vel"...) ──
        $cambioRaw = $esqueleto?->uno('CAMBIO') ?? $car->transmission;
        $cambioTxt = $cambioRaw
            ? (str_contains(strtolower($cambioRaw), 'auto') || stripos($cambioRaw, 'DSG') !== false || stripos($cambioRaw, 'Tiptronic') !== false
                ? 'Automático' : 'Manual')
            : null;
        $kmTxt = $car->mileage ? number_format($car->mileage, 0, ',', '.').' km' : null;
        $anioTxt = $car->year ?: null;
        $claimParts = array_filter([
            $potencia,
            $kmTxt,
        ]);

        // ── Origen: España o Alemania. NO solo «importación»: también gestionamos compras en España ──
        $paisOrigen = strtolower((string) ($car->origin_country ?? ''));
        $esAlemania = str_contains($paisOrigen, 'alem') || $paisOrigen === 'de';
        $esEspana = str_contains($paisOrigen, 'espa') || $paisOrigen === 'es';
        $origenLabel = $esAlemania ? 'Importado desde Alemania' : ($esEspana ? 'Localizado en España' : 'Origen verificado');
        $origenSub = $esAlemania ? 'Historial completo y verificado' : 'Historial verificado';

        // ── Estado de gestión. NUNCA «EN STOCK»: no vendemos coches, gestionamos la compra ──
        $estadoLabels = [
            'Located' => 'Localizado', 'Valuing' => 'En valoración', 'Offered' => 'Oferta enviada',
            'Reserved' => 'Reservado para ti', 'Purchased' => 'Comprado', 'In_transit' => 'En tránsito',
            'Processing' => 'En trámites', 'Delivered' => 'Entregado',
        ];
        $estadoLabel = $estadoLabels[$car->status] ?? 'Disponible para gestión';

        // ── Contenido enriquecido: esqueleto (ficha-publicitaria.txt) con fallback a los datos de la IA del coche ──
        // A22b: el enlace es público. Todo lo que venga del ZIP o de la BD pasa por el filtro.
        $porqueTexto = \App\Support\FiltroPublico::texto($esqueleto?->uno('POR_QUE') ?: ($car->recommendation ?? ''));
        $valoracionTexto = \App\Support\FiltroPublico::texto($esqueleto?->uno('VALORACION') ?: ($car->valuation ?? ''));

        // El veredicto del semáforo es INTERNO (A22): al cliente solo le llega
        // nuestra valoración escrita, sin etiqueta ni nota.

        // ── Pros: solo lo bueno para el cliente. A1+A2 auditoría 09-sep-2026:
        //    el controller YA calcula $argumentosPublicos a partir de
        //    ficha-cliente.json (escrito para el cliente). NUNCA del bloque
        //    interno A_FAVOR del esqueleto técnico, porque ahí viajan
        //    "hueco de importación", "vendibilidad", "vendedor de origen",
        //    cifras en euros del mercado alemán, etc.
        $prosLista = $argumentosPublicos ?? [];

        // ── Ficha del cliente v2 (JSON del ZIP), con listas ya filtradas ──
        $fichaPendiente = $ficha['pendiente_comprobar'] ?? [];
        $fichaVerificado = $ficha['verificado'] ?? [];
        $fichaNoIncluye = $ficha['no_incluye'] ?? ['Seguro del vehículo', 'Impuesto municipal de circulación', 'Mantenimiento, reparaciones y desgaste', 'Garantía mecánica'];
        $fichaPasos = $ficha['pasos'] ?? [
            ['cuando' => 'Semana 0', 'que' => 'Reserva y bloqueo de la unidad con el vendedor'],
            ['cuando' => 'Semana 1', 'que' => 'Compra, documentación y preparación de la exportación'],
            ['cuando' => 'Semanas 2-3', 'que' => 'Transporte hasta España'],
            ['cuando' => 'Semanas 3-4', 'que' => 'ITV de importación, impuestos y matriculación'],
            ['cuando' => 'Semana 4', 'que' => 'Entrega, con el coche ya a tu nombre'],
        ];
        $fichaFaq = $ficha['faq'] ?? [
            ['pregunta' => '¿El coche es vuestro?', 'respuesta' => 'No. Nosotros gestionamos la compra: el vehículo se compra al vendedor y se matricula directamente a tu nombre.'],
            ['pregunta' => '¿Lleva garantía?', 'respuesta' => 'JJ Import Motors no ofrece garantía. La que pueda existir es la del vendedor, según la ley que le sea aplicable. Si quieres cobertura mecánica, se puede contratar aparte con una compañía especializada.'],
            ['pregunta' => '¿Qué pasa si al llegar no es como se dijo?', 'respuesta' => 'Antes de comprar se hace una inspección previa con fotos y vídeo. Si aparece algo que no encaja con lo publicado, te informamos y decides tú si se sigue adelante.'],
            ['pregunta' => '¿Cuánto tarda?', 'respuesta' => 'Entre tres y cinco semanas desde la reserva. Es una estimación: depende del transporte y de las citas de ITV.'],
            ['pregunta' => '¿Puedo verlo antes de comprarlo?', 'respuesta' => 'No somos concesionario y el coche no está en nuestras instalaciones. Puedes ir a verlo al vendedor o pedir la inspección previa con fotos y vídeo detallados.'],
            ['pregunta' => '¿Cómo se paga?', 'respuesta' => 'Con una reserva inicial para bloquear la unidad y el resto según el calendario acordado antes de empezar.'],
        ];
        $fechaDatos = $ficha['fecha_datos'] ?? now()->format('d/m/Y');

        // ── Comparativa de mercado ──
        $fmtEur = fn ($n) => $n !== null ? number_format((float) $n, 0, ',', '.').' €' : null;
        $marketMin = $fmtEur($car->market_min ?? null);
        $marketAvg = $fmtEur($car->market_avg ?? null);
        $marketMax = $fmtEur($car->market_max ?? null);
        $hayMercado = $marketMin || $marketAvg || $marketMax;
    @endphp
    <header class="hero">
        <div class="hero-inner">
            <div class="hero-left">
                <div class="hero-eyebrow">📋 Informe de oportunidad exclusivo</div>
                <h1 class="h1">
                    {{ $car->brand }}<br>
                    <span class="accent">{{ $car->model }}</span>
                </h1>
                @if(count($claimParts) > 0)
                    <p class="claim">{{ implode(' · ', $claimParts) }} · Verificado por nuestro equipo</p>
                @else
                    <p class="claim">Verificado por nuestro equipo</p>
                @endif

                <div class="price-card">
                    <div class="price-label">Precio total cliente</div>
                    <div class="price-value">{{ number_format($precio, 0, ',', '.') }} €</div>
                    <div class="price-caption">+ gastos gestión de compra</div>
                </div>

                <div class="hero-actions">
                    <a href="https://wa.me/34675701439?text={{ urlencode('Hola, me interesa el '.$car->brand.' '.$car->model.' que habéis compartido conmigo.') }}"
                       target="_blank" rel="noopener" class="btn primary">
                        💬 Quiero gestionar la compra
                    </a>
                    <a href="tel:+34675701439" class="btn ghost">
                        📞 Llamar ahora
                    </a>
                </div>
            </div>

            @if(count($fotos) > 0)
                <div class="hero-photo">
                    <img src="{{ $fotos[0] }}" alt="{{ $car->brand }} {{ $car->model }}">
                    <div class="hero-photo-badge live">{{ $estadoLabel }}</div>
                </div>
            @endif
        </div>
    </header>

    {{-- ── TRUST BAR ────────────────────────────────────── --}}
    <section class="trust">
        <div class="trust-inner">
            <div class="trust-item">
                <div class="trust-icon">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2"><path d="M12 22s-8-4.5-8-11.8A8 8 0 0112 2a8 8 0 018 8.2c0 7.3-8 11.8-8 11.8z"/><circle cx="12" cy="10" r="3"/></svg>
                </div>
                <div class="trust-text">
                    <strong>{{ $origenLabel }}</strong>
                    {{ $origenSub }}
                </div>
            </div>
            <div class="trust-item">
                <div class="trust-icon">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2"><path d="M9 12l2 2 4-4M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                </div>
                <div class="trust-text">
                    <strong>Historial verificado</strong>
                    Origen y kilometraje confirmados
                </div>
            </div>
            <div class="trust-item">
                <div class="trust-icon">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2"><path d="M3 9l9-7 9 7v11a2 2 0 01-2 2H5a2 2 0 01-2-2z"/><polyline points="9,22 9,12 15,12 15,22"/></svg>
                </div>
                <div class="trust-text">
                    <strong>Tramitación completa</strong>
                    ITV, COC, DGT y gestoría
                </div>
            </div>
            <div class="trust-item">
                <div class="trust-icon">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2"><path d="M12 1v22M17 5H9.5a3.5 3.5 0 000 7h5a3.5 3.5 0 010 7H6"/></svg>
                </div>
                <div class="trust-text">
                    <strong>Gestión integral</strong>
                    Compra, transporte y trámites incluidos
                </div>
            </div>
        </div>
    </section>

    {{-- ── KPI BAR ─────────────────────────────────────── --}}
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

    {{-- ── CONTENIDO PRINCIPAL ─────────────────────────── --}}
    <main class="container">

        {{-- NUESTRA VALORACIÓN (sin veredicto interno ni nota: A22) --}}
        @if($valoracionTexto)
            <section id="veredicto" class="verdict reveal">
                <div class="verdict-eyebrow">Nuestra valoración</div>
                <h2 class="verdict-h">Qué nos parece esta unidad</h2>
                <p class="verdict-body">{!! \App\Support\Esqueleto::negrita($valoracionTexto) !!}</p>
                <div class="verdict-footer">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><polyline points="12,6 12,12 16,14"/></svg>
                    Datos del vehículo comprobados el {{ $fechaDatos }}
                </div>
            </section>
        @endif

        {{-- ¿POR QUÉ ESTE COCHE? --}}
        @if($porqueTexto)
            <section class="why">
                <div class="section-title">¿Por qué este coche?</div>
                <p class="why-body">{!! \App\Support\Esqueleto::negrita($porqueTexto) !!}</p>
            </section>
        @endif

        {{-- C3 auditoría 09-sep-2026: precio origen + gastos de compra.
             El cliente ve "qué cuesta el coche + qué cuesta traerlo" SIN
             desglose de margen, con aviso de que el total es estimación
             y se confirma por escrito antes de la reserva. --}}
        @if(!empty($precioCliente['origen']))
            @php
                $pc = $precioCliente;
                $fmtEurPc = fn ($n) => $n !== null ? number_format((float) $n, 0, ',', '.').' €' : null;
                $hayDesglose = !empty($pc['desglose']) && count($pc['desglose']) > 1;
            @endphp
            <section class="precio-cliente">
                <div class="section-title">Inversión estimada</div>
                <h2 class="section-h">Qué pagarías por este coche</h2>
                <div class="precio-grid">
                    <div class="precio-box"><div class="k">Precio del anuncio</div><div class="v">{{ $fmtEurPc($pc['origen']) }}</div></div>
                    @if($pc['gastos'] !== null)
                        <div class="precio-box"><div class="k">Gastos de compra</div><div class="v">{{ $fmtEurPc($pc['gastos']) }}</div></div>
                        <div class="precio-box precio-total"><div class="k">Total estimado</div><div class="v">{{ $fmtEurPc($pc['total_estimado']) }}</div></div>
                    @endif
                </div>
                @if($hayDesglose)
                    <details class="precio-desglose">
                        <summary>Ver desglose de gastos</summary>
                        <ul>
                            @foreach($pc['desglose'] as $concepto => $valor)
                                @if($concepto !== 'Precio del anuncio')
                                    <li><span>{{ $concepto }}</span><span>{{ $fmtEurPc($valor) }}</span></li>
                                @endif
                            @endforeach
                        </ul>
                    </details>
                @endif
                <p class="precio-caption">+ gastos gestión de compra (transporte + IVA + IEDMT + gestoría)</p>
                <p class="nota-fina">{{ $pc['aviso_precio_final'] }}</p>
            </section>
        @endif

        {{-- COMPARATIVA DE MERCADO --}}
        @if($hayMercado)
            <section class="market">
                <div class="section-title">Comparativa de mercado</div>
                <h2 class="section-h">Así se compara este precio</h2>
                <div class="market-grid">
                    @if($marketMin)
                        <div class="market-box"><div class="k">Mínimo mercado</div><div class="v">{{ $marketMin }}</div></div>
                    @endif
                    @if($marketAvg)
                        <div class="market-box"><div class="k">Precio medio</div><div class="v">{{ $marketAvg }}</div></div>
                    @endif
                    @if($marketMax)
                        <div class="market-box"><div class="k">Máximo mercado</div><div class="v">{{ $marketMax }}</div></div>
                    @endif
                </div>
                <p class="nota-fina">Rango de precios de unidades similares publicadas en España a fecha de {{ $fechaDatos }}. Es una referencia de mercado, no una promesa de ahorro.</p>
            </section>
        @endif

        {{-- INCLUYE (lo que va incluido en el precio) --}}
        <section class="incluye">
            <h3>Qué incluye este precio</h3>
            <div class="incluye-grid">
                <div class="incluye-item">
                    <span class="incluye-check">✓</span>
                    <div>
                        <strong>Búsqueda y verificación</strong>
                        Inspección física, documental y de mercado
                    </div>
                </div>
                <div class="incluye-item">
                    <span class="incluye-check">✓</span>
                    <div>
                        <strong>Gestión de la compra</strong>
                        Negociación con el vendedor y pago seguro
                    </div>
                </div>
                <div class="incluye-item">
                    <span class="incluye-check">✓</span>
                    <div>
                        <strong>Transporte a España</strong>
                        Logística y seguimiento del envío
                    </div>
                </div>
                <div class="incluye-item">
                    <span class="incluye-check">✓</span>
                    <div>
                        <strong>Trámites de matriculación</strong>
                        ITV, COC, DGT y gestoría completa
                    </div>
                </div>
                <div class="incluye-item">
                    <span class="incluye-check">✓</span>
                    <div>
                        <strong>Historial verificado</strong>
                        Origen y kilometraje confirmados
                    </div>
                </div>
                <div class="incluye-item">
                    <span class="incluye-check">✓</span>
                    <div>
                        <strong>Entrega en tu domicilio</strong>
                        Huelva y provincia
                    </div>
                </div>
            </div>
        </section>

                {{-- PUNTOS A FAVOR (al cliente solo lo bueno) --}}
        @if(count($prosLista) > 0)
            <section>
                <div class="section-title">Puntos a favor</div>
                <h2 class="section-h">Por qué destaca este coche</h2>
                <div class="proscons">
                    <div class="pc-col pros" style="grid-column: 1 / -1;">
                        <h3>Lo que hace fuerte a esta unidad</h3>
                        <ul>
                            @foreach($prosLista as $item)
                                <li>{{ $item }}</li>
                            @endforeach
                        </ul>
                    </div>
                </div>
            </section>
        @endif

        {{-- ESPECIFICACIONES --}}
        @php
            // 1) Lo mejor: la ficha del cliente v2 (contenido/json/ficha-cliente.json),
            //    que trae los 16 campos ya tipados como {etiqueta, valor}.
            $specRows = [];
            foreach (($ficha['spec'] ?? []) as $fila) {
                if (is_array($fila) && ($fila['etiqueta'] ?? '') !== '') {
                    $specRows[] = ['k' => $fila['etiqueta'], 'v' => $fila['valor'] ?? ''];
                }
            }

            // 2) Si el coche es anterior a la ficha v2: los pares [SPEC] Etiqueta | Valor
            //    de ficha-publicitaria.txt, que es lo que genera empaquetar.py.
            //    (Antes se buscaban bloques [MARCA]/[MODELO]… que nunca han existido:
            //     por eso la ficha técnica no salía en el enlace.)
            if (empty($specRows) && $esqueleto) {
                foreach ($esqueleto->todos('SPEC') as $par) {
                    $trozos = array_map('trim', explode('|', (string) $par, 2));
                    if (count($trozos) === 2 && $trozos[0] !== '' && $trozos[1] !== '') {
                        $specRows[] = ['k' => $trozos[0], 'v' => $trozos[1]];
                    }
                }
            }

            // 3) Último recurso: lo que haya en el propio coche.
            if (empty($specRows)) {
                $fallback = [
                    'Marca' => $car->brand, 'Modelo' => $car->model,
                    'Año' => $car->year, 'Kilómetros' => $car->mileage ? number_format($car->mileage, 0, ',', '.').' km' : null,
                    'Combustible' => $car->fuel ?? null, 'Cambio' => $car->transmission ?? null,
                    'Versión' => $car->version ?? null, 'Tracción' => $car->drivetrain ?? null,
                ];
                foreach ($fallback as $k => $v) {
                    if ($v) $specRows[] = ['k' => $k, 'v' => $v];
                }
            }
        @endphp
        @if(count($specRows) > 0)
            <section id="detalles" class="specs">
                <div class="section-title">Ficha técnica</div>
                <h2 class="section-h">Detalles del vehículo</h2>
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

        {{-- EQUIPAMIENTO --}}
        @if($esqueleto)
            @php $equipamiento = $esqueleto->lista('EQUIPAMIENTO'); @endphp
            @if(count($equipamiento) > 0)
                <section>
                    <div class="section-title">Equipamiento</div>
                    <h2 class="section-h">Extras y opciones destacadas</h2>
                    <div class="equip">
                        @foreach($equipamiento as $item)
                            <div class="equip-item">{{ $item }}</div>
                        @endforeach
                    </div>
                </section>
            @endif
        @endif

        {{-- ESTADO: VERIFICADO Y PENDIENTE (la honestidad sostiene la ficha) --}}
        @if(count($fichaVerificado) > 0 || count($fichaPendiente) > 0)
            <section class="reveal">
                <div class="section-title">Estado</div>
                <h2 class="section-h">Qué está comprobado y qué queda por comprobar</h2>
                <div class="dos-col">
                    @if(count($fichaVerificado) > 0)
                        <ul class="lista-limpia lista-ok">
                            @foreach($fichaVerificado as $item)
                                <li>{{ is_string($item) ? $item : '' }}</li>
                            @endforeach
                        </ul>
                    @endif
                    @if(count($fichaPendiente) > 0)
                        <ul class="lista-limpia lista-pend">
                            @foreach($fichaPendiente as $item)
                                <li>{{ is_string($item) ? $item : '' }}</li>
                            @endforeach
                        </ul>
                    @endif
                </div>
            </section>
        @endif

{{-- GALERÍA --}}
        @if(count($fotos) > 1)
            @php
                $galleryCols = min(4, max(1, count($fotos)));
            @endphp
            <section id="galeria" class="gallery-wrap">
                <div class="section-title">Galería</div>
                <h2 class="section-h">Fotos reales del vehículo</h2>
                <div class="gallery {{ $galleryCols === 4 ? 'four' : ($galleryCols === 3 ? 'three' : ($galleryCols === 2 ? 'two' : 'one')) }}" id="gallery">
                    @foreach(array_slice($fotos, 0, 8) as $i => $foto)
                        <div class="shot" data-index="{{ $i }}" onclick="openLightbox({{ $i }})">
                            <img src="{{ $foto }}" alt="{{ $car->brand }} {{ $car->model }} — foto {{ $i+1 }}" loading="{{ $i === 0 ? 'eager' : 'lazy' }}" decoding="async">
                        </div>
                    @endforeach
                </div>
                @if(count($fotos) > 8)
                    {{-- El resto no se carga hasta que se abre: 30 fotos de golpe entierran el resto de la ficha --}}
                    <details id="galeria-resto">
                        <summary class="ver-todas">Ver las {{ count($fotos) }} fotos</summary>
                        <div class="gallery {{ $galleryCols === 4 ? 'four' : 'three' }}" style="margin-top:14px">
                            @foreach(array_slice($fotos, 8, null, true) as $i => $foto)
                                <div class="shot" data-index="{{ $i }}" onclick="openLightbox({{ $i }})">
                                    <img src="{{ $foto }}" alt="{{ $car->brand }} {{ $car->model }} — foto {{ $i+1 }}" loading="lazy" decoding="async">
                                </div>
                            @endforeach
                        </div>
                    </details>
                @endif
            </section>
        @endif


        {{-- QUÉ NO INCLUYE --}}
        <section class="reveal">
            <div class="section-title">Transparencia</div>
            <h2 class="section-h">Qué no incluye</h2>
            <ul class="lista-limpia lista-no">
                @foreach($fichaNoIncluye as $item)
                    <li>{{ is_string($item) ? $item : '' }}</li>
                @endforeach
            </ul>
        </section>

        {{-- CÓMO FUNCIONA --}}
        <section class="reveal">
            <div class="section-title">El proceso</div>
            <h2 class="section-h">Cómo funciona</h2>
            <div class="pasos">
                @foreach($fichaPasos as $paso)
                    <div class="paso">
                        <b>{{ is_array($paso) ? ($paso['cuando'] ?? '') : '' }}</b>
                        {{ is_array($paso) ? ($paso['que'] ?? '') : (string) $paso }}
                    </div>
                @endforeach
            </div>
            <p class="nota-fina">Plazos estimados: dependen del transporte y de las citas de ITV.</p>
        </section>

        {{-- QUÉ HACEMOS Y QUÉ NO — bloque fijo (A31, .ai/rules/business-model.md) --}}
        <section class="aviso-legal reveal">
            <div class="section-title">Importante</div>
            <h2 class="section-h">Qué hacemos y qué no hacemos</h2>
            <div class="dos-col">
                <ul class="lista-limpia lista-ok">
                    <li>Localizamos la unidad y comprobamos su historial y su documentación</li>
                    <li>Negociamos y coordinamos la compra con el vendedor</li>
                    <li>Organizamos el transporte hasta España</li>
                    <li>Tramitamos la ITV de importación, los impuestos y la matriculación</li>
                    <li>Te acompañamos hasta que el coche está a tu nombre</li>
                </ul>
                <ul class="lista-limpia lista-no">
                    <li><strong>No vendemos coches:</strong> JJ Import Motors no es el vendedor ni el propietario del vehículo. La compraventa es entre el vendedor y tú.</li>
                    <li>No respondemos de averías, desgastes o defectos que no sean visibles en la documentación y en la inspección previa.</li>
                    <li>No hacemos mantenimiento ni reparaciones, ni ofrecemos financiación.</li>
                </ul>
            </div>
            <div class="titular" style="margin-top:18px">JJ Import Motors no ofrece garantía de ningún tipo sobre el vehículo.</div>
            <p>Cualquier garantía o responsabilidad que exista corresponde al vendedor, según la ley que le sea aplicable. Nuestro servicio es la gestión de la búsqueda, la verificación y la importación, con honorarios acordados de antemano. Si quieres cobertura mecánica, puede contratarse aparte con una compañía especializada.</p>
        </section>

        {{-- PREGUNTAS FRECUENTES --}}
        <section class="faq reveal">
            <div class="section-title">Dudas</div>
            <h2 class="section-h">Preguntas frecuentes</h2>
            @foreach($fichaFaq as $i => $item)
                <details @if($i === 0) open @endif>
                    <summary>{{ is_array($item) ? ($item['pregunta'] ?? '') : '' }}</summary>
                    <p>{{ is_array($item) ? ($item['respuesta'] ?? '') : '' }}</p>
                </details>
            @endforeach
        </section>

        {{-- CTA FINAL --}}
        <section class="cta-final">
            <div class="cta-eyebrow">¿Seguimos adelante?</div>
            <h2>Gestionamos la compra de este coche por ti</h2>
            <p>Escríbenos por WhatsApp o llama. Te explicamos el proceso completo: búsqueda, verificación, compra, transporte y trámites.</p>
            <div class="cta-buttons">
                <a href="https://wa.me/34675701439?text={{ urlencode('Hola, me interesa el '.$car->brand.' '.$car->model.'. ¿Podemos hablar sobre el proceso de compra?') }}"
                   target="_blank" rel="noopener" class="btn primary big">
                    💬 Hablar por WhatsApp
                </a>
                <a href="tel:+34675701439" class="btn ghost big">
                    📞 675 70 14 39
                </a>
            </div>
        </section>

    </main>

    {{-- ── FOOTER ──────────────────────────────────────── --}}
    <footer>
        <div class="brand">
            @if($logoBase64)
                <img src="{{ $logoBase64 }}" alt="JJ Import Motors" style="height:32px;">
            @endif
            <span class="brand-text">JJ Import Motors</span>
        </div>
        <div class="slogan">Gestión de compra e importación de vehículos · España y Alemania</div>
        <div class="links">
            <a href="mailto:jjimportmotors@gmail.com">jjimportmotors@gmail.com</a>
            <span style="opacity: 0.4;">·</span>
            <a href="tel:+34675701439">+34 675 70 14 39</a>
            <span style="opacity: 0.4;">·</span>
            <a href="tel:+34691485927">+34 691 48 59 27</a>
            <span style="opacity: 0.4;">·</span>
            <span>Huelva, España</span>
        </div>
        <div class="copy">
            JJ Import Motors (Huelva) presta un servicio de gestión de búsqueda, compra e importación de vehículos.
            No es vendedora ni propietaria del vehículo y no ofrece garantía sobre él.
            El precio indicado es el del vehículo; a él se suman los gastos de gestión de compra.
            Disponibilidad y precio sujetos a confirmación en el momento de la reserva.
            <br><br>
            © {{ date('Y') }} JJ Import Motors · Datos del vehículo comprobados el {{ $fechaDatos }}
        </div>
    </footer>

    <script>
    (function () {
        var quietud = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
        var nativo = window.CSS && CSS.supports && CSS.supports('animation-timeline: view()');
        if (nativo || quietud || !('IntersectionObserver' in window)) return;
        var els = document.querySelectorAll('.reveal');
        els.forEach(function (el) { el.classList.add('js-oculto'); });
        var io = new IntersectionObserver(function (entradas) {
            entradas.forEach(function (e) {
                if (!e.isIntersecting) return;
                e.target.classList.remove('js-oculto');
                e.target.classList.add('js-visible');
                io.unobserve(e.target);
            });
        }, { rootMargin: '0px 0px -10% 0px', threshold: 0.08 });
        els.forEach(function (el) { io.observe(el); });
    })();
    </script>

    {{-- ── LIGHTBOX ────────────────────────────────────── --}}
    @if(count($fotos) > 1)
    <div class="lightbox" id="lightbox">
        <button class="lightbox-close" onclick="closeLightbox()" aria-label="Cerrar">×</button>
        <button class="lightbox-prev" onclick="prevPhoto()" aria-label="Anterior">‹</button>
        <button class="lightbox-next" onclick="nextPhoto()" aria-label="Siguiente">›</button>
        <img id="lightbox-img" src="" alt="">
        <div class="lightbox-counter" id="lightbox-counter"></div>
    </div>
    <script>
        (function() {
            const fotos = @json($fotos);
            let idx = 0;
            const lb = document.getElementById('lightbox');
            const img = document.getElementById('lightbox-img');
            const cnt = document.getElementById('lightbox-counter');

            window.openLightbox = function(i) {
                idx = i;
                show();
            };
            window.closeLightbox = function() {
                lb.classList.remove('open');
                document.body.style.overflow = '';
            };
            window.nextPhoto = function() {
                idx = (idx + 1) % fotos.length;
                show();
            };
            window.prevPhoto = function() {
                idx = (idx - 1 + fotos.length) % fotos.length;
                show();
            };
            function show() {
                img.src = fotos[idx];
                cnt.textContent = (idx + 1) + ' / ' + fotos.length;
                lb.classList.add('open');
                document.body.style.overflow = 'hidden';
            }

            document.addEventListener('keydown', function(e) {
                if (!lb.classList.contains('open')) return;
                if (e.key === 'Escape') closeLightbox();
                if (e.key === 'ArrowLeft') prevPhoto();
                if (e.key === 'ArrowRight') nextPhoto();
            });
            lb.addEventListener('click', function(e) {
                if (e.target === lb) closeLightbox();
            });
        })();
    </script>
    @endif

</body>
</html>
