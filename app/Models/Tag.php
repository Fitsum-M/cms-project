<?php

namespace App\Models;

use App\Contracts\HasContentAssignments;
use Database\Factories\TagFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

#[Fillable([
    'name',
    'slug',
    'description',
])]
class Tag extends Model implements HasContentAssignments
{
    /** @use HasFactory<TagFactory> */
    use HasFactory;

    public function scopeWhereNameInsensitive(Builder $query, string $name): Builder
    {
        return $query->whereRaw('LOWER(name) = ?', [mb_strtolower(trim($name))]);
    }

    public static function findByNameInsensitive(string $name): ?self
    {
        return static::query()->whereNameInsensitive($name)->first();
    }

    public function hasAssignedContent(): bool
    {
        return $this->assignedContentCount() > 0;
    }

    public function assignedContentCount(): int
    {
        if (! Schema::hasTable('post_tag')) {
            return 0;
        }

        return (int) DB::table('post_tag')->where('tag_id', $this->id)->count();
    }

    public function taxonomyLabel(): string
    {
        return 'tag';
    }
}
