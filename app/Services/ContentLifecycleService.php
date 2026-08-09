<?php

namespace App\Services;

use App\Contracts\HasContentLifecycle;
use App\Enums\ContentStatus;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Shared content lifecycle transitions (SRS 12.5.2).
 */
class ContentLifecycleService
{
    public function __construct(
        private readonly ContentSlugService $slugs,
    ) {}

    public function canEdit(HasContentLifecycle $content): bool
    {
        return ! $content->isTrashed();
    }

    public function canPublish(HasContentLifecycle $content): bool
    {
        if ($content->isTrashed()) {
            return false;
        }

        return in_array($content->contentStatus(), [
            ContentStatus::Draft,
            ContentStatus::PendingReview,
            ContentStatus::Archived,
        ], true);
    }

    public function canUnpublish(HasContentLifecycle $content): bool
    {
        return ! $content->isTrashed()
            && $content->contentStatus() === ContentStatus::Published;
    }

    public function canArchive(HasContentLifecycle $content): bool
    {
        if ($content->isTrashed()) {
            return false;
        }

        return in_array($content->contentStatus(), [
            ContentStatus::Draft,
            ContentStatus::PendingReview,
            ContentStatus::Published,
        ], true);
    }

    public function canTrash(HasContentLifecycle $content): bool
    {
        return ! $content->isTrashed();
    }

    public function canRestore(HasContentLifecycle $content): bool
    {
        return $content->isTrashed()
            || $content->contentStatus() === ContentStatus::Archived;
    }

    public function canForceDelete(HasContentLifecycle $content): bool
    {
        return $content->isTrashed();
    }

    /**
     * Create / save as draft (default starting status).
     */
    public function saveAsDraft(HasContentLifecycle $content): HasContentLifecycle
    {
        if ($content->isTrashed()) {
            $this->deny('Trashed content cannot be edited.');
        }

        $content->setContentStatus(ContentStatus::Draft);
        $content->save();

        return $content;
    }

    /**
     * Authors/Contributors without publish permission submit for review.
     */
    public function submitForReview(HasContentLifecycle $content): HasContentLifecycle
    {
        $this->assertNotTrashed($content);

        if (! in_array($content->contentStatus(), [ContentStatus::Draft, ContentStatus::PendingReview], true)) {
            $this->deny('Only draft content can be submitted for review.');
        }

        $content->setContentStatus(ContentStatus::PendingReview);
        $content->save();

        return $content;
    }

    /**
     * Publish when the actor has publish permission; otherwise move to Pending Review.
     */
    public function publish(HasContentLifecycle $content, User $actor): HasContentLifecycle
    {
        $this->assertNotTrashed($content);

        if (! $this->canPublish($content) && $content->contentStatus() !== ContentStatus::Published) {
            $this->deny('This content cannot be published from its current status.');
        }

        if (! $actor->can($content->publishPermission()->value)) {
            return $this->submitForReview($content);
        }

        $content->setContentStatus(ContentStatus::Published);
        $content->markPublishedAt();
        $content->save();

        return $content;
    }

    /**
     * Unpublish to Draft (default) or Archived.
     */
    public function unpublish(
        HasContentLifecycle $content,
        ContentStatus $target = ContentStatus::Draft,
    ): HasContentLifecycle {
        $this->assertNotTrashed($content);

        if (! $this->canUnpublish($content)) {
            $this->deny('Only published content can be unpublished.');
        }

        if (! in_array($target, [ContentStatus::Draft, ContentStatus::Archived], true)) {
            $this->deny('Unpublish target must be Draft or Archived.');
        }

        $content->setContentStatus($target);
        $content->save();

        return $content;
    }

    public function archive(HasContentLifecycle $content): HasContentLifecycle
    {
        $this->assertNotTrashed($content);

        if (! $this->canArchive($content)) {
            $this->deny('This content cannot be archived from its current status.');
        }

        $content->setContentStatus(ContentStatus::Archived);
        $content->save();

        return $content;
    }

    /**
     * Soft-delete → Trashed (excluded from default listings).
     */
    public function trash(HasContentLifecycle $content): HasContentLifecycle
    {
        if (! $this->canTrash($content)) {
            $this->deny('Content is already trashed.');
        }

        $content->trash();

        return $content;
    }

    /**
     * Restore trashed → Draft (slug suffix if conflict).
     * Restore archived → Draft (default) or Published (requires publish permission when Published).
     */
    public function restore(
        HasContentLifecycle $content,
        User $actor,
        ContentStatus $target = ContentStatus::Draft,
    ): HasContentLifecycle {
        if (! $this->canRestore($content)) {
            $this->deny('Only trashed or archived content can be restored.');
        }

        if ($content->isTrashed()) {
            if (! $actor->can($content->restorePermission()->value)) {
                $this->deny('You do not have permission to restore this content.');
            }

            if ($target !== ContentStatus::Draft) {
                $this->deny('Restoring from trash defaults to Draft status.');
            }

            return DB::transaction(function () use ($content): HasContentLifecycle {
                $content->restoreFromTrash();
                $this->ensureUniqueSlugAfterRestore($content);
                $content->setContentStatus(ContentStatus::Draft);
                $content->save();

                return $content;
            });
        }

        // Archived → Draft or Published
        if (! in_array($target, [ContentStatus::Draft, ContentStatus::Published], true)) {
            $this->deny('Archived content may be restored to Draft or Published.');
        }

        if ($target === ContentStatus::Published) {
            if (! $actor->can($content->publishPermission()->value)) {
                $this->deny('You do not have permission to publish this content.');
            }
            $content->setContentStatus(ContentStatus::Published);
            $content->markPublishedAt();
        } else {
            $content->setContentStatus(ContentStatus::Draft);
        }

        $content->save();

        return $content;
    }

    /**
     * Hard delete — only while trashed; Admin force-delete permission.
     */
    public function forceDelete(HasContentLifecycle $content, User $actor): void
    {
        if (! $this->canForceDelete($content)) {
            $this->deny('Hard delete is only allowed for trashed content.');
        }

        if (! $actor->can($content->forceDeletePermission()->value)) {
            $this->deny('You do not have permission to permanently delete this content.');
        }

        $content->permanentlyDelete();
    }

    private function ensureUniqueSlugAfterRestore(HasContentLifecycle $content): void
    {
        $resolved = $this->slugs->resolve([
            'title' => $content->contentTitle(),
            'slug' => $content->contentSlug(),
            'scope' => $content->contentSlugScope(),
            'ignore_id' => $content->getKey(),
            'current_slug' => $content->contentSlug(),
            'has_been_published' => $content->hasBeenPublished(),
            'confirm_slug_change' => true,
            'accept_conflict_resolution' => true,
        ]);

        if ($resolved !== $content->contentSlug()) {
            $content->setContentSlug($resolved);
        }
    }

    private function assertNotTrashed(HasContentLifecycle $content): void
    {
        if ($content->isTrashed()) {
            $this->deny('Trashed content cannot be modified. Restore it first.');
        }
    }

    private function deny(string $message): never
    {
        throw ValidationException::withMessages([
            'status' => $message,
        ]);
    }
}
