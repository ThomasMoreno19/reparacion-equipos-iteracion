<?php

namespace App\Domain\Movement;

use App\Models\Equipo;
use App\Models\Movimiento;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use RuntimeException;

final class ImportMovements
{
    public function __construct(private ExcelMovementReader $excelReader) {}

    /**
     * @return array{processed: int, errors: array<int, string>}
     */
    public function execute(UploadedFile $file, int $companyId): array
    {
        try {
            $rows = $this->excelReader->read($file);
        } catch (RuntimeException $exception) {
            return ['processed' => 0, 'errors' => [$exception->getMessage()]];
        }

        if ($rows === []) {
            return ['processed' => 0, 'errors' => ['El Excel no contiene filas de movimientos.']];
        }

        $processed = 0;
        $errors = [];

        DB::transaction(function () use ($rows, $companyId, &$processed, &$errors): void {
            foreach ($rows as $row) {
                try {
                    DB::transaction(function () use ($row, $companyId): void {
                        $this->importRow($row['values'], $companyId);
                    });
                    $processed++;
                } catch (\Throwable $exception) {
                    $errors[] = 'Fila '.$row['row'].': '.$exception->getMessage();
                }
            }
        });

        return compact('processed', 'errors');
    }

    private function importRow(array $values, int $companyId): void
    {
        $movementId = $this->id($this->value($values, 'IdMovimiento', 'id_movimiento'), 'IdMovimiento');
        $equipmentId = $this->equipmentId($this->value($values, 'IdEquipo', 'id_equipo'));
        $date = $this->value($values, 'Fecha', 'fecha');

        Equipo::query()->updateOrCreate(
            ['id' => $equipmentId, 'id_empresa' => $companyId],
            [
                'nombre_equipo' => $this->value($values, 'Equipo', 'NombreEquipo', 'nombre_equipo'),
                'marca' => $this->value($values, 'Marca', 'marca'),
                'modelo' => $this->value($values, 'Modelo', 'modelo'),
                'nro_serie' => $this->value($values, 'Nro Serie o Dominio', 'nro_serie'),
                'fecha_creacion' => $date,
            ],
        );

        $movement = Movimiento::query()->find($movementId);

        if ($movement !== null && (int) $movement->id_empresa !== $companyId) {
            throw new RuntimeException("El IdMovimiento {$movementId} pertenece a otra empresa.");
        }

        $password = $this->value($values, 'Password', 'Contrasena', 'Contraseña', 'contrasena');
        $movementData = [
            'id_empresa' => $companyId,
            'id_equipo' => $equipmentId > 0 ? $equipmentId : null,
            'nombre_cliente' => $this->value($values, 'Cliente', 'NombreCliente', 'nombre_cliente'),
            'estado' => $this->value($values, 'Estado', 'estado'),
            'fecha' => $date,
            'observacion' => $this->value($values, 'Observaciones', 'Observacion', 'observacion'),
            'cuil' => $this->value($values, 'CUIT', 'Cuil', 'CUIL', 'cuil'),
            'contrasena' => $password !== '' ? Hash::make($password) : null,
        ];

        if ($movement === null) {
            Movimiento::query()->create(['id' => $movementId] + $movementData);

            return;
        }

        $movement->fill($movementData);
        $movement->save();
    }

    private function id(string $value, string $field): int
    {
        if (! is_numeric($value) || (float) $value < 1 || floor((float) $value) !== (float) $value) {
            throw new RuntimeException("{$field} debe ser un entero mayor que cero.");
        }

        return (int) $value;
    }

    private function equipmentId(string $value): int
    {
        if (! is_numeric($value) || (float) $value < 1 || floor((float) $value) !== (float) $value) {
            throw new RuntimeException('IdEquipo es obligatorio y debe ser un entero mayor que cero.');
        }

        return (int) $value;
    }

    private function value(array $values, string ...$keys): string
    {
        foreach ($keys as $key) {
            if (array_key_exists($key, $values) && trim((string) $values[$key]) !== '') {
                return trim((string) $values[$key]);
            }
        }

        return '';
    }
}
