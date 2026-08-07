<?php

namespace App\Policies;

use App\Enums\Permission;
use App\Models\CustomTaxonomy;
use App\Models\User;

class CustomTaxonomyPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can(Permission::TaxonomiesView->value);
    }

    public function view(User $user, CustomTaxonomy $customTaxonomy): bool
    {
        return $user->can(Permission::TaxonomiesView->value);
    }

    public function create(User $user): bool
    {
        return $user->can(Permission::TaxonomiesCreate->value);
    }

    public function update(User $user, CustomTaxonomy $customTaxonomy): bool
    {
        return $user->can(Permission::TaxonomiesEdit->value);
    }

    public function delete(User $user, CustomTaxonomy $customTaxonomy): bool
    {
        if (! $user->can(Permission::TaxonomiesDelete->value)) {
            return false;
        }

        return $customTaxonomy->terms->every(fn ($term) => ! $term->hasAssignedContent());
    }

    public function restore(User $user, CustomTaxonomy $customTaxonomy): bool
    {
        return false;
    }

    public function forceDelete(User $user, CustomTaxonomy $customTaxonomy): bool
    {
        return $this->delete($user, $customTaxonomy);
    }
}
