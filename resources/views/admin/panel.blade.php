@extends('layouts.app')

@push('styles')
<link rel="stylesheet" href="{{ asset('css/admin.css') }}">
<link rel="stylesheet" href="{{ asset('css/admin-modal.css') }}">
<link rel="stylesheet" href="{{ asset('css/admin-options.css') }}">
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
        </div>
    </header>

    @if(session('success'))<div class="admin-success">{{ session('success') }}</div>@endif
    @if($errors->any())<div class="admin-error">{{ $errors->first() }}</div>@endif

    <section class="admin-form-panel is-hidden" id="new-company">
        <div class="form-panel-heading"><h2>Nueva empresa</h2><button type="button" class="close-button" data-toggle="new-company">&times;</button></div>
        <form class="admin-form" method="POST" action="{{ route('admin.company.store') }}" enctype="multipart/form-data">
            @csrf
            <label>Nombre de la empresa<input name="nombre" required maxlength="100"></label>
            <label>Usuario Admin de la empresa<input name="admin_nombre" required maxlength="100" autocomplete="username"></label>
            <label>Contraseña del Admin<input type="password" name="admin_contrasena" required minlength="2" autocomplete="new-password"></label>
            <label>Imagen o logo<input type="file" name="imagen" accept="image/*"></label>
            <button class="admin-button" type="submit">Guardar Empresa</button>
        </form>
    </section>

    <section class="companies-panel">
        <div class="companies-grid">
            @forelse($companies as $company)
                <article class="company-card">
                    <details class="card-options">
                        <summary aria-label="Opciones de {{ $company->nombre }}">⋮</summary>
                        <div class="options-menu">
                            <button type="button" data-edit-company data-edit-url="{{ route('admin.company.update', $company->id) }}" data-edit-id="{{ $company->id }}" data-edit-name="{{ $company->nombre }}" data-edit-admin-name="{{ $company->admin?->nombre }}" data-edit-admin-exists="{{ $company->admin ? '1' : '0' }}">Editar empresa</button>
                            <form method="POST" action="{{ route('admin.company.destroy', $company->id) }}" onsubmit="return confirm('¿Eliminar esta empresa, sus equipos y su cuenta Admin?')">
                                @csrf @method('DELETE')
                                <button type="submit">Eliminar empresa</button>
                            </form>
                        </div>
                    </details>
                    <div class="company-image-wrap">
                        <img src="{{ $company->logo_url ?: asset('images/company-placeholder.svg') }}" alt="Logo de {{ $company->nombre }}" class="company-image">
                    </div>
                    <h2>{{ $company->nombre }}</h2>
                    <p class="company-id">ID: {{ $company->id }}</p>
                    <p class="company-id">Usuario Admin: {{ $company->admin?->nombre ?: 'Sin asignar' }}</p>
                     <div class="company-actions">
                         <a class="admin-button button-link" href="{{ route('company', $company->id) }}">Ver Página</a>
                         <a class="admin-button button-link" href="{{ route('company.settings', $company->id) }}">Gestionar</a>
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
        <div class="form-panel-heading"><div><h2 id="import-modal-title">Importar movimientos desde Excel</h2><p id="import-company-name"></p></div><button type="button" class="close-button" data-close-import>&times;</button></div>
        <form class="admin-form" method="POST" action="{{ route('admin.import') }}" enctype="multipart/form-data">
            @csrf
            <input type="hidden" name="empresa_id" id="import-company-id">
            <label>Archivo Excel (.xlsx)<input type="file" name="excel_file" accept=".xlsx,application/vnd.openxmlformats-officedocument.spreadsheetml.sheet" required></label>
            <button class="admin-button" type="submit">Importar / actualizar movimientos</button>
        </form>
        @if(session('import_errors'))<div class="admin-error"><strong>Filas con error</strong><ul>@foreach(session('import_errors') as $error)<li>{{ $error }}</li>@endforeach</ul></div>@endif
    </section>
</div>
<div class="modal-backdrop is-hidden" id="edit-modal" role="dialog" aria-modal="true" aria-labelledby="edit-modal-title">
    <section class="import-modal">
        <div class="form-panel-heading"><h2 id="edit-modal-title">Editar empresa y Admin</h2><button type="button" class="close-button" data-close-edit>&times;</button></div>
        <form class="admin-form" id="edit-company-form" method="POST" enctype="multipart/form-data">
            @csrf @method('PUT')
            <label>Nombre de la empresa<input name="nombre" id="edit-company-name" required maxlength="100"></label>
            <label>Usuario Admin de la empresa<input name="admin_nombre" id="edit-admin-name" required maxlength="100" autocomplete="username"></label>
            <label>Nueva contraseña del Admin<input type="password" name="admin_contrasena" id="edit-admin-password" minlength="2" autocomplete="new-password" placeholder="Dejar en blanco para conservarla"></label>
            <label>Nuevo logo<input type="file" name="imagen" accept="image/*"></label>
            <label class="checkbox-label"><input type="checkbox" name="eliminar_logo" value="1"> Eliminar logo actual</label>
            <button class="admin-button" type="submit">Guardar cambios</button>
        </form>
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
document.querySelectorAll('[data-edit-company]').forEach(function (button) {
    button.addEventListener('click', function () {
        document.getElementById('edit-company-form').action = button.dataset.editUrl;
        document.getElementById('edit-company-name').value = button.dataset.editName;
        document.getElementById('edit-admin-name').value = button.dataset.editAdminName || '';
        var adminPassword = document.getElementById('edit-admin-password');
        adminPassword.value = '';
        adminPassword.required = button.dataset.editAdminExists !== '1';
        document.getElementById('edit-modal').classList.remove('is-hidden');
    });
});
document.querySelector('[data-close-edit]').addEventListener('click', function () { document.getElementById('edit-modal').classList.add('is-hidden'); });
document.getElementById('edit-modal').addEventListener('click', function (event) { if (event.target === this) this.classList.add('is-hidden'); });
</script>
@endsection
