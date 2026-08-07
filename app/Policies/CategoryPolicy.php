<?php

namespace App\Policies;

use App\Enums\Permission;
use App\Models\Category;
use App\Models\User;

class CategoryPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can(Permission::TaxonomiesView->value);
    }

    public function view(User $user, Category $category): bool
    {
        return $user->can(Permission::TaxonomiesView->value);
    }

    public function create(User $user): bool
    {
        return $user->can(Permission::TaxonomiesCreate->value);
    }

    public function update(User $user, Category $category): bool
    {
        return $user->can(Permission::TaxonomiesEdit->value);
    }

    public function delete(User $user, Category $category): bool
    {
        return $user->can(Permission::TaxonomiesDelete->value)
            && ! $category->hasAssignedContent();
    }

    public function restore(User $user, Category $category): bool
    {
        return false;
    }

    public function forceDelete(User $user, Category $category): bool
    {
        return $this->delete($user, $category);
    }
}
