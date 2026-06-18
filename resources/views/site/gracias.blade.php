@extends('site.layout')

@section('title', 'Gracias por tu compra')
@section('meta_description', 'Tu pago TipiDV está en proceso. Revisa tu correo para la clave de activación.')

@section('content')
<section style="padding:64px 0;text-align:center">
    <div class="container" style="max-width:560px">
        <div class="form-card">
            <div style="font-size:3rem;margin-bottom:12px">✅</div>
            <h1 style="margin:0 0 12px;font-size:1.75rem">¡Gracias!</h1>
            <p style="color:var(--muted);margin:0 0 24px">
                Si tu pago fue aprobado, en unos minutos recibirás un correo con tu clave
                <strong>TDV-XXXX-XXXX-XXXX</strong> y cuántos equipos incluye tu paquete.
            </p>
            <ol style="text-align:left;color:var(--muted);font-size:.95rem;line-height:1.7">
                <li>Revisa bandeja de entrada y spam.</li>
                <li>Instala TipiDV en cada PC de digitalización.</li>
                <li>En todos los equipos: el mismo correo + la misma clave (1 clave = N PCs según tu paquete).</li>
            </ol>
            <div style="margin-top:28px;display:flex;flex-wrap:wrap;gap:12px;justify-content:center">
                @if(!empty($downloadUrl))
                    <a href="{{ $downloadUrl }}" class="btn btn-primary">Descargar TipiDV</a>
                @endif
                <a href="https://wa.me/{{ config('marketing.whatsapp') }}?text=Acabo%20de%20comprar%20TipiDV" class="btn btn-outline" target="_blank" rel="noopener">Soporte WhatsApp</a>
            </div>
            <p style="margin-top:24px;font-size:.85rem;color:var(--muted)">
                ¿El pago quedó pendiente? Wompi te notificará. Si necesitas ayuda:
                <a href="mailto:{{ config('marketing.contact_email') }}">{{ config('marketing.contact_email') }}</a>
            </p>
        </div>
    </div>
</section>
@endsection
