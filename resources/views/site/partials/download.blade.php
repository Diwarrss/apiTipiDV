{{-- Descarga — solo instalador Setup.exe --}}
<section id="descargar" class="section-band">
    <div class="container">
        <header class="section-head">
            <span class="section-eyebrow">Descarga</span>
            <h2>Instalador TipiDV para Windows</h2>
            <p>Windows 10 u 11 · 64 bits · <strong>TipiDV-Setup.exe</strong></p>
        </header>

        <div class="download-card">
            @if(!empty($hasDownload))
                @if(!empty($releaseVersion))
                    <span class="download-version">Versión {{ $releaseVersion }}</span>
                @endif
                <div class="download-actions">
                    <a href="{{ route('site.download') }}" class="btn btn-primary btn-download">
                        ⬇ Descargar TipiDV-Setup.exe
                    </a>
                </div>
                <ol class="download-steps">
                    <li>Instala en cada PC de digitalización.</li>
                    <li>Activa con tu clave TDV y correo de compra.</li>
                    <li>Tipifica y exporta soportes con abreviatura MinSalud.</li>
                </ol>
            @else
                <p class="download-unavailable">
                    El instalador se publicará aquí pronto.
                    <a href="{{ url('/comprar') }}">Compra tu licencia</a> y recibe el enlace por correo, o
                    <a href="https://wa.me/{{ config('marketing.whatsapp') }}" target="_blank" rel="noopener">WhatsApp</a>.
                </p>
            @endif
        </div>
    </div>
</section>
