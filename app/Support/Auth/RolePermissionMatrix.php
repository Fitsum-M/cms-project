<?php

namespace App\Support\Auth;

use App\Enums\Permission;
use App\Enums\UserRole;

/**
 * Maps each MVP role to its Spatie permissions per SRS Section 11.4.
 * Ownership enforcement ("own only") is applied later in U.05 policies;
 * this matrix only assigns the capability flags.
 */
final class RolePermissionMatrix
{
    /**
     * @return list<Permission>
     */
    public static function permissionsFor(UserRole $role): array
    {
        return match ($role) {
            UserRole::Administrator => self::administrator(),
            UserRole::Editor => self::editor(),
            UserRole::Author => self::author(),
            UserRole::Contributor => self::contributor(),
        };
    }

    /**
     * @return list<Permission>
     */
    private static function administrator(): array
    {
        return Permission::cases();
    }

    /**
     * @return list<Permission>
     */
    private static function editor(): array
    {
        return [
            Permission::DashboardView,
            Permission::DashboardViewAllDrafts,
            Permission::DashboardViewRecentAll,

            Permission::PostsViewAll,
            Permission::PostsViewOwn,
            Permission::PostsCreate,
            Permission::PostsEditOwn,
            Permission::PostsEditOthers,
            Permission::PostsPublish,
            Permission::PostsDeleteOwn,
            Permission::PostsDeleteOthers,
            Permission::PostsRestore,
            Permission::PostsDuplicate,

            Permission::PagesViewAll,
            Permission::PagesViewOwn,
            Permission::PagesCreate,
            Permission::PagesEditOwn,
            Permission::PagesEditOthers,
            Permission::PagesPublish,
            Permission::PagesDeleteOwn,
            Permission::PagesDeleteOthers,
            Permission::PagesRestore,

            Permission::CustomPostTypesManage,

            Permission::TaxonomiesView,
            Permission::TaxonomiesCreate,
            Permission::TaxonomiesEdit,
            Permission::TaxonomiesDelete,

            Permission::MediaView,
            Permission::MediaUpload,
            Permission::MediaEditOwn,
            Permission::MediaEditOthers,
            Permission::MediaDelete,

            Permission::UsersViewAll,
            Permission::UsersEditOwn,

            Permission::SeoConfigureContent,
            Permission::SeoDefaultsView,
        ];
    }

    /**
     * @return list<Permission>
     */
    private static function author(): array
    {
        return [
            Permission::DashboardView,

            Permission::PostsViewOwn,
            Permission::PostsCreate,
            Permission::PostsEditOwn,
            Permission::PostsDeleteOwn,
            Permission::PostsDuplicate,

            Permission::PagesViewOwn,
            Permission::PagesCreate,
            Permission::PagesEditOwn,
            Permission::PagesDeleteOwn,

            Permission::TaxonomiesView,

            Permission::MediaView,
            Permission::MediaUpload,
            Permission::MediaEditOwn,

            Permission::UsersEditOwn,

            Permission::SeoConfigureContent,
        ];
    }

    /**
     * @return list<Permission>
     */
    private static function contributor(): array
    {
        return [
            Permission::DashboardView,

            Permission::PostsViewOwn,
            Permission::PostsCreate,
            Permission::PostsEditOwn,

            Permission::TaxonomiesView,

            Permission::MediaView,

            Permission::UsersEditOwn,
        ];
    }
}
