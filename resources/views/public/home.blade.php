@extends('layouts.app')

@push('styles')
<link rel="stylesheet" href="{{ asset('css/public-lookup.css') }}">
@endpush

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
    <button class="primary">Buscar empresas</button>
</form>
@if($errors->any()) <div class="message">{{ $errors->first() }}</div> @endif
@if(!empty($searched))
<section class="company-results">
    <h2>Empresas con equipos asociados</h2>
    <div class="company-results-list">
        @forelse($companies as $match)
            @php($company = $match['company'])
            <article class="card company-result">
                @if($company->logo_url)
                    <img class="company-result-logo" src="{{ $company->logo_url }}" alt="Logo de {{ $company->nombre }}">
                @else
                    <div class="company-result-avatar">{{ mb_strtoupper(mb_substr($company->nombre, 0, 1)) }}</div>
                @endif
                <div class="company-result-info">
                    <h3>{{ $company->nombre }}</h3>
                    <p>{{ $match['equipment_count'] }} {{ $match['equipment_count'] === 1 ? 'equipo asociado' : 'equipos asociados' }}</p>
                </div>
                <form method="POST" action="{{ route('public.lookup.detail') }}">
                    @csrf
                    <input type="hidden" name="company_id" value="{{ $company->id }}">
                    <input type="hidden" name="type" value="{{ $type }}">
                    <input type="hidden" name="value" value="{{ $value }}">
                    <button class="primary" type="submit">Ver detalle</button>
                </form>
            </article>
        @empty
            <div class="message">No se encontraron empresas con equipos asociados a ese dato.</div>
        @endforelse
    </div>
</section>
@endif
<script>document.getElementById('lookup-type').addEventListener('change',function(){document.getElementById('lookup-label').firstChild.textContent=this.value==='serial'?'Dominio / número de serie':'CUIL/CUIT o DNI';});</script>
@endsection
