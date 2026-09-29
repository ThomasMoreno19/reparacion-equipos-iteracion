<?php
namespace App\Domain\Repair;
use App\Models\Equipo;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use RuntimeException;
final class ImportRepairs
{
    public function execute(UploadedFile $file, int $companyId): array
    {
        $handle = fopen($file->getRealPath(), 'rb');
        if ($handle === false) throw new RuntimeException('No se pudo abrir el CSV.');
        $headers = fgetcsv($handle, 0, ';'); $processed = 0; $errors = [];
        DB::transaction(function () use ($handle, $headers, $companyId, &$processed, &$errors): void {
            while (($row = fgetcsv($handle, 0, ';')) !== false) {
                try { Equipo::query()->updateOrCreate(['id' => $this->id($headers ?: [], $row)], $this->mapRow($headers ?: [], $row, $companyId)); $processed++; }
                catch (\Throwable $exception) { $errors[] = 'Fila '.($processed + count($errors) + 2).': '.$exception->getMessage(); }
            }
        });
        fclose($handle); return compact('processed', 'errors');
    }
    private function id(array $headers, array $row): int { $data = array_combine($headers, $row); $id = (int) ($data['IdEquipo'] ?? $data['id_equipo'] ?? 0); if ($id < 1) throw new RuntimeException('IdEquipo es obligatorio.'); return $id; }
    private function mapRow(array $headers, array $row, int $companyId): array
    {
        $values = array_combine($headers, $row); if (!is_array($values)) throw new RuntimeException('Cantidad de columnas inválida.');
        $get = fn (string ...$keys): string => trim((string) collect($keys)->map(fn ($key) => $values[$key] ?? null)->first(fn ($value) => $value !== null));
        $movement = (int) $get('IdMovimiento', 'id_movimiento'); if ($movement < 1) throw new RuntimeException('IdMovimiento es obligatorio.');
        $password = $get('Password', 'Contrasena', 'Contraseña');
        return ['id_empresa' => $companyId, 'id_movimiento' => $movement, 'nombre_equipo' => $get('NombreEquipo'), 'marca' => $get('Marca'), 'modelo' => $get('Modelo'), 'nro_serie' => $get('Nro Serie o Dominio'), 'nombre_cliente' => $get('NombreCliente'), 'estado' => $get('Estado'), 'fecha' => $get('Fecha'), 'fecha_creacion' => $get('Fecha'), 'observacion' => $get('Observaciones'), 'cuil' => $get('Cuil', 'CUIL'), 'contrasena' => $password !== '' ? Hash::make($password) : null];
    }
}
