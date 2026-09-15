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

    /**
     * @return Collection<int, Post>
     */
    public function latestByType(string $postType, int $limit = 6): Collection
    {
        return $this->publishedPostsQuery($postType)
            ->limit($limit)
            ->get();
    }

    /**
     * @return Collection<int, Post>
     */
    public function orderedByType(string $postType, int $limit = 50): Collection
    {
        return $this->publishedPostsQuery($postType)
            ->get()
            ->sortBy(function (Post $post): int {
                $order = $post->customField('display_order') ?? $post->customField('step_number');

                return is_numeric($order) ? (int) $order : PHP_INT_MAX;
            })
            ->values()
            ->take($limit);
    }

    /**
     * @return array{
     *     team: Collection<int, Post>,
     *     services: Collection<int, Post>,
     *     products: Collection<int, Post>,
     *     testimonials: Collection<int, Post>,
     *     news: Collection<int, Post>
     * }
     */
    public function demoWebsiteSections(): array
    {
        return [
            'team' => $this->latestByType(\App\Support\CustomFields\CustomFieldRegistry::TEAM_MEMBERS, 6),
            'services' => $this->orderedByType(\App\Support\CustomFields\CustomFieldRegistry::SERVICES, 6),
            'products' => $this->latestByType(\App\Support\CustomFields\CustomFieldRegistry::PRODUCTS, 6),
            'testimonials' => $this->latestByType(\App\Support\CustomFields\CustomFieldRegistry::TESTIMONIALS, 6),
            'news' => $this->latestByType('post', 3),
        ];
    }

    /**
     * Capstone organization homepage sections (Abdi Gudina replica).
     *
     * @return array<string, mixed>
     */
    public function organizationWebsiteSections(): array
    {
        $registry = \App\Support\CustomFields\CustomFieldRegistry::class;

        return [
            'heroPage' => $this->findPublicPage('home-hero'),
            'aboutPage' => $this->findPublicPage('about-us'),
            'stats' => $this->orderedByType($registry::STATS, 8),
            'services' => $this->orderedByType($registry::SERVICES, 8),
            'team' => $this->latestByType($registry::TEAM_MEMBERS, 6),
            'joinSteps' => $this->orderedByType($registry::JOIN_STEPS, 8),
            'faqs' => $this->orderedByType($registry::FAQS, 12),
            'memberSaccos' => $this->latestByType($registry::MEMBER_SACCOS, 64),
            'impactStories' => $this->latestByType($registry::IMPACT_STORIES, 8),
            'resources' => $this->latestByType($registry::RESOURCES, 8),
            'news' => $this->latestByType('post', 3),
            'contactPage' => $this->findPublicPage('contact'),
        ];
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
            ->with(['author', 'parent', 'featuredImage', 'seo'])
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
