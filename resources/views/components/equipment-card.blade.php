@props(['equipment'])
<article class="card">
    <div class="client">{{ $equipment->nombre_cliente ?: 'Cliente' }}</div>
    <div class="details">
        <div><span>Equipo</span><span>{{ collect([$equipment->nombre_equipo, $equipment->marca, $equipment->modelo, $equipment->nro_serie])->filter()->join(' | ') }}</span></div>
        <div><span>Estado</span><span class="status">{{ $equipment->estado ?: 'Sin estado' }}</span></div>
        <div><span>Actualizado</span><span>{{ $equipment->fecha ?: 'Sin fecha' }}</span></div>
        @if($equipment->observacion)<div><span>Observaciones</span><span>{{ $equipment->observacion }}</span></div>@endif
    </div>
</article>
