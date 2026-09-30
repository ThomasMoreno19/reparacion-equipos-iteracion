<?php

namespace App\Models;

use App\Role;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Empresa extends Model
{
    protected $table = 'empresa';

    public $timestamps = false;

    protected $fillable = [
        'nombre',
        'fecha_creacion',
        'logo_url',
        'telefono',
        'ubicacion',
        'tieneCarrito',
        'deshabilitar_excel',
        'imagenesEnArticulos',
        'incluirHorarios',
        'pedidosFueraHorario',
        'incluirCodigoBarra',
        'toleranciaMinDias',
        'toleranciaMaxDias',
    ];

    public function equipos(): HasMany
    {
        return $this->hasMany(Equipo::class, 'id_empresa');
    }

    public function movimientos(): HasMany
    {
        return $this->hasMany(Movimiento::class, 'id_empresa');
    }

    public function admin(): HasOne
    {
        return $this->hasOne(Usuario::class, 'id_empresa')->where('role', Role::Admin->value);
    }
}
