<?php

namespace App\Domain\Movement;

use App\Domain\Movement\Contracts\MovementRepository;
use Illuminate\Support\Collection;
use Illuminate\Validation\ValidationException;

final readonly class LookupMovement
{
    public function __construct(private MovementRepository $movements) {}

    /**
     * @return array{movimientos: Collection, empresas: Collection}
     */
    public function byCuil(string $cuil, ?int $companyId = null): array
    {
        return $this->execute('cuil', $cuil, $companyId);
    }

    /**
     * @return array{movimientos: Collection, empresas: Collection}
     */
    public function bySerial(string $serial, ?int $companyId = null): array
    {
        return $this->execute('serial', $serial, $companyId);
    }

    private function execute(string $type, string $value, ?int $companyId): array
    {
        $value = mb_strtoupper(trim($value));

        if ($value === '') {
            throw ValidationException::withMessages(['lookup' => 'Ingrese un dato para consultar.']);
        }

        $movimientos = match (true) {
            $type === 'cuil' && $companyId !== null => $this->movements->findByCompanyAndCuil($companyId, $value),
            $type === 'serial' && $companyId !== null => $this->movements->findByCompanyAndSerial($companyId, $value),
            $type === 'cuil' => $this->movements->findByCuil($value),
            default => $this->movements->findBySerial($value),
        };

        return ['movimientos' => $movimientos, 'empresas' => $movimientos->pluck('empresa')->filter()->unique('id')->values()];
    }
}
