<?php

namespace App\Support;

use Illuminate\Support\Facades\Schema;

final class PostTypeRegistry
{
    /**
     * Built-in + registered custom post type keys available for taxonomy association.
     * CPT registration (Phase 7) will populate `post_types` when that table exists.
     *
     * @return array<string, string>
     */
    public static function options(): array
    {
        $options = [
            'post' => 'Posts (standard)',
        ];

        if (Schema::hasTable('post_types')) {
            $rows = \Illuminate\Support\Facades\DB::table('post_types')
                ->orderBy('plural_name')
                ->get(['slug', 'plural_name']);

            foreach ($rows as $row) {
                $options[(string) $row->slug] = (string) $row->plural_name;
            }
        }

        return $options;
    }

    /**
     * @return list<string>
     */
    public static function keys(): array
    {
        return array_keys(self::options());
    }
}
