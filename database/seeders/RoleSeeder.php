<?php

namespace Database\Seeders;

use App\Enums\Permission;
use App\Models\Role;
use App\Support\Auth\RolePermissionMatrix;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission as PermissionModel;
use Spatie\Permission\PermissionRegistrar;

class RoleSeeder extends Seeder
{
    /**
     * Seed MVP default roles into the database and wire the Section 11.4 permission matrix.
     * Roles remain editable in the admin UI after seeding.
     */
    public function run(): void
    {
        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        foreach (Permission::cases() as $permission) {
            PermissionModel::findOrCreate($permission->value, 'web');
        }

        foreach (RolePermissionMatrix::definitions() as $roleName => $definition) {
            $role = Role::findOrCreate($roleName, 'web');
            $role->forceFill([
                'description' => $definition['description'],
                'color' => $definition['color'],
                'icon' => $definition['icon'],
                'is_system' => $definition['is_system'],
            ])->save();

            $permissionNames = array_map(
                static fn (Permission $permission): string => $permission->value,
                $definition['permissions'],
            );

            $role->syncPermissions($permissionNames);
        }

        app()[PermissionRegistrar::class]->forgetCachedPermissions();
    }
}
