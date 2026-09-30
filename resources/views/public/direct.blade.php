@extends('layouts.app')

@push('styles')
<link rel="stylesheet" href="{{ asset('css/public-lookup.css') }}">
@endpush

@section('content')
<header class="company-header">
    @if($company->logo_url)
        <img class="logo" src="{{ $company->logo_url }}" alt="Logo de {{ $company->nombre }}">
    @endif
    <h1>{{ $company->nombre }}</h1>
</header>

@if($requiresPassword)
    <section class="detail-gate">
        <h2>Se requiere contraseña</h2>
        <p>Este movimiento está protegido. Ingresá la contraseña para ver su detalle.</p>
        @if($passwordError)<div class="message">{{ $passwordError }}</div>@endif
        <form class="form" method="POST" action="{{ route('repair.direct.verify') }}">
            @csrf
            <input type="hidden" name="company_id" value="{{ $company->id }}">
            <input type="hidden" name="movement_id" value="{{ $movement->id }}">
            <input type="hidden" name="equipment_id" value="{{ $movement->id_equipo }}">
            <label>Contraseña<input type="password" name="password" required maxlength="100" autocomplete="current-password"></label>
            <button class="primary">Ver movimiento</button>
        </form>
    </section>
@else
    <x-movement-card :movement="$movement" />
@endif
@endsection
