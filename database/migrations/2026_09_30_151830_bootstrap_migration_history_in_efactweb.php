<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $baseline = [
            '2026_09_30_131010_create_movimiento_table',
            '2026_09_30_135003_add_role_and_company_to_usuario_table',
            '2026_09_30_140128_set_default_role_for_new_usuarios',
            '2026_09_30_142727_transfer_reparacion_equipment_to_efactweb',
            '2026_09_30_142728_remove_local_company_foreign_key_from_usuario',
            '2026_09_30_151829_merge_reparacion_admins_into_efactweb_usuario',
        ];
        $sourceMigrations = DB::connection('mysql')->table('migrations')->pluck('migration')->all();

        foreach ($baseline as $migration) {
            if (! in_array($migration, $sourceMigrations, true)) {
                throw new RuntimeException("No se puede preparar el historial: falta la migración {$migration} en reparacion_local.");
            }
        }

        $schema = Schema::connection('efactweb');

        if (! $schema->hasTable('migrations')) {
            $schema->create('migrations', function (Blueprint $table): void {
                $table->id();
                $table->string('migration');
                $table->integer('batch');
            });
        }

        $target = DB::connection('efactweb');

        foreach ([...$baseline, '2026_09_30_151830_bootstrap_migration_history_in_efactweb'] as $migration) {
            if (! $target->table('migrations')->where('migration', $migration)->exists()) {
                $target->table('migrations')->insert(['migration' => $migration, 'batch' => 1]);
            }
        }
    }

    public function down(): void
    {
        throw new RuntimeException('No se revierte automáticamente la línea base migrada a efactweb_local.');
    }
};
