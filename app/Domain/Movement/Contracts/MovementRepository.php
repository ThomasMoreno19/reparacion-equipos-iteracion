<?php

namespace App\Domain\Movement\Contracts;

use App\Models\Movimiento;
use Illuminate\Support\Collection;

interface MovementRepository
{
    public function findAllByCompany(int $companyId): Collection;

    public function findById(int $companyId, int $movementId, int $equipmentId): ?Movimiento;

    public function findByCompanyAndCuil(int $companyId, string $cuil): Collection;

    public function findByCompanyAndSerial(int $companyId, string $serial): Collection;

    public function findByCuil(string $cuil): Collection;

    public function findBySerial(string $serial): Collection;
}
