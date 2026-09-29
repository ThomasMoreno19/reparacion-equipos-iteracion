@extends('layouts.app')
@section('content')
<h1>Consulta el estado de tu equipo</h1>
<form class="form" method="POST" action="{{ route('public.lookup') }}">
    @csrf
    <label>Tipo de consulta
        <select name="type" id="lookup-type"><option value="cuil" @selected(old('type', 'cuil') === 'cuil')>CUIL/CUIT o DNI</option><option value="serial" @selected(old('type') === 'serial')>Dominio / número de serie</option></select>
    </label>
    <label id="lookup-label">{{ old('type') === 'serial' ? 'Dominio / número de serie' : 'CUIL/CUIT o DNI' }}
        <input name="value" required value="{{ old('value') }}" maxlength="80">
    </label>
    <label>Contraseña <input type="password" name="password" maxlength="100" placeholder="Si tu técnico te la proporcionó"></label>
    <button class="primary">Consultar</button>
</form>
@if($errors->any()) <div class="message">{{ $errors->first() }}</div> @endif
@if(!empty($searched))
<section class="cards">@forelse($equipos as $equipo)<x-equipment-card :equipment="$equipo" />@empty<div class="message">No se encontraron equipos.</div>@endforelse</section>
@endif
<script>document.getElementById('lookup-type').addEventListener('change',function(){document.getElementById('lookup-label').firstChild.textContent=this.value==='serial'?'Dominio / número de serie':'CUIL/CUIT o DNI';});</script>
@endsection
