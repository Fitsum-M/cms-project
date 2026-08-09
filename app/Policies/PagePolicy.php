<?php

namespace App\Policies;

use App\Enums\Permission;
use App\Models\Page;
use App\Models\User;
use App\Support\Auth\OwnershipAuthorizer;

class PagePolicy
{
    public function __construct(
        private readonly OwnershipAuthorizer $ownership,
    ) {}

    public function viewAny(User $user): bool
    {
        return $user->can(Permission::PagesViewOwn->value)
            || $user->can(Permission::PagesViewAll->value);
    }

    public function view(User $user, Page $page): bool
    {
        return $this->ownership->canViewPage($user, $page);
    }

    public function create(User $user): bool
    {
        return $user->can(Permission::PagesCreate->value);
    }

    public function update(User $user, Page $page): bool
    {
        if ($page->trashed()) {
            return false;
        }

        return $this->ownership->canEditPage($user, $page);
    }

    public function delete(User $user, Page $page): bool
    {
        return $this->ownership->canDeletePage($user, $page);
    }

    public function restore(User $user, Page $page): bool
    {
        return $user->can(Permission::PagesRestore->value)
            && $this->ownership->canViewPage($user, $page);
    }

    public function forceDelete(User $user, Page $page): bool
    {
        return $user->can(Permission::PagesForceDelete->value);
    }
}
