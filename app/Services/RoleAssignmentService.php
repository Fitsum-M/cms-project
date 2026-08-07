<?php

namespace App\Services;

use App\Enums\Permission;
use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Auth\Access\AuthorizationException;

/**
 * Role changes with privilege-escalation prevention (SRS 11.3 / 15.5).
 */
class RoleAssignmentService
{
    /**
     * @throws AuthorizationException
     */
    public function assign(User $actor, User $target, UserRole|string $role): void
    {
        $this->assertCanChangeRole($actor, $target);

        $target->assignSingleRole($role);
    }

    /**
     * @throws AuthorizationException
     */
    public function assertCanChangeRole(User $actor, User $target): void
    {
        if (! $actor->can(Permission::UsersEditRole->value)) {
            throw new AuthorizationException('You are not allowed to change user roles.');
        }

        if ($actor->is($target)) {
            throw new AuthorizationException('You cannot change your own role.');
        }
    }

    public function canChangeRole(User $actor, User $target): bool
    {
        try {
            $this->assertCanChangeRole($actor, $target);

            return true;
        } catch (AuthorizationException) {
            return false;
        }
    }
}
