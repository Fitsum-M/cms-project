<?php

namespace App\Models;

use App\Contracts\HasContentAssignments;
use Database\Factories\CategoryFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

#[Fillable([
    'name',
    'slug',
    'parent_id',
    'description',
])]
class Category extends Model implements HasContentAssignments
{
    /** @use HasFactory<CategoryFactory> */
    use HasFactory;

    public function parent(): BelongsTo
    {
        return $this->belongsTo(self::class, 'parent_id');
    }

    public function children(): HasMany
    {
        return $this->hasMany(self::class, 'parent_id')->orderBy('name');
    }

    /**
     * @return list<int>
     */
    public function descendantIds(): array
    {
        $ids = [];
        $queue = $this->children()->pluck('id')->all();

        while ($queue !== []) {
            $currentId = array_shift($queue);
            $ids[] = $currentId;

            $childIds = self::query()
                ->where('parent_id', $currentId)
                ->pluck('id')
                ->all();

            foreach ($childIds as $childId) {
                $queue[] = $childId;
            }
        }

        return $ids;
    }

    public function isDescendantOf(self $other): bool
    {
        return in_array($this->id, $other->descendantIds(), true);
    }

    public function wouldCreateCycle(?int $parentId): bool
    {
        if ($parentId === null) {
            return false;
        }

        if ($this->exists && $parentId === $this->id) {
            return true;
        }

        if (! $this->exists) {
            return false;
        }

        return in_array($parentId, $this->descendantIds(), true);
    }

    public function posts(): BelongsToMany
    {
        return $this->belongsToMany(Post::class, 'category_post')->withTimestamps();
    }

    public function hasAssignedContent(): bool
    {
        return $this->assignedContentCount() > 0;
    }

    public function assignedContentCount(): int
    {
        if (! Schema::hasTable('category_post')) {
            return 0;
        }

        return (int) DB::table('category_post')->where('category_id', $this->id)->count();
    }

    public function taxonomyLabel(): string
    {
        return 'category';
    }
}
