<?php

namespace App\Infrastructure\Persistence;

use App\Domain\Movement\Contracts\MovementRepository;
use App\Models\Equipo;
use App\Models\Movimiento;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Query\Builder as QueryBuilder;
use Illuminate\Support\Collection;

final class EloquentMovementRepository implements MovementRepository
{
    public function findAllByCompany(int $companyId): Collection
    {
        $movements = $this->baseQuery()
            ->where('id_empresa', $companyId)
            ->latest('id')
            ->get();

        return $this->attachEquipment($movements);
    }

    public function findById(int $companyId, int $movementId, int $equipmentId): ?Movimiento
    {
        $movement = $this->onlyLatestPerEquipment($this->baseQuery())
            ->where('id_empresa', $companyId)
            ->where('id', $movementId)
            ->where('id_equipo', $equipmentId)
            ->first();

        if ($movement === null) {
            return null;
        }

        return $this->attachEquipment(new Collection([$movement]))->first();
    }

    public function findByCompanyAndCuil(int $companyId, string $cuil): Collection
    {
        $movements = $this->onlyLatestPerEquipment($this->baseQuery())
            ->where('id_empresa', $companyId)
            ->where('cuil', $cuil)
            ->latest('id')
            ->get();

        return $this->attachEquipment($movements);
    }

    public function findByCompanyAndSerial(int $companyId, string $serial): Collection
    {
        $movements = $this->onlyLatestPerEquipment($this->baseQuery())
            ->where('id_empresa', $companyId)
            ->whereExists(function (QueryBuilder $query) use ($serial): void {
                $this->serialEquipmentExists($query, $serial);
            })
            ->latest('id')
            ->get();

        return $this->attachEquipment($movements);
    }

    public function findByCuil(string $cuil): Collection
    {
        $movements = $this->onlyLatestPerEquipment($this->baseQuery())
            ->where('cuil', $cuil)
            ->latest('id')
            ->get();

        return $this->attachEquipment($movements);
    }

    public function findBySerial(string $serial): Collection
    {
        $movements = $this->onlyLatestPerEquipment($this->baseQuery())
            ->whereExists(function (QueryBuilder $query) use ($serial): void {
                $this->serialEquipmentExists($query, $serial);
            })
            ->latest('id')
            ->get();

        return $this->attachEquipment($movements);
    }

    private function baseQuery(): Builder
    {
        return Movimiento::with('empresa');
    }

    private function serialEquipmentExists(QueryBuilder $query, string $serial): void
    {
        $query->selectRaw('1')
            ->from('equipo as equipment')
            ->whereColumn('equipment.id', 'movimiento.id_equipo')
            ->whereColumn('equipment.id_empresa', 'movimiento.id_empresa')
            ->where('equipment.nro_serie', $serial);
    }

    private function onlyLatestPerEquipment(Builder $query): Builder
    {
        return $query->whereNotExists(function (QueryBuilder $newerMovement): void {
            $newerMovement->selectRaw('1')
                ->from('movimiento as newer')
                ->whereColumn('newer.id_empresa', 'movimiento.id_empresa')
                ->whereColumn('newer.id', '>', 'movimiento.id')
                ->where(function (QueryBuilder $sameEquipment): void {
                    $sameEquipment->where(function (QueryBuilder $linkedEquipment): void {
                        $linkedEquipment->whereNotNull('movimiento.id_equipo')
                            ->whereColumn('newer.id_equipo', 'movimiento.id_equipo');
                    })->orWhere(function (QueryBuilder $unlinkedMovement): void {
                        $unlinkedMovement->whereNull('movimiento.id_equipo')
                            ->whereNull('newer.id_equipo')
                            ->whereColumn('newer.cuil', 'movimiento.cuil');
                    });
                });
        });
    }

    private function attachEquipment(Collection $movements): Collection
    {
        $pairs = $movements
            ->filter(static fn (Movimiento $movement): bool => $movement->id_equipo !== null)
            ->map(static fn (Movimiento $movement): array => [
                'id' => (int) $movement->id_equipo,
                'id_empresa' => (int) $movement->id_empresa,
            ])
            ->unique(static fn (array $pair): string => $pair['id_empresa'].':'.$pair['id'])
            ->values();

        $equipmentByPair = collect();

        if ($pairs->isNotEmpty()) {
            $equipment = Equipo::query()->where(function (Builder $query) use ($pairs): void {
                foreach ($pairs as $pair) {
                    $query->orWhere(function (Builder $pairQuery) use ($pair): void {
                        $pairQuery->where('id', $pair['id'])
                            ->where('id_empresa', $pair['id_empresa']);
                    });
                }
            })->get();

            $equipmentByPair = $equipment->keyBy(static fn (Equipo $item): string => (int) $item->id_empresa.':'.(int) $item->id);
        }

        foreach ($movements as $movement) {
            $key = $movement->id_equipo === null
                ? ''
                : (int) $movement->id_empresa.':'.(int) $movement->id_equipo;

            $movement->setRelation('equipo', $equipmentByPair->get($key));
        }

        return $movements;
    }
}
