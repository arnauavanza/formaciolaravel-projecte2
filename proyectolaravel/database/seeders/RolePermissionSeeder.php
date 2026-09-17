<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class RolePermissionSeeder extends Seeder
{
    public function run(): void
    {
        $permissions = [
            'tickets.read',
            'tickets.create',
            'tickets.update',
            'tickets.delete',
        ];

        $permissionModels = collect($permissions)->mapWithKeys(
            fn (string $permission): array => [
                $permission => Permission::query()->firstOrCreate([
                    'name' => $permission,
                    'guard_name' => 'web',
                ]),
            ],
        );

        $roles = [
            'customer' => [
                'tickets.read',
                'tickets.create',
            ],
            'agent' => [
                'tickets.read',
                'tickets.update',
            ],
            'admin' => $permissions,
        ];

        foreach ($roles as $roleName => $rolePermissions) {
            $role = Role::query()->firstOrCreate([
                'name' => $roleName,
                'guard_name' => 'web',
            ]);
            $role->syncPermissions(
                collect($rolePermissions)
                    ->map(fn (string $permission) => $permissionModels->get($permission))
                    ->all(),
            );
        }
    }
}
