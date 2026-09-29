@extends('layouts.app')

@push('styles')
<link rel="stylesheet" href="{{ asset('css/admin.css') }}">
<link rel="stylesheet" href="{{ asset('css/admin-modal.css') }}">
@endpush

@section('content')
<div class="admin-page">
    <header class="admin-header">
        <div>
            <h1>Gestión de Empresas</h1>
            <p>Reparación de equipos</p>
        </div>
        <div class="admin-header-actions">
            <button class="admin-button" type="button" data-toggle="new-company">+ Nueva Empresa</button>
            <form method="POST" action="{{ route('logout') }}">@csrf<button class="admin-logout" type="submit">Cerrar sesión</button></form>
        </div>
    </header>

    @if(session('success'))<div class="admin-success">{{ session('success') }}</div>@endif
    @if($errors->any())<div class="admin-error">{{ $errors->first() }}</div>@endif

    <section class="admin-form-panel is-hidden" id="new-company">
        <div class="form-panel-heading"><h2>Nueva empresa</h2><button type="button" class="close-button" data-toggle="new-company">&times;</button></div>
        <form class="admin-form" method="POST" action="{{ route('admin.company.store') }}" enctype="multipart/form-data">
            @csrf
            <label>Nombre de la empresa<input name="nombre" required maxlength="150"></label>
            <label>Imagen o logo<input type="file" name="imagen" accept="image/*"></label>
            <button class="admin-button" type="submit">Guardar Empresa</button>
        </form>
    </section>

    <section class="companies-panel">
        <div class="companies-grid">
            @forelse($companies as $company)
                <article class="company-card">
                    <div class="company-image-wrap">
                        <img src="{{ $company->logo_url ?: asset('images/company-placeholder.svg') }}" alt="Logo de {{ $company->nombre }}" class="company-image">
                    </div>
                    <h2>{{ $company->nombre }}</h2>
                    <p class="company-id">ID: {{ $company->id }}</p>
                    <div class="company-actions">
                        <a class="admin-button button-link" href="{{ route('company', $company->id) }}">Ver Página</a>
                        <button class="admin-button button-link" type="button" data-company-id="{{ $company->id }}" data-company-name="{{ $company->nombre }}" data-scroll-import>Cargar Movimientos</button>
                    </div>
                </article>
            @empty
                <p class="empty-state">Todavía no hay empresas cargadas.</p>
            @endforelse
        </div>
    </section>

</div>
<div class="modal-backdrop is-hidden" id="import-modal" role="dialog" aria-modal="true" aria-labelledby="import-modal-title">
    <section class="import-modal">
        <div class="form-panel-heading"><div><h2 id="import-modal-title">Cargar movimientos</h2><p id="import-company-name"></p></div><button type="button" class="close-button" data-close-import>&times;</button></div>
        <form class="admin-form" method="POST" action="{{ route('admin.import') }}" enctype="multipart/form-data">
            @csrf
            <input type="hidden" name="empresa_id" id="import-company-id">
            <label>Archivo CSV<input type="file" name="csv_file" accept=".csv,text/csv" required></label>
            <button class="admin-button" type="submit">Importar Movimientos</button>
        </form>
        @if(session('import_errors'))<div class="admin-error"><strong>Filas con error</strong><ul>@foreach(session('import_errors') as $error)<li>{{ $error }}</li>@endforeach</ul></div>@endif
    </section>
</div>
<script>
document.querySelectorAll('[data-toggle]').forEach(function (button) {
    button.addEventListener('click', function () { document.getElementById(button.dataset.toggle).classList.toggle('is-hidden'); });
});
document.querySelectorAll('[data-scroll-import]').forEach(function (button) {
    button.addEventListener('click', function () {
        document.getElementById('import-company-id').value = button.dataset.companyId;
        document.getElementById('import-company-name').textContent = 'Empresa seleccionada: ' + button.dataset.companyName;
        document.getElementById('import-modal').classList.remove('is-hidden');
    });
});
document.querySelector('[data-close-import]').addEventListener('click', function () { document.getElementById('import-modal').classList.add('is-hidden'); });
document.getElementById('import-modal').addEventListener('click', function (event) { if (event.target === this) this.classList.add('is-hidden'); });
</script>
@endsection
