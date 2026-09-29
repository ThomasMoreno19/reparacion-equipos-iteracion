<?php
namespace App\Infrastructure\Persistence;
use App\Domain\Repair\Contracts\RepairRepository;
use App\Models\Equipo;
use Illuminate\Support\Collection;
final class EloquentRepairRepository implements RepairRepository
{
    public function findById(int $companyId, int $equipmentId): ?Equipo { return Equipo::with('empresa')->where('id_empresa', $companyId)->find($equipmentId); }
    public function findByCompanyAndCuil(int $companyId, string $cuil): Collection { return Equipo::with('empresa')->where('id_empresa', $companyId)->where('cuil', $cuil)->latest('id')->get(); }
    public function findByCompanyAndSerial(int $companyId, string $serial): Collection { return Equipo::with('empresa')->where('id_empresa', $companyId)->where('nro_serie', $serial)->latest('id')->get(); }
    public function findByCuil(string $cuil): Collection { return Equipo::with('empresa')->where('cuil', $cuil)->latest('id')->get(); }
    public function findBySerial(string $serial): Collection { return Equipo::with('empresa')->where('nro_serie', $serial)->latest('id')->get(); }
}
