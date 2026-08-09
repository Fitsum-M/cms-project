<?php

namespace App\Enums;

enum Permission: string
{
    // Dashboard
    case DashboardView = 'dashboard.view';
    case DashboardViewAllDrafts = 'dashboard.view_all_drafts';
    case DashboardViewRecentAll = 'dashboard.view_recent_all';

    // Posts (also used for Custom Post Type content — SRS 11.4)
    case PostsViewAll = 'posts.view_all';
    case PostsViewOwn = 'posts.view_own';
    case PostsCreate = 'posts.create';
    case PostsEditOwn = 'posts.edit_own';
    case PostsEditOthers = 'posts.edit_others';
    case PostsPublish = 'posts.publish';
    case PostsDeleteOwn = 'posts.delete_own';
    case PostsDeleteOthers = 'posts.delete_others';
    case PostsRestore = 'posts.restore';
    case PostsForceDelete = 'posts.force_delete';
    case PostsDuplicate = 'posts.duplicate';

    // Pages
    case PagesViewAll = 'pages.view_all';
    case PagesViewOwn = 'pages.view_own';
    case PagesCreate = 'pages.create';
    case PagesEditOwn = 'pages.edit_own';
    case PagesEditOthers = 'pages.edit_others';
    case PagesPublish = 'pages.publish';
    case PagesDeleteOwn = 'pages.delete_own';
    case PagesDeleteOthers = 'pages.delete_others';
    case PagesRestore = 'pages.restore';
    case PagesForceDelete = 'pages.force_delete';

    // Custom post type registration (content reuses posts.* permissions)
    case CustomPostTypesManage = 'custom_post_types.manage';

    // Taxonomies
    case TaxonomiesView = 'taxonomies.view';
    case TaxonomiesCreate = 'taxonomies.create';
    case TaxonomiesEdit = 'taxonomies.edit';
    case TaxonomiesDelete = 'taxonomies.delete';

    // Digital Asset Management
    case MediaView = 'media.view';
    case MediaUpload = 'media.upload';
    case MediaEditOwn = 'media.edit_own';
    case MediaEditOthers = 'media.edit_others';
    case MediaDelete = 'media.delete';
    case MediaForceDelete = 'media.force_delete';

    // Identity & Access Management
    case UsersViewAll = 'users.view_all';
    case UsersCreate = 'users.create';
    case UsersEditOwn = 'users.edit_own';
    case UsersEditOthers = 'users.edit_others';
    case UsersEditRole = 'users.edit_role';
    case UsersDelete = 'users.delete';
    case UsersSuspend = 'users.suspend';

    // System Configuration
    case SettingsView = 'settings.view';
    case SettingsEdit = 'settings.edit';

    // SEO & Metadata
    case SeoConfigureContent = 'seo.configure_content';
    case SeoDefaultsView = 'seo.defaults.view';
    case SeoDefaultsEdit = 'seo.defaults.edit';

    public function label(): string
    {
        return match ($this) {
            self::DashboardView => 'View Dashboard',
            self::DashboardViewAllDrafts => 'View All Drafts',
            self::DashboardViewRecentAll => 'View Recent Content (all)',
            self::PostsViewAll => 'View All Posts',
            self::PostsViewOwn => 'View Own Posts',
            self::PostsCreate => 'Create Post',
            self::PostsEditOwn => 'Edit Own Post',
            self::PostsEditOthers => 'Edit Others\' Posts',
            self::PostsPublish => 'Publish Post',
            self::PostsDeleteOwn => 'Delete Own Post',
            self::PostsDeleteOthers => 'Delete Others\' Posts',
            self::PostsRestore => 'Restore Post',
            self::PostsForceDelete => 'Hard Delete Post',
            self::PostsDuplicate => 'Duplicate Post',
            self::PagesViewAll => 'View All Pages',
            self::PagesViewOwn => 'View Own Pages',
            self::PagesCreate => 'Create Page',
            self::PagesEditOwn => 'Edit Own Page',
            self::PagesEditOthers => 'Edit Others\' Pages',
            self::PagesPublish => 'Publish Page',
            self::PagesDeleteOwn => 'Delete Own Page',
            self::PagesDeleteOthers => 'Delete Others\' Pages',
            self::PagesRestore => 'Restore Page',
            self::PagesForceDelete => 'Hard Delete Page',
            self::CustomPostTypesManage => 'Manage Custom Post Types',
            self::TaxonomiesView => 'View Taxonomies',
            self::TaxonomiesCreate => 'Create Taxonomy Term',
            self::TaxonomiesEdit => 'Edit Taxonomy Term',
            self::TaxonomiesDelete => 'Delete Taxonomy Term',
            self::MediaView => 'View Library',
            self::MediaUpload => 'Upload Media',
            self::MediaEditOwn => 'Edit Own Media',
            self::MediaEditOthers => 'Edit Others\' Media',
            self::MediaDelete => 'Delete Media',
            self::MediaForceDelete => 'Force Delete Media',
            self::UsersViewAll => 'View All Users',
            self::UsersCreate => 'Create User',
            self::UsersEditOwn => 'Edit Own Profile',
            self::UsersEditOthers => 'Edit User',
            self::UsersEditRole => 'Edit User Role',
            self::UsersDelete => 'Delete User',
            self::UsersSuspend => 'Suspend User',
            self::SettingsView => 'View Settings',
            self::SettingsEdit => 'Edit Settings',
            self::SeoConfigureContent => 'Configure SEO on Content',
            self::SeoDefaultsView => 'View SEO Defaults',
            self::SeoDefaultsEdit => 'Edit SEO Defaults',
        };
    }

    public function group(): string
    {
        return match ($this) {
            self::DashboardView, self::DashboardViewAllDrafts, self::DashboardViewRecentAll => 'Dashboard',
            self::PostsViewAll, self::PostsViewOwn, self::PostsCreate, self::PostsEditOwn, self::PostsEditOthers,
            self::PostsPublish, self::PostsDeleteOwn, self::PostsDeleteOthers, self::PostsRestore,
            self::PostsForceDelete, self::PostsDuplicate => 'Posts',
            self::PagesViewAll, self::PagesViewOwn, self::PagesCreate, self::PagesEditOwn, self::PagesEditOthers,
            self::PagesPublish, self::PagesDeleteOwn, self::PagesDeleteOthers, self::PagesRestore,
            self::PagesForceDelete => 'Pages',
            self::CustomPostTypesManage => 'Custom Post Types',
            self::TaxonomiesView, self::TaxonomiesCreate, self::TaxonomiesEdit, self::TaxonomiesDelete => 'Taxonomies',
            self::MediaView, self::MediaUpload, self::MediaEditOwn, self::MediaEditOthers, self::MediaDelete,
            self::MediaForceDelete => 'Digital Asset Management',
            self::UsersViewAll, self::UsersCreate, self::UsersEditOwn, self::UsersEditOthers, self::UsersEditRole,
            self::UsersDelete, self::UsersSuspend => 'Identity & Access Management',
            self::SettingsView, self::SettingsEdit => 'System Configuration',
            self::SeoConfigureContent, self::SeoDefaultsView, self::SeoDefaultsEdit => 'SEO & Metadata',
        };
    }
}
