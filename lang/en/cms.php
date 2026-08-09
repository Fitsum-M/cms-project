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

];
