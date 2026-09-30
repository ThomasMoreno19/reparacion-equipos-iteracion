@extends('layouts.app')

@push('styles')
<link rel="stylesheet" href="{{ asset('css/admin-settings.css') }}">
@endpush

@section('content')
<div class="settings-page">
    <header class="settings-header">
        <div class="settings-heading">
            @if($company->logo_url)
                <img src="{{ $company->logo_url }}" alt="Logo de {{ $company->nombre }}" class="settings-logo">
            @endif
            <div>
                <p class="eyebrow">Configuración de empresa</p>
                <h1>{{ $company->nombre }}</h1>
                <p class="settings-subtitle">Administrá clientes, equipos y movimientos cargados.</p>
            </div>
        </div>
        <div class="settings-header-actions">
            @if(auth()->user()->isSuperadmin())
                <a class="settings-back" href="{{ route('admin.panel') }}">Volver al panel</a>
            @else
                <a class="settings-back" href="{{ route('company', $company->id) }}">Ver página pública</a>
            @endif
        </div>
    </header>

    <section class="settings-summary" aria-label="Resumen de la empresa">
        <div><strong>{{ $clients->count() }}</strong><span>Clientes</span></div>
        <div><strong>{{ $equipment->count() }}</strong><span>Equipos</span></div>
        <div><strong>{{ $movements->count() }}</strong><span>Movimientos</span></div>
    </section>

    <nav class="settings-tabs" aria-label="Secciones de gestión">
        <button type="button" class="settings-tab is-active" data-settings-tab="clients">Clientes <span>{{ $clients->count() }}</span></button>
        <button type="button" class="settings-tab" data-settings-tab="movements">Movimientos <span>{{ $movements->count() }}</span></button>
    </nav>

    <section class="settings-section" data-settings-panel="clients">
        <div class="section-intro">
            <div>
                <p class="eyebrow">Directorio</p>
                <h2>Lista de clientes</h2>
            </div>
            <p>Consultá los datos y los equipos asociados a cada cliente.</p>
        </div>

        <div class="management-list">
            @forelse($clients as $clientMovements)
                @php($client = $clientMovements->first())
                @php($clientName = $client->nombre_cliente ?: 'Cliente sin nombre')
                @php($clientEquipments = $clientMovements->pluck('equipo')->filter()->unique('id')->values())
                <article class="management-card client-card">
                    <div class="card-topline">
                        <div class="client-avatar">{{ mb_strtoupper(mb_substr($clientName, 0, 1)) }}</div>
                        <div class="card-title">
                            <h3>{{ $clientName }}</h3>
                            <p>DNI / CUIL: <strong>{{ $client->cuil ?: 'No informado' }}</strong></p>
                        </div>
                        <span class="count-badge">{{ $clientEquipments->count() }} {{ $clientEquipments->count() === 1 ? 'equipo' : 'equipos' }}</span>
                    </div>

                    @if($clientEquipments->isNotEmpty())
                        <button type="button" class="equipment-toggle" data-toggle-equipment aria-expanded="false">
                            Ver equipos <span aria-hidden="true">+</span>
                        </button>
                        <div class="equipment-list is-hidden">
                            @foreach($clientEquipments as $item)
                                @php($lastMovement = $clientMovements->firstWhere('id_equipo', $item->id))
                                <div class="management-equipment">
                                    <div>
                                        <strong>{{ $item->nombre_equipo ?: 'Equipo sin nombre' }}</strong>
                                        <span>{{ collect([$item->marca, $item->modelo, $item->nro_serie])->filter()->join(' / ') ?: 'Sin identificación adicional' }}</span>
                                    </div>
                                    <div class="equipment-side">
                                        <span class="status-pill">{{ $lastMovement?->estado ?: 'Sin estado' }}</span>
                                        <small>{{ $lastMovement?->fecha ?: 'Sin fecha' }}</small>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @endif
                </article>
            @empty
                <div class="empty-management">
                    <strong>No hay clientes cargados</strong>
                    <span>Los clientes aparecerán cuando importes movimientos para esta empresa.</span>
                </div>
            @endforelse
        </div>
    </section>

    <section class="settings-section is-hidden" data-settings-panel="movements">
        <div class="section-intro">
            <div>
                <p class="eyebrow">Historial</p>
                <h2>Lista de movimientos</h2>
            </div>
            <p>Todos los movimientos asociados a los equipos de esta empresa.</p>
        </div>

        <div class="management-list">
            @forelse($movements as $movement)
                <article class="management-card movement-card">
                    <div class="card-topline">
                        <div class="movement-mark">+</div>
                        <div class="card-title">
                            <h3>Movimiento #{{ $movement->id }}</h3>
                            <p>{{ $movement->equipo ? 'Equipo asociado' : 'Sin equipo asociado' }}</p>
                        </div>
                        <span class="movement-date">{{ $movement->fecha ?: 'Sin fecha' }}</span>
                    </div>
                    <div class="movement-equipment-list">
                        <div class="movement-equipment-row">
                            <span><strong>{{ $movement->nombre_cliente ?: 'Cliente sin nombre' }}</strong> / {{ $movement->cuil ?: 'DNI/CUIT no informado' }}</span>
                            <span class="status-pill">{{ $movement->estado ?: 'Sin estado' }}</span>
                        </div>
                        @if($movement->equipo)
                            <div class="movement-equipment-row">
                                <span>{{ collect([$movement->equipo->nombre_equipo, $movement->equipo->marca, $movement->equipo->modelo, $movement->equipo->nro_serie])->filter()->join(' / ') ?: 'Equipo sin identificación' }}</span>
                            </div>
                        @endif
                        @if($movement->observacion)
                            <div class="movement-observation">{{ $movement->observacion }}</div>
                        @endif
                    </div>
                </article>
            @empty
                <div class="empty-management">
                    <strong>No hay movimientos cargados</strong>
                    <span>Importá un archivo CSV desde el panel de empresas para comenzar.</span>
                </div>
            @endforelse
        </div>
    </section>
</div>

<script>
document.querySelectorAll('[data-settings-tab]').forEach(function (tab) {
    tab.addEventListener('click', function () {
        document.querySelectorAll('[data-settings-tab]').forEach(function (item) { item.classList.toggle('is-active', item === tab); });
        document.querySelectorAll('[data-settings-panel]').forEach(function (panel) { panel.classList.toggle('is-hidden', panel.dataset.settingsPanel !== tab.dataset.settingsTab); });
    });
});
document.querySelectorAll('[data-toggle-equipment]').forEach(function (button) {
    button.addEventListener('click', function () {
        var list = button.nextElementSibling;
        var expanded = button.getAttribute('aria-expanded') === 'true';
        button.setAttribute('aria-expanded', String(!expanded));
        button.classList.toggle('is-expanded', !expanded);
        list.classList.toggle('is-hidden', expanded);
        button.querySelector('span').textContent = expanded ? '+' : '-';
    });
});
</script>
@endsection
