<?php

namespace Tests\Feature;

use App\Domain\Company\Contracts\CompanyRepository;
use App\Domain\Movement\Contracts\MovementRepository;
use App\Domain\Movement\LookupMovement;
use App\Models\Empresa;
use App\Models\Equipo;
use App\Models\Movimiento;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Hash;
use Mockery;
use Tests\TestCase;

class PublicLookupFlowTest extends TestCase
{
    private $movementRepository;

    private $companyRepository;

    protected function setUp(): void
    {
        parent::setUp();

        $this->movementRepository = Mockery::mock(MovementRepository::class);
        $this->companyRepository = Mockery::mock(CompanyRepository::class);

        $this->app->instance(MovementRepository::class, $this->movementRepository);
        $this->app->instance(CompanyRepository::class, $this->companyRepository);
        $this->app->instance(LookupMovement::class, new LookupMovement($this->movementRepository));
    }

    public function test_public_search_has_no_password_field_and_lists_matching_companies(): void
    {
        $company = $this->company(7, 'Taller Norte');
        $movement = $this->movement($company, 90001, 80001, 'Cliente de prueba', null);
        $this->movementRepository->shouldReceive('findByCuil')
            ->once()
            ->with('20312345678')
            ->andReturn(new Collection([$movement]));

        $this->post('/consultar', ['type' => 'cuil', 'value' => '20312345678'])
            ->assertOk()
            ->assertSee('Taller Norte')
            ->assertSee('Ver detalle')
            ->assertSee('name="type"', false)
            ->assertDontSee('name="password"', false);
    }

    public function test_company_detail_requests_password_only_when_a_matching_movement_is_protected(): void
    {
        $company = $this->company(7, 'Taller Norte');
        $protectedMovement = $this->movement($company, 90001, 80001, 'Cliente confidencial', Hash::make('12'));
        $this->companyRepository->shouldReceive('find')->once()->with(7)->andReturn($company);
        $this->movementRepository->shouldReceive('findByCompanyAndCuil')
            ->once()
            ->with(7, '20312345678')
            ->andReturn(new Collection([$protectedMovement]));

        $this->post('/consultar/detalle', [
            'company_id' => 7,
            'type' => 'cuil',
            'value' => '20312345678',
        ])
            ->assertOk()
            ->assertSee('Se requiere contraseña')
            ->assertSee('name="password"', false)
            ->assertDontSee('Cliente confidencial');
    }

    public function test_company_detail_shows_unprotected_movements_without_asking_for_password(): void
    {
        $company = $this->company(7, 'Taller Norte');
        $movement = $this->movement($company, 90001, 80001, 'Cliente visible', null);
        $movement->setRelation('equipo', new Equipo([
            'id' => 80001,
            'id_empresa' => 7,
            'nombre_equipo' => 'Notebook de prueba',
        ]));
        $this->companyRepository->shouldReceive('find')->once()->with(7)->andReturn($company);
        $this->movementRepository->shouldReceive('findByCompanyAndCuil')
            ->once()
            ->with(7, '20312345678')
            ->andReturn(new Collection([$movement]));

        $this->post('/consultar/detalle', [
            'company_id' => 7,
            'type' => 'cuil',
            'value' => '20312345678',
        ])
            ->assertOk()
            ->assertSee('Cliente visible')
            ->assertSee('Notebook de prueba')
            ->assertDontSee('name="password"', false);
    }

    public function test_company_detail_shows_protected_movements_after_a_valid_password(): void
    {
        $company = $this->company(7, 'Taller Norte');
        $movement = $this->movement($company, 90001, 80001, 'Cliente protegido', Hash::make('12'));
        $movement->setRelation('equipo', new Equipo([
            'id' => 80001,
            'id_empresa' => 7,
            'nombre_equipo' => 'Notebook protegida',
        ]));
        $this->companyRepository->shouldReceive('find')->once()->with(7)->andReturn($company);
        $this->movementRepository->shouldReceive('findByCompanyAndCuil')
            ->once()
            ->with(7, '20312345678')
            ->andReturn(new Collection([$movement]));

        $this->post('/consultar/detalle', [
            'company_id' => 7,
            'type' => 'cuil',
            'value' => '20312345678',
            'password' => '12',
        ])
            ->assertOk()
            ->assertSee('Cliente protegido')
            ->assertSee('Notebook protegida');
    }

    public function test_company_detail_keeps_protected_movement_hidden_after_an_invalid_password(): void
    {
        $company = $this->company(7, 'Taller Norte');
        $movement = $this->movement($company, 90001, 80001, 'Cliente privado', Hash::make('12'));
        $this->companyRepository->shouldReceive('find')->once()->with(7)->andReturn($company);
        $this->movementRepository->shouldReceive('findByCompanyAndCuil')
            ->once()
            ->with(7, '20312345678')
            ->andReturn(new Collection([$movement]));

        $this->post('/consultar/detalle', [
            'company_id' => 7,
            'type' => 'cuil',
            'value' => '20312345678',
            'password' => 'incorrecta',
        ])
            ->assertOk()
            ->assertSee('La contraseña ingresada no es válida.')
            ->assertDontSee('Cliente privado');
    }

    public function test_direct_link_to_a_protected_movement_shows_a_password_gate(): void
    {
        $company = $this->company(7, 'Taller Norte');
        $movement = $this->movement($company, 90001, 80001, 'Cliente confidencial', Hash::make('12'));
        $this->companyRepository->shouldReceive('find')->once()->with(7)->andReturn($company);
        $this->movementRepository->shouldReceive('findById')->once()->with(7, 90001, 80001)->andReturn($movement);

        $this->get('/7/90001/80001')
            ->assertOk()
            ->assertSee('Se requiere contraseña')
            ->assertDontSee('Cliente confidencial');
    }

    private function company(int $id, string $name): Empresa
    {
        $company = new Empresa(['nombre' => $name]);
        $company->id = $id;

        return $company;
    }

    private function movement(Empresa $company, int $movementId, int $equipmentId, string $client, ?string $password): Movimiento
    {
        $movement = new Movimiento([
            'id' => $movementId,
            'id_empresa' => $company->id,
            'id_equipo' => $equipmentId,
            'nombre_cliente' => $client,
            'estado' => 'En reparación',
            'cuil' => '20312345678',
            'contrasena' => $password,
        ]);
        $movement->setRelation('empresa', $company);
        $movement->setRelation('equipo', null);

        return $movement;
    }
}
