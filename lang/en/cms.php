<?php

return [

    /*
    |--------------------------------------------------------------------------
    | CMS navigation (SRS §10.1, GAP.NFR.02)
    |--------------------------------------------------------------------------
    */
    'navigation' => [
        'groups' => [
            'content' => 'Content',
            'dam' => 'Digital Asset Management',
            'iam' => 'Identity & Access Management',
            'system' => 'System Configuration',
        ],
        'dashboard' => 'Dashboard',
    ],

    /*
    |--------------------------------------------------------------------------
    | Dashboard widgets (SRS §10.3, §20.1)
    |--------------------------------------------------------------------------
    */
    'dashboard' => [
        'overview' => [
            'heading' => 'Overview',
            'description' => 'Current content and account totals across the CMS.',
            'stats' => [
                'posts' => 'Posts',
                'posts_description' => 'Published, draft, and pending posts',
                'pages' => 'Pages',
                'pages_description' => 'All non-trashed pages',
                'media' => 'Media',
                'media_description' => 'Items in the media library',
                'users' => 'Users',
                'users_description' => 'Active, pending, and suspended accounts',
            ],
        ],
        'recent_content' => [
            'heading' => 'Recent Content',
            'description' => 'Last :count edited posts and pages.',
            'empty' => 'No recently edited content yet.',
        ],
        'draft_summary' => [
            'heading' => 'Draft Summary',
            'description' => 'Your drafts.',
            'description_with_review' => 'Your drafts and content awaiting review.',
            'my_drafts' => 'My drafts',
            'awaiting_review' => 'Awaiting review',
            'empty_own' => 'You have no drafts.',
            'empty_review' => 'No content is awaiting review.',
        ],
        'quick_actions' => [
            'heading' => 'Quick Actions',
            'description' => 'Jump into common editorial tasks.',
            'add_post' => 'Add New Post',
            'add_post_description' => 'Create a draft post',
            'add_page' => 'Add New Page',
            'add_page_description' => 'Create a new page',
            'upload_media' => 'Upload Media',
            'upload_media_description' => 'Add files to the library',
        ],
        'content_table' => [
            'title' => 'Title',
            'type' => 'Type',
            'status' => 'Status',
            'author' => 'Author',
            'updated' => 'Updated',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Shared table labels
    |--------------------------------------------------------------------------
    */
    'tables' => [
        'title' => 'Title',
    ],

    /*
    |--------------------------------------------------------------------------
    | Identity & Access Management — roles & permissions (SRS §10.1, §11.4)
    |--------------------------------------------------------------------------
    */
    'iam' => [
        'permissions' => [
            'granted' => 'Granted',
            'denied' => 'Denied',
        ],
        'roles' => [
            'system_role' => 'System role',
            'coverage' => 'Permission coverage',
            'coverage_detail' => ':percent% of all capabilities',
            'group_coverage' => ':granted of :total granted in this group',
            'matrix_heading' => 'Role-based access',
            'matrix_description' => 'Permissions are defined at the role level. Users inherit all capabilities of their single assigned role. Per-user overrides are not supported (SRS 11.2 / 15.6).',
            'comparison_heading' => 'Capability comparison',
            'capability' => 'Capability',
            'permissions_count' => ':granted / :total permissions',
            'view_details' => 'View role',
        ],
    ],
];
