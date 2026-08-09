<?php

namespace App\Models;

use Database\Factories\PostTypeFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Schema;

#[Fillable([
    'plural_name',
    'singular_name',
    'slug',
    'icon',
    'supports_categories',
    'supports_tags',
    'supports_excerpt',
    'supports_featured_image',
    'default_schema_type',
    'sort_order',
])]
class PostType extends Model
{
    /** @use HasFactory<PostTypeFactory> */
    use HasFactory;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'sort_order' => 'integer',
            'supports_categories' => 'boolean',
            'supports_tags' => 'boolean',
            'supports_excerpt' => 'boolean',
            'supports_featured_image' => 'boolean',
        ];
    }

    public function posts(): HasMany
    {
        return $this->hasMany(Post::class, 'post_type', 'slug');
    }

    /**
     * @return list<int>
     */
    public function customTaxonomyIds(): array
    {
        if (! Schema::hasTable('custom_taxonomy_post_type')) {
            return [];
        }

        return \Illuminate\Support\Facades\DB::table('custom_taxonomy_post_type')
            ->where('post_type_key', $this->slug)
            ->orderBy('custom_taxonomy_id')
            ->pluck('custom_taxonomy_id')
            ->map(fn ($id): int => (int) $id)
            ->all();
    }

    public function hasAssignedContent(): bool
    {
        if (! Schema::hasTable('posts')) {
            return false;
        }

        return $this->posts()->withTrashed()->exists();
    }

    public function postsCount(): int
    {
        if (! Schema::hasTable('posts')) {
            return 0;
        }

        return $this->posts()->count();
    }

    /**
     * Filament / Blade icon name (Heroicon).
     */
    public function resolvedIcon(): string
    {
        return filled($this->icon) ? (string) $this->icon : 'heroicon-o-rectangle-stack';
    }
}
