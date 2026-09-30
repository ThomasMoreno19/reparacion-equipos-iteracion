<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    protected $connection = 'efactweb';

    public function up(): void
    {
        $source = DB::connection('mysql');
        $target = DB::connection('efactweb');
        $schema = Schema::connection('efactweb');

        if (! $schema->hasTable('usuario') || ! $schema->hasTable('empresa')) {
            throw new RuntimeException('Faltan las tablas usuario o empresa en efactweb_local.');
        }

        $addRole = ! $schema->hasColumn('usuario', 'role');
        $addCompany = ! $schema->hasColumn('usuario', 'id_empresa');

        if ($addRole || $addCompany) {
            $schema->table('usuario', function (Blueprint $table) use ($addRole, $addCompany): void {
                if ($addRole) {
                    $table->string('role', 20)->default('Admin')->after('contrasena');
                }

                if ($addCompany) {
                    $table->integer('id_empresa')->nullable()->after('role');
                }
            });
        }

        if ($addCompany) {
            $schema->table('usuario', function (Blueprint $table): void {
                $table->unique('id_empresa', 'usuario_id_empresa_unique');
                $table->foreign('id_empresa', 'usuario_id_empresa_foreign')
                    ->references('id')
                    ->on('empresa')
                    ->cascadeOnDelete();
            });
        }

        $efactwebAdmin = $target->table('usuario')->where('nombre', 'admin')->first();

        if ($efactwebAdmin === null) {
            throw new RuntimeException('No se encontró el usuario admin existente de EFactWeb; se cancela la fusión para proteger sus credenciales.');
        }

        $target->table('usuario')->where('id', $efactwebAdmin->id)->update([
            'role' => 'Superadmin',
            'id_empresa' => null,
        ]);

        foreach ($source->table('usuario')->orderBy('id')->get() as $sourceUser) {
            if ($sourceUser->nombre === 'admin') {
                if ($sourceUser->role !== 'Superadmin') {
                    throw new RuntimeException('El usuario admin de Reparación no tiene rol Superadmin; se cancela la fusión.');
                }

                continue;
            }

            if ($target->table('usuario')->where('nombre', $sourceUser->nombre)->exists()) {
                throw new RuntimeException("Ya existe el usuario {$sourceUser->nombre} en efactweb_local.");
            }

            if (! in_array($sourceUser->role, ['Superadmin', 'Admin'], true)) {
                throw new RuntimeException("El usuario {$sourceUser->nombre} tiene un rol desconocido.");
            }

            if ($sourceUser->id_empresa !== null && ! $target->table('empresa')->where('id', $sourceUser->id_empresa)->exists()) {
                throw new RuntimeException("La empresa {$sourceUser->id_empresa} del usuario {$sourceUser->nombre} no existe en efactweb_local.");
            }

            if ($target->table('usuario')->where('id', $sourceUser->id)->exists()) {
                throw new RuntimeException("El ID {$sourceUser->id} del usuario {$sourceUser->nombre} ya está ocupado en efactweb_local.");
            }

            $target->table('usuario')->insert([
                'id' => $sourceUser->id,
                'nombre' => $sourceUser->nombre,
                'contrasena' => $sourceUser->contrasena,
                'role' => $sourceUser->role,
                'id_empresa' => $sourceUser->id_empresa,
            ]);
        }
    }

    public function down(): void
    {
        throw new RuntimeException('No se puede revertir automáticamente la fusión de usuarios sin perder cambios posteriores en efactweb_local.usuario.');
    }
};
