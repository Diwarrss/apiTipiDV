{{-- Tabla MinSalud — nombramiento de soportes --}}
<section id="soportes" class="section-band section-band--alt">
    <div class="container">
        <header class="section-head">
            <span class="section-eyebrow">Ministerio de Salud</span>
            <h2>Soportes con el nombramiento que exige el MinSalud</h2>
            <p>
                A partir del <strong>{{ config('marketing.support_types_effective', '1 de junio de 2026') }}</strong>,
                los soportes deben renombrarse con estas abreviaturas.
                TipiDV los exporta listos: <code>FEV.pdf</code>, <code>HEV.pdf</code>, <code>EPI.pdf</code>…
            </p>
        </header>

        <div class="soportes-card">
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
                Plantilla precargada en TipiDV · editable desde Configuración según el protocolo de tu IPS u hospital.
            </p>
        </div>
    </div>
</section>
