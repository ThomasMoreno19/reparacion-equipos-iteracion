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

@if(empty($searched))
    <p class="lookup-intro">Ingresá tu DNI/CUIL o el número de serie para consultar los movimientos de esta empresa.</p>
    <form class="form" method="POST" action="{{ route('public.lookup.detail') }}">
        @csrf
        <input type="hidden" name="company_id" value="{{ $company->id }}">
        <label>Tipo de consulta
            <select name="type">
                <option value="cuil">CUIL/CUIT o DNI</option>
                <option value="serial">Dominio / número de serie</option>
            </select>
        </label>
        <label>Dato de consulta<input name="value" required maxlength="80"></label>
        <button class="primary">Buscar movimientos</button>
    </form>
@else
    @if($requiresPassword)
        <section class="detail-gate">
            <h2>Se requiere contraseña</h2>
            <p>Algunos movimientos de esta consulta están protegidos. Ingresá la contraseña para ver el detalle.</p>
            @if($passwordError)<div class="message">{{ $passwordError }}</div>@endif
            <form class="form" method="POST" action="{{ route('public.lookup.detail') }}">
                @csrf
                <input type="hidden" name="company_id" value="{{ $company->id }}">
                <input type="hidden" name="type" value="{{ $lookupType }}">
                <input type="hidden" name="value" value="{{ $lookupValue }}">
                <label>Contraseña<input type="password" name="password" required maxlength="100" autocomplete="current-password"></label>
                <button class="primary">Ver movimientos</button>
            </form>
        </section>
    @else
        <section class="cards">
            @forelse($movimientos as $movimiento)
                <x-movement-card :movement="$movimiento" />
            @empty
                <div class="message">No se encontraron movimientos para ese dato en esta empresa.</div>
            @endforelse
        </section>
    @endif
@endif

@if($errors->any())<div class="message">{{ $errors->first() }}</div>@endif
@endsection
