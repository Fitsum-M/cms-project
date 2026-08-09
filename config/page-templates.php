<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Page templates (SRS 12.3.5)
    |--------------------------------------------------------------------------
    |
    | System-level frontend presentation variants. Keys are stored on pages.
    | When a page has no template (null), "default" is assumed.
    |
    */
    'templates' => [
        'default' => [
            'label' => 'Default',
            'description' => 'Standard content page layout.',
            'icon' => 'heroicon-o-document',
        ],
        'full-width' => [
            'label' => 'Full Width',
            'description' => 'Edge-to-edge content without a sidebar.',
            'icon' => 'heroicon-o-arrows-pointing-out',
        ],
        'landing' => [
            'label' => 'Landing',
            'description' => 'Marketing-oriented landing page layout.',
            'icon' => 'heroicon-o-rocket-launch',
        ],
        'sidebar' => [
            'label' => 'With Sidebar',
            'description' => 'Content with a secondary sidebar column.',
            'icon' => 'heroicon-o-view-columns',
        ],
        'contact' => [
            'label' => 'Contact',
            'description' => 'Contact page with form-oriented layout.',
            'icon' => 'heroicon-o-envelope',
        ],
    ],

    'default' => 'default',
];
