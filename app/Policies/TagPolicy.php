<?php

namespace App\Policies;

use App\Enums\Permission;
use App\Models\Tag;
use App\Models\User;

class TagPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can(Permission::TaxonomiesView->value);
    }

    public function view(User $user, Tag $tag): bool
    {
        return $user->can(Permission::TaxonomiesView->value);
    }

    public function create(User $user): bool
    {
        return $user->can(Permission::TaxonomiesCreate->value);
    }

    public function update(User $user, Tag $tag): bool
    {
        return $user->can(Permission::TaxonomiesEdit->value);
    }

    public function delete(User $user, Tag $tag): bool
    {
        return $user->can(Permission::TaxonomiesDelete->value)
            && ! $tag->hasAssignedContent();
    }

    public function restore(User $user, Tag $tag): bool
    {
        return false;
    }

    public function forceDelete(User $user, Tag $tag): bool
    {
        return $this->delete($user, $tag);
    }
}
