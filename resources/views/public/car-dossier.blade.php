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
            /* 12-sep-2026: estaba en `center`, así que cada fila centraba el
               grupo icono+texto y, al medir distinto cada texto, los iconos
               quedaban desalineados entre sí. Con flex-start forman columna. */
            justify-content: flex-start;
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
            display: flow-root;
        }
        .container > section + section { margin-top: 80px; }

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
        .gallery-wrap { }
        /* La galería sube fuera de <main class="container">, entre la barra de
           confianza y el contenido: necesita su propio ancho y padding. */
        .gallery-top { max-width: 1100px; margin: 0 auto; padding: 56px 24px 0; }
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
        .specs { }
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
        }
        .why-body { color: #e5e7eb; font-size: 16px; line-height: 1.65; }

        /* ── COMPARATIVA DE MERCADO ────────────────────── */
        .market { }
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
        .precio-cliente { }
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
        }
        .cta-final::before {
            content: ''; position: absolute; inset: 0;
            background: radial-gradient(circle at 50% 0%, rgba(232, 89, 12, 0.15) 0%, transparent 40%);
        }
        .cta-final > * { position: relative; }
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

        /* ══════════════════════════════════════════════════
           REDISEÑO MÓVIL · 12-sep-2026
           La ficha medía 11.658 px (14,4 pantallas de móvil) y el precio no
           aparecía hasta la pantalla 6. Lo que sigue es lo que la comprime
           a ~6 pantallas sin perder un solo dato.
           ══════════════════════════════════════════════════ */

        /* — Carrusel de fotos: sustituye a la rejilla de 8 fotos apiladas
             (2.222 px en móvil, el bloque más grande de la página) — */
        .carrusel {
            display: flex; gap: 10px;
            overflow-x: auto;
            scroll-snap-type: x mandatory;
            -webkit-overflow-scrolling: touch;
            scrollbar-width: none;
            padding-bottom: 4px;
        }
        .carrusel::-webkit-scrollbar { display: none; }
        .carrusel .shot {
            flex: 0 0 86%; scroll-snap-align: center;
            aspect-ratio: 4/3; margin: 0;
        }
        .carrusel .shot:first-child { grid-column: auto; grid-row: auto; }
        .carrusel-pie {
            display: flex; align-items: center; justify-content: space-between;
            margin-top: 10px; font-size: 12px; color: var(--platinum);
        }
        .carrusel-pie .ver-todas { margin: 0; }

        /* — Chips de equipamiento: 15 filas de lista pasan a etiquetas — */
        .chips { display: flex; flex-wrap: wrap; gap: 8px; }
        .chip {
            font-size: 12.5px; color: #e8eeff; line-height: 1;
            padding: 9px 13px; border-radius: 999px;
            background: rgba(143, 163, 217, 0.10);
            border: 1px solid rgba(143, 163, 217, 0.22);
        }

        /* — Plegables con aspecto de control (antes no parecían pulsables) — */
        .plegable { border-top: 1px solid rgba(143, 163, 217, 0.16); margin-top: 18px; }
        .plegable > summary {
            list-style: none; cursor: pointer;
            display: flex; align-items: center; justify-content: space-between;
            gap: 10px; padding: 16px 2px; min-height: 44px;
            font-size: 14px; font-weight: 700; color: #fff;
        }
        .plegable > summary::-webkit-details-marker { display: none; }
        .plegable > summary::after {
            content: '⌄'; font-size: 18px; line-height: 1;
            color: var(--orange); transition: transform .2s ease;
        }
        .plegable[open] > summary::after { transform: rotate(180deg); }
        .plegable-cuerpo { padding: 2px 0 18px; }

        /* — Bloque de precio: una sola horquilla protagonista — */
        .precio-horquilla {
            background: rgba(143, 163, 217, 0.06);
            border: 1px solid rgba(143, 163, 217, 0.22);
            border-radius: 18px; padding: 26px 22px;
        }
        .precio-horquilla .etq {
            font-size: 10.5px; text-transform: uppercase; letter-spacing: 1.6px;
            color: var(--platinum); font-weight: 700; margin-bottom: 10px;
        }
        .precio-horquilla .cifra {
            font-size: 34px; font-weight: 900; color: #fff;
            line-height: 1.05; letter-spacing: -0.8px;
        }
        .precio-horquilla .cifra .guion { color: var(--platinum); font-weight: 600; }
        .precio-horquilla .aprox {
            font-size: 13px; color: #c7d4f5; margin-top: 8px;
        }
        .precio-lineas {
            margin-top: 20px; padding-top: 16px;
            border-top: 1px dashed rgba(143, 163, 217, 0.22);
            display: flex; flex-direction: column; gap: 11px;
        }
        .precio-lineas .fila {
            display: flex; align-items: baseline; justify-content: space-between;
            gap: 14px; font-size: 13.5px; color: #c7d4f5;
        }
        .precio-lineas .fila .c { flex: 1; }
        .precio-lineas .fila .i { font-weight: 700; color: #fff; white-space: nowrap; }
        .precio-lineas .fila.coche .i { color: #fff; }
        .precio-lineas .fila.suma {
            border-top: 1px solid rgba(143, 163, 217, 0.22);
            padding-top: 12px; margin-top: 3px;
            font-size: 14.5px; color: #fff; font-weight: 700;
        }
        .precio-lineas .fila.suma .i { color: var(--orange); }

        /* — Mercado como barra de rango (antes: 3 tarjetas apiladas) — */
        .rango { margin-top: 6px; }
        .rango-barra {
            position: relative; height: 8px; border-radius: 999px;
            background: linear-gradient(90deg, rgba(74,222,128,.55) 0%, rgba(143,163,217,.45) 50%, rgba(253,186,116,.55) 100%);
            margin: 46px 0 10px;
        }
        .rango-marca {
            position: absolute; top: -7px; width: 3px; height: 22px;
            border-radius: 2px; background: #fff;
            box-shadow: 0 0 0 3px rgba(10, 21, 53, .85);
        }
        .rango-marca span {
            position: absolute; bottom: 26px; left: 50%; transform: translateX(-50%);
            white-space: nowrap; font-size: 11.5px; font-weight: 800; color: #fff;
            background: var(--orange); padding: 4px 9px; border-radius: 999px;
        }
        .rango-topes {
            display: flex; justify-content: space-between;
            font-size: 12px; color: var(--platinum);
        }
        .rango-topes b { display: block; color: #fff; font-size: 13.5px; font-weight: 800; }
        .rango-topes .der { text-align: right; }
        .rango-nota { font-size: 12px; color: var(--platinum); margin-top: 14px; line-height: 1.5; }

        /* — Incluye / No incluye, uno al lado del otro — */
        .dos-listas { display: grid; grid-template-columns: 1fr 1fr; gap: 26px; }
        .dos-listas h3 {
            font-size: 12px; text-transform: uppercase; letter-spacing: 1.2px;
            font-weight: 800; margin-bottom: 12px;
            display: block;
        }
        /* `.incluye h3` pone un círculo verde con ✓ delante del título. Aquí
           hay DOS títulos y uno es "No incluido": el ✓ verde en ese sería
           justo lo contrario de lo que dice. Se anula. */
        .dos-listas h3::before { content: none; }
        .dos-listas .si h3 { color: #4ade80; }
        .dos-listas .no h3 { color: var(--platinum); }
        .dos-listas ul { list-style: none; display: flex; flex-direction: column; gap: 9px; }
        .dos-listas li { font-size: 13.5px; line-height: 1.45; padding-left: 20px; position: relative; }
        .dos-listas .si li { color: #e8eeff; }
        .dos-listas .no li { color: #a8b6d8; }
        .dos-listas .si li::before { content: '✓'; position: absolute; left: 0; color: #4ade80; font-weight: 800; }
        .dos-listas .no li::before { content: '–'; position: absolute; left: 0; color: var(--platinum); font-weight: 800; }

        /* — Barra CTA fija: antes el primer botón disponible mientras hacías
             scroll estaba en la pantalla 13 — */
        .cta-fijo {
            position: fixed; left: 0; right: 0; bottom: 0; z-index: 60;
            display: none; gap: 10px; padding: 10px 14px;
            padding-bottom: max(10px, env(safe-area-inset-bottom));
            background: rgba(10, 21, 53, 0.94);
            backdrop-filter: blur(12px);
            border-top: 1px solid rgba(143, 163, 217, 0.22);
        }
        .cta-fijo.visible { display: flex; }
        .cta-fijo .btn { flex: 1; justify-content: center; min-height: 48px; font-size: 14.5px; }
        .cta-fijo .btn.tel { flex: 0 0 56px; min-width: 56px; }

        /* — Línea de disponibilidad honesta — */
        .disponibilidad {
            font-size: 13px; color: var(--platinum); line-height: 1.5;
            margin: 22px 0 0; font-style: italic;
        }

        /* ── RESPONSIVE ───────────────────────────────── */
        @media (max-width: 800px) {
            .nav-links { display: none; }
            .nav-cta span.long { display: none; }
            .hero { min-height: auto; padding: 40px 20px 56px; }
            .hero-inner { grid-template-columns: 1fr; gap: 32px; }
            .hero-photo { aspect-ratio: 16/10; transform: rotate(0); }
            .trust { padding: 18px 16px; }
            /* Insignias en 2×2 con el icono arriba: ocupa la mitad de alto y
               se lee como insignia, no como lista (12-sep-2026). */
            .trust-inner { grid-template-columns: 1fr 1fr; gap: 18px 14px; }
            .trust-item { flex-direction: column; align-items: flex-start; gap: 8px; }
            .trust-text { font-size: 11.5px; }
            .trust-text strong { font-size: 12.5px; }
            .container { padding: 50px 20px; }
            .gallery-top { padding: 36px 20px 0; }
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
            .price-value { font-size: 32px; }
            .h1 { font-size: 32px; }

            /* ── Compresión vertical (12-sep-2026) ──
               La ficha medía 11.658 px en móvil (14,4 pantallas) y el precio no
               aparecía hasta la sexta. El salto entre secciones era de 80 px:
               en móvil eso solo es scroll. Y hueco abajo para que la barra CTA
               fija no tape el pie. */
            .container > section + section { margin-top: 48px; }
            body { padding-bottom: 76px; }

            /* Etiquetas de 10 px → 11 px: en móvil 10 px no se lee cómodo */
            .kpi-k, .spec-row .k, .precio-box .k { font-size: 11px; }

            .dos-listas { grid-template-columns: 1fr; gap: 24px; }
            .precio-horquilla { padding: 22px 18px; }
            .precio-horquilla .cifra { font-size: 28px; }
            .carrusel .shot { flex-basis: 88%; }

            /* El bloque de A31 se queda COMPLETO y visible (lo exige el spec
               del skill), pero más compacto: era 1.126 px, 1,4 pantallas. */
            .aviso-legal { padding: 24px 20px; }
            .aviso-legal .lista-limpia li { font-size: 13px; line-height: 1.45; }
            .aviso-legal .titular { font-size: 15px; }
            .aviso-legal p { font-size: 13px; line-height: 1.55; }
            .dos-col { gap: 18px; }
            .pasos { gap: 10px; }
        }
        @media (max-width: 480px) {
            /* 12-sep-2026: este bloque dejaba las insignias en UNA columna
               (llegaba después del breakpoint de 800 px y ganaba). Se quedan
               en 2×2, que es donde más se nota la compresión. La galería
               tampoco vuelve a una columna: ahora es un carrusel. */
            .trust-inner { grid-template-columns: 1fr 1fr; }
            .h1 { font-size: 29px; }
            .hero-actions .btn { flex: 1; justify-content: center; }
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
        // FIX 12-sep-2026 (tres precios en la misma página): el hero mostraba
        // su propio precio ($car->sale_price — columna que no existe — o
        // purchase_price, el coste del coche SIN gastos) mientras más abajo el
        // bloque de "inversión estimada" daba otro total. En el Arteon el
        // cliente veía 31.929 € arriba, 39.232 € en medio y 48.931 € de total.
        //
        // Ahora hay UNA sola fuente ($precioCliente) y se expresa como
        // HORQUILLA, nunca como cifra cerrada (decisión del usuario 12-sep-2026
        // + `07-marketing/ficha_cliente.md` §3.5: prohibido "precio final" o
        // "IVA incluido", porque no somos el vendedor).
        $fmtEur0 = fn ($n) => $n === null ? null : number_format((float) $n, 0, ',', '.').' €';
        $pcMin = $precioCliente['total_min'] ?? null;
        $pcMax = $precioCliente['total_max'] ?? null;
        $hayHorquilla = $pcMin && $pcMax && $pcMax > $pcMin;
        $precioTitular = $hayHorquilla
            ? number_format((float) $pcMin, 0, ',', '.').' – '.$fmtEur0($pcMax)
            : ($fmtEur0($precioCliente['total_estimado'] ?? null) ?? $fmtEur0($car->purchase_price ?? null));
        $potencia = $esqueleto?->uno('POTENCIA');
        // ── Cambio: limpio, sin coletillas técnicas (NO mostramos "doble embrague", "DSG", "6 vel"...) ──
        $cambioRaw = $esqueleto?->uno('CAMBIO') ?? $car->transmission;
        $cambioTxt = $cambioRaw
            ? (str_contains(strtolower($cambioRaw), 'auto') || stripos($cambioRaw, 'DSG') !== false || stripos($cambioRaw, 'Tiptronic') !== false
                ? 'Automático' : 'Manual')
            : null;
        $kmTxt = $car->mileage ? number_format($car->mileage, 0, ',', '.').' km' : null;
        $anioTxt = $car->year ?: null;

        // ── Origen: España o Alemania. NO solo «importación»: también gestionamos compras en España ──
        // (pais_origen es la columna real; origin_country no existe en el schema)
        $paisOrigen = strtolower((string) ($car->pais_origen ?? ''));
        $esAlemania = str_contains($paisOrigen, 'alem') || $paisOrigen === 'de';
        $esEspana = str_contains($paisOrigen, 'espa') || $paisOrigen === 'es';
        $origenLabel = $esAlemania ? 'Importado desde Alemania' : ($esEspana ? 'Localizado en España' : 'Origen verificado');
        $origenSub = $esAlemania ? 'Historial completo y verificado' : 'Historial verificado';

        // ── Combustible: la BD lo trae del scraping en inglés (gasoline/diesel).
        //    Al cliente siempre en español. ──
        $fuelMap = [
            'gasoline' => 'Gasolina', 'petrol' => 'Gasolina', 'gas' => 'Gasolina',
            'diesel' => 'Diésel',
            'hybrid' => 'Híbrido', 'plug-in hybrid' => 'Híbrido enchufable', 'phev' => 'Híbrido enchufable',
            'electric' => 'Eléctrico', 'ev' => 'Eléctrico',
            'lpg' => 'GLP', 'glp' => 'GLP',
        ];
        $fuelTxt = $fuelMap[strtolower(trim((string) $car->fuel))] ?? ($car->fuel ? ucfirst(mb_strtolower($car->fuel)) : null);

        // Tracción: la BD guarda los códigos internos (FWD/RWD/AWD). Igual que
        // el combustible, al cliente en español (12-sep-2026).
        $traccionTxt = match (strtoupper(trim((string) ($car->drivetrain ?? '')))) {
            'FWD' => 'Delantera',
            'RWD' => 'Trasera',
            'AWD', '4WD' => 'Total (4x4)',
            default => $car->drivetrain ?: null,
        };

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
        // 12-sep-2026: antes era una rejilla de 6 tarjetas escritas a mano en la
        // plantilla. Ahora sale de la ficha del cliente (FC_INCLUYE) si el ZIP
        // la trae, con el mismo contenido como respaldo.
        $fichaIncluye = $ficha['incluye'] ?? [
            'Búsqueda y verificación de la unidad',
            'Negociación y gestión de la compra',
            'Transporte hasta España',
            'ITV de importación, impuestos y matriculación',
            'Historial y kilometraje comprobados',
            'Entrega en Huelva y provincia',
        ];
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
                {{-- 12-sep-2026: "Informe de oportunidad exclusivo" sonaba a
                     teletienda. Sin nombre de cliente (decisión del usuario:
                     el enlace se reenvía por WhatsApp y debe ser neutro). --}}
                <div class="hero-eyebrow">Informe de la unidad · {{ $fechaDatos }}</div>
                <h1 class="h1">
                    {{ $car->brand }}<br>
                    <span class="accent">{{ $car->model }}</span>
                </h1>
                @php
                    // Todos los datos clave en UNA línea, como el mockup v3.
                    // Sin potencia: en 375 px dejaba un "320 CV" huérfano en
                    // una segunda línea. Va en la ficha técnica.
                    $heroDatos = array_filter([$anioTxt, $kmTxt, $fuelTxt, $cambioTxt]);
                @endphp
                @if(count($heroDatos) > 0)
                    <p class="claim">{{ implode(' · ', $heroDatos) }}</p>
                @else
                    <p class="claim">Verificado por nuestro equipo</p>
                @endif

                @if($precioTitular)
                    <div class="price-card">
                        <div class="price-label">Puesto en Huelva · aprox.</div>
                        <div class="price-value">{{ $precioTitular }}</div>
                        <div class="price-caption">Coche, transporte, impuestos y gestión — desglose más abajo</div>
                    </div>
                @endif

                <div class="hero-actions">
                    <a href="https://wa.me/34675701439?text={{ urlencode('Hola, me interesa el '.$car->brand.' '.$car->model.' que habéis compartido conmigo.') }}"
                       target="_blank" rel="noopener" class="btn primary">
                        💬 Me interesa, ¿hablamos?
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

    {{-- Auditoría 12-sep-2026 (móvil): la galería estaba dentro del contenido,
         después de la valoración, y era una rejilla de 8 fotos apiladas —
         2.222 px en móvil, el bloque más grande de toda la página. Ahora sube
         justo tras la barra de confianza (quien abre el enlace quiere ver el
         coche) y es un carrusel con scroll-snap: se ven todas deslizando y
         ocupa el alto de una sola foto. Los width/height evitan que la página
         salte mientras cargan. --}}
    @if(count($fotos) > 1)
        <section id="galeria" class="gallery-wrap gallery-top">
            <div class="section-title">Galería</div>
            <h2 class="section-h">Fotos reales del vehículo</h2>
            <div class="gallery carrusel" id="gallery">
                @foreach(array_slice($fotos, 0, 10) as $i => $foto)
                    <div class="shot" data-index="{{ $i }}" onclick="openLightbox({{ $i }})">
                        <img src="{{ $foto }}" width="800" height="600"
                             alt="{{ $car->brand }} {{ $car->model }} — foto {{ $i+1 }}"
                             loading="{{ $i === 0 ? 'eager' : 'lazy' }}" decoding="async">
                    </div>
                @endforeach
            </div>
            <div class="carrusel-pie">
                <span>Desliza para ver más · {{ count($fotos) }} fotos</span>
                @if(count($fotos) > 10)
                    <a href="#" class="ver-todas" onclick="event.preventDefault(); openLightbox(0);">Ver todas</a>
                @endif
            </div>
        </section>
    @endif

    {{-- ── KPI BAR: ELIMINADA (12-sep-2026) ──────────────
         Mostraba Año / Kilómetros / Combustible / Cambio / Origen a razón de UN
         dato por fila en móvil (494 px), y cuatro de esos cinco campos se
         repetían más abajo en la ficha técnica — además con valores que no
         coincidían entre sí ("01/2023" arriba vs "2023" abajo; "Automático" vs
         "Automático (DSG 7v)"). Los datos clave están ahora en una línea bajo el
         título de la portada; el detalle completo, en la ficha técnica. --}}

    {{-- ── CONTENIDO PRINCIPAL ─────────────────────────── --}}
    <main class="container">

        {{-- NUESTRA VALORACIÓN (sin veredicto interno ni nota: A22) --}}
        @if($valoracionTexto || $porqueTexto)
            <section id="veredicto" class="verdict reveal">
                <div class="verdict-eyebrow">Nuestra valoración</div>
                <h2 class="verdict-h">Qué nos parece esta unidad</h2>
                @if($valoracionTexto)
                    <p class="verdict-body">{!! \App\Support\Esqueleto::negrita($valoracionTexto) !!}</p>
                @endif
                @if($porqueTexto)
                    <p class="verdict-body" style="margin-top:16px">{!! \App\Support\Esqueleto::negrita($porqueTexto) !!}</p>
                @endif
                <div class="verdict-footer">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><polyline points="12,6 12,12 16,14"/></svg>
                    Datos del vehículo comprobados el {{ $fechaDatos }}
                </div>
            </section>
        @endif

        {{-- GALERÍA — subida tras la valoración (12-sep-2026): el cliente quiere
             VER el coche antes de leer cifras; las fotos son el mejor gancho. --}}

        {{-- C3 auditoría 09-sep-2026: precio origen + gastos de compra.
             El cliente ve "qué cuesta el coche + qué cuesta traerlo" SIN
             desglose de margen, con aviso de que el total es estimación
             y se confirma por escrito antes de la reserva. --}}
        @if($precioTitular)
            @php
                $pc = $precioCliente;
                $fmtEurPc = fn ($n) => $n !== null ? number_format((float) $n, 0, ',', '.').' €' : null;
                // Las líneas del desglose ya vienen agrupadas y sin honorarios
                // etiquetados (PrecioClienteCalculator las toma de los bloques
                // [GASTO] del ZIP, que la skill funde con la gestoría).
                $lineasCoche = [];
                $lineasGastos = [];
                foreach (($pc['desglose'] ?? []) as $concepto => $valor) {
                    if (str_contains(mb_strtolower((string) $concepto), 'precio del coche')) {
                        $lineasCoche[$concepto] = $valor;
                    } else {
                        $lineasGastos[$concepto] = $valor;
                    }
                }
            @endphp
            <section class="precio-cliente">
                <div class="section-title">El precio</div>
                <h2 class="section-h">Cuánto te costaría, puesto en Huelva</h2>

                <div class="precio-horquilla">
                    <div class="etq">{{ $hayHorquilla ? 'Horquilla estimada' : 'Estimación' }}</div>
                    <div class="cifra">
                        @if($hayHorquilla)
                            {{ number_format((float) $pcMin, 0, ',', '.') }}<span class="guion"> – </span>{{ $fmtEur0($pcMax) }}
                        @else
                            {{ $precioTitular }}
                        @endif
                    </div>
                    <div class="aprox">Coche, transporte, impuestos y gestión incluidos</div>

                    {{-- Desglose DETALLADO y siempre visible (decisión del
                         usuario 12-sep-2026: "los gastos siempre aproximados,
                         y desglosados con detalle para que el cliente lo pueda
                         saber"). Cada línea va con ~ delante: ninguna es fija. --}}
                    @if(count($lineasCoche) + count($lineasGastos) > 0)
                        <div class="precio-lineas">
                            @foreach($lineasCoche as $concepto => $valor)
                                <div class="fila coche">
                                    <span class="c">{{ $concepto }}</span>
                                    <span class="i">{{ $fmtEurPc($valor) }}</span>
                                </div>
                            @endforeach
                            @foreach($lineasGastos as $concepto => $valor)
                                <div class="fila">
                                    <span class="c">{{ $concepto }}</span>
                                    <span class="i">~ {{ $fmtEurPc($valor) }}</span>
                                </div>
                            @endforeach
                            @if(!empty($pc['total_estimado']))
                                <div class="fila suma">
                                    <span class="c">Total aproximado</span>
                                    <span class="i">~ {{ $fmtEurPc($pc['total_estimado']) }}</span>
                                </div>
                            @endif
                        </div>
                    @endif
                </div>

                @if(!empty($pc['banda_motivo']))
                    <p class="nota-fina">{{ $pc['banda_motivo'] }}</p>
                @endif
                <p class="nota-fina">{{ $pc['aviso_precio_final'] }}</p>
            </section>
        @endif

        {{-- Auditoría 12-sep-2026 (diseño): "qué incluye"/"qué no incluye" vivían
             muy lejos del precio (incluye después de la comparativa de mercado y
             puntos a favor; no-incluye después de la ficha técnica y galería).
             Se colocan justo debajo del precio para que la transparencia
             (qué pagas / qué te llevas / qué no) sea un bloque único. --}}
        {{-- QUÉ INCLUYE / QUÉ NO — 12-sep-2026: estaban en dos secciones
             separadas y lejanas (incluye tras la comparativa de mercado, no
             incluye casi al final de la página). Juntas, y pegadas al precio,
             la transparencia se lee como un bloque: qué pagas, qué te llevas,
             qué no. --}}
        <section class="incluye">
            <div class="dos-listas">
                <div class="si">
                    <h3>Incluido en el precio</h3>
                    <ul>
                        @foreach($fichaIncluye as $item)
                            <li>{{ is_string($item) ? $item : '' }}</li>
                        @endforeach
                    </ul>
                </div>
                <div class="no">
                    <h3>No incluido</h3>
                    <ul>
                        @foreach($fichaNoIncluye as $item)
                            <li>{{ is_string($item) ? $item : '' }}</li>
                        @endforeach
                    </ul>
                </div>
            </div>
            <p class="disponibilidad">Esta unidad seguía publicada el {{ $fechaDatos }}. No reservamos nada hasta que tú lo digas.</p>
        </section>

{{-- COMPARATIVA DE MERCADO --}}
        @if($hayMercado)
            @php
                // 12-sep-2026: eran tres tarjetas apiladas (una por fila en
                // móvil) que no decían nada por sí solas. Una barra de rango
                // con la posición de nuestra estimación se entiende de un
                // vistazo — y es lo que pedía el spec del skill
                // (`ficha_cliente.md` §5: "barra de rango con mín-mediana-máx").
                $mMin = $car->market_min ? (float) $car->market_min : null;
                $mMax = $car->market_max ? (float) $car->market_max : null;
                $mAvg = $car->market_avg ? (float) $car->market_avg : null;
                $nuestro = $precioCliente['total_estimado'] ?? null;

                $posPct = null;
                if ($mMin && $mMax && $mMax > $mMin && $nuestro) {
                    $posPct = (($nuestro - $mMin) / ($mMax - $mMin)) * 100;
                    $posPct = max(2, min(98, $posPct));
                }
                // A28: si nuestra estimación queda por encima de la media del
                // mercado, se dice. Un cliente que lo descubre solo se va.
                $porEncimaDeMedia = $mAvg && $nuestro && $nuestro > $mAvg;
            @endphp
            <section class="market">
                <div class="section-title">El mercado español</div>
                <h2 class="section-h">Cómo está de precio</h2>

                @if($posPct !== null)
                    <div class="rango">
                        <div class="rango-barra">
                            <div class="rango-marca" style="left: {{ round($posPct, 1) }}%">
                                <span>Esta unidad</span>
                            </div>
                        </div>
                        <div class="rango-topes">
                            <div><b>{{ $marketMin }}</b>el más barato</div>
                            <div class="der"><b>{{ $marketMax }}</b>el más caro</div>
                        </div>
                        @if($marketAvg)
                            <p class="rango-nota">Precio medio de unidades parecidas en España: <strong style="color:#fff">{{ $marketAvg }}</strong>.</p>
                        @endif
                    </div>
                @else
                    <div class="market-grid">
                        @if($marketMin)
                            <div class="market-box"><div class="k">El más barato</div><div class="v">{{ $marketMin }}</div></div>
                        @endif
                        @if($marketAvg)
                            <div class="market-box"><div class="k">Precio medio</div><div class="v">{{ $marketAvg }}</div></div>
                        @endif
                        @if($marketMax)
                            <div class="market-box"><div class="k">El más caro</div><div class="v">{{ $marketMax }}</div></div>
                        @endif
                    </div>
                @endif

                @if($porEncimaDeMedia)
                    <p class="rango-nota">Esta unidad queda por encima de la media del mercado. Lo justifican
                    su equipamiento y su kilometraje; si buscas ajustar el precio, podemos seguir
                    buscando otras opciones.</p>
                @endif
                <p class="nota-fina">Comparativa de unidades similares publicadas en España a fecha de {{ $fechaDatos }}. Es una referencia de mercado, no una promesa de ahorro.</p>
            </section>
        @endif

        {{-- PUNTOS A FAVOR (al cliente solo lo bueno) --}}
        @if(count($prosLista) > 0)
            <section>
                <div class="section-title">Puntos a favor</div>
                <h2 class="section-h">Lo mejor de esta unidad</h2>
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
                    'Combustible' => $fuelTxt, 'Cambio' => $cambioTxt,
                    'Versión' => $car->version ?? null, 'Tracción' => $traccionTxt,
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

        {{-- EQUIPAMIENTO — 12-sep-2026: eran 15 filas de lista, una por línea
             (776 px en móvil). Como chips y dentro de un plegable ocupa una
             línea hasta que el cliente decide abrirlo, que es exactamente lo
             que proponía el mockup v3. --}}
        @if($esqueleto || !empty($ficha['equipamiento']))
            @php
                $equipamiento = $ficha['equipamiento'] ?? [];
                if (empty($equipamiento) && $esqueleto) {
                    $equipamiento = $esqueleto->lista('EQUIPAMIENTO');
                }
                $equipamiento = array_values(array_filter(
                    (array) $equipamiento,
                    fn ($v) => is_string($v) && trim($v) !== ''
                ));
            @endphp
            @if(count($equipamiento) > 0)
                <details class="plegable">
                    <summary>Equipamiento y extras ({{ count($equipamiento) }})</summary>
                    <div class="plegable-cuerpo">
                        <div class="chips">
                            @foreach($equipamiento as $item)
                                <span class="chip">{{ $item }}</span>
                            @endforeach
                        </div>
                    </div>
                </details>
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
            <div class="section-title">Cómo trabajamos</div>
            <h2 class="section-h">Somos gestores, no vendedores</h2>
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

        {{-- PREGUNTAS FRECUENTES + CTA FINAL (separados a partials 10-sep-2026) --}}
        @include('public.dossier.partials.faq')
        @include('public.dossier.partials.cta-final')

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

    {{-- ── BARRA CTA FIJA ──────────────────────────────────
         12-sep-2026: el botón de la portada desaparecía en la pantalla 2 y el
         siguiente estaba en la pantalla 13 de 14. Si el cliente se convencía a
         mitad de página, no tenía dónde pulsar. Es el MISMO CTA (regla §3.7 del
         spec: un solo CTA), solo que siempre accesible. --}}
    <div class="cta-fijo" id="cta-fijo">
        <a href="https://wa.me/34675701439?text={{ urlencode('Hola, me interesa el '.$car->brand.' '.$car->model.' que habéis compartido conmigo.') }}"
           target="_blank" rel="noopener" class="btn primary">
            💬 Hablar por WhatsApp
        </a>
        <a href="tel:+34675701439" class="btn ghost tel" aria-label="Llamar a JJ Import Motors">📞</a>
    </div>
    <script>
    (function () {
        var barra = document.getElementById('cta-fijo');
        var hero = document.querySelector('.hero');
        if (!barra || !hero) return;

        // Aparece cuando la portada (con su propio CTA) sale de pantalla.
        if ('IntersectionObserver' in window) {
            new IntersectionObserver(function (entradas) {
                entradas.forEach(function (e) {
                    barra.classList.toggle('visible', !e.isIntersecting);
                });
            }, { threshold: 0 }).observe(hero);
        } else {
            barra.classList.add('visible');
        }
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
