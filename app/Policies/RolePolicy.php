<?php

namespace App\Policies;

use App\Enums\Permission;
use App\Enums\UserRole;
use App\Models\User;
use Spatie\Permission\Models\Role;

class RolePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can(Permission::UsersViewAll->value)
            || $user->can(Permission::UsersEditRole->value);
    }

    public function view(User $user, Role $role): bool
    {
        return $this->viewAny($user);
    }

    public function create(User $user): bool
    {
        return $user->can(Permission::UsersEditRole->value);
    }

    public function update(User $user, Role $role): bool
    {
        return $user->can(Permission::UsersEditRole->value);
    }

    public function delete(User $user, Role $role): bool
    {
        if ($role->name === UserRole::Administrator->value) {
            return false;
        }

        return $user->can(Permission::UsersEditRole->value);
    }

    public function restore(User $user, Role $role): bool
    {
        return false;
    }

    public function forceDelete(User $user, Role $role): bool
    {
        return $this->delete($user, $role);
    }
}
