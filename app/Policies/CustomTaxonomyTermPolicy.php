<?php

namespace App\Policies;

use App\Enums\Permission;
use App\Models\CustomTaxonomyTerm;
use App\Models\User;

class CustomTaxonomyTermPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can(Permission::TaxonomiesView->value);
    }

    public function view(User $user, CustomTaxonomyTerm $term): bool
    {
        return $user->can(Permission::TaxonomiesView->value);
    }

    public function create(User $user): bool
    {
        return $user->can(Permission::TaxonomiesCreate->value);
    }

    public function update(User $user, CustomTaxonomyTerm $term): bool
    {
        return $user->can(Permission::TaxonomiesEdit->value);
    }

    public function delete(User $user, CustomTaxonomyTerm $term): bool
    {
        return $user->can(Permission::TaxonomiesDelete->value)
            && ! $term->hasAssignedContent();
    }

    public function restore(User $user, CustomTaxonomyTerm $term): bool
    {
        return false;
    }

    public function forceDelete(User $user, CustomTaxonomyTerm $term): bool
    {
        return $this->delete($user, $term);
    }
}
