@extends('site.layout')

@section('seo_page', 'comprar')
@section('title', config('marketing.seo.pages.comprar.title', 'Comprar licencia'))
@section('meta_description', config('marketing.seo.pages.comprar.description'))
@section('canonical', url('/comprar'))

@section('content')
<section class="checkout-section">
    <div class="container checkout-container">
        <div class="section-title">
            <h1>Comprar licencia TipiDV</h1>
            <p class="checkout-lead">Una clave para todos tus equipos · pago seguro con Wompi</p>
        </div>

        <livewire:checkout-wizard />
    </div>
</section>
@endsection
