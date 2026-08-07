<?php

namespace App\Policies;

use App\Enums\Permission;
use App\Models\User;
use App\Services\RoleAssignmentService;

class UserPolicy
{
    public function __construct(private readonly RoleAssignmentService $roles) {}

    public function viewAny(User $actor): bool
    {
        return $actor->can(Permission::UsersViewAll->value);
    }

    public function view(User $actor, User $target): bool
    {
        if ($actor->can(Permission::UsersViewAll->value)) {
            return true;
        }

        return $actor->can(Permission::UsersEditOwn->value) && $actor->is($target);
    }

    public function create(User $actor): bool
    {
        return $actor->can(Permission::UsersCreate->value);
    }

    public function update(User $actor, User $target): bool
    {
        if ($actor->is($target)) {
            return $actor->can(Permission::UsersEditOwn->value);
        }

        return $actor->can(Permission::UsersEditOthers->value);
    }

    /**
     * Privilege-escalation prevention: never allow changing your own role.
     */
    public function updateRole(User $actor, User $target): bool
    {
        return $this->roles->canChangeRole($actor, $target);
    }

    public function delete(User $actor, User $target): bool
    {
        if ($actor->is($target)) {
            return false;
        }

        return $actor->can(Permission::UsersDelete->value);
    }

    public function suspend(User $actor, User $target): bool
    {
        if ($actor->is($target)) {
            return false;
        }

        return $actor->can(Permission::UsersSuspend->value);
    }

    public function forceDelete(User $actor, User $target): bool
    {
        return $this->delete($actor, $target);
    }

    public function restore(User $actor, User $target): bool
    {
        return $actor->can(Permission::UsersDelete->value);
    }
}
