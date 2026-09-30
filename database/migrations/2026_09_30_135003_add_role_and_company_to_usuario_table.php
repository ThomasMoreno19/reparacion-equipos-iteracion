<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (! Schema::hasTable('usuario') || ! Schema::hasTable('empresa')) {
            return;
        }

        $addRole = ! Schema::hasColumn('usuario', 'role');
        $addCompanyId = ! Schema::hasColumn('usuario', 'id_empresa');

        if ($addRole || $addCompanyId) {
            Schema::table('usuario', function (Blueprint $table) use ($addRole, $addCompanyId): void {
                if ($addRole) {
                    $table->string('role', 20)->default('Admin')->after('contrasena');
                }

                if ($addCompanyId) {
                    $table->unsignedInteger('id_empresa')->nullable()->after('role');
                }
            });
        }

        if ($addRole) {
            DB::table('usuario')->update(['role' => 'Superadmin']);
        }

        if ($addCompanyId) {
            Schema::table('usuario', function (Blueprint $table): void {
                $table->unique('id_empresa', 'usuario_id_empresa_unique');
                $table->foreign('id_empresa', 'usuario_id_empresa_foreign')
                    ->references('id')
                    ->on('empresa')
                    ->cascadeOnDelete();
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (! Schema::hasTable('usuario')) {
            return;
        }

        if (Schema::hasColumn('usuario', 'id_empresa')) {
            Schema::table('usuario', function (Blueprint $table): void {
                $table->dropForeign('usuario_id_empresa_foreign');
                $table->dropUnique('usuario_id_empresa_unique');
                $table->dropColumn('id_empresa');
            });
        }

        if (Schema::hasColumn('usuario', 'role')) {
            Schema::table('usuario', function (Blueprint $table): void {
                $table->dropColumn('role');
            });
        }
    }
};
