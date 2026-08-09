<?php

namespace App\Models;

use App\Contracts\HasContentLifecycle;
use App\Contracts\Ownable;
use App\Enums\ContentSlugScope;
use App\Enums\ContentStatus;
use App\Enums\Permission;
use App\Models\Concerns\HasContentLifecycle as HasContentLifecycleTrait;
use App\Services\ContentUrlGenerator;
use App\Support\PageTemplateRegistry;
use Database\Factories\PageFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable([
    'title',
    'slug',
    'body',
    'author_id',
    'parent_id',
    'sort_order',
    'template',
    'show_in_navigation',
    'status',
    'published_at',
])]
class Page extends Model implements HasContentLifecycle, Ownable
{
    /** @use HasFactory<PageFactory> */
    use HasContentLifecycleTrait;
    use HasFactory;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'parent_id' => 'integer',
            'author_id' => 'integer',
            'sort_order' => 'integer',
            'show_in_navigation' => 'boolean',
            'status' => ContentStatus::class,
            'published_at' => 'datetime',
        ];
    }

    public function author(): BelongsTo
    {
        return $this->belongsTo(User::class, 'author_id');
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(self::class, 'parent_id');
    }

    public function children(): HasMany
    {
        return $this->hasMany(self::class, 'parent_id')
            ->orderBy('sort_order')
            ->orderBy('title');
    }

    /**
     * Effective template key (null/unknown → Default).
     */
    public function resolvedTemplate(): string
    {
        return PageTemplateRegistry::resolve($this->template);
    }

    public function templateLabel(): string
    {
        return PageTemplateRegistry::label($this->template);
    }

    public function templateIcon(): string
    {
        return PageTemplateRegistry::icon($this->template);
    }

    public function isNavigationReady(): bool
    {
        return (bool) $this->show_in_navigation;
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

    /**
     * @return list<string>
     */
    public function ancestorTitles(): array
    {
        $titles = [];
        $current = $this->parent;

        while ($current !== null) {
            array_unshift($titles, $current->title);
            $current = $current->parent;
        }

        return $titles;
    }

    public function hierarchicalLabel(): string
    {
        $parts = [...$this->ancestorTitles(), $this->title];

        return implode(' › ', $parts);
    }

    public function publicPath(): string
    {
        return app(ContentUrlGenerator::class)->pagePath($this);
    }

    public function ownerKey(): ?int
    {
        return $this->author_id;
    }

    public function contentSlugScope(): ContentSlugScope
    {
        return ContentSlugScope::Pages;
    }

    public function publishPermission(): Permission
    {
        return Permission::PagesPublish;
    }

    public function restorePermission(): Permission
    {
        return Permission::PagesRestore;
    }

    public function forceDeletePermission(): Permission
    {
        return Permission::PagesForceDelete;
    }
}
