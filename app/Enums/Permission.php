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
}
