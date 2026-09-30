<?php

namespace App\Models;

use App\Role;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Foundation\Auth\User as Authenticatable;

class Usuario extends Authenticatable
{
    protected $table = 'usuario';

    public $timestamps = false;

    protected $fillable = ['nombre', 'contrasena', 'role', 'id_empresa'];

    protected $hidden = ['contrasena', 'remember_token'];

    protected function casts(): array
    {
        return [
            'role' => Role::class,
            'id_empresa' => 'integer',
        ];
    }

    public function empresa(): BelongsTo
    {
        return $this->belongsTo(Empresa::class, 'id_empresa');
    }

    public function hasRole(Role ...$roles): bool
    {
        return $this->role instanceof Role && in_array($this->role, $roles, true);
    }

    public function canAccessCompany(int $companyId): bool
    {
        return $this->hasRole(Role::Superadmin)
            || ($this->hasRole(Role::Admin) && $this->id_empresa === $companyId);
    }

    public function isSuperadmin(): bool
    {
        return $this->hasRole(Role::Superadmin);
    }

    public function getAuthPassword(): string
    {
        return $this->contrasena;
    }

    public function getAuthPasswordName(): string
    {
        return 'contrasena';
    }
}
