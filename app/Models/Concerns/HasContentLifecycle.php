<?php

namespace App\Models\Concerns;

use App\Enums\ContentStatus;
use Illuminate\Database\Eloquent\SoftDeletes;

trait HasContentLifecycle
{
    use SoftDeletes;

    public function contentStatus(): ContentStatus
    {
        $status = $this->status;

        if ($status instanceof ContentStatus) {
            return $status;
        }

        return ContentStatus::tryFrom((string) $status) ?? ContentStatus::Draft;
    }

    public function setContentStatus(ContentStatus $status): void
    {
        $this->status = $status;
    }

    public function contentTitle(): string
    {
        return (string) $this->title;
    }

    public function contentSlug(): string
    {
        return (string) $this->slug;
    }

    public function setContentSlug(string $slug): void
    {
        $this->slug = $slug;
    }

    public function hasBeenPublished(): bool
    {
        return $this->published_at !== null;
    }

    public function markPublishedAt(?\DateTimeInterface $when = null): void
    {
        if ($this->published_at === null) {
            $this->published_at = $when ?? now();
        }
    }

    public function trash(): void
    {
        $this->delete();
    }

    public function restoreFromTrash(): void
    {
        $this->restore();
    }

    public function isTrashed(): bool
    {
        return $this->trashed();
    }

    public function permanentlyDelete(): void
    {
        $this->forceDelete();
    }

    /**
     * Effective editorial status label including Trashed.
     */
    public function lifecycleLabel(): string
    {
        if ($this->trashed()) {
            return 'Trashed';
        }

        return $this->contentStatus()->label();
    }
}
