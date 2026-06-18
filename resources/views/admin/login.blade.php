@extends('admin.layout')

@section('title', 'Ingresar')

@section('content')
    <div class="login-logo">TD</div>
    <h1>Super administrador</h1>
    <p class="subtitle">Acceso restringido al panel TipiDV.</p>

    @if ($errors->any())
        <div class="toast toast--error" role="alert" style="margin-bottom:16px;position:static;animation:none;box-shadow:none;">
            <span class="toast-icon">✕</span>
            <span class="toast-body">{{ $errors->first() }}</span>
        </div>
    @endif

    <form method="post" action="{{ route('admin.login.submit') }}">
        @csrf
        <div class="field">
            <label for="email">Correo</label>
            <input id="email" name="email" type="email" value="{{ old('email') }}" required autofocus
                class="@error('email') input-invalid @enderror">
            @error('email')<span class="field-error">{{ $message }}</span>@enderror
        </div>
        <div class="field">
            <label for="password">Contraseña</label>
            <input id="password" name="password" type="password" required
                class="@error('password') input-invalid @enderror">
            @error('password')<span class="field-error">{{ $message }}</span>@enderror
        </div>
        <button type="submit" class="btn" style="width:100%;margin-top:4px;">Entrar</button>
    </form>
@endsection
