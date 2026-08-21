<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <title>Contrato firmado · JJ Import Motors</title>
    <style>
        @page { size: A4; margin: 18mm 14mm 22mm; }
        body { font-family: 'Helvetica', 'Arial', sans-serif; color: #1f2937; font-size: 11px; line-height: 1.55; }
        h2 { font-size: 16px; color: #1f4e79; margin-top: 18px; margin-bottom: 8px; border-bottom: 2px solid #1f4e79; padding-bottom: 4px; }
        h3 { font-size: 12px; color: #0e2c46; margin-top: 12px; margin-bottom: 4px; font-weight: 600; }
        hr { border: 0; border-top: 1px solid #e5e8eb; margin: 10px 0; }
        p { margin: 6px 0; text-align: justify; }
        strong { color: #0f121c; }
        .cover { background: linear-gradient(135deg, #1f4e79 0%, #0e2c46 100%); color: white; padding: 18px; border-radius: 8px; margin-bottom: 16px; }
        .cover h1 { font-size: 18px; margin: 0 0 4px; }
        .cover small { color: #dbe8f4; font-size: 10px; }
        .badge { display: inline-block; background: #10b981; color: white; padding: 3px 8px; border-radius: 4px; font-size: 9px; font-weight: 600; letter-spacing: 1px; text-transform: uppercase; }
        .meta { margin-top: 12px; padding: 10px 12px; background: #f3f4f7; border: 1px solid #e6e8ee; border-radius: 6px; font-size: 9.5px; }
        .meta dt { color: #4a5266; font-weight: 600; text-transform: uppercase; letter-spacing: 0.5px; font-size: 8.5px; margin-top: 4px; }
        .meta dd { margin: 0; color: #0f121c; }
        .hash { font-family: 'Courier New', monospace; word-break: break-all; font-size: 8.5px; color: #4a5266; }
        .footer-block { margin-top: 22px; padding-top: 12px; border-top: 2px solid #1f4e79; font-size: 9px; color: #4a5266; }
        .signature { margin-top: 18px; display: flex; justify-content: space-between; gap: 24px; }
        .signature .box { flex: 1; border-top: 1px solid #1c2030; padding-top: 4px; font-size: 9px; color: #4a5266; }
    </style>
</head>
<body>

<div class="cover">
    <h1>Contrato de prestación de servicios</h1>
    <small>Servicio de importación de vehículo desde Alemania</small>
    <div style="margin-top:10px;">
        <span class="badge">FIRMADO ELECTRÓNICAMENTE</span>
    </div>
    <dl class="meta">
        <dt>Contrato</dt>
        <dd>#{{ $contract->id }} · versión {{ $contract->contract_version }}</dd>
        <dt>Vehículo</dt>
        <dd>{{ $contract->car->brand }} {{ $contract->car->model }} ({{ $contract->car->year }})</dd>
        <dt>Cliente</dt>
        <dd>{{ $contract->client_name ?: '—' }} · {{ $contract->client_email }}</dd>
        <dt>Fecha de firma</dt>
        <dd>{{ $acceptedAt }} (hora servidor)</dd>
        <dt>Dirección IP</dt>
        <dd>{{ $ip }}</dd>
        @if($trackingUrl)
        <dt>URL seguimiento</dt>
        <dd>{{ $trackingUrl }}</dd>
        @endif
        <dt>Hash SHA256 del texto firmado</dt>
        <dd class="hash">{{ $hash }}</dd>
    </dl>
</div>

{!! $contractTextHtml !!}

<div class="signature">
    <div class="box">
        <strong>EL PRESTADOR</strong><br>
        {{ $prestador['razon_social'] }}<br>
        CIF: {{ $prestador['cif'] }}<br>
        {{ $prestador['direccion'] }}
    </div>
    <div class="box">
        <strong>EL CLIENTE</strong><br>
        {{ $contract->client_name ?: '—' }}<br>
        DNI/NIE: {{ $contract->client_dni ?: '—' }}<br>
        Email: {{ $contract->client_email }}
    </div>
</div>

<div class="footer-block">
    <p>
        <strong>Integridad del documento.</strong> El texto íntegro del contrato
        firmado genera el hash SHA256 indicado más arriba. Cualquier
        modificación posterior del texto (en config/contracts.php) NO afecta a
        este contrato, que permanece vinculante bajo el hash registrado.
    </p>
    <p>
        Conforme al art. 9 de la Ley 34/2002 de Servicios de la Sociedad de la
        Información y de Comercio Electrónico (LSSI-CE) y al art. 25 del
        Reglamento (UE) 910/2014 (eIDAS), la presente firma electrónica
        constituye consentimiento válido para la perfección del contrato.
        Conservación: 5 años desde la finalización del servicio.
    </p>
</div>

</body>
</html>