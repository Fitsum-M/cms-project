<?php

return [
    /**
     * Tree model fields — aligned to CMS pages table.
     */
    'column_name' => [
        'order' => 'sort_order',
        'parent' => 'parent_id',
        'title' => 'title',
    ],
    /**
     * CMS roots use NULL parent_id (not -1).
     */
    'default_parent_id' => null,
    /**
     * Tree model default children key name
     */
    'default_children_key_name' => 'children',
];
