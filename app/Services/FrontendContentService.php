<?php

namespace App\Services;

use App\Enums\ContentStatus;
use App\Enums\PostVisibility;
use App\Models\Page;
use App\Models\Post;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

class FrontendContentService
{
    /**
     * @return Builder<Post>
     */
    public function publishedPostsQuery(?string $postType = null): Builder
    {
        $query = Post::query()
            ->with(['author', 'categories', 'tags', 'featuredImage'])
            ->where('status', ContentStatus::Published)
            ->where('visibility', PostVisibility::Public)
            ->where(function (Builder $query): void {
                $query->whereNull('published_at')
                    ->orWhere('published_at', '<=', now());
            })
            ->orderByDesc('published_at')
            ->orderByDesc('id');

        if ($postType !== null) {
            $query->where('post_type', $postType);
        }

        return $query;
    }

    public function paginatedPosts(int $perPage = 10, ?string $postType = 'post'): LengthAwarePaginator
    {
        return $this->publishedPostsQuery($postType)->paginate($perPage);
    }

    public function paginatedPostsByType(string $postType, int $perPage = 12): LengthAwarePaginator
    {
        return $this->publishedPostsQuery($postType)->paginate($perPage);
    }

    public function findPublicPost(string $slug): ?Post
    {
        $post = Post::query()
            ->with(['author', 'categories', 'tags', 'featuredImage'])
            ->where('slug', $slug)
            ->first();

        if ($post === null || ! $post->isPubliclyAccessible()) {
            return null;
        }

        return $post;
    }

    /**
     * @return Collection<int, Page>
     */
    public function navigationPages(): Collection
    {
        $publishedVisibility = function (Builder $query): void {
            $query->whereNull('published_at')
                ->orWhere('published_at', '<=', now());
        };

        return Page::query()
            ->where('status', ContentStatus::Published)
            ->where('show_in_navigation', true)
            ->whereNull('parent_id')
            ->where($publishedVisibility)
            ->with(['children' => function ($query) use ($publishedVisibility): void {
                $query
                    ->where('status', ContentStatus::Published)
                    ->where('show_in_navigation', true)
                    ->where($publishedVisibility)
                    ->orderBy('sort_order')
                    ->orderBy('title');
            }])
            ->orderBy('sort_order')
            ->orderBy('title')
            ->get();
    }

    public function findPublicPage(string $slug): ?Page
    {
        $page = Page::query()
            ->with(['author', 'parent', 'featuredImage'])
            ->where('slug', $slug)
            ->first();

        if ($page === null || ! $this->isPublicPage($page)) {
            return null;
        }

        return $page;
    }

    public function isPublicPage(Page $page): bool
    {
        if ($page->trashed()) {
            return false;
        }

        if ($page->contentStatus() !== ContentStatus::Published) {
            return false;
        }

        if ($page->published_at !== null && $page->published_at->isFuture()) {
            return false;
        }

        return true;
    }
}
