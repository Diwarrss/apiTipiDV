<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <title>Licencia TipiDV</title>
</head>
<body style="font-family:Segoe UI,Arial,sans-serif;color:#111827;line-height:1.5;">
    <h2 style="color:#f26c20;">TipiDV — licencia activada</h2>
    <p>Hola{{ $subscription->customer_name ? ' ' . e($subscription->customer_name) : '' }},</p>
    <p>Gracias por tu pago. Tu licencia por equipo ya está disponible.</p>
    <p><strong>Clave de licencia:</strong><br>
        <code style="font-size:18px;background:#f3f4f6;padding:8px 12px;display:inline-block;">{{ $subscription->license_key }}</code>
    </p>
    <p><strong>Vigencia hasta:</strong> {{ $subscription->expires_at->timezone('America/Bogota')->format('d/m/Y') }}</p>
    <p><strong>Equipos incluidos:</strong> {{ $subscription->machine_slots }}</p>
    <h3>Activar en tu PC</h3>
    <ol>
        <li>Abre <strong>TipiDV</strong> en el equipo donde vas a trabajar.</li>
        <li>Ingresa tu correo <strong>{{ $subscription->customer_email }}</strong> y la clave de arriba.</li>
        <li>La app vincula automáticamente ese equipo a tu licencia.</li>
    </ol>
    <p>Portal y renovaciones: <a href="{{ $portalUrl }}">{{ $portalUrl }}</a></p>
    <p style="color:#6b7280;font-size:13px;">Ing. Diego Vargas · TipiDV</p>
</body>
</html>
