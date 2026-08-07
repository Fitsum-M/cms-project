<?php

namespace App\Support\Auth;

use App\Contracts\Ownable;
use App\Enums\Permission;
use App\Models\User;

/**
 * Ownership checks for own-content-only rules (SRS 11.3 / 11.4).
 */
final class OwnershipAuthorizer
{
    public function owns(User $actor, Ownable $record): bool
    {
        $ownerId = $record->ownerKey();

        return $ownerId !== null && (int) $ownerId === (int) $actor->getKey();
    }

    public function canView(User $actor, Ownable $record, Permission $viewOwn, Permission $viewAll): bool
    {
        if ($actor->can($viewAll->value)) {
            return true;
        }

        return $actor->can($viewOwn->value) && $this->owns($actor, $record);
    }

    public function canEdit(User $actor, Ownable $record, Permission $editOwn, Permission $editOthers): bool
    {
        if ($this->owns($actor, $record)) {
            return $actor->can($editOwn->value);
        }

        return $actor->can($editOthers->value);
    }

    public function canDelete(User $actor, Ownable $record, Permission $deleteOwn, Permission $deleteOthers): bool
    {
        if ($this->owns($actor, $record)) {
            return $actor->can($deleteOwn->value);
        }

        return $actor->can($deleteOthers->value);
    }

    /**
     * Posts / CPT content authorization helpers.
     */
    public function canViewPost(User $actor, Ownable $record): bool
    {
        return $this->canView($actor, $record, Permission::PostsViewOwn, Permission::PostsViewAll);
    }

    public function canEditPost(User $actor, Ownable $record): bool
    {
        return $this->canEdit($actor, $record, Permission::PostsEditOwn, Permission::PostsEditOthers);
    }

    public function canDeletePost(User $actor, Ownable $record): bool
    {
        return $this->canDelete($actor, $record, Permission::PostsDeleteOwn, Permission::PostsDeleteOthers);
    }

    public function canViewPage(User $actor, Ownable $record): bool
    {
        return $this->canView($actor, $record, Permission::PagesViewOwn, Permission::PagesViewAll);
    }

    public function canEditPage(User $actor, Ownable $record): bool
    {
        return $this->canEdit($actor, $record, Permission::PagesEditOwn, Permission::PagesEditOthers);
    }

    public function canDeletePage(User $actor, Ownable $record): bool
    {
        return $this->canDelete($actor, $record, Permission::PagesDeleteOwn, Permission::PagesDeleteOthers);
    }

    public function canEditMedia(User $actor, Ownable $record): bool
    {
        return $this->canEdit($actor, $record, Permission::MediaEditOwn, Permission::MediaEditOthers);
    }
}
