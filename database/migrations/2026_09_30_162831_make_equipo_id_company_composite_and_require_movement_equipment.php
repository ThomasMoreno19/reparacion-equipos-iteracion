<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $database = DB::connection();

        $invalidMovements = $database->table('movimiento')
            ->where(function ($query): void {
                $query->whereNull('id_equipo')->orWhere('id_equipo', 0);
            })
            ->get(['id', 'id_empresa', 'id_equipo']);

        foreach ($invalidMovements as $movement) {
            if ((int) $movement->id !== 26 || (int) $movement->id_empresa !== 20) {
                throw new RuntimeException("El movimiento {$movement->id} de la empresa {$movement->id_empresa} no tiene un equipo; hay que resolverlo antes de aplicar la clave compuesta.");
            }

            $delete = $database->table('movimiento')
                ->where('id', $movement->id)
                ->where('id_empresa', $movement->id_empresa);

            if ($movement->id_equipo === null) {
                $delete->whereNull('id_equipo');
            } else {
                $delete->where('id_equipo', 0);
            }

            $delete->delete();
        }

        $duplicateEquipmentKeys = $database->table('equipo')
            ->select('id', 'id_empresa')
            ->groupBy('id', 'id_empresa')
            ->havingRaw('COUNT(*) > 1')
            ->count();

        if ($duplicateEquipmentKeys > 0) {
            throw new RuntimeException('Hay equipos duplicados dentro de una misma empresa; no se puede crear la clave compuesta.');
        }

        $orphanMovements = $database->table('movimiento as movement')
            ->leftJoin('equipo as equipment', function ($join): void {
                $join->on('equipment.id', '=', 'movement.id_equipo')
                    ->on('equipment.id_empresa', '=', 'movement.id_empresa');
            })
            ->whereNull('equipment.id')
            ->count();

        if ($orphanMovements > 0) {
            throw new RuntimeException('Hay movimientos que no tienen un equipo de la misma empresa.');
        }

        Schema::table('movimiento', function (Blueprint $table): void {
            $table->dropForeign('movimiento_id_equipo_foreign');
        });

        Schema::table('equipo', function (Blueprint $table): void {
            $table->dropPrimary();
            $table->primary(['id', 'id_empresa'], 'equipo_id_empresa_primary');
        });

        Schema::table('movimiento', function (Blueprint $table): void {
            $table->unsignedInteger('id_equipo')->nullable(false)->change();
            $table->foreign(['id_equipo', 'id_empresa'], 'movimiento_equipo_empresa_foreign')
                ->references(['id', 'id_empresa'])
                ->on('equipo')
                ->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        throw new RuntimeException('La clave compuesta de equipo y la obligatoriedad del movimiento no se revierten automáticamente.');
    }
};
