{{-- Demo animada: tipificar páginas → unir por tipo en un PDF --}}
<section id="como-une" class="section-band section-band--alt" aria-labelledby="merge-demo-title">
    <div class="container">
        <header class="section-head">
            <span class="section-eyebrow">Así une los PDF</span>
            <h2 id="merge-demo-title">Páginas del mismo tipo → un solo archivo</h2>
            <p>
                Tipificas cada página del lote. Al procesar, TipiDV <strong>agrupa y une</strong> las del mismo tipo
                en un PDF (por ejemplo todas las HEV en <code>HEV.pdf</code>).
            </p>
        </header>

        <div class="merge-demo" role="img" aria-label="Animación: páginas tipificadas se unen en un PDF por tipo">
            <div class="merge-demo-col">
                <span class="merge-demo-label">Lote tipificado</span>
                <div class="merge-pages" aria-hidden="true">
                    <div class="merge-page merge-page--hev" style="--i:0"><span>pág. 1</span><em>HEV</em></div>
                    <div class="merge-page merge-page--fev" style="--i:1"><span>pág. 2</span><em>FEV</em></div>
                    <div class="merge-page merge-page--hev" style="--i:2"><span>pág. 3</span><em>HEV</em></div>
                    <div class="merge-page merge-page--epi" style="--i:3"><span>pág. 4</span><em>EPI</em></div>
                    <div class="merge-page merge-page--fev" style="--i:4"><span>pág. 5</span><em>FEV</em></div>
                </div>
            </div>

            <div class="merge-demo-arrow" aria-hidden="true">
                <span class="merge-demo-arrow-icon">→</span>
                <span class="merge-demo-arrow-caption">Procesar</span>
            </div>

            <div class="merge-demo-col">
                <span class="merge-demo-label">Salida unida</span>
                <div class="merge-files" aria-hidden="true">
                    <div class="merge-file merge-file--hev" style="--j:0">
                        <div class="merge-file-stack">
                            <span></span><span></span>
                        </div>
                        <div class="merge-file-meta">
                            <strong>HEV.pdf</strong>
                            <small>2 páginas unidas</small>
                        </div>
                    </div>
                    <div class="merge-file merge-file--fev" style="--j:1">
                        <div class="merge-file-stack">
                            <span></span><span></span>
                        </div>
                        <div class="merge-file-meta">
                            <strong>FEV.pdf</strong>
                            <small>2 páginas unidas</small>
                        </div>
                    </div>
                    <div class="merge-file merge-file--epi" style="--j:2">
                        <div class="merge-file-stack">
                            <span></span>
                        </div>
                        <div class="merge-file-meta">
                            <strong>EPI.pdf</strong>
                            <small>1 página</small>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <p class="merge-demo-note">
            En Configuración puedes activar o desactivar <strong>«Fusionar en un solo PDF»</strong> por cada tipo.
            Si lo desactivas, exporta un archivo por página.
        </p>
    </div>
</section>
