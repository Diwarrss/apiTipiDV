{{-- Casos de uso: salud (MinSalud) + otros flujos PDF --}}
<section id="casos" class="section-band">
    <div class="container">
        <header class="section-head">
            <span class="section-eyebrow">Para qué sirve</span>
            <h2>No solo MinSalud: tipifica cualquier lote PDF</h2>
            <p>
                TipiDV nació para la facturación hospitalaria en Colombia, pero la lógica es la misma en cualquier oficina:
                <strong>clasificar páginas → exportar por tipo → carpeta lista</strong>.
            </p>
        </header>

        <div class="use-cases-grid">
            @foreach(config('marketing.use_cases', []) as $case)
                <article class="use-case {{ !empty($case['featured']) ? 'use-case--featured' : '' }}">
                    @if(!empty($case['featured']))
                        <span class="use-case-badge">Caso principal · Colombia</span>
                    @endif
                    <div class="use-case-icon" aria-hidden="true">{{ $case['icon'] ?? '📄' }}</div>
                    <h3>{{ $case['title'] ?? '' }}</h3>
                    <p>{{ $case['desc'] ?? '' }}</p>
                </article>
            @endforeach
        </div>
    </div>
</section>
