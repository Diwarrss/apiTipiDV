@extends('admin.layout')

@section('title', $subscription->license_key)

@section('nav')
    <a href="{{ route('admin.dashboard') }}" class="admin-nav-link">← Licencias</a>
@endsection

@section('content')
@php
    $activeCount = $subscription->activations->filter(fn ($a) => $a->isActive())->count();
    $isActive = $subscription->isActive();
@endphp

<div class="page-header">
    <div class="page-header-row">
        <div>
            <div class="license-key">
                {{ $subscription->license_key }}
                <button type="button" class="btn-copy" data-copy="{{ $subscription->license_key }}">Copiar</button>
            </div>
            <p style="margin-top:8px;">
                @if ($subscription->organization_name)
                    <strong>{{ $subscription->organization_name }}</strong> ·
                @endif
                {{ $subscription->customer_email }}
            </p>
        </div>
        <div>
            @if ($isActive)
                <span class="badge badge--ok">● Vigente</span>
            @else
                <span class="badge badge--no">● Vencida</span>
            @endif
            @if (($subscription->metadata['source'] ?? null) === 'admin_manual')
                <span class="badge badge--warn">Manual / regalo</span>
            @endif
        </div>
    </div>
</div>

<div class="stats" style="margin-bottom:20px;">
    <div class="stat">
        <span class="stat-value stat-value--green">{{ $activeCount }}</span>
        <span class="stat-label">Equipos activos</span>
    </div>
    <div class="stat">
        <span class="stat-value stat-value--orange">{{ $subscription->machine_slots }}</span>
        <span class="stat-label">Cupos totales</span>
    </div>
    <div class="stat">
        <span class="stat-value" style="font-size:1.1rem;padding-top:6px;">{{ $subscription->expires_at->format('d/m/Y') }}</span>
        <span class="stat-label">Vence</span>
    </div>
</div>

<div class="card">
    <h2 class="card-title">Información</h2>
    <div class="detail-grid">
        <div class="detail-item">
            <label>Responsable</label>
            <span>{{ $subscription->customer_name ?? '—' }}</span>
        </div>
        <div class="detail-item">
            <label>Periodo</label>
            <span>{{ $subscription->billing_period }}</span>
        </div>
        <div class="detail-item">
            <label>Vigencia</label>
            <span>{{ $subscription->starts_at->format('d/m/Y') }} → {{ $subscription->expires_at->format('d/m/Y') }}</span>
        </div>
        <div class="detail-item">
            <label>Estado</label>
            <span>{{ $subscription->status }}</span>
        </div>
        <div class="detail-item">
            <label>Monto último pago</label>
            <span>{{ $subscription->amount_cop ? '$ '.number_format((float)$subscription->amount_cop, 0, ',', '.') : '—' }}</span>
        </div>
        <div class="detail-item">
            <label>Referencia Wompi</label>
            <span><code>{{ $subscription->wompi_reference ?? '—' }}</code></span>
        </div>
    </div>
</div>

<div class="card">
    <h2 class="card-title">Acciones</h2>

    <div class="action-block">
        <p class="action-block-title">Correo de licencia</p>
        <form method="post" action="{{ route('admin.subscriptions.resend-email', $subscription) }}" class="row">
            @csrf
            <p style="margin:0;color:var(--muted);font-size:.9rem;flex:1;">
                Reenvía la clave <strong>{{ $subscription->license_key }}</strong> y el enlace de descarga a
                <strong>{{ $subscription->customer_email }}</strong>.
            </p>
            <button type="submit" class="btn btn--ghost">Reenviar correo</button>
        </form>
    </div>

    <div class="action-block">
        <p class="action-block-title">Extender vigencia</p>
        <form method="post" action="{{ route('admin.subscriptions.extend', $subscription) }}" class="row">
            @csrf
            <div class="field">
                <label for="months">Meses a agregar</label>
                <input type="number" id="months" name="months" value="{{ old('months', 12) }}" min="1" max="60"
                    class="@error('months') input-invalid @enderror">
                @error('months')<span class="field-error">{{ $message }}</span>@enderror
            </div>
            <button type="submit" class="btn">Extender</button>
        </form>
    </div>

    <div class="action-block">
        <p class="action-block-title">Cupos de equipo</p>
        <form method="post" action="{{ route('admin.subscriptions.slots', $subscription) }}" class="row">
            @csrf
            <div class="field">
                <label for="machine_slots">Número de PCs permitidos</label>
                <input type="number" id="machine_slots" name="machine_slots"
                    value="{{ old('machine_slots', $subscription->machine_slots) }}" min="1" max="99"
                    class="@error('machine_slots') input-invalid @enderror">
                @error('machine_slots')<span class="field-error">{{ $message }}</span>@enderror
            </div>
            <button type="submit" class="btn">Actualizar cupos</button>
        </form>
    </div>
</div>

<div class="card card--flush">
    <div style="padding:16px 22px 0;">
        <h2 class="card-title" style="margin-bottom:0;">Equipos activados</h2>
    </div>
    <div class="table-wrap">
        <table>
            <thead>
                <tr>
                    <th>Equipo</th>
                    <th>Huella</th>
                    <th>Activado</th>
                    <th>Último uso</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                @forelse ($subscription->activations as $act)
                    <tr>
                        <td><strong>{{ $act->machine_label ?? 'Sin nombre' }}</strong></td>
                        <td><code style="font-size:.75rem;">{{ Str::limit($act->machine_fingerprint, 20) }}</code></td>
                        <td>{{ $act->activated_at?->timezone('America/Bogota')->format('d/m/Y H:i') }}</td>
                        <td>{{ $act->last_seen_at?->timezone('America/Bogota')->format('d/m/Y H:i') ?? '—' }}</td>
                        <td>
                            @if ($act->isActive())
                                <form method="post" action="{{ route('admin.activations.deactivate', $act) }}"
                                    data-confirm="¿Desvincular este equipo? La PC dejará de usar la licencia.">
                                    @csrf
                                    <button type="submit" class="btn btn--danger btn--sm">Desvincular</button>
                                </form>
                            @else
                                <span class="badge badge--no">Inactivo</span>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="5" class="table-empty">Ningún equipo activado aún.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection
