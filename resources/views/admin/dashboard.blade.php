@extends('admin.layout')

@section('title', 'Licencias')

@section('nav')
    <a href="{{ route('admin.dashboard') }}">Licencias</a>
@endsection

@section('content')
<h1 style="margin:0 0 16px;">Licencias TipiDV</h1>

<div class="stats">
    <div class="stat"><b>{{ $stats['total'] }}</b> Total</div>
    <div class="stat"><b>{{ $stats['active'] }}</b> Vigentes</div>
    <div class="stat"><b>{{ $stats['expired'] }}</b> Vencidas</div>
</div>

<div class="card">
    <form method="get" class="row">
        <div class="field" style="flex:3;">
            <label>Buscar</label>
            <input name="q" value="{{ $q }}" placeholder="Clave, correo, hospital…">
        </div>
        <button type="submit" class="btn">Buscar</button>
    </form>
</div>

<div class="card" style="padding:0;overflow:hidden;">
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
                        <span style="color:#6b7280;font-size:13px;">{{ $sub->customer_email }}</span>
                    </td>
                    <td>
                        {{ $sub->expires_at->timezone('America/Bogota')->format('d/m/Y') }}
                        @if ($sub->isActive())
                            <span class="badge badge--ok">OK</span>
                        @else
                            <span class="badge badge--no">Vencida</span>
                        @endif
                    </td>
                    <td>{{ $sub->active_activations_count }} / {{ $sub->machine_slots }}</td>
                    <td><a href="{{ route('admin.subscriptions.show', $sub) }}">Ver</a></td>
                </tr>
            @empty
                <tr><td colspan="5" style="color:#6b7280;">Sin licencias aún.</td></tr>
            @endforelse
        </tbody>
    </table>
</div>

{{ $subscriptions->links() }}
@endsection
