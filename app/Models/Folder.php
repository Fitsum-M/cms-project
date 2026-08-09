<?php

namespace App\Models;

use Database\Factories\FolderFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable([
    'name',
    'parent_id',
])]
class Folder extends Model
{
    /** @use HasFactory<FolderFactory> */
    use HasFactory;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'parent_id' => 'integer',
        ];
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(self::class, 'parent_id');
    }

    public function children(): HasMany
    {
        return $this->hasMany(self::class, 'parent_id')->orderBy('name');
    }

    public function mediaAssets(): HasMany
    {
        return $this->hasMany(MediaAsset::class, 'folder_id');
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

    public function isEmpty(): bool
    {
        return ! $this->children()->exists() && ! $this->mediaAssets()->exists();
    }
}
