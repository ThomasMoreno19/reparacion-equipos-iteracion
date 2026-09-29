<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;

class Usuario extends Authenticatable
{
    protected $table = 'usuario';
    public $timestamps = false;
    protected $fillable = ['nombre', 'contrasena'];
    protected $hidden = ['contrasena', 'remember_token'];
    public function getAuthPassword(): string
    {
        return $this->contrasena;
    }
}
