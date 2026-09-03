<?php

namespace App\Support\Auth;

use App\Enums\Permission;

/**
 * Default role → permission matrix for seeding only (SRS Section 11.4).
 * Runtime roles live in the database and are managed via the admin UI.
 */
final class RolePermissionMatrix
{
    /**
     * Seed definitions for MVP default roles.
     *
     * @return array<string, array{
     *     description: string,
     *     color: string,
     *     icon: string,
     *     is_system: bool,
     *     permissions: list<Permission>
     * }>
     */
    public static function definitions(): array
    {
        return [
            'Administrator' => [
                'description' => 'Full system access including all modules, user management, role assignment, and system configuration.',
                'color' => 'danger',
                'icon' => 'heroicon-o-shield-check',
                'is_system' => true,
                'permissions' => self::administrator(),
            ],
            'Editor' => [
                'description' => 'Full content and taxonomy management; can publish, edit others\' content, and manage media. Restricted from user role assignment, system settings, and SEO Defaults.',
                'color' => 'warning',
                'icon' => 'heroicon-o-pencil-square',
                'is_system' => true,
                'permissions' => self::editor(),
            ],
            'Author' => [
                'description' => 'Can create and edit own posts and pages, assign taxonomies, upload media, and configure SEO on own content. Cannot publish; submits content for review.',
                'color' => 'info',
                'icon' => 'heroicon-o-document-text',
                'is_system' => true,
                'permissions' => self::author(),
            ],
            'Contributor' => [
                'description' => 'Most restricted role. Can create own draft posts only. Cannot upload media directly; may select from existing library. Cannot manage pages.',
                'color' => 'gray',
                'icon' => 'heroicon-o-pencil',
                'is_system' => true,
                'permissions' => self::contributor(),
            ],
        ];
    }

    /**
     * @return list<Permission>
     */
    public static function permissionsFor(string $roleName): array
    {
        return self::definitions()[$roleName]['permissions'] ?? [];
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
