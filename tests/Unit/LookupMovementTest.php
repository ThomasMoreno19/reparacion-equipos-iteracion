<?php

namespace Tests\Unit;

use App\Domain\Movement\Contracts\MovementRepository;
use App\Domain\Movement\LookupMovement;
use App\Models\Movimiento;
use Illuminate\Support\Collection;
use PHPUnit\Framework\TestCase;

class LookupMovementTest extends TestCase
{
    public function test_it_looks_up_movements_for_a_company_by_cuil(): void
    {
        $movement = new Movimiento(['id' => 26, 'cuil' => '20345678901']);
        $movement->setRelation('empresa', null);
        $movements = new Collection([$movement]);
        $repository = $this->createMock(MovementRepository::class);
        $repository->expects($this->once())
            ->method('findByCompanyAndCuil')
            ->with(4, '20345678901')
            ->willReturn($movements);

        $result = (new LookupMovement($repository))->byCuil('20345678901', 4);

        $this->assertSame($movements, $result['movimientos']);
        $this->assertCount(1, $result['movimientos']);
    }
}
