<?php

namespace App\Support\Dashboard;

use App\Enums\ContentStatus;
use App\Filament\Resources\Pages\PageResource;
use App\Filament\Resources\Posts\PostResource;
use App\Models\Page;
use App\Models\Post;
use App\Support\Settings\GeneralSettings;
use Carbon\CarbonInterface;

/**
 * One row in the Dashboard Recent Content widget (SRS 10.3 / D.02).
 */
final readonly class RecentContentItem
{
    public function __construct(
        public string $type,
        public int $id,
        public string $title,
        public ContentStatus $status,
        public ?string $authorName,
        public CarbonInterface $updatedAt,
        public string $editUrl,
    ) {}

    public static function fromPost(Post $post): self
    {
        return new self(
            type: 'post',
            id: (int) $post->getKey(),
            title: (string) $post->title,
            status: $post->status instanceof ContentStatus
                ? $post->status
                : ContentStatus::from((string) $post->status),
            authorName: $post->author?->name,
            updatedAt: $post->updated_at,
            editUrl: PostResource::getUrl('edit', ['record' => $post]),
        );
    }

    public static function fromPage(Page $page): self
    {
        return new self(
            type: 'page',
            id: (int) $page->getKey(),
            title: (string) $page->title,
            status: $page->status instanceof ContentStatus
                ? $page->status
                : ContentStatus::from((string) $page->status),
            authorName: $page->author?->name,
            updatedAt: $page->updated_at,
            editUrl: PageResource::getUrl('edit', ['record' => $page]),
        );
    }

    public function typeLabel(): string
    {
        return match ($this->type) {
            'post' => 'Post',
            'page' => 'Page',
            default => ucfirst($this->type),
        };
    }

    public function key(): string
    {
        return $this->type.':'.$this->id;
    }

    public function formattedUpdatedAt(): string
    {
        return app(GeneralSettings::class)->formatDateTime($this->updatedAt) ?? '—';
    }
}
