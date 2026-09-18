<?php

namespace Tests\Feature;

use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RolePermissionTest extends TestCase
{
    use RefreshDatabase;

    public function test_roles_have_the_expected_permissions(): void
    {
        $this->seed(RolePermissionSeeder::class);

        $customer = User::factory()->create();
        $customer->assignRole('customer');

        $this->assertTrue($customer->hasRole('customer'));
        $this->assertTrue($customer->can('tickets.read'));
        $this->assertTrue($customer->can('tickets.create'));
        $this->assertFalse($customer->can('tickets.update'));
        $this->assertFalse($customer->can('tickets.delete'));

        $supervisor = User::factory()->create();
        $supervisor->assignRole('supervisor');

        $this->assertTrue($supervisor->hasRole('supervisor'));
        $this->assertTrue($supervisor->can('tickets.read'));
        $this->assertTrue($supervisor->can('tickets.view_all'));
        $this->assertTrue($supervisor->can('tickets.assign'));
        $this->assertFalse($supervisor->can('tickets.close'));
    }

    public function test_role_permission_seeder_is_idempotent(): void
    {
        $this->seed(RolePermissionSeeder::class);
        $this->seed(RolePermissionSeeder::class);
        $this->assertDatabaseCount('permissions', 11);
        $this->assertDatabaseCount('roles', 4);
        $this->assertDatabaseCount('role_has_permissions', 21);
    }
}
