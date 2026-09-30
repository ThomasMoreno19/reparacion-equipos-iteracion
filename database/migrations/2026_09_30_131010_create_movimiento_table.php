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
        Schema::create('movimiento', function (Blueprint $table) {
            $table->unsignedInteger('id')->primary();
            $table->unsignedInteger('id_empresa')->index();
            $table->unsignedInteger('id_equipo')->nullable()->index();
            $table->string('nombre_cliente', 150)->nullable();
            $table->string('estado', 150)->nullable();
            $table->string('fecha', 50)->nullable();
            $table->text('observacion')->nullable();
            $table->string('cuil', 30)->nullable()->index();
            $table->string('contrasena')->nullable();
        });

        $this->copyLegacyMovements();

        $legacyColumns = Schema::hasTable('equipo')
            ? array_values(array_filter(
                ['id_movimiento', 'nombre_cliente', 'estado', 'fecha', 'observacion', 'cuil', 'contrasena'],
                static fn (string $column): bool => Schema::hasColumn('equipo', $column),
            ))
            : [];

        if ($legacyColumns !== []) {
            Schema::table('equipo', function (Blueprint $table) use ($legacyColumns): void {
                $table->dropColumn($legacyColumns);
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('movimiento');
    }

    private function copyLegacyMovements(): void
    {
        if (! Schema::hasTable('equipo') || ! Schema::hasColumn('equipo', 'id_movimiento')) {
            return;
        }

        $columns = ['id', 'id_empresa', 'id_movimiento', 'nombre_cliente', 'estado', 'fecha', 'observacion', 'cuil', 'contrasena'];

        foreach (DB::table('equipo')->select($columns)->where('id_movimiento', '>', 0)->get() as $legacyEquipment) {
            DB::table('movimiento')->updateOrInsert(
                ['id' => (int) $legacyEquipment->id_movimiento],
                [
                    'id_empresa' => (int) $legacyEquipment->id_empresa,
                    'id_equipo' => (int) $legacyEquipment->id,
                    'nombre_cliente' => $legacyEquipment->nombre_cliente,
                    'estado' => $legacyEquipment->estado,
                    'fecha' => $legacyEquipment->fecha,
                    'observacion' => $legacyEquipment->observacion,
                    'cuil' => $legacyEquipment->cuil,
                    'contrasena' => $legacyEquipment->contrasena,
                ],
            );
        }
    }
};
