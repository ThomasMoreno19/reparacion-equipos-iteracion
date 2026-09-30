<?php

use Illuminate\Database\Connection;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Schema\Builder as SchemaBuilder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    protected $connection = 'efactweb';

    public function up(): void
    {
        $source = DB::connection('mysql');
        $target = DB::connection('efactweb');
        $targetSchema = Schema::connection('efactweb');
        $sourceSchema = Schema::connection('mysql');

        foreach (['empresa', 'equipo', 'movimiento'] as $table) {
            if (! $sourceSchema->hasTable($table)) {
                throw new RuntimeException("No existe la tabla {$table} en reparacion_local.");
            }
        }

        if (! $targetSchema->hasTable('empresa')) {
            throw new RuntimeException('No existe empresa en efactweb_local.');
        }

        $this->assertSourceRelationships($source);
        $this->copyCompanies($source->table('empresa')->orderBy('id')->get(), $target);

        if (! $targetSchema->hasTable('equipo')) {
            $this->createEquipmentTable($targetSchema);
        }

        if (! $targetSchema->hasTable('movimiento')) {
            $this->createMovementTable($targetSchema);
        }

        $this->copyRows('equipo', $source->table('equipo')->orderBy('id')->get(), $target);
        $this->copyRows('movimiento', $source->table('movimiento')->orderBy('id')->get(), $target);
    }

    public function down(): void
    {
        throw new RuntimeException('La transferencia a efactweb_local no se revierte automáticamente para proteger los datos compartidos.');
    }

    private function copyCompanies(Collection $companies, Connection $target): void
    {
        foreach ($companies as $company) {
            $name = (string) $company->nombre;

            if (mb_strlen($name) > 100) {
                throw new RuntimeException("La empresa con ID {$company->id} excede los 100 caracteres permitidos por efactweb_local.");
            }

            $existing = $target->table('empresa')->where('id', $company->id)->first();

            if ($existing !== null) {
                if ($existing->nombre !== $company->nombre) {
                    throw new RuntimeException("El ID de empresa {$company->id} ya pertenece a otra empresa en efactweb_local.");
                }

                continue;
            }

            $target->table('empresa')->insert([
                'id' => $company->id,
                'nombre' => $name,
                'telefono' => null,
                'ubicacion' => null,
                'tieneCarrito' => 1,
                'deshabilitar_excel' => 0,
                'logo_url' => $company->logo_url,
                'fecha_creacion' => $company->fecha_creacion ? substr((string) $company->fecha_creacion, 0, 10) : null,
                'imagenesEnArticulos' => 1,
                'incluirHorarios' => 0,
                'pedidosFueraHorario' => 0,
                'incluirCodigoBarra' => 0,
                'toleranciaMinDias' => null,
                'toleranciaMaxDias' => null,
            ]);
        }
    }

    private function assertSourceRelationships(Connection $source): void
    {
        $unknownCompanyInEquipment = $source->table('equipo as e')
            ->leftJoin('empresa as c', 'c.id', '=', 'e.id_empresa')
            ->whereNull('c.id')
            ->count();
        $unknownCompanyInMovements = $source->table('movimiento as m')
            ->leftJoin('empresa as c', 'c.id', '=', 'm.id_empresa')
            ->whereNull('c.id')
            ->count();
        $unknownEquipmentInMovements = $source->table('movimiento as m')
            ->leftJoin('equipo as e', 'e.id', '=', 'm.id_equipo')
            ->whereNotNull('m.id_equipo')
            ->whereNull('e.id')
            ->count();
        $companyMismatch = $source->table('movimiento as m')
            ->join('equipo as e', 'e.id', '=', 'm.id_equipo')
            ->whereColumn('m.id_empresa', '!=', 'e.id_empresa')
            ->count();

        if ($unknownCompanyInEquipment + $unknownCompanyInMovements + $unknownEquipmentInMovements + $companyMismatch > 0) {
            throw new RuntimeException('Hay equipos o movimientos con referencias inválidas en reparacion_local; no se migraron los datos.');
        }
    }

    private function createEquipmentTable(SchemaBuilder $schema): void
    {
        $schema->create('equipo', function (Blueprint $table): void {
            $table->unsignedInteger('id')->primary();
            $table->integer('id_empresa')->index();
            $table->string('marca', 150)->nullable();
            $table->string('modelo', 150)->nullable();
            $table->string('nombre_equipo', 150)->nullable();
            $table->string('fecha_creacion', 30)->nullable();
            $table->string('nro_serie', 100)->nullable();
            $table->foreign('id_empresa')->references('id')->on('empresa')->cascadeOnDelete();
        });
    }

    private function createMovementTable(SchemaBuilder $schema): void
    {
        $schema->create('movimiento', function (Blueprint $table): void {
            $table->unsignedInteger('id')->primary();
            $table->integer('id_empresa')->index();
            $table->unsignedInteger('id_equipo')->nullable()->index();
            $table->string('nombre_cliente', 150)->nullable();
            $table->string('estado', 150)->nullable();
            $table->string('fecha', 50)->nullable();
            $table->text('observacion')->nullable();
            $table->string('cuil', 30)->nullable()->index();
            $table->string('contrasena')->nullable();
            $table->foreign('id_empresa')->references('id')->on('empresa')->cascadeOnDelete();
            $table->foreign('id_equipo')->references('id')->on('equipo')->nullOnDelete();
        });
    }

    private function copyRows(string $tableName, Collection $sourceRows, Connection $target): void
    {
        foreach ($sourceRows as $sourceRow) {
            $row = (array) $sourceRow;
            $existing = $target->table($tableName)->where('id', $row['id'])->first();

            if ($existing !== null) {
                foreach ($row as $column => $value) {
                    if ($existing->{$column} != $value) {
                        throw new RuntimeException("El ID {$row['id']} de {$tableName} ya existe con otros datos en efactweb_local.");
                    }
                }

                continue;
            }

            $target->table($tableName)->insert($row);
        }
    }
};
