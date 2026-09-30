<?php

namespace Tests\Feature;

use App\Domain\Company\Contracts\CompanyRepository;
use App\Models\Empresa;
use App\Models\Usuario;
use App\Role;
use Illuminate\Support\Collection;
use Mockery;
use Tests\TestCase;

class CompanyAdminManagementTest extends TestCase
{
    public function test_superadmin_sees_admin_credentials_in_company_forms(): void
    {
        $company = new Empresa(['nombre' => 'Taller Norte']);
        $company->id = 31;
        $admin = new Usuario(['nombre' => 'taller-norte-admin', 'role' => Role::Admin, 'id_empresa' => 31]);
        $company->setRelation('admin', $admin);

        $companies = Mockery::mock(CompanyRepository::class);
        $companies->shouldReceive('all')->once()->andReturn(new Collection([$company]));
        $this->app->instance(CompanyRepository::class, $companies);

        $this->actingAs($this->user(Role::Superadmin))
            ->get('/admin/panel')
            ->assertOk()
            ->assertSee('name="admin_nombre"', false)
            ->assertSee('name="admin_contrasena"', false)
            ->assertSee('id="edit-admin-name"', false)
            ->assertSee('id="edit-admin-password"', false)
            ->assertSee('data-edit-admin-name="taller-norte-admin"', false)
            ->assertSee('Dejar en blanco para conservarla');
    }

    public function test_company_admin_cannot_access_superadmin_routes(): void
    {
        $admin = $this->user(Role::Admin, 61);

        $this->actingAs($admin)->get('/admin/panel')->assertForbidden();
        $this->actingAs($admin)->post('/admin/empresas')->assertForbidden();
    }

    public function test_company_admin_cannot_access_another_company_configuration(): void
    {
        $this->actingAs($this->user(Role::Admin, 61))
            ->get('/configuracion/62')
            ->assertForbidden();
    }

    private function user(Role $role, ?int $companyId = null): Usuario
    {
        $user = new Usuario([
            'nombre' => 'test-user',
            'role' => $role,
            'id_empresa' => $companyId,
        ]);
        $user->id = 1;

        return $user;
    }
}
