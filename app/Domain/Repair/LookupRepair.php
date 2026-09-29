<?php
namespace App\Domain\Repair;
use App\Domain\Repair\Contracts\RepairRepository;
use Illuminate\Support\Collection;
use Illuminate\Validation\ValidationException;
final readonly class LookupRepair
{
    public function __construct(private RepairRepository $repairs) {}
    /** @return array{equipos: Collection, empresas: Collection} */
    public function byCuil(string $cuil, ?int $companyId = null): array { return $this->execute('cuil', $cuil, $companyId); }
    /** @return array{equipos: Collection, empresas: Collection} */
    public function bySerial(string $serial, ?int $companyId = null): array { return $this->execute('serial', $serial, $companyId); }
    private function execute(string $type, string $value, ?int $companyId): array
    {
        $value = mb_strtoupper(trim($value));
        if ($value === '') throw ValidationException::withMessages(['lookup' => 'Ingrese un dato para consultar.']);
        $equipos = match (true) {
            $type === 'cuil' && $companyId !== null => $this->repairs->findByCompanyAndCuil($companyId, $value),
            $type === 'serial' && $companyId !== null => $this->repairs->findByCompanyAndSerial($companyId, $value),
            $type === 'cuil' => $this->repairs->findByCuil($value),
            default => $this->repairs->findBySerial($value),
        };
        return ['equipos' => $equipos, 'empresas' => $equipos->pluck('empresa')->filter()->unique('id')->values()];
    }
}
