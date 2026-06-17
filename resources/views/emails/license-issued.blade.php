<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <title>Licencia TipiDV</title>
</head>
<body style="font-family:Segoe UI,Arial,sans-serif;color:#111827;line-height:1.6;max-width:560px;margin:0 auto;padding:24px;">
    <h2 style="color:#f26c20;margin-top:0;">TipiDV — tu licencia está lista</h2>
    <p>Hola{{ $subscription->customer_name ? ' ' . e($subscription->customer_name) : '' }},</p>
    @if($subscription->organization_name)
        <p>Institución: <strong>{{ $subscription->organization_name }}</strong></p>
    @endif
    <p>Gracias por tu pago. Guarda este correo: lo necesitarás para activar TipiDV en tu computador.</p>

    <p style="margin:24px 0;"><strong>Tu clave de licencia:</strong><br>
        <code style="font-size:20px;background:#f3f4f6;padding:12px 16px;display:inline-block;letter-spacing:1px;">{{ $subscription->license_key }}</code>
    </p>

    <p><strong>Correo de la cuenta:</strong> {{ $subscription->customer_email }}</p>
    <p><strong>Vigente hasta:</strong> {{ $subscription->expires_at->timezone('America/Bogota')->format('d/m/Y') }}</p>
    <p><strong>Computadores incluidos:</strong> {{ $subscription->machine_slots }}</p>

    <h3 style="color:#111827;">Activar (2 minutos)</h3>
    <ol>
        <li>Descarga e instala <strong>TipiDV</strong>@if(!empty($downloadUrl)) (<a href="{{ $downloadUrl }}">TipiDV-Setup.exe</a>)@endif en el PC de digitalización.</li>
        <li>Ingresa el correo <strong>{{ $subscription->customer_email }}</strong> y la clave de arriba.</li>
        <li>Clic en <strong>Activar equipo</strong>. No hace falta volver a hacerlo en ese mismo PC.</li>
    </ol>

    <p>¿Otro computador? Compra una licencia adicional en el portal (mismo correo institucional) o elige «Otro computador más» al pagar.</p>
    <p>Renovar: <a href="{{ $portalUrl }}">{{ $portalUrl }}</a></p>

    <p style="color:#6b7280;font-size:13px;margin-top:32px;">
        <a href="{{ config('marketing.author_url', 'https://diego.gridsoft.co/') }}" style="color:#6b7280">{{ config('marketing.author', 'Ing. Diego Vargas') }}</a> · TipiDV
    </p>
</body>
</html>
