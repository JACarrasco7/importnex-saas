<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $car->brand }} {{ $car->model }} · JJ Import Motors</title>
    <meta name="robots" content="noindex, nofollow">
    <meta property="og:title" content="{{ $car->brand }} {{ $car->model }} · JJ Import Motors">
    <meta property="og:description" content="{{ ($car->purchase_price ?? 0) > 0 ? number_format($car->purchase_price, 0, ',', '.').' € · ' : '' }}{{ $car->mileage ? number_format($car->mileage, 0, ',', '.').' km · ' : '' }}Dossier JJ Import Motors">
    @if(count($fotos) > 0)
        <meta property="og:image" content="{{ $fotos[0] }}">
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
        .nav-cta .ic { width: 16px; height: 16px; }
        .plazo-chip .ic { width: 13px; height: 13px; }
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
            display: inline-flex; align-items: center; gap: 9px;
            background: rgba(232, 89, 12, 0.12);
            border: 1px solid rgba(232, 89, 12, 0.35);
            color: #fdba74;
            padding: 7px 16px; border-radius: 100px;
            font-size: 11px; font-weight: 700; letter-spacing: 1.4px;
            text-transform: uppercase;
            margin-bottom: 16px;
        }
        .hero-eyebrow::before {
            content: ''; width: 7px; height: 7px; border-radius: 50%;
            background: var(--orange);
            box-shadow: 0 0 0 3px rgba(232, 89, 12, 0.22);
            flex-shrink: 0;
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
        .price-value.price-on-request { font-size: 30px; letter-spacing: 0; }
        .price-caption { font-size: 12px; color: #cbd5e1; margin-top: 8px; }
        .price-note { font-size: 10px; color: #94a3b8; margin-top: 6px; line-height: 1.4; max-width: 280px; }
        .plazo-chip {
            display: inline-flex; align-items: center; gap: 6px;
            margin-top: 12px; padding: 6px 12px; border-radius: 100px;
            background: rgba(16, 185, 129, 0.15); border: 1px solid rgba(16, 185, 129, 0.35);
            color: #4ade80; font-size: 12px; font-weight: 700;
        }
        .hero-chips { display: flex; gap: 8px; flex-wrap: wrap; margin-bottom: 24px; }
        .chip {
            padding: 6px 14px; border-radius: 100px;
            background: rgba(255, 255, 255, 0.08); border: 1px solid rgba(255, 255, 255, 0.15);
            font-size: 12.5px; font-weight: 600; color: #e5e7eb;
            backdrop-filter: blur(6px);
        }
        .dgt-badge {
            display: inline-flex; align-items: center; gap: 8px;
            padding: 8px 16px; border-radius: 10px; margin-bottom: 18px;
            font-weight: 900; font-size: 16px; letter-spacing: 1px;
            border: 2px solid;
        }
        .dgt-badge .dgt-label { font-size: 10px; font-weight: 600; text-transform: uppercase; letter-spacing: 1px; opacity: 0.8; }
        .dgt-0 { background: rgba(59, 130, 246, 0.15); border-color: #3b82f6; color: #93c5fd; }
        .dgt-eco { background: rgba(16, 185, 129, 0.15); border-color: #10b981; color: #6ee7b7; }
        .dgt-b { background: rgba(34, 197, 94, 0.15); border-color: #22c55e; color: #86efac; }
        .dgt-c { background: rgba(250, 204, 21, 0.15); border-color: #facc15; color: #fde047; }
        .verdict-badge {
            display: inline-flex; align-items: center;
            padding: 7px 16px; border-radius: 100px;
            font-size: 12px; font-weight: 800; letter-spacing: 1px;
            text-transform: uppercase; margin-bottom: 14px;
        }
        .v-green { background: rgba(16, 185, 129, 0.2); color: #4ade80; border: 1px solid rgba(16, 185, 129, 0.4); }
        .v-orange { background: rgba(232, 89, 12, 0.2); color: #fdba74; border: 1px solid rgba(232, 89, 12, 0.4); }
        .v-red { background: rgba(239, 68, 68, 0.2); color: #fca5a5; border: 1px solid rgba(239, 68, 68, 0.4); }
        .argumentos-grid {
            display: grid; grid-template-columns: 1fr 1fr; gap: 14px;
            margin-bottom: 80px;
        }
        .argumento-item {
            display: flex; gap: 14px; align-items: flex-start;
            background: rgba(143, 163, 217, 0.06);
            border: 1px solid rgba(143, 163, 217, 0.2);
            border-radius: 14px; padding: 20px 22px;
            font-size: 14.5px; color: #e5e7eb; line-height: 1.55;
        }
        .argumento-check {
            color: var(--gold); font-size: 18px; flex-shrink: 0;
            background: rgba(244, 197, 66, 0.12);
            width: 34px; height: 34px; border-radius: 50%;
            display: inline-flex; align-items: center; justify-content: center;
        }
        .w-badge {
            display: inline-block; margin-left: 8px; padding: 2px 8px;
            border-radius: 100px; font-size: 10px; font-weight: 800;
            text-transform: uppercase; letter-spacing: 0.5px; vertical-align: middle;
        }
        .w-high { background: rgba(16, 185, 129, 0.2); color: #4ade80; }
        .w-med { background: rgba(250, 204, 21, 0.15); color: #fde047; }
        .legal { font-size: 10.5px; color: #64748b; margin-top: 12px; line-height: 1.5; max-width: 720px; margin-left: auto; margin-right: auto; }

        .hero-actions { display: flex; gap: 12px; flex-wrap: wrap; }
        .btn {
            display: inline-flex; align-items: center; gap: 8px;
            padding: 13px 22px; border-radius: 10px;
            font-size: 14px; font-weight: 700;
            text-decoration: none; cursor: pointer;
            border: 1px solid transparent;
            transition: all 0.18s ease;
        }
        .btn.ghost {
            background: rgba(255, 255, 255, 0.08);
            color: #fff; border-color: rgba(255, 255, 255, 0.18);
        }
        .btn.ghost:hover { background: rgba(255, 255, 255, 0.16); }
        .btn .ic { width: 18px; height: 18px; flex-shrink: 0; }
        .btn.wa {
            background: #25D366; color: #06240f;
            box-shadow: 0 6px 20px rgba(37, 211, 102, 0.35);
        }
        .btn.wa:hover { background: #1fb959; transform: translateY(-2px); box-shadow: 0 10px 24px rgba(37, 211, 102, 0.45); }

        .hero-photo {
            position: relative;
            border-radius: 24px; overflow: hidden;
            box-shadow: 0 40px 100px rgba(0, 0, 0, 0.5),
                        0 0 0 1px rgba(255, 255, 255, 0.08);
            aspect-ratio: 4/3;
            background: #14265a;
            transform: rotate(-1.2deg);
        }
        .hero-photo-empty {
            display: flex; flex-direction: column; align-items: center; justify-content: center;
            gap: 16px; color: var(--platinum);
            background: linear-gradient(135deg, rgba(143, 163, 217, 0.08) 0%, rgba(143, 163, 217, 0.02) 100%);
            border: 1px dashed rgba(143, 163, 217, 0.25);
        }
        .hero-photo-empty span { font-size: 13px; font-weight: 600; opacity: 0.7; }
        .hero-photo img { width: 100%; height: 100%; object-fit: cover; }

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
        .tips h3 svg { width: 22px; height: 22px; color: var(--gold); flex-shrink: 0; }
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
            .argumentos-grid { grid-template-columns: 1fr; margin-bottom: 50px; }
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
                <svg class="ic" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 16.92v3a2 2 0 01-2.18 2 19.79 19.79 0 01-8.63-3.07 19.5 19.5 0 01-6-6 19.79 19.79 0 01-3.07-8.67A2 2 0 014.11 2h3a2 2 0 012 1.72c.127.96.361 1.903.7 2.81a2 2 0 01-.45 2.11L8.09 9.91a16 16 0 006 6l1.27-1.27a2 2 0 012.11-.45c.907.339 1.85.573 2.81.7A2 2 0 0122 16.92z"/></svg>
                <span class="long">675 70 14 39</span>
            </a>
        </div>
    </nav>

    {{-- ── HERO ────────────────────────────────────────── --}}
    @php
        $esEspaña = ! $car->isImport();
        $origenTxt = $car->pais_origen ?: null;
        $precio = $car->purchase_price ?? 0;
        $potencia = $esqueleto?->uno('POTENCIA') ?? ($car->cv ? $car->cv.' CV' : null);
        $cambioTxt = $car->transmission ?: $esqueleto?->uno('CAMBIO');
        $kmTxt = $car->mileage ? number_format($car->mileage, 0, ',', '.').' km' : null;
        $anioTxt = $car->year ?: null;
        if ($anioTxt && preg_match('/\d{4}/', $anioTxt, $mYear)) { $anioTxt = $mYear[0]; }
        $fuelMap = [
            'gasoline' => 'Gasolina', 'petrol' => 'Gasolina',
            'diesel' => 'Diésel', 'gasoil' => 'Diésel', 'gasóleo' => 'Diésel',
            'hybrid' => 'Híbrido', 'hibrido' => 'Híbrido', 'híbrido' => 'Híbrido',
            'electric' => 'Eléctrico', 'electrico' => 'Eléctrico', 'eléctrico' => 'Eléctrico',
            'lpg' => 'GLP', 'glp' => 'GLP', 'cng' => 'GNC', 'gnc' => 'GNC',
        ];
        $fuelKey = mb_strtolower(trim((string) $car->fuel));
        $fuelTxt = $fuelMap[$fuelKey] ?? ($car->fuel ?: null);
        $dgt = $esqueleto?->uno('ETIQUETA_DGT');
        $tituloFicha = $esqueleto?->uno('TITULO');
        $claimFicha = $esqueleto?->uno('CLAIM');
        $precioCaption = $esqueleto?->uno('PRECIO_CAPTION');
        $precioNota = $esqueleto?->uno('PRECIO_NOTA');
        $plazo = $esqueleto?->uno('PLAZO');
        $claimParts = array_filter([$potencia, $fuelTxt, $kmTxt]);
    @endphp
    <header class="hero">
        <div class="hero-inner">
            <div class="hero-left">
                <div class="hero-eyebrow">Dossier exclusivo · {{ $esEspaña ? 'Búsqueda en España' : ($origenTxt ? 'Importación desde '.$origenTxt : 'Importación') }}</div>
                @if($dgt)
                    <div class="dgt-badge dgt-{{ strtolower($dgt) }}">
                        <span class="dgt-label">Etiqueta</span> {{ strtoupper($dgt) }}
                    </div>
                @endif
                <h1 class="h1">
                    {{ $car->brand }}<br>
                    <span class="accent">{{ $car->model }}</span>
                </h1>
                @if($claimFicha)
                    <p class="claim">{{ $claimFicha }}</p>
                @elseif(count($claimParts) > 0)
                    <p class="claim">{{ implode(' · ', $claimParts) }} · {{ $esEspaña ? 'Disponible en España' : 'Listo para importar' }}</p>
                @else
                    <p class="claim">{{ $esEspaña ? 'Disponible en España' : 'Listo para importar' }}</p>
                @endif

                @if(count($claimParts) > 0)
                    <div class="hero-chips">
                        @foreach($claimParts as $chip)
                            <span class="chip">{{ $chip }}</span>
                        @endforeach
                        @if($cambioTxt)<span class="chip">{{ ucfirst($cambioTxt) }}</span>@endif
                    </div>
                @endif

                <div class="price-card">
                    <div class="price-label">Precio del vehículo</div>
                    @if($precio > 0)
                        <div class="price-value">{{ number_format($precio, 0, ',', '.') }} €</div>
                    @else
                        <div class="price-value price-on-request">A consultar</div>
                    @endif
                    <div class="price-caption">{{ $precioCaption ?? '+ costes de servicio y gestión' }}</div>
                    @if($precioNota)
                        <div class="price-note">{{ $precioNota }}</div>
                    @endif
                    @if($plazo)
                        <div class="plazo-chip">
                            <svg class="ic" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
                            {{ $plazo }}
                        </div>
                    @endif
                </div>

                <div class="hero-actions">
                    <a href="https://wa.me/34675701439?text={{ urlencode('Hola, me interesa el '.$car->brand.' '.$car->model.'. ¿Podéis darme más información del servicio?') }}"
                       target="_blank" rel="noopener" class="btn wa">
                        <svg class="ic" viewBox="0 0 24 24" fill="currentColor"><path d="M12.04 2c-5.46 0-9.91 4.45-9.91 9.91 0 1.75.46 3.45 1.32 4.95L2.05 22l5.25-1.38a9.87 9.87 0 004.74 1.21h.01c5.46 0 9.9-4.45 9.9-9.91 0-2.65-1.03-5.14-2.9-7.01A9.82 9.82 0 0012.04 2zm0 18.15c-1.48 0-2.93-.4-4.2-1.15l-.3-.18-3.12.82.83-3.04-.2-.31a8.26 8.26 0 01-1.26-4.38c0-4.54 3.7-8.24 8.24-8.24 2.2 0 4.27.86 5.82 2.42a8.18 8.18 0 012.41 5.83c-.01 4.54-3.7 8.23-8.22 8.23zm4.52-6.16c-.25-.13-1.47-.72-1.69-.8-.23-.08-.39-.13-.56.12-.16.25-.64.81-.78.97-.15.17-.29.19-.54.06-.25-.12-1.05-.39-1.99-1.23-.74-.66-1.23-1.47-1.38-1.72-.14-.25-.01-.38.11-.51.11-.11.25-.29.37-.43.13-.15.17-.25.25-.42.08-.17.04-.31-.02-.43-.06-.13-.56-1.34-.76-1.84-.2-.48-.4-.42-.56-.42h-.48c-.17 0-.43.06-.66.31-.22.25-.86.85-.86 2.07 0 1.22.89 2.4 1.01 2.56.12.17 1.75 2.67 4.23 3.74.59.26 1.05.41 1.41.52.59.19 1.13.16 1.56.1.48-.07 1.47-.6 1.67-1.18.21-.58.21-1.07.14-1.18-.06-.1-.22-.16-.47-.28z"/></svg>
                        Consultar por WhatsApp
                    </a>
                    <a href="tel:+34675701439" class="btn ghost">
                        <svg class="ic" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 16.92v3a2 2 0 01-2.18 2 19.79 19.79 0 01-8.63-3.07 19.5 19.5 0 01-6-6 19.79 19.79 0 01-3.07-8.67A2 2 0 014.11 2h3a2 2 0 012 1.72c.127.96.361 1.903.7 2.81a2 2 0 01-.45 2.11L8.09 9.91a16 16 0 006 6l1.27-1.27a2 2 0 012.11-.45c.907.339 1.85.573 2.81.7A2 2 0 0122 16.92z"/></svg>
                        Llamar ahora
                    </a>
                </div>
            </div>

            @if(count($fotos) > 0)
                <div class="hero-photo">
                    <img src="{{ $fotos[0] }}" alt="{{ $car->brand }} {{ $car->model }}">
                </div>
            @else
                <div class="hero-photo hero-photo-empty">
                    <svg width="80" height="80" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.2" opacity="0.4">
                        <path d="M5 17h14M5 17l2-8h10l2 8M5 17a2 2 0 002 2 2 2 0 002-2M15 17a2 2 0 002 2 2 2 0 002-2"/>
                        <circle cx="8" cy="9" r="1.5"/><circle cx="16" cy="9" r="1.5"/>
                    </svg>
                    <span>Foto del vehículo disponible bajo petición</span>
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
                    <strong>{{ $esEspaña ? 'Búsqueda en España' : ($origenTxt ? 'Importado desde '.$origenTxt : 'Importación') }}</strong>
                    Historial completo y verificado
                </div>
            </div>
            <div class="trust-item">
                <div class="trust-icon">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2"><path d="M9 12l2 2 4-4M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                </div>
                <div class="trust-text">
                    <strong>Inspección previa</strong>
                    Revisión antes de {{ $esEspaña ? 'comprar' : 'importar' }}
                </div>
            </div>
            <div class="trust-item">
                <div class="trust-icon">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2"><path d="M3 9l9-7 9 7v11a2 2 0 01-2 2H5a2 2 0 01-2-2z"/><polyline points="9,22 9,12 15,12 15,22"/></svg>
                </div>
                <div class="trust-text">
                    <strong>Gestión completa</strong>
                    Trámites y transporte incluidos
                </div>
            </div>
            <div class="trust-item">
                <div class="trust-icon">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2"><path d="M12 1v22M17 5H9.5a3.5 3.5 0 000 7h5a3.5 3.5 0 010 7H6"/></svg>
                </div>
                <div class="trust-text">
                    <strong>Transparente</strong>
                    Sin costes ocultos
                </div>
            </div>
        </div>
    </section>

    {{-- ── KPI BAR ─────────────────────────────────────── --}}
    @php
        $kpis = [
            ['k' => 'Año', 'v' => $anioTxt, 's' => $car->year ? 'Primera matriculación' : null, 'class' => ''],
            ['k' => 'Kilómetros', 'v' => $kmTxt ?? '—', 's' => 'Verificados', 'class' => ''],
            ['k' => 'Combustible', 'v' => $fuelTxt ?? '—', 's' => null, 'class' => ''],
            ['k' => 'Cambio', 'v' => ucfirst($cambioTxt ?? '—'), 's' => null, 'class' => ''],
            ['k' => 'Potencia', 'v' => $potencia ?? '—', 's' => null, 'class' => ''],
            ['k' => 'Procedencia', 'v' => $car->pais_origen ?? '—', 's' => 'Historial limpio', 'class' => 'green'],
        ];
        $kpis = array_filter($kpis, fn($x) => !empty($x['v']) && $x['v'] !== '—');
    @endphp
    @if(count($kpis) > 0)
        <section class="kpi-bar">
            <div class="kpi-grid">
                @foreach($kpis as $k)
                    <div class="kpi">
                        <div class="kpi-k">{{ $k['k'] }}</div>
                        <div class="kpi-v @if($k['class']){{ $k['class'] }}@endif">{{ $k['v'] }}</div>
                        @if($k['s'])<div class="kpi-s">{{ $k['s'] }}</div>@endif
                    </div>
                @endforeach
            </div>
        </section>
    @endif

    {{-- ── CONTENIDO PRINCIPAL ─────────────────────────── --}}
    <main class="container">

        {{-- VEREDICTO --}}
        @php
            $verdictMap = [
                'Buy' => ['label' => 'Recomendado', 'class' => 'v-green'],
                'Buy if price drops' => ['label' => 'Comprar si baja de precio', 'class' => 'v-orange'],
                'Doubtful' => ['label' => 'Dudoso', 'class' => 'v-orange'],
                'Discard' => ['label' => 'No recomendado', 'class' => 'v-red'],
            ];
            $verdictInfo = $verdictMap[$car->verdict] ?? null;
            $dictamen = $esqueleto?->uno('DICTAMEN');
        @endphp
        @if($dictamen || $verdictInfo)
            <section id="veredicto" class="verdict">
                <div class="verdict-eyebrow">Veredicto JJ Import Motors</div>
                @if($verdictInfo)
                    <div class="verdict-badge {{ $verdictInfo['class'] }}">{{ $verdictInfo['label'] }}</div>
                @endif
                <h2 class="verdict-h">{{ $esqueleto?->uno('RESUMEN') ?? $tituloFicha ?? 'Nuestra recomendación' }}</h2>
                <p class="verdict-body">{{ $dictamen ?? $car->verdict_reasoning ?? 'Hemos analizado este vehículo en detalle: historial, estado, documentación y precio frente al mercado. El resultado de ese análisis está resumido aquí. Consúltanos cualquier duda.' }}</p>
                <div class="verdict-footer">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><polyline points="12,6 12,12 16,14"/></svg>
                    Análisis actualizado el {{ ($car->verdict_at ?? now())->format('d/m/Y') }} · Basado en inspección + datos del fabricante
                </div>
            </section>
        @endif

        {{-- POR QUÉ ESTE COCHE (argumentos de la ficha) --}}
        @php $argumentos = $esqueleto ? $esqueleto->todos('ARGUMENTO') : []; @endphp
        @if(count($argumentos) > 0)
            <section id="argumentos">
                <div class="section-title">Argumentos</div>
                <h2 class="section-h">¿Por qué este coche?</h2>
                <div class="argumentos-grid">
                    @foreach($argumentos as $arg)
                        <div class="argumento-item">
                            <span class="argumento-check">★</span>
                            <div>{!! App\Support\Esqueleto::negrita($arg) !!}</div>
                        </div>
                    @endforeach
                </div>
            </section>
        @endif

        {{-- INCLUYE (contenido real de la ficha; fallback a servicio genérico) --}}
        @php
            $incluyeFicha = $esqueleto ? $esqueleto->todos('INCLUYE') : [];
            $incluyeFallback = [
                ['strong' => $esEspaña ? 'Búsqueda en España' : 'Búsqueda e importación', 'txt' => 'Buscamos el mejor coche para ti'],
                ['strong' => 'Inspección previa', 'txt' => 'Revisión del vehículo antes de '.($esEspaña ? 'comprar' : 'importar')],
                ['strong' => 'Gestión completa', 'txt' => $esEspaña ? 'Trámites y transporte' : 'Trámites, transporte y matriculación'],
                ['strong' => 'Historial verificado', 'txt' => 'Origen y kilometraje confirmados'],
                ['strong' => 'Entrega a domicilio', 'txt' => 'Te lo dejamos en tu puerta'],
                ['strong' => 'Acompañamiento', 'txt' => 'Contigo durante todo el proceso'],
            ];
        @endphp
        <section class="incluye">
            <h3>Qué incluye este precio</h3>
            <div class="incluye-grid">
                @if(count($incluyeFicha) > 0)
                    @foreach($incluyeFicha as $item)
                        <div class="incluye-item">
                            <span class="incluye-check">✓</span>
                            <div><strong>{{ $item }}</strong></div>
                        </div>
                    @endforeach
                @else
                    @foreach($incluyeFallback as $item)
                        <div class="incluye-item">
                            <span class="incluye-check">✓</span>
                            <div>
                                <strong>{{ $item['strong'] }}</strong>
                                {{ $item['txt'] }}
                            </div>
                        </div>
                    @endforeach
                @endif
            </div>
        </section>

        {{-- GALERÍA --}}
        @if(count($fotos) > 1)
            <section id="galeria" class="gallery-wrap">
                <div class="section-title">Galería</div>
                <h2 class="section-h">Fotos reales del vehículo</h2>
                <div class="gallery" id="gallery">
                    @foreach($fotos as $i => $foto)
                        <div class="shot" data-index="{{ $i }}" onclick="openLightbox({{ $i }})">
                            <img src="{{ $foto }}" alt="Foto {{ $i+1 }}">
                        </div>
                    @endforeach
                </div>
            </section>
        @endif

        {{-- PROS / CONS (desde BD: análisis con peso) --}}
        @php
            $prosDb = $car->pros ?? [];
            $consDb = $car->cons ?? [];
        @endphp
        @if(count($prosDb) > 0 || count($consDb) > 0)
            <section>
                <div class="section-title">Puntos clave</div>
                <h2 class="section-h">Lo bueno y lo que debes saber</h2>
                <div class="proscons">
                    @if(count($prosDb) > 0)
                        <div class="pc-col pros">
                            <h3>Puntos a favor</h3>
                            <ul>
                                @foreach($prosDb as $p)
                                    <li>
                                        {{ $p['text'] ?? $p }}
                                        @if(($p['weight'] ?? '') === 'high')<span class="w-badge w-high">clave</span>@endif
                                    </li>
                                @endforeach
                            </ul>
                        </div>
                    @endif
                    @if(count($consDb) > 0)
                        <div class="pc-col cons">
                            <h3>Aspectos a considerar</h3>
                            <ul>
                                @foreach($consDb as $c)
                                    <li>
                                        {{ $c['text'] ?? $c }}
                                        @if(($c['weight'] ?? '') === 'medium')<span class="w-badge w-med">revisar</span>@endif
                                    </li>
                                @endforeach
                            </ul>
                        </div>
                    @endif
                </div>
            </section>
        @endif

        {{-- ESPECIFICACIONES (SPEC de la ficha + datos de BD) --}}
        @php
            $specRows = [];
            if ($esqueleto) {
                foreach ($esqueleto->filas('SPEC') as $fila) {
                    if (count($fila) >= 2) {
                        $specRows[] = ['k' => $fila[0], 'v' => $fila[1]];
                    }
                }
            }
            $extras = [
                'Color' => $car->color,
                'Puertas' => $car->doors,
                'Plazas' => $car->seats,
                'Combustible' => $fuelTxt,
                'Kilómetros' => $kmTxt,
                'Procedencia' => $car->pais_origen,
                'Versión' => $car->version,
            ];
            foreach ($extras as $k => $v) {
                if ($v && ! collect($specRows)->contains(fn($r) => mb_strtolower($r['k']) === mb_strtolower($k))) {
                    $specRows[] = ['k' => $k, 'v' => $v];
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

        {{-- EQUIPAMIENTO (ficha o BD) --}}
        @php
            $equipamiento = $esqueleto ? $esqueleto->lista('EQUIPAMIENTO') : [];
            if (count($equipamiento) === 0 && $car->equipment) {
                $equipamiento = $car->equipment;
            }
        @endphp
        @if(count($equipamiento) > 0)
            <section>
                <div class="section-title">Equipamiento</div>
                <h2 class="section-h">Extras y opciones destacadas</h2>
                <div class="equip">
                    @foreach($equipamiento as $item)
                        <div class="equip-item">{{ is_string($item) ? $item : ($item['text'] ?? '') }}</div>
                    @endforeach
                </div>
            </section>
        @endif

        {{-- TIPS (BD) --}}
        @if($car->tips && count($car->tips) > 0)
            <section class="tips">
                <h3><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M9 18h6M10 22h4M12 2a7 7 0 00-4 12.7c.6.5 1 1.4 1 2.3h6c0-.9.4-1.8 1-2.3A7 7 0 0012 2z"/></svg> Cosas que debes saber antes de comprar</h3>
                <ul>
                    @foreach($car->tips as $t)
                        <li>{{ is_string($t) ? $t : ($t['text'] ?? '') }}</li>
                    @endforeach
                </ul>
            </section>
        @endif

        {{-- CTA FINAL --}}
        <section class="cta-final">
            <div class="cta-eyebrow">¿Te interesa este coche?</div>
            <h2>Consúltanos el servicio completo</h2>
            <p>Este precio es el del vehículo. Nosotros gestionamos {{ $esEspaña ? 'la compra' : 'la importación' }} y todos los trámites para que lo recibas listo en tu domicilio{{ $plazo ? ' en '.$plazo : '' }}.</p>
            <div class="cta-buttons">
                <a href="https://wa.me/34675701439?text={{ urlencode('Hola, me interesa el '.$car->brand.' '.$car->model.'. ¿Podéis darme más información del servicio?') }}"
                   target="_blank" rel="noopener" class="btn wa big">
                    <svg class="ic" viewBox="0 0 24 24" fill="currentColor"><path d="M12.04 2c-5.46 0-9.91 4.45-9.91 9.91 0 1.75.46 3.45 1.32 4.95L2.05 22l5.25-1.38a9.87 9.87 0 004.74 1.21h.01c5.46 0 9.9-4.45 9.9-9.91 0-2.65-1.03-5.14-2.9-7.01A9.82 9.82 0 0012.04 2zm0 18.15c-1.48 0-2.93-.4-4.2-1.15l-.3-.18-3.12.82.83-3.04-.2-.31a8.26 8.26 0 01-1.26-4.38c0-4.54 3.7-8.24 8.24-8.24 2.2 0 4.27.86 5.82 2.42a8.18 8.18 0 012.41 5.83c-.01 4.54-3.7 8.23-8.22 8.23zm4.52-6.16c-.25-.13-1.47-.72-1.69-.8-.23-.08-.39-.13-.56.12-.16.25-.64.81-.78.97-.15.17-.29.19-.54.06-.25-.12-1.05-.39-1.99-1.23-.74-.66-1.23-1.47-1.38-1.72-.14-.25-.01-.38.11-.51.11-.11.25-.29.37-.43.13-.15.17-.25.25-.42.08-.17.04-.31-.02-.43-.06-.13-.56-1.34-.76-1.84-.2-.48-.4-.42-.56-.42h-.48c-.17 0-.43.06-.66.31-.22.25-.86.85-.86 2.07 0 1.22.89 2.4 1.01 2.56.12.17 1.75 2.67 4.23 3.74.59.26 1.05.41 1.41.52.59.19 1.13.16 1.56.1.48-.07 1.47-.6 1.67-1.18.21-.58.21-1.07.14-1.18-.06-.1-.22-.16-.47-.28z"/></svg>
                    Consultar por WhatsApp
                </a>
                <a href="tel:+34675701439" class="btn ghost big">
                    <svg class="ic" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 16.92v3a2 2 0 01-2.18 2 19.79 19.79 0 01-8.63-3.07 19.5 19.5 0 01-6-6 19.79 19.79 0 01-3.07-8.67A2 2 0 014.11 2h3a2 2 0 012 1.72c.127.96.361 1.903.7 2.81a2 2 0 01-.45 2.11L8.09 9.91a16 16 0 006 6l1.27-1.27a2 2 0 012.11-.45c.907.339 1.85.573 2.81.7A2 2 0 0122 16.92z"/></svg>
                    675 70 14 39
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
        <div class="slogan">Especialistas en importación de vehículos desde Alemania y España</div>
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
            © {{ date('Y') }} JJ Import Motors · Dossier generado el {{ now()->format('d/m/Y H:i') }}
            @php $legal = $esqueleto?->uno('LEGAL'); @endphp
            @if($legal)
                <div class="legal">{{ $legal }}</div>
            @endif
        </div>
    </footer>

    {{-- ── LIGHTBOX ────────────────────────────────────── --}}
    @if(count($fotos) > 1)
    <div class="lightbox" id="lightbox" role="dialog" aria-modal="true" aria-label="Galería de fotos">
        <button class="lightbox-close" onclick="closeLightbox()" aria-label="Cerrar galería">×</button>
        <button class="lightbox-prev" onclick="prevPhoto()" aria-label="Foto anterior">‹</button>
        <button class="lightbox-next" onclick="nextPhoto()" aria-label="Foto siguiente">›</button>
        <img id="lightbox-img" src="" alt="{{ $car->brand }} {{ $car->model }}">
        <div class="lightbox-counter" id="lightbox-counter" aria-live="polite"></div>
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