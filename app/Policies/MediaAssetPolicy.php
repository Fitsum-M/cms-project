<?php

namespace App\Policies;

use App\Enums\Permission;
use App\Models\MediaAsset;
use App\Models\User;
use App\Support\Auth\OwnershipAuthorizer;

class MediaAssetPolicy
{
    public function __construct(
        private readonly OwnershipAuthorizer $ownership,
    ) {}

    public function viewAny(User $user): bool
    {
        return $user->can(Permission::MediaView->value);
    }

    public function view(User $user, MediaAsset $mediaAsset): bool
    {
        return $user->can(Permission::MediaView->value);
    }

    public function create(User $user): bool
    {
        return $user->can(Permission::MediaUpload->value);
    }

    public function update(User $user, MediaAsset $mediaAsset): bool
    {
        return $this->ownership->canEditMedia($user, $mediaAsset);
    }

    public function delete(User $user, MediaAsset $mediaAsset): bool
    {
        return $user->can(Permission::MediaDelete->value);
    }

    public function restore(User $user, MediaAsset $mediaAsset): bool
    {
        return false;
    }

    public function forceDelete(User $user, MediaAsset $mediaAsset): bool
    {
        return $user->can(Permission::MediaForceDelete->value);
    }
}
