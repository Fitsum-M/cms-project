<?php

namespace App\Contracts;

use App\Enums\ContentSlugScope;
use App\Enums\ContentStatus;
use App\Enums\Permission;

/**
 * Shared content lifecycle for Posts, Pages, and Custom Post Types (via Posts).
 */
interface HasContentLifecycle
{
    public function getKey();

    public function contentStatus(): ContentStatus;

    public function setContentStatus(ContentStatus $status): void;

    public function contentSlugScope(): ContentSlugScope;

    public function contentTitle(): string;

    public function contentSlug(): string;

    public function setContentSlug(string $slug): void;

    public function hasBeenPublished(): bool;

    public function markPublishedAt(?\DateTimeInterface $when = null): void;

    public function publishPermission(): Permission;

    public function restorePermission(): Permission;

    public function forceDeletePermission(): Permission;

    public function trash(): void;

    public function restoreFromTrash(): void;

    public function isTrashed(): bool;

    public function permanentlyDelete(): void;

    public function save(array $options = []);
}
