@extends('admin.layout')

@section('title', 'Licencias')

@section('nav')
    <a href="{{ route('admin.dashboard') }}" class="admin-nav-link is-active">Licencias</a>
@endsection

@section('content')
<div class="page-header">
    <h1>Licencias TipiDV</h1>
    <p>Gestión de suscripciones y activaciones</p>
</div>

<div class="stats">
    <div class="stat">
        <span class="stat-value stat-value--orange">{{ $stats['total'] }}</span>
        <span class="stat-label">Total</span>
    </div>
    <div class="stat">
        <span class="stat-value stat-value--green">{{ $stats['active'] }}</span>
        <span class="stat-label">Vigentes</span>
    </div>
    <div class="stat">
        <span class="stat-value stat-value--muted">{{ $stats['expired'] }}</span>
        <span class="stat-label">Vencidas</span>
    </div>
</div>

<div class="card">
    <form method="get" class="row">
        <div class="field" style="flex:3;">
            <label for="search-q">Buscar licencia</label>
            <input id="search-q" name="q" value="{{ $q }}" placeholder="Clave TDV, correo, hospital…">
        </div>
        <button type="submit" class="btn">Buscar</button>
        @if ($q)
            <a href="{{ route('admin.dashboard') }}" class="btn btn--ghost">Limpiar</a>
        @endif
    </form>
</div>

<div class="card card--flush">
    <div class="table-wrap">
        <table>
            <thead>
                <tr>
                    <th>Clave</th>
                    <th>Cliente</th>
                    <th>Vence</th>
                    <th>Equipos</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                @forelse ($subscriptions as $sub)
                    <tr>
                        <td><code>{{ $sub->license_key }}</code></td>
                        <td>
                            @if ($sub->organization_name)
                                <strong>{{ $sub->organization_name }}</strong><br>
                            @endif
                            <span style="color:var(--muted);font-size:.82rem;">{{ $sub->customer_email }}</span>
                        </td>
                        <td>
                            {{ $sub->expires_at->timezone('America/Bogota')->format('d/m/Y') }}
                            @if ($sub->isActive())
                                <span class="badge badge--ok">Vigente</span>
                            @else
                                <span class="badge badge--no">Vencida</span>
                            @endif
                        </td>
                        <td>
                            <strong>{{ $sub->active_activations_count }}</strong>
                            <span style="color:var(--muted);">/ {{ $sub->machine_slots }}</span>
                        </td>
                        <td>
                            <a href="{{ route('admin.subscriptions.show', $sub) }}" class="btn btn--sm btn--ghost">Ver detalle →</a>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="5" class="table-empty">Sin licencias{{ $q ? ' para esta búsqueda' : '' }}.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

@if ($subscriptions->hasPages())
    <div class="pagination-wrap">{{ $subscriptions->links() }}</div>
@endif
@endsection
