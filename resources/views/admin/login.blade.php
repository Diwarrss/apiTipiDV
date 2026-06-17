@extends('admin.layout')

@section('title', 'Ingresar')

@section('content')
<div class="card" style="max-width:400px;margin:40px auto;">
    <h1 style="margin:0 0 8px;font-size:1.35rem;">Super administrador</h1>
    <p style="color:#6b7280;font-size:14px;margin:0 0 20px;">Solo acceso autorizado.</p>

    @if ($errors->any())
        <div class="error">{{ $errors->first() }}</div>
    @endif

    <form method="post" action="{{ route('admin.login.submit') }}">
        @csrf
        <div class="field">
            <label for="email">Correo</label>
            <input id="email" name="email" type="email" value="{{ old('email') }}" required autofocus>
        </div>
        <div class="field">
            <label for="password">Contraseña</label>
            <input id="password" name="password" type="password" required>
        </div>
        <button type="submit" class="btn" style="width:100%;">Entrar</button>
    </form>
</div>
@endsection
