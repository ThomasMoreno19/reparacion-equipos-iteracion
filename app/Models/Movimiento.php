<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Movimiento extends Model
{
    protected $table = 'movimiento';

    public $timestamps = false;

    public $incrementing = false;

    protected $primaryKey = 'id';

    protected $fillable = ['id', 'id_empresa', 'id_equipo', 'nombre_cliente', 'estado', 'fecha', 'observacion', 'cuil', 'contrasena'];

    protected $hidden = ['contrasena'];

    public function empresa(): BelongsTo
    {
        return $this->belongsTo(Empresa::class, 'id_empresa');
    }

    public function getEquipoAttribute(): ?Equipo
    {
        if ($this->relationLoaded('equipo')) {
            return $this->getRelation('equipo');
        }

        if ($this->id_equipo === null) {
            return null;
        }

        $equipment = Equipo::query()
            ->where('id', $this->id_equipo)
            ->where('id_empresa', $this->id_empresa)
            ->first();

        $this->setRelation('equipo', $equipment);

        return $equipment;
    }
}
