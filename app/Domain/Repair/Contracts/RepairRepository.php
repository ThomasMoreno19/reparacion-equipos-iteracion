<?php
namespace App\Domain\Repair\Contracts;
use App\Models\Equipo;
use Illuminate\Support\Collection;
interface RepairRepository
{
    public function findById(int $companyId, int $equipmentId): ?Equipo;
    public function findByCompanyAndCuil(int $companyId, string $cuil): Collection;
    public function findByCompanyAndSerial(int $companyId, string $serial): Collection;
    public function findByCuil(string $cuil): Collection;
    public function findBySerial(string $serial): Collection;
}
