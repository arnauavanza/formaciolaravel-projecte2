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
            'tickets.view_all',
            'tickets.update_all',
            'tickets.delete_all',
            'tickets.create_for_others',
            'tickets.assign',
            'tickets.close',
            'comments.create',
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
                'comments.create',
            ],

            'agent' => [
                'tickets.read',
                'tickets.update',
                'comments.create',
            ],

            'supervisor' => [
                'tickets.read',
                'tickets.view_all',
                'tickets.assign',
                'comments.create',
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
