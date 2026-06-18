<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <title>Licencia TipiDV</title>
</head>
<body style="font-family:Segoe UI,Arial,sans-serif;color:#111827;line-height:1.5;max-width:560px;margin:0 auto;padding:24px;">
    <h2 style="color:#f26c20;margin-top:0;">TipiDV — licencia activada</h2>
    <p>Hola{{ $subscription->customer_name ? ' ' . e($subscription->customer_name) : '' }},</p>
    <p>Gracias por tu pago. Tu licencia ya está disponible.</p>

    <p><strong>Clave de licencia:</strong><br>
        <code style="font-size:18px;background:#f3f4f6;padding:10px 14px;display:inline-block;border-radius:6px;">{{ $subscription->license_key }}</code>
    </p>

    <p><strong>Vigencia hasta:</strong> {{ $subscription->expires_at->timezone('America/Bogota')->format('d/m/Y') }}</p>
    <p><strong>Equipos incluidos en tu paquete:</strong> {{ $subscription->machine_slots }}</p>

    @if($subscription->machine_slots > 1)
        <p style="background:#fff7ed;border-left:4px solid #f26c20;padding:12px 14px;border-radius:4px;">
            Esta <strong>única clave</strong> puede activarse en hasta <strong>{{ $subscription->machine_slots }} PCs</strong>.
            Usa el mismo correo (<strong>{{ $subscription->customer_email }}</strong>) y la misma clave en cada equipo.
        </p>
    @endif

    @if(!empty($downloadUrl))
        <p style="margin:20px 0;text-align:center;">
            <a href="{{ $downloadUrl }}" style="display:inline-block;background:#f26c20;color:#fff;text-decoration:none;padding:14px 28px;border-radius:8px;font-weight:700;">
                Descargar TipiDV para Windows
            </a>
        </p>
        @if(!empty($portableDownloadUrl))
            <p style="text-align:center;font-size:13px;color:#6b7280;margin:0 0 16px;">
                También disponible en versión portable:
                <a href="{{ $portableDownloadUrl }}" style="color:#f26c20;">TipiDV-Portable.zip</a>
            </p>
        @endif
    @else
        <p style="background:#f3f4f6;padding:12px 14px;border-radius:4px;font-size:14px;">
            Descarga el instalador desde el portal:
            <a href="{{ $portalUrl }}" style="color:#f26c20;">{{ $portalUrl }}</a>
        </p>
    @endif

    <h3 style="margin-bottom:8px;">Activar en tu PC</h3>
    <ol style="padding-left:20px;">
        <li>Instala y abre <strong>TipiDV</strong> en el equipo de digitalización.</li>
        <li>Ingresa tu correo <strong>{{ $subscription->customer_email }}</strong> y la clave de arriba.</li>
        <li>La app vincula ese PC a tu paquete (huella de máquina).</li>
        @if($subscription->machine_slots > 1)
            <li>Repite en los demás equipos hasta completar los {{ $subscription->machine_slots }} cupos.</li>
        @endif
    </ol>

    <p style="font-size:13px;color:#6b7280;">Portal y renovaciones: <a href="{{ $portalUrl }}" style="color:#f26c20;">{{ $portalUrl }}</a></p>
    <p style="color:#6b7280;font-size:13px;margin-bottom:0;">Ing. Diego Vargas · TipiDV</p>
</body>
</html>
