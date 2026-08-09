<?php

namespace App\Policies;

use App\Enums\Permission;
use App\Models\PostType;
use App\Models\User;

class PostTypePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can(Permission::CustomPostTypesManage->value);
    }

    public function view(User $user, PostType $postType): bool
    {
        return $user->can(Permission::CustomPostTypesManage->value);
    }

    public function create(User $user): bool
    {
        return $user->can(Permission::CustomPostTypesManage->value);
    }

    public function update(User $user, PostType $postType): bool
    {
        return $user->can(Permission::CustomPostTypesManage->value);
    }

    public function delete(User $user, PostType $postType): bool
    {
        return $user->can(Permission::CustomPostTypesManage->value)
            && ! $postType->hasAssignedContent();
    }

    public function restore(User $user, PostType $postType): bool
    {
        return false;
    }

    public function forceDelete(User $user, PostType $postType): bool
    {
        return $this->delete($user, $postType);
    }
}
