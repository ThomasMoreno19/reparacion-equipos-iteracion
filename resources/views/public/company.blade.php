@extends('layouts.app')
@section('content')
<header class="company-header">@if($company->logo_url)<img class="logo" src="{{ $company->logo_url }}" alt="Logo de {{ $company->nombre }}">@endif<h1>{{ $company->nombre }}</h1></header>
<form class="form" method="POST" action="{{ route('public.lookup') }}">
    @csrf <input type="hidden" name="company_id" value="{{ $company->id }}">
    <label>Tipo de consulta<select name="type"><option value="cuil">CUIL/CUIT o DNI</option><option value="serial">Dominio / número de serie</option></select></label>
    <label>Dato de consulta<input name="value" required maxlength="80"></label>
    <label>Contraseña<input type="password" name="password" maxlength="100"></label>
    <button class="primary">Consultar</button>
</form>
@if($errors->any())<div class="message">{{ $errors->first() }}</div>@endif
@if(!empty($searched))<section class="cards">@forelse($equipos as $equipo)<x-equipment-card :equipment="$equipo" />@empty<div class="message">No se encontraron equipos.</div>@endforelse</section>@endif
@endsection
