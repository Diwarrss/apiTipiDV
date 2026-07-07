@extends('admin.layout')

@section('title', 'Ingresar')

@section('content')
    <a href="{{ route('site.home') }}" class="login-home-link" aria-label="Volver al sitio TipiDV">
        <img src="{{ asset('images/tipidv-logo.png') }}" alt="" class="login-brand-logo" width="56" height="56">
        <span class="login-brand-wordmark">Tipi<span>DV</span></span>
    </a>

    <h1>Panel de administración</h1>
    <p class="subtitle">Acceso restringido para gestión de licencias.</p>

    @if ($errors->any())
        <div class="toast toast--error" role="alert" style="margin-bottom:16px;position:static;animation:none;box-shadow:none;">
            <span class="toast-icon">✕</span>
            <span class="toast-body">{{ $errors->first() }}</span>
        </div>
    @endif

    <form method="post" action="{{ route('admin.login.submit') }}" class="login-form">
        @csrf
        <div class="field">
            <label for="email">Correo</label>
            <input id="email" name="email" type="email" value="{{ old('email') }}" required autofocus
                autocomplete="username" placeholder="admin@ejemplo.com"
                class="@error('email') input-invalid @enderror">
            @error('email')<span class="field-error">{{ $message }}</span>@enderror
        </div>
        <div class="field">
            <label for="password">Contraseña</label>
            <input id="password" name="password" type="password" required autocomplete="current-password"
                class="@error('password') input-invalid @enderror">
            @error('password')<span class="field-error">{{ $message }}</span>@enderror
        </div>
        <button type="submit" class="btn login-submit">Entrar al panel</button>
    </form>

    <p class="login-footer">
        <a href="{{ route('site.home') }}" class="login-back">← Volver al sitio web</a>
    </p>
@endsection
