<?php

namespace App\Policies;

use App\Enums\Permission;
use App\Models\Folder;
use App\Models\User;

class FolderPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can(Permission::MediaView->value);
    }

    public function view(User $user, Folder $folder): bool
    {
        return $user->can(Permission::MediaView->value);
    }

    public function create(User $user): bool
    {
        return $user->can(Permission::MediaUpload->value);
    }

    public function update(User $user, Folder $folder): bool
    {
        return $user->can(Permission::MediaUpload->value)
            || $user->can(Permission::MediaEditOthers->value)
            || $user->can(Permission::MediaEditOwn->value);
    }

    public function delete(User $user, Folder $folder): bool
    {
        return $user->can(Permission::MediaDelete->value);
    }

    public function restore(User $user, Folder $folder): bool
    {
        return false;
    }

    public function forceDelete(User $user, Folder $folder): bool
    {
        return $user->can(Permission::MediaForceDelete->value);
    }
}
