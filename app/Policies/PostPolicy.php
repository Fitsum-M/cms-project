<?php

namespace App\Policies;

use App\Enums\Permission;
use App\Models\Post;
use App\Models\User;
use App\Support\Auth\OwnershipAuthorizer;

class PostPolicy
{
    public function __construct(
        private readonly OwnershipAuthorizer $ownership,
    ) {}

    public function viewAny(User $user): bool
    {
        return $user->can(Permission::PostsViewOwn->value)
            || $user->can(Permission::PostsViewAll->value);
    }

    public function view(User $user, Post $post): bool
    {
        return $this->ownership->canViewPost($user, $post);
    }

    public function create(User $user): bool
    {
        return $user->can(Permission::PostsCreate->value);
    }

    public function update(User $user, Post $post): bool
    {
        if ($post->trashed()) {
            return false;
        }

        return $this->ownership->canEditPost($user, $post);
    }

    public function delete(User $user, Post $post): bool
    {
        return $this->ownership->canDeletePost($user, $post);
    }

    public function restore(User $user, Post $post): bool
    {
        return $user->can(Permission::PostsRestore->value)
            && $this->ownership->canViewPost($user, $post);
    }

    public function forceDelete(User $user, Post $post): bool
    {
        return $user->can(Permission::PostsForceDelete->value);
    }

    public function duplicate(User $user, Post $post): bool
    {
        if ($post->trashed()) {
            return false;
        }

        return $user->can(Permission::PostsDuplicate->value)
            && $user->can(Permission::PostsCreate->value)
            && $this->ownership->canViewPost($user, $post);
    }
}
