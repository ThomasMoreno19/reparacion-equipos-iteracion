<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Equipo extends Model
{
    protected $table = 'equipo';
    public $timestamps = false;
    public $incrementing = false;
    protected $primaryKey = 'id';
    protected $fillable = ['id', 'id_empresa', 'id_movimiento', 'marca', 'modelo', 'nombre_equipo', 'fecha_creacion', 'nombre_cliente', 'nro_serie', 'estado', 'fecha', 'observacion', 'cuil', 'contrasena'];
    protected $hidden = ['contrasena'];
    public function empresa(): BelongsTo
    {
        return $this->belongsTo(Empresa::class, 'id_empresa');
    }
}
