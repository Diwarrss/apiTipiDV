{{-- Tabla MinSalud — nombramiento de soportes (RIPS / FEV) --}}
<section id="soportes" class="section-band section-band--alt">
    <div class="container">
        <header class="section-head">
            <span class="section-eyebrow">Ministerio de Salud y Protección Social</span>
            <h2>Plantilla oficial para soportes de la FEV en salud</h2>
            <p>
                El MinSalud reglamenta el <strong>RIPS</strong> como soporte de la <strong>Factura Electrónica de Venta (FEV)</strong>.
                TipiDV trae precargadas las abreviaturas que exige la norma; también puedes adaptarlas o usar otros tipos para documentos distintos.
            </p>
        </header>

        @php($norms = config('marketing.minsalud_norms', []))
        @if(count($norms) > 0)
            <div class="norms-strip">
                @foreach($norms as $norm)
                    <div class="norm-card">
                        <span class="norm-label">Resolución</span>
                        <strong class="norm-number">{{ $norm['number'] ?? '' }}</strong>
                        @if(!empty($norm['date']))
                            <span class="norm-date">{{ $norm['date'] }}</span>
                        @endif
                        <p>{{ $norm['summary'] ?? '' }}</p>
                    </div>
                @endforeach
            </div>
        @endif

        <div class="soportes-card">
            <div class="soportes-card-head">
                <h3>Abreviaturas de soporte</h3>
                <p>
                    Vigentes desde el <strong>{{ config('marketing.support_types_effective', '1 de junio de 2026') }}</strong>.
                    Exportación: <code>FEV.pdf</code>, <code>HEV.pdf</code>, <code>EPI.pdf</code>…
                </p>
            </div>
            <div class="soportes-table-wrap">
                <table class="soportes-table">
                    <thead>
                        <tr>
                            <th>Abreviatura</th>
                            <th>Significado del soporte</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach(config('marketing.support_types', []) as $type)
                            <tr>
                                <td><span class="soporte-code">{{ $type['code'] }}</span></td>
                                <td>{{ $type['name'] }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            <p class="soportes-note">
                Plantilla precargada · editable en Configuración según tu IPS, hospital u otro protocolo interno.
            </p>
        </div>
    </div>
</section>
