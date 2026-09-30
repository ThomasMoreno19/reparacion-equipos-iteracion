@props(['movement'])
@php($equipment = $movement->equipo)
<article class="card">
    <div class="client">{{ $movement->nombre_cliente ?: 'Cliente' }}</div>
    <div class="details">
        <div><span>Movimiento</span><span>#{{ $movement->id }}</span></div>
        @if($equipment)
            <div><span>Equipo</span><span>{{ collect([$equipment->nombre_equipo, $equipment->marca, $equipment->modelo, $equipment->nro_serie])->filter()->join(' | ') ?: 'Sin identificación' }}</span></div>
        @else
            <div><span>Equipo</span><span>Sin equipo asociado</span></div>
        @endif
        <div><span>Estado</span><span class="status">{{ $movement->estado ?: 'Sin estado' }}</span></div>
        <div><span>Actualizado</span><span>{{ $movement->fecha ?: 'Sin fecha' }}</span></div>
        @if($movement->observacion)<div><span>Observaciones</span><span>{{ $movement->observacion }}</span></div>@endif
    </div>
</article>
