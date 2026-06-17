@extends('admin.layout')

@section('title', $subscription->license_key)

@section('nav')
    <a href="{{ route('admin.dashboard') }}">← Licencias</a>
@endsection

@section('content')
<h1 style="margin:0 0 4px;"><code>{{ $subscription->license_key }}</code></h1>
<p style="color:#6b7280;margin:0 0 20px;">
    @if ($subscription->organization_name)
        {{ $subscription->organization_name }} ·
    @endif
    {{ $subscription->customer_email }}
</p>

<div class="card">
    <h2 style="margin:0 0 12px;font-size:1rem;">Datos</h2>
    <table>
        <tr><th style="width:160px;">Responsable</th><td>{{ $subscription->customer_name ?? '—' }}</td></tr>
        <tr><th>Periodo</th><td>{{ $subscription->billing_period }}</td></tr>
        <tr><th>Vigencia</th><td>{{ $subscription->starts_at->format('d/m/Y') }} → {{ $subscription->expires_at->format('d/m/Y') }}</td></tr>
        <tr><th>Estado</th><td>{{ $subscription->status }} @if($subscription->isActive())<span class="badge badge--ok">Vigente</span>@endif</td></tr>
        <tr><th>Cupos PC</th><td>{{ $subscription->machine_slots }}</td></tr>
        <tr><th>Monto último</th><td>{{ $subscription->amount_cop ? '$ '.number_format((float)$subscription->amount_cop, 0, ',', '.') : '—' }}</td></tr>
        <tr><th>Wompi ref.</th><td>{{ $subscription->wompi_reference ?? '—' }}</td></tr>
    </table>
</div>

<div class="card">
    <h2 style="margin:0 0 12px;font-size:1rem;">Acciones</h2>
    <form method="post" action="{{ route('admin.subscriptions.extend', $subscription) }}" class="row">
        @csrf
        <div class="field">
            <label>Extender meses</label>
            <input type="number" name="months" value="12" min="1" max="60">
        </div>
        <button type="submit" class="btn">Extender vigencia</button>
    </form>
    <form method="post" action="{{ route('admin.subscriptions.slots', $subscription) }}" class="row" style="margin-top:12px;">
        @csrf
        <div class="field">
            <label>Cupos de equipo</label>
            <input type="number" name="machine_slots" value="{{ $subscription->machine_slots }}" min="1" max="99">
        </div>
        <button type="submit" class="btn">Actualizar cupos</button>
    </form>
</div>

<div class="card">
    <h2 style="margin:0 0 12px;font-size:1rem;">Equipos activados</h2>
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
                    <td>{{ $act->machine_label ?? '—' }}</td>
                    <td><code style="font-size:11px;">{{ Str::limit($act->machine_fingerprint, 16) }}</code></td>
                    <td>{{ $act->activated_at?->format('d/m/Y H:i') }}</td>
                    <td>{{ $act->last_seen_at?->format('d/m/Y H:i') ?? '—' }}</td>
                    <td>
                        @if ($act->isActive())
                            <form method="post" action="{{ route('admin.activations.deactivate', $act) }}" onsubmit="return confirm('¿Desvincular este equipo?');">
                                @csrf
                                <button type="submit" class="btn btn--danger" style="padding:4px 10px;font-size:12px;">Desvincular</button>
                            </form>
                        @else
                            <span class="badge badge--no">Inactivo</span>
                        @endif
                    </td>
                </tr>
            @empty
                <tr><td colspan="5" style="color:#6b7280;">Ningún equipo activado aún.</td></tr>
            @endforelse
        </tbody>
    </table>
</div>
@endsection
