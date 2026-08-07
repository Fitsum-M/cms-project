<?php

namespace App\Models;

use App\Contracts\HasContentAssignments;
use Database\Factories\CustomTaxonomyTermFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

#[Fillable([
    'custom_taxonomy_id',
    'name',
    'slug',
    'parent_id',
    'description',
])]
class CustomTaxonomyTerm extends Model implements HasContentAssignments
{
    /** @use HasFactory<CustomTaxonomyTermFactory> */
    use HasFactory;

    public function taxonomy(): BelongsTo
    {
        return $this->belongsTo(CustomTaxonomy::class, 'custom_taxonomy_id');
    }

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

    public function hasAssignedContent(): bool
    {
        return $this->assignedContentCount() > 0;
    }

    public function assignedContentCount(): int
    {
        if (! Schema::hasTable('custom_taxonomy_term_post')) {
            return 0;
        }

        return (int) DB::table('custom_taxonomy_term_post')
            ->where('custom_taxonomy_term_id', $this->id)
            ->count();
    }

    public function taxonomyLabel(): string
    {
        return 'term';
    }
}
