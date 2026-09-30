<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Equipo extends Model
{
    protected $table = 'equipo';

    public $timestamps = false;

    public $incrementing = false;

    protected $primaryKey = 'id';

    protected $fillable = ['id', 'id_empresa', 'marca', 'modelo', 'nombre_equipo', 'fecha_creacion', 'nro_serie'];

    public function empresa(): BelongsTo
    {
        return $this->belongsTo(Empresa::class, 'id_empresa');
    }

    public function movimientos(): HasMany
    {
        return $this->hasMany(Movimiento::class, 'id_equipo', 'id')
            ->where('id_empresa', $this->id_empresa);
    }

    protected function setKeysForSelectQuery($query)
    {
        return $query
            ->where('id', $this->getKeyForSelectQuery())
            ->where('id_empresa', $this->getAttribute('id_empresa'));
    }

    protected function setKeysForSaveQuery($query)
    {
        return $query
            ->where('id', $this->getKeyForSaveQuery())
            ->where('id_empresa', $this->getAttribute('id_empresa'));
    }
}
