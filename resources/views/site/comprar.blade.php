@extends('site.layout')

@section('title', 'Comprar licencia')
@section('meta_description', 'Compra tu licencia TipiDV en línea. Arma tu paquete por cantidad de equipos. Pago seguro con Wompi.')

@section('content')
<section class="checkout-section">
    <div class="container checkout-container">
        <div class="section-title">
            <h2>Comprar licencia</h2>
            <p class="checkout-lead">Una clave para todos tus equipos · pago seguro con Wompi</p>
        </div>

        <livewire:checkout-wizard />
    </div>
</section>
@endsection
