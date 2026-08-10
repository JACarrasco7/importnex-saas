@php
    $precio_honorarios = '1.500 €';
    $telefono_1 = '675 70 14 39';
    $telefono_2 = '691 48 59 27';
    $email = 'jjimportmotors@gmail.com';
    $qr_url = 'https://jjimportmotors.on-forge.com/request/jj-import-motors';

    $qr_svg = null;
    try {
        $qr_svg = \SimpleSoftwareIO\QrCode\Generator::class
            ? (new \SimpleSoftwareIO\QrCode\Generator())->format('svg')
                ->size(280)
                ->margin(1)
                ->errorCorrection('H')
                ->backgroundColor(255, 255, 255)
                ->color(6, 16, 31)
                ->generate($qr_url)
            : null;
    } catch (\Exception $e) {
        $qr_svg = null;
    }
@endphp

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>JJ Import Motors - Folleto</title>
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800;900&display=swap');

        * { margin: 0; padding: 0; box-sizing: border-box; }

        html, body {
            font-family: 'Inter', 'Segoe UI', Arial, sans-serif;
            background: #0f1d42;
            color: #e5e7eb;
            width: 100%;
            min-height: 100vh;
            -webkit-font-smoothing: antialiased;
            -webkit-print-color-adjust: exact;
            print-color-adjust: exact;
        }

        body {
            position: relative;
            padding: 40px 40px 80px 40px;
            min-height: 100vh;
            background:
                radial-gradient(ellipse at 100% 0%, rgba(26, 48, 109, 0.10) 0%, transparent 45%),
                radial-gradient(ellipse at 0% 100%, rgba(190, 192, 195, 0.10) 0%, transparent 45%),
                linear-gradient(180deg, #0f1d42 0%, #14265a 50%, #0f1d42 100%);
        }

        body::before {
            content: '';
            position: absolute;
            inset: 0;
            background-image:
                linear-gradient(rgba(56, 189, 248, 0.035) 1px, transparent 1px),
                linear-gradient(90deg, rgba(56, 189, 248, 0.035) 1px, transparent 1px);
            background-size: 28px 28px;
            pointer-events: none;
            z-index: 0;
        }

        body::after {
            content: '';
            position: absolute;
            top: 35%;
            right: -120px;
            width: 380px;
            height: 380px;
            background: radial-gradient(circle, rgba(26, 48, 109, 0.08) 0%, transparent 60%);
            border-radius: 50%;
            pointer-events: none;
            z-index: 0;
        }

        .container {
            position: relative;
            z-index: 1;
            max-width: 1100px;
            margin: 0 auto;
        }

        /* ============ HEADER ============ */
        .header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding-bottom: 18px;
            border-bottom: 1px solid rgba(56, 189, 248, 0.12);
            margin-bottom: 26px;
        }

        .logo {
            height: 52px;
            width: auto;
            filter: drop-shadow(0 2px 8px rgba(0,0,0,0.4));
        }

        .badge-llave {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            background: linear-gradient(135deg, #1A306D 0%, #2a3d87 100%);
            color: #fff;
            padding: 9px 18px;
            border-radius: 100px;
            font-size: 11px;
            font-weight: 700;
            letter-spacing: 1.5px;
            box-shadow: 0 4px 14px rgba(26, 48, 109, 0.35);
        }

        .badge-llave svg {
            width: 14px;
            height: 14px;
            fill: #fff;
        }

        /* ============ HERO ============ */
        .hero {
            width: 100%;
            margin-bottom: 30px;
            margin-top: 15px !important;
        }

        .hero-eyebrow {
            display: inline-flex;
            align-items: center;
            gap: 9px;
            background: rgba(26, 48, 109, 0.08);
            border: 1px solid rgba(143, 163, 217, 0.25);
            color: #8fa3d9;
            padding: 6px 14px;
            border-radius: 100px;
            font-size: 10.5px;
            font-weight: 600;
            letter-spacing: 1.5px;
            text-transform: uppercase;
            margin-bottom: 12px;
        }

        .hero-eyebrow .pulse {
            width: 6px;
            height: 6px;
            background: #8fa3d9;
            border-radius: 50%;
            box-shadow: 0 0 10px #8fa3d9;
        }

        .h1-title {
            font-family: 'Inter', sans-serif;
            font-size: 32px;
            font-weight: 800;
            line-height: 1.15;
            color: #ffffff;
            margin-bottom: 8px;
            letter-spacing: -0.8px;
            width: 100%;
        }

        .h1-title .accent {
            color: #8fa3d9;
            font-weight: 800;
            display: inline-block;
        }

        .hero-subtitle {
            font-size: 15px;
            color: #94a3b8;
            font-weight: 400;
            margin-bottom: 16px;
            letter-spacing: 0.2px;
        }

        .sub-badges {
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
        }

        .sub-badge {
            background: rgba(30, 41, 59, 0.5);
            border: 1px solid rgba(143, 163, 217, 0.2);
            color: #cbd5e1;
            padding: 7px 16px;
            border-radius: 100px;
            font-size: 12.5px;
            font-weight: 500;
            display: flex;
            align-items: center;
            gap: 7px;
        }

        .sub-badge .check {
            width: 14px;
            height: 14px;
            background: #8fa3d9;
            border-radius: 50%;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
        }

        .sub-badge .check::after {
            content: '';
            width: 4px;
            height: 2.5px;
            border-left: 1.8px solid #0f1d42;
            border-bottom: 1.8px solid #0f1d42;
            transform: rotate(-45deg) translate(0.8px, -0.8px);
        }

        /* ============ 3 CARDS (icono + título horizontal) ============ */
        .cards {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 14px;
            margin-bottom: 32px;
        }

        .card {
            background: linear-gradient(180deg, rgba(15, 23, 42, 0.85) 0%, rgba(15, 23, 42, 0.55) 100%);
            border-radius: 12px;
            padding: 14px;
            border-top: 3px solid;
            box-shadow:
                0 1px 3px rgba(0, 0, 0, 0.4),
                0 8px 24px rgba(0, 0, 0, 0.35),
                inset 0 1px 0 rgba(255, 255, 255, 0.05);
            transition: transform 0.2s, box-shadow 0.2s;
        }

        .card:hover {
            transform: translateY(-2px);
            box-shadow:
                0 2px 6px rgba(0, 0, 0, 0.5),
                0 14px 32px rgba(0, 0, 0, 0.45),
                inset 0 1px 0 rgba(255, 255, 255, 0.08);
        }

        .card.informe {
            border-top-color: #8fa3d9;
            box-shadow:
                0 1px 3px rgba(0, 0, 0, 0.4),
                0 8px 24px rgba(143, 163, 217, 0.18),
                inset 0 1px 0 rgba(255, 255, 255, 0.05);
        }

        .card.ahorro {
            border-top-color: #22c55e;
            box-shadow:
                0 1px 3px rgba(0, 0, 0, 0.4),
                0 8px 24px rgba(34, 197, 94, 0.18),
                inset 0 1px 0 rgba(255, 255, 255, 0.05);
        }

        .card.gestion {
            border-top-color: #BEC0C3;
            box-shadow:
                0 1px 3px rgba(0, 0, 0, 0.4),
                0 8px 24px rgba(190, 192, 195, 0.18),
                inset 0 1px 0 rgba(255, 255, 255, 0.05);
        }

        .card-header {
            display: flex;
            align-items: center;
            gap: 10px;
            margin-bottom: 8px;
        }

        .card-icon {
            width: 36px;
            height: 36px;
            border-radius: 9px;
            display: flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
        }

        .card.informe .card-icon { background: rgba(26, 48, 109, 0.15); color: #8fa3d9; }
        .card.ahorro .card-icon { background: rgba(34, 197, 94, 0.15); color: #22c55e; }
        .card.gestion .card-icon { background: rgba(190, 192, 195, 0.15); color: #BEC0C3; }

        .card h3 {
            font-size: 12.5px;
            font-weight: 700;
            color: #f1f5f9;
            letter-spacing: 0.6px;
            line-height: 1.1;
        }

        .card p {
            font-size: 11.5px;
            color: #94a3b8;
            line-height: 1.5;
        }

        /* ============ PROCESS ============ */
        .process-section {
            margin-bottom: 26px;
        }

        .section-tag {
            color: #8fa3d9;
            font-size: 10px;
            font-weight: 700;
            letter-spacing: 2.5px;
            margin-bottom: 4px;
            text-transform: uppercase;
            display: block;
        }

        .section-title {
            font-size: 18px;
            font-weight: 800;
            color: #f8fafc;
            margin-bottom: 22px;
            letter-spacing: -0.3px;
        }

        .process-steps {
            display: grid;
            grid-template-columns: repeat(6, 1fr);
            gap: 8px;
            position: relative;
        }

        .step {
            text-align: center;
            position: relative;
        }

        .step-badge {
            width: 42px;
            height: 42px;
            border-radius: 50%;
            margin: 0 auto 8px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-family: 'Segoe UI', 'Helvetica Neue', Arial, sans-serif;
            font-size: 15px;
            font-weight: 700;
            color: #0f1d42;
            background: #1e293b;
            border: 2px solid #8fa3d9;
            position: relative;
            z-index: 2;
            box-shadow: 0 0 0 4px #0f1d42, 0 4px 14px rgba(143, 163, 217, 0.3);
            line-height: 1;
        }

        .step.active .step-badge {
            background: #8fa3d9;
            color: #0f1d42;
        }

        .step.final .step-badge {
            background: linear-gradient(135deg, #8fa3d9, #BEC0C3);
            color: #fff;
            border-color: #BEC0C3;
            box-shadow: 0 0 0 4px #0f1d42, 0 4px 18px rgba(190, 192, 195, 0.5);
        }

        .step:not(:last-child)::after {
            content: '';
            position: absolute;
            top: 21px;
            left: calc(50% + 24px);
            right: calc(-50% + 24px);
            height: 0;
            border-top: 2px dashed rgba(143, 163, 217, 0.3);
            z-index: 1;
        }

        .step-title {
            font-size: 11px;
            font-weight: 700;
            color: #e2e8f0;
            margin-bottom: 3px;
            letter-spacing: 0.4px;
            text-transform: uppercase;
        }

        .step-desc {
            font-size: 10.5px;
            color: #94a3b8;
            line-height: 1.35;
        }

        /* ============ TRANSPARENCY ============ */
        .transparency-section {
            background: linear-gradient(180deg, rgba(15, 23, 42, 0.6) 0%, rgba(15, 23, 42, 0.35) 100%);
            border: 1px solid rgba(56, 189, 248, 0.12);
            border-radius: 14px;
            padding: 20px 24px;
            margin-bottom: 24px;
        }

        .transparency-section .section-tag,
        .transparency-section .section-title,
        .transparency-section .transparency-desc {
            text-align: center;
        }

        .transparency-section .section-title { margin-bottom: 6px; }
        .transparency-section .section-tag { margin-bottom: 3px; }

        .transparency-desc {
            font-size: 12px;
            color: #94a3b8;
            margin-bottom: 16px;
        }

        .transparency-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 8px 28px;
        }

        .transparency-item {
            display: flex;
            align-items: center;
            gap: 10px;
            font-size: 12px;
            color: #cbd5e1;
            padding: 4px 0;
        }

        .transparency-item .check {
            width: 16px;
            height: 16px;
            background: #8fa3d9;
            border-radius: 50%;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
        }

        .transparency-item .check::after {
            content: '';
            width: 5px;
            height: 3px;
            border-left: 2px solid #0f1d42;
            border-bottom: 2px solid #0f1d42;
            transform: rotate(-45deg) translate(0.8px, -0.8px);
        }

        .transparency-item strong {
            color: #f1f5f9;
            font-weight: 600;
        }

        .transparency-item .sub {
            color: #64748b;
            font-size: 10.5px;
        }

        /* ============ HONORARIOS + QR ============ */
        .honorarios-row {
            display: grid;
            grid-template-columns: 1.6fr 1fr;
            gap: 14px;
            margin-bottom: 28px;
        }

        .honorarios-section {
            background: linear-gradient(135deg, rgba(26, 48, 109, 0.10) 0%, rgba(190, 192, 195, 0.10) 100%);
            border: 1px solid rgba(143, 163, 217, 0.25);
            border-radius: 14px;
            padding: 18px 22px;
            text-align: center;
            display: flex;
            flex-direction: column;
            justify-content: center;
        }

        .honorarios-tag {
            color: #8fa3d9;
            font-size: 10px;
            font-weight: 700;
            letter-spacing: 2.5px;
            margin-bottom: 6px;
            text-transform: uppercase;
            display: inline-flex;
            align-items: center;
            gap: 6px;
        }

        .honorarios-tag svg {
            width: 14px;
            height: 14px;
            fill: #8fa3d9;
        }

        .honorarios-title {
            font-size: 14px;
            font-weight: 700;
            color: #f8fafc;
            margin-bottom: 6px;
        }

        .honorarios-price {
            font-size: 44px;
            font-weight: 900;
            color: #8fa3d9;
            line-height: 1;
            margin-bottom: 5px;
        }

        .honorarios-conditions {
            font-size: 11.5px;
            color: #94a3b8;
            line-height: 1.5;
        }

        .honorarios-conditions strong { color: #e2e8f0; }

        .qr-card {
            background: linear-gradient(180deg, rgba(15, 23, 42, 0.75) 0%, rgba(15, 23, 42, 0.45) 100%);
            border: 1px solid rgba(143, 163, 217, 0.3);
            border-radius: 14px;
            padding: 12px;
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .qr-box {
            flex-shrink: 0;
            width: 95px;
            height: 95px;
            background: #fff;
            border-radius: 9px;
            padding: 4px;
            display: flex;
            align-items: center;
            justify-content: center;
            box-shadow: 0 4px 18px rgba(143, 163, 217, 0.3);
        }

        .qr-box svg {
            width: 100%;
            height: 100%;
            display: block;
        }

        .qr-content {
            flex: 1;
        }

        .qr-tag {
            color: #8fa3d9;
            font-size: 9.5px;
            font-weight: 700;
            letter-spacing: 1.8px;
            margin-bottom: 4px;
            text-transform: uppercase;
        }

        .qr-title {
            font-size: 14px;
            font-weight: 800;
            color: #f8fafc;
            margin-bottom: 4px;
            line-height: 1.1;
        }

        .qr-desc {
            font-size: 10px;
            color: #94a3b8;
            line-height: 1.35;
        }

        /* ============ FOOTER ============ */
        .footer {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 10px;
            background: rgba(15, 23, 42, 0.55);
            border-radius: 10px;
            padding: 12px 16px;
            border: 1px solid rgba(56, 189, 248, 0.12);
        }

        .footer-item {
            display: flex;
            align-items: center;
            gap: 10px;
            font-size: 11.5px;
            color: #cbd5e1;
        }

        .footer-item .icon {
            width: 32px;
            height: 32px;
            border-radius: 8px;
            background: rgba(26, 48, 109, 0.14);
            color: #8fa3d9;
            display: flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
        }

        .footer-item .label {
            font-size: 9px;
            color: #64748b;
            text-transform: uppercase;
            letter-spacing: 0.6px;
            font-weight: 600;
            margin-bottom: 1px;
        }

        .footer-item .value {
            font-weight: 700;
            color: #f1f5f9;
            font-size: 12px;
            letter-spacing: 0.2px;
        }

        .disclaimer {
            text-align: center;
            font-size: 10px;
            color: #94a3b8;
            margin-top: 20px;
            line-height: 1.5;
            padding: 0 18px;
            font-style: italic;
        }
    </style>
</head>
<body>
    <div class="container">

        <div class="header">
            <img src="{{ $logo_base64 }}" alt="JJ Import Motors" class="logo">
            <span class="badge-llave"><span class="dot"></span>SERVICIO LLAVE EN MANO</span>
        </div>

        <div class="hero">
            <span class="hero-eyebrow"><span class="pulse"></span>Importación Premium · Alemania → España</span>
            <h1 class="h1-title">Tu vehículo ideal, <span class="accent">directo a la puerta de tu casa.</span></h1>
            <p class="hero-subtitle">Nos encargamos de buscar, negociar y matricular tu vehículo desde Alemania con servicio 100% Llave en Mano.</p>
            <div class="sub-badges">
                <div class="sub-badge"><span class="check"></span>Informes Objetivos</div>
                <div class="sub-badge"><span class="check"></span>Papeleo Cero para Ti</div>
                <div class="sub-badge"><span class="check"></span>Entrega Matriculado</div>
            </div>
        </div>

        <div class="cards" style="margin-top: 28px;">
            <div class="card informe">
                <div class="card-header">
                    <div class="card-icon">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M9 11l3 3L22 4"/><path d="M21 12v7a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11"/></svg>
                    </div>
                    <h3>INFORME TÉCNICO</h3>
                </div>
                <p>Investigamos a fondo el vehículo: mecánica, historial, recalls y comparables reales del mercado.</p>
            </div>
            <div class="card ahorro">
                <div class="card-header">
                    <div class="card-icon">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><circle cx="12" cy="12" r="10"/><path d="M16 8h-6a2 2 0 1 0 0 4h4a2 2 0 1 1 0 4H8"/><path d="M12 6v2m0 8v2"/></svg>
                    </div>
                    <h3>AHORRO REAL</h3>
                </div>
                <p>El mercado alemán ofrece mejor precio y mayor equipamiento en vehículos equivalentes.</p>
            </div>
            <div class="card gestion">
                <div class="card-header">
                    <div class="card-icon">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><circle cx="12" cy="12" r="10"/><path d="M12 6v6l4 2"/></svg>
                    </div>
                    <h3>GESTIÓN 100% TOTAL</h3>
                </div>
                <p>Transporte, ITV de importación, impuesto IEDMT, tasas DGT y matriculación a tu nombre.</p>
            </div>
        </div>

        <div class="process-section">
            <span class="section-tag">PASO A PASO</span>
            <h2 class="section-title">El Proceso de Importación</h2>
            <div class="process-steps">
                <div class="step active">
                    <div class="step-badge">1</div>
                    <div class="step-title">Tus Datos</div>
                    <div class="step-desc">Nos cuentas qué vehículo buscas</div>
                </div>
                <div class="step active">
                    <div class="step-badge">2</div>
                    <div class="step-title">Rastreo</div>
                    <div class="step-desc">Búsqueda activa en Alemania</div>
                </div>
                <div class="step active">
                    <div class="step-badge">3</div>
                    <div class="step-title">Investigación</div>
                    <div class="step-desc">Revisión técnica y de historial</div>
                </div>
                <div class="step active">
                    <div class="step-badge">4</div>
                    <div class="step-title">Informe Ficha</div>
                    <div class="step-desc">Fotos y precio estimado claro</div>
                </div>
                <div class="step active">
                    <div class="step-badge">5</div>
                    <div class="step-title">Activación</div>
                    <div class="step-desc">Compra, flete y trámites legales</div>
                </div>
                <div class="step final">
                    <div class="step-badge">6</div>
                    <div class="step-title">¡Entrega!</div>
                    <div class="step-desc">Llaves en mano, matriculado</div>
                </div>
            </div>
        </div>

        <div class="transparency-section">
            <span class="section-tag">TRANSPARENCIA TOTAL</span>
            <h2 class="section-title">¿Qué incluye nuestro servicio de gestión?</h2>
            <p class="transparency-desc">Gestionamos cada trámite necesario para que no tengas que preocuparte por nada.</p>
            <div class="transparency-grid">
                <div class="transparency-item">
                    <span class="check"></span>
                    <span><strong>Búsqueda y Verificación</strong> <span class="sub">— Inspección en origen</span></span>
                </div>
                <div class="transparency-item">
                    <span class="check"></span>
                    <span><strong>Impuesto Matriculación</strong> <span class="sub">— Liquidación IEDMT</span></span>
                </div>
                <div class="transparency-item">
                    <span class="check"></span>
                    <span><strong>Transporte Internacional</strong> <span class="sub">— Alemania → España</span></span>
                </div>
                <div class="transparency-item">
                    <span class="check"></span>
                    <span><strong>Tasas Oficiales DGT</strong> <span class="sub">— Trámites incluidos</span></span>
                </div>
                <div class="transparency-item">
                    <span class="check"></span>
                    <span><strong>ITV de Importación</strong> <span class="sub">— Ficha reducida incluida</span></span>
                </div>
                <div class="transparency-item">
                    <span class="check"></span>
                    <span><strong>Gestión Documental y COC</strong> <span class="sub">— Certificado conformidad</span></span>
                </div>
                <div class="transparency-item">
                    <span class="check"></span>
                    <span><strong>Informe Técnico Objetivo</strong> <span class="sub">— Historial y valoración</span></span>
                </div>
                <div class="transparency-item">
                    <span class="check"></span>
                    <span><strong>Matriculación Final</strong> <span class="sub">— A tu nombre en España</span></span>
                </div>
            </div>
        </div>

        <div class="honorarios-row">
            <div class="honorarios-section">
                <div class="honorarios-tag">
                    <svg viewBox="0 0 24 24" fill="currentColor"><path d="M19.14 17.14c-.27-.27-.45-.61-.6-.96-.16-.39-.23-.71-.23-1.18v-3c0-.55-.45-1-1-1H6.69c-.55 0-1 .45-1 1v3c0 .47-.07.79-.23 1.18-.15.35-.33.69-.6.96-.29.29-.43.7-.43 1.12 0 .83.67 1.5 1.5 1.5h1.5c.83 0 1.5-.67 1.5-1.5 0-.12 0-.27-.06-.39h7.06c-.06.12-.06.27-.06.39 0 .83.67 1.5 1.5 1.5h1.5c.83 0 1.5-.67 1.5-1.5 0-.42-.14-.83-.43-1.12zM6.69 13.5h10.62c.41 0 .75.34.75.75v.75H5.94v-.75c0-.41.34-.75.75-.75zm10.62-4.5H6.69c-.41 0-.75.34-.75.75v.75h12.12v-.75c0-.41-.34-.75-.75-.75zm3-3H3.69c-.55 0-1 .45-1 1v9c0 .55.45 1 1 1h1.16c.13-.39.34-.74.6-1.04.27-.27.45-.61.6-.96.16-.39.23-.71.23-1.18V9.75c0-.83.67-1.5 1.5-1.5h10.62c.83 0 1.5.67 1.5 1.5v3.82c0 .47.07.79.23 1.18.15.35.33.69.6.96.26.3.47.65.6 1.04h1.16c.55 0 1-.45 1-1v-9c0-.55-.45-1-1-1z"/></svg>
                    Nuestros Honorarios de Gestión
                </div>
                <div class="honorarios-price">{{ $precio_honorarios }}</div>
                <div class="honorarios-conditions"><strong>+ coste del vehículo e impuestos</strong><br>Contacta sin compromiso · Ponte en manos de profesionales.</div>
            </div>
            <div class="qr-card">
                <div class="qr-box">
                    {!! $qr_svg !!}
                </div>
                <div class="qr-content">
                    <div class="qr-tag">Solicita tu vehículo</div>
                    <div class="qr-title">Escanea para<br>empezar</div>
                    <div class="qr-desc">Rellena el formulario de solicitud directo desde tu móvil.</div>
                </div>
            </div>
        </div>

        <div class="footer">
            <div class="footer-item">
                <div class="icon">
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72 12.84 12.84 0 0 0 .7 2.81 2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45 12.84 12.84 0 0 0 2.81.7A2 2 0 0 1 22 16.92z"/></svg>
                </div>
                <div>
                    <div class="label">Teléfono 1</div>
                    <div class="value">{{ $telefono_1 }}</div>
                </div>
            </div>
            <div class="footer-item">
                <div class="icon">
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72 12.84 12.84 0 0 0 .7 2.81 2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45 12.84 12.84 0 0 0 2.81.7A2 2 0 0 1 22 16.92z"/></svg>
                </div>
                <div>
                    <div class="label">Teléfono 2</div>
                    <div class="value">{{ $telefono_2 }}</div>
                </div>
            </div>
            <div class="footer-item">
                <div class="icon">
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"/><polyline points="22,6 12,13 2,6"/></svg>
                </div>
                <div>
                    <div class="label">Email</div>
                    <div class="value">{{ $email }}</div>
                </div>
            </div>
        </div>

        <p class="disclaimer">*El precio final puede variar según las especificaciones del vehículo, impuestos aplicables, logística y transporte.</p>

    </div>
</body>
</html>
