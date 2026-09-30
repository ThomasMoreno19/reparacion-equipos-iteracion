<?php

namespace Tests\Unit;

use App\Models\Usuario;
use App\Role;
use PHPUnit\Framework\TestCase;

class UserRoleTest extends TestCase
{
    public function test_admin_can_access_only_its_assigned_company(): void
    {
        $admin = new Usuario(['role' => Role::Admin, 'id_empresa' => 7]);

        $this->assertSame(Role::Admin, $admin->role);
        $this->assertTrue($admin->canAccessCompany(7));
        $this->assertFalse($admin->canAccessCompany(8));
    }

    public function test_superadmin_can_access_any_company(): void
    {
        $superadmin = new Usuario(['role' => Role::Superadmin]);

        $this->assertSame(Role::Superadmin, $superadmin->role);
        $this->assertTrue($superadmin->canAccessCompany(7));
        $this->assertTrue($superadmin->canAccessCompany(8));
    }
}
