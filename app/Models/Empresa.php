<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
class Empresa extends Model
{
    protected $table = 'Empresa'; public $timestamps = false; protected $fillable = ['nombre', 'fecha_creacion', 'logo_url'];
    public function equipos(): HasMany { return $this->hasMany(Equipo::class, 'id_empresa'); }
}
