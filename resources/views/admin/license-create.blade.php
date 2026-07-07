@extends('admin.layout')

@section('title', 'Nueva licencia')

@section('nav')
    <a href="{{ route('admin.dashboard') }}" class="admin-nav-link">Licencias</a>
    <a href="{{ route('admin.licenses.create') }}" class="admin-nav-link is-active">Nueva licencia</a>
@endsection

@section('content')
<div class="page-header">
    <h1>Crear licencia</h1>
    <p>Regala o asigna una licencia manualmente. Opcionalmente envía el correo con la clave TDV.</p>
</div>

<div class="card">
    <form method="post" action="{{ route('admin.licenses.store') }}">
        @csrf

        <div class="form-grid">
            <div class="field">
                <label for="customer_email">Correo del destinatario *</label>
                <input id="customer_email" name="customer_email" type="email" value="{{ old('customer_email') }}" required
                    placeholder="hospital@ejemplo.com" class="@error('customer_email') input-invalid @enderror">
                @error('customer_email')<span class="field-error">{{ $message }}</span>@enderror
                <span class="field-hint">Debe usar este mismo correo al activar TipiDV en cada PC.</span>
            </div>

            <div class="field">
                <label for="customer_name">Nombre del contacto</label>
                <input id="customer_name" name="customer_name" type="text" value="{{ old('customer_name') }}"
                    placeholder="María Pérez">
            </div>

            <div class="field field--full">
                <label for="organization_name">Hospital / empresa</label>
                <input id="organization_name" name="organization_name" type="text" value="{{ old('organization_name') }}"
                    placeholder="Clínica Ejemplo S.A.S.">
            </div>

            <div class="field">
                <label for="machine_slots">Equipos permitidos</label>
                <input id="machine_slots" name="machine_slots" type="number" min="1" max="99"
                    value="{{ old('machine_slots', 1) }}" required class="@error('machine_slots') input-invalid @enderror">
                @error('machine_slots')<span class="field-error">{{ $message }}</span>@enderror
            </div>

            <div class="field">
                <label for="months">Vigencia (meses)</label>
                <input id="months" name="months" type="number" min="1" max="60"
                    value="{{ old('months', 12) }}" required class="@error('months') input-invalid @enderror">
                @error('months')<span class="field-error">{{ $message }}</span>@enderror
            </div>

            <div class="field">
                <label for="billing_period">Periodo</label>
                <select id="billing_period" name="billing_period">
                    <option value="annual" @selected(old('billing_period', 'annual') === 'annual')>Anual</option>
                    <option value="monthly" @selected(old('billing_period') === 'monthly')>Mensual</option>
                </select>
            </div>

            <div class="field field--full">
                <label for="admin_note">Nota interna (opcional)</label>
                <input id="admin_note" name="admin_note" type="text" value="{{ old('admin_note') }}"
                    placeholder="Ej. Cortesía demo, convenio hospital X…">
            </div>

            <div class="field field--full">
                <label class="checkbox-row">
                    <input type="checkbox" name="send_email" value="1" @checked(!session()->hasOldInput() || old('send_email'))>
                    <span>Enviar correo con la clave y enlace de descarga</span>
                </label>
            </div>
        </div>

        <div class="form-actions">
            <button type="submit" class="btn">Crear licencia</button>
            <a href="{{ route('admin.dashboard') }}" class="btn btn--ghost">Cancelar</a>
        </div>
    </form>
</div>
@endsection
