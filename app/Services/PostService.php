<?php

namespace App\Services;

use App\Enums\ContentSlugScope;
use App\Enums\ContentStatus;
use App\Enums\PostVisibility;
use App\Models\Post;
use App\Models\User;
use App\Support\PostTypeRegistry;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class PostService
{
    public function __construct(
        private readonly ContentSlugService $slugs,
        private readonly ContentLifecycleService $lifecycle,
        private readonly TaxonomyAssignmentService $taxonomies,
    ) {}

    /**
     * @param  array{
     *     title: string,
     *     slug?: string|null,
     *     body?: string|null,
     *     excerpt?: string|null,
     *     author_id?: int|null,
     *     featured_image_id?: int|null,
     *     post_type?: string,
     *     status?: string|ContentStatus,
     *     visibility?: string|PostVisibility,
     *     password?: string|null,
     *     published_at?: mixed,
     *     confirm_slug_change?: bool,
     *     accept_conflict_resolution?: bool,
     *     category_ids?: list<int|string>,
     *     tag_ids?: list<int|string>,
     *     custom_term_ids?: list<int|string>
     * }  $data
     */
    public function create(array $data, User $actor): Post
    {
        return DB::transaction(function () use ($data, $actor): Post {
            $title = trim((string) $data['title']);
            if ($title === '') {
                throw ValidationException::withMessages([
                    'title' => 'A title is required.',
                ]);
            }

            $authorId = $this->resolveAuthorId($data['author_id'] ?? null, $actor, isCreate: true);
            $visibility = $this->resolveVisibility($data['visibility'] ?? PostVisibility::Public);
            $password = $this->resolvePassword($visibility, $data['password'] ?? null, existingHash: null);
            $postType = $this->resolvePostType($data['post_type'] ?? 'post');
            $publishedAt = $this->resolvePublishedAt($data['published_at'] ?? null, isCreate: true);
            $status = $this->resolveInitialStatus($data['status'] ?? ContentStatus::Draft, $actor);

            $slug = $this->slugs->resolve([
                'title' => $title,
                'slug' => $data['slug'] ?? null,
                'scope' => ContentSlugScope::Posts,
                'has_been_published' => false,
                'confirm_slug_change' => false,
                'accept_conflict_resolution' => (bool) ($data['accept_conflict_resolution'] ?? false),
            ]);

            $body = $this->nullableString($data['body'] ?? null);
            $excerpt = $this->resolveExcerpt($data['excerpt'] ?? null, $body);

            $post = Post::query()->create([
                'title' => mb_substr($title, 0, 255),
                'slug' => $slug,
                'body' => $body,
                'excerpt' => $excerpt,
                'author_id' => $authorId,
                'featured_image_id' => $this->resolveFeaturedImageId($data['featured_image_id'] ?? null),
                'post_type' => $postType,
                'status' => $status,
                'visibility' => $visibility,
                'password' => $password,
                'published_at' => $publishedAt,
            ]);

            $this->syncTaxonomies($post, $data);

            return $post->fresh(['author', 'featuredImage', 'categories', 'tags', 'customTaxonomyTerms']) ?? $post;
        });
    }

    /**
     * @param  array{
     *     title?: string,
     *     slug?: string|null,
     *     body?: string|null,
     *     excerpt?: string|null,
     *     author_id?: int|null,
     *     featured_image_id?: int|null,
     *     post_type?: string,
     *     status?: string|ContentStatus,
     *     visibility?: string|PostVisibility,
     *     password?: string|null,
     *     published_at?: mixed,
     *     confirm_slug_change?: bool,
     *     accept_conflict_resolution?: bool,
     *     category_ids?: list<int|string>,
     *     tag_ids?: list<int|string>,
     *     custom_term_ids?: list<int|string>
     * }  $data
     */
    public function update(Post $post, array $data, User $actor): Post
    {
        return DB::transaction(function () use ($post, $data, $actor): Post {
            if ($post->trashed()) {
                throw ValidationException::withMessages([
                    'status' => 'Trashed posts cannot be edited. Restore the post first.',
                ]);
            }

            $title = array_key_exists('title', $data)
                ? trim((string) $data['title'])
                : $post->title;

            if ($title === '') {
                throw ValidationException::withMessages([
                    'title' => 'A title is required.',
                ]);
            }

            $authorId = array_key_exists('author_id', $data)
                ? $this->resolveAuthorId($data['author_id'], $actor, isCreate: false, post: $post)
                : $post->author_id;

            $visibility = array_key_exists('visibility', $data)
                ? $this->resolveVisibility($data['visibility'])
                : $post->visibility;

            $password = array_key_exists('password', $data) || array_key_exists('visibility', $data)
                ? $this->resolvePassword(
                    $visibility,
                    $data['password'] ?? null,
                    existingHash: $post->password,
                    passwordProvided: array_key_exists('password', $data),
                )
                : $post->password;

            $postType = array_key_exists('post_type', $data)
                ? $this->resolvePostType((string) $data['post_type'])
                : $post->post_type;

            $publishedAt = array_key_exists('published_at', $data)
                ? $this->resolvePublishedAt($data['published_at'], isCreate: false, fallback: $post->published_at)
                : $post->published_at;

            $slug = $this->slugs->resolve([
                'title' => $title,
                'slug' => array_key_exists('slug', $data) ? $data['slug'] : $post->slug,
                'scope' => ContentSlugScope::Posts,
                'ignore_id' => $post->id,
                'current_slug' => $post->slug,
                'has_been_published' => $post->hasBeenPublished(),
                'confirm_slug_change' => (bool) ($data['confirm_slug_change'] ?? false),
                'accept_conflict_resolution' => (bool) ($data['accept_conflict_resolution'] ?? false),
            ]);

            $body = array_key_exists('body', $data)
                ? $this->nullableString($data['body'])
                : $post->body;

            $excerpt = array_key_exists('excerpt', $data)
                ? $this->resolveExcerpt($data['excerpt'], $body)
                : ($post->excerpt ?: $this->autoExcerpt($body));

            $post->fill([
                'title' => mb_substr($title, 0, 255),
                'slug' => $slug,
                'body' => $body,
                'excerpt' => $excerpt,
                'author_id' => $authorId,
                'featured_image_id' => array_key_exists('featured_image_id', $data)
                    ? $this->resolveFeaturedImageId($data['featured_image_id'])
                    : $post->featured_image_id,
                'post_type' => $postType,
                'visibility' => $visibility,
                'password' => $password,
                'published_at' => $publishedAt,
            ])->save();

            if (array_key_exists('status', $data) && $data['status'] !== null) {
                $this->applyStatusChange($post->fresh() ?? $post, $data['status'], $actor);
            }

            $post = $post->fresh() ?? $post;
            $this->syncTaxonomies($post, $data, onlyProvided: true);

            return $post->fresh(['author', 'featuredImage', 'categories', 'tags', 'customTaxonomyTerms']) ?? $post;
        });
    }

    public function effectiveExcerpt(Post $post): string
    {
        if (filled($post->excerpt)) {
            return (string) $post->excerpt;
        }

        return $this->autoExcerpt($post->body) ?? '';
    }

    /**
     * Published + public visibility + publish date reached.
     */
    public function isPubliclyAccessible(Post $post): bool
    {
        if ($post->trashed()) {
            return false;
        }

        if ($post->contentStatus() !== ContentStatus::Published) {
            return false;
        }

        if ($post->visibility !== PostVisibility::Public) {
            return false;
        }

        if ($post->published_at !== null && $post->published_at->isFuture()) {
            return false;
        }

        return true;
    }

    /**
     * Duplicate a post as Draft with "(Copy)" title (SRS 12.2.15).
     * SEO metadata is copied when the SEO module is present (Phase 6).
     */
    public function duplicate(Post $source, User $actor): Post
    {
        if ($source->trashed()) {
            throw ValidationException::withMessages([
                'status' => 'Trashed posts cannot be duplicated. Restore the post first.',
            ]);
        }

        if (! $actor->can(\App\Enums\Permission::PostsDuplicate->value)
            || ! $actor->can(\App\Enums\Permission::PostsCreate->value)) {
            throw ValidationException::withMessages([
                'status' => 'You do not have permission to duplicate this post.',
            ]);
        }

        return DB::transaction(function () use ($source, $actor): Post {
            $source->loadMissing(['categories', 'tags', 'customTaxonomyTerms']);

            $title = mb_substr(rtrim($source->title).' (Copy)', 0, 255);
            $visibility = $source->visibility ?? PostVisibility::Public;
            $tempPassword = $visibility === PostVisibility::PasswordProtected
                ? Str::random(32)
                : null;

            $copy = $this->create([
                'title' => $title,
                'body' => $source->body,
                'excerpt' => $source->excerpt,
                'author_id' => $source->author_id,
                'featured_image_id' => $source->featured_image_id,
                'post_type' => $source->post_type,
                'status' => ContentStatus::Draft->value,
                'visibility' => $visibility->value,
                'password' => $tempPassword,
                'published_at' => now(),
                'category_ids' => $source->categories->modelKeys(),
                'tag_ids' => $source->tags->modelKeys(),
                'custom_term_ids' => $source->customTaxonomyTerms->modelKeys(),
            ], $actor);

            if ($visibility === PostVisibility::PasswordProtected && filled($source->password)) {
                $copy->forceFill(['password' => $source->password])->save();
            }

            return $copy->fresh(['author', 'featuredImage', 'categories', 'tags', 'customTaxonomyTerms']) ?? $copy;
        });
    }

    public function changeStatus(Post $post, ContentStatus|string $status, User $actor): Post
    {
        if ($post->trashed()) {
            throw ValidationException::withMessages([
                'status' => 'Trashed posts cannot change status. Restore the post first.',
            ]);
        }

        $target = $status instanceof ContentStatus
            ? $status
            : ContentStatus::tryFrom((string) $status);

        if ($target === null) {
            throw ValidationException::withMessages([
                'status' => 'Invalid status.',
            ]);
        }

        if ($post->contentStatus() === $target) {
            return $post;
        }

        if ($post->contentStatus() === ContentStatus::Archived) {
            if ($target === ContentStatus::Draft || $target === ContentStatus::Published) {
                $this->lifecycle->restore($post, $actor, $target);

                return $post->fresh() ?? $post;
            }

            if ($target === ContentStatus::PendingReview) {
                $this->lifecycle->restore($post, $actor, ContentStatus::Draft);
                $this->lifecycle->submitForReview($post->fresh() ?? $post);

                return $post->fresh() ?? $post;
            }
        }

        if ($post->contentStatus() === ContentStatus::Published && $target === ContentStatus::PendingReview) {
            $this->lifecycle->unpublish($post, ContentStatus::Draft);
            $this->lifecycle->submitForReview($post->fresh() ?? $post);

            return $post->fresh() ?? $post;
        }

        $this->applyStatusChange($post, $target, $actor);

        return $post->fresh() ?? $post;
    }

    /**
     * @param  iterable<int, Post>  $posts
     * @return array{success: int, failed: int}
     */
    public function bulkChangeStatus(iterable $posts, ContentStatus|string $status, User $actor): array
    {
        $success = 0;
        $failed = 0;

        foreach ($posts as $post) {
            if ($post->trashed() || ! $actor->can('update', $post)) {
                $failed++;

                continue;
            }

            try {
                $this->changeStatus($post, $status, $actor);
                $success++;
            } catch (ValidationException) {
                $failed++;
            }
        }

        return ['success' => $success, 'failed' => $failed];
    }

    /**
     * @param  iterable<int, Post>  $posts
     * @return array{success: int, failed: int}
     */
    public function bulkChangeAuthor(iterable $posts, int $authorId, User $actor): array
    {
        if (! $actor->can(\App\Enums\Permission::PostsEditOthers->value)) {
            throw ValidationException::withMessages([
                'author_id' => 'Only Editors and Administrators may bulk-change authors.',
            ]);
        }

        $authorId = $this->assertActiveAuthor($authorId);
        $success = 0;
        $failed = 0;

        foreach ($posts as $post) {
            if ($post->trashed() || ! $actor->can('update', $post)) {
                $failed++;

                continue;
            }

            $post->forceFill(['author_id' => $authorId])->save();
            $success++;
        }

        return ['success' => $success, 'failed' => $failed];
    }

    /**
     * @param  iterable<int, Post>  $posts
     * @param  list<int|string>  $categoryIds
     * @return array{success: int, failed: int}
     */
    public function bulkAssignCategories(iterable $posts, array $categoryIds, User $actor): array
    {
        $ids = array_values(array_unique(array_map('intval', $categoryIds)));
        $success = 0;
        $failed = 0;

        foreach ($posts as $post) {
            if ($post->trashed() || ! $actor->can('update', $post)) {
                $failed++;

                continue;
            }

            try {
                $merged = array_values(array_unique([
                    ...$post->categories()->pluck('categories.id')->all(),
                    ...$ids,
                ]));
                $this->taxonomies->syncPostCategories($post, $merged);
                $success++;
            } catch (ValidationException) {
                $failed++;
            }
        }

        return ['success' => $success, 'failed' => $failed];
    }

    /**
     * @param  iterable<int, Post>  $posts
     * @param  list<int|string>  $tagIds
     * @return array{success: int, failed: int}
     */
    public function bulkAssignTags(iterable $posts, array $tagIds, User $actor): array
    {
        $ids = array_values(array_unique(array_map('intval', $tagIds)));
        $success = 0;
        $failed = 0;

        foreach ($posts as $post) {
            if ($post->trashed() || ! $actor->can('update', $post)) {
                $failed++;

                continue;
            }

            try {
                $merged = array_values(array_unique([
                    ...$post->tags()->pluck('tags.id')->all(),
                    ...$ids,
                ]));
                $this->taxonomies->syncPostTags($post, $merged);
                $success++;
            } catch (ValidationException) {
                $failed++;
            }
        }

        return ['success' => $success, 'failed' => $failed];
    }

    /**
     * @param  iterable<int, Post>  $posts
     * @return array{success: int, failed: int}
     */
    public function bulkRestore(iterable $posts, User $actor): array
    {
        $success = 0;
        $failed = 0;

        foreach ($posts as $post) {
            if (! $this->lifecycle->canRestore($post) || ! $actor->can('restore', $post)) {
                $failed++;

                continue;
            }

            try {
                $this->lifecycle->restore($post, $actor, ContentStatus::Draft);
                $success++;
            } catch (ValidationException) {
                $failed++;
            }
        }

        return ['success' => $success, 'failed' => $failed];
    }

    /**
     * @param  iterable<int, Post>  $posts
     * @return array{success: int, failed: int}
     */
    public function bulkTrash(iterable $posts, User $actor): array
    {
        $success = 0;
        $failed = 0;

        foreach ($posts as $post) {
            if (! $actor->can('delete', $post)) {
                $failed++;

                continue;
            }

            try {
                $this->lifecycle->trash($post);
                $success++;
            } catch (ValidationException) {
                $failed++;
            }
        }

        return ['success' => $success, 'failed' => $failed];
    }

    /**
     * @param  iterable<int, Post>  $posts
     * @return array{success: int, failed: int}
     */
    public function bulkForceDelete(iterable $posts, User $actor): array
    {
        $success = 0;
        $failed = 0;

        foreach ($posts as $post) {
            if (! $actor->can('forceDelete', $post)) {
                $failed++;

                continue;
            }

            try {
                $this->lifecycle->forceDelete($post, $actor);
                $success++;
            } catch (ValidationException) {
                $failed++;
            }
        }

        return ['success' => $success, 'failed' => $failed];
    }

    private function applyStatusChange(Post $post, mixed $status, User $actor): void
    {
        $target = $status instanceof ContentStatus
            ? $status
            : ContentStatus::tryFrom((string) $status);

        if ($target === null || $target === $post->contentStatus()) {
            return;
        }

        match ($target) {
            ContentStatus::Draft => $post->contentStatus() === ContentStatus::Published
                ? $this->lifecycle->unpublish($post, ContentStatus::Draft)
                : $this->lifecycle->saveAsDraft($post),
            ContentStatus::PendingReview => $this->lifecycle->submitForReview($post),
            ContentStatus::Published => $this->lifecycle->publish($post, $actor),
            ContentStatus::Archived => $this->lifecycle->archive($post),
        };
    }

    private function resolveInitialStatus(mixed $status, User $actor): ContentStatus
    {
        $resolved = $status instanceof ContentStatus
            ? $status
            : (ContentStatus::tryFrom((string) $status) ?? ContentStatus::Draft);

        if ($resolved === ContentStatus::Published && ! $actor->can(\App\Enums\Permission::PostsPublish->value)) {
            return ContentStatus::PendingReview;
        }

        return $resolved;
    }

    private function resolveAuthorId(mixed $authorId, User $actor, bool $isCreate, ?Post $post = null): int
    {
        $canReassign = $actor->can(\App\Enums\Permission::PostsEditOthers->value);

        if ($isCreate) {
            if ($authorId === null || $authorId === '' || ! $canReassign) {
                return (int) $actor->getKey();
            }

            return $this->assertActiveAuthor((int) $authorId);
        }

        /** @var Post $post */
        if (! $canReassign) {
            return (int) $post->author_id;
        }

        if ($authorId === null || $authorId === '') {
            throw ValidationException::withMessages([
                'author_id' => 'An author is required.',
            ]);
        }

        return $this->assertActiveAuthor((int) $authorId);
    }

    private function assertActiveAuthor(int $authorId): int
    {
        $user = User::query()->find($authorId);

        if ($user === null || ! $user->isActive()) {
            throw ValidationException::withMessages([
                'author_id' => 'Author must be an active user.',
            ]);
        }

        return $authorId;
    }

    private function resolveVisibility(mixed $visibility): PostVisibility
    {
        if ($visibility instanceof PostVisibility) {
            return $visibility;
        }

        return PostVisibility::tryFrom((string) $visibility) ?? PostVisibility::Public;
    }

    private function resolvePassword(
        PostVisibility $visibility,
        mixed $password,
        ?string $existingHash,
        bool $passwordProvided = true,
    ): ?string {
        if ($visibility !== PostVisibility::PasswordProtected) {
            return null;
        }

        $password = is_string($password) ? $password : null;

        if (filled($password)) {
            return Hash::make($password);
        }

        if ($existingHash !== null && $existingHash !== '') {
            return $existingHash;
        }

        if (! $passwordProvided && $existingHash) {
            return $existingHash;
        }

        throw ValidationException::withMessages([
            'password' => 'A password is required when visibility is Password Protected.',
        ]);
    }

    private function resolvePostType(string $postType): string
    {
        $postType = trim($postType) ?: 'post';

        if (! in_array($postType, PostTypeRegistry::keys(), true)) {
            throw ValidationException::withMessages([
                'post_type' => 'Unknown post type.',
            ]);
        }

        return $postType;
    }

    private function resolvePublishedAt(mixed $value, bool $isCreate, mixed $fallback = null): ?\Illuminate\Support\Carbon
    {
        if ($value === null || $value === '') {
            if ($isCreate) {
                return now();
            }

            return $fallback instanceof \Illuminate\Support\Carbon
                ? $fallback
                : ($fallback !== null ? \Illuminate\Support\Carbon::parse($fallback) : null);
        }

        return \Illuminate\Support\Carbon::parse($value);
    }

    private function resolveExcerpt(mixed $excerpt, ?string $body): ?string
    {
        $excerpt = is_string($excerpt) ? trim($excerpt) : null;

        if (filled($excerpt)) {
            return mb_substr($excerpt, 0, 500);
        }

        return $this->autoExcerpt($body);
    }

    private function autoExcerpt(?string $body): ?string
    {
        if ($body === null || trim($body) === '') {
            return null;
        }

        $plain = trim(html_entity_decode(strip_tags($body), ENT_QUOTES | ENT_HTML5, 'UTF-8'));
        $plain = preg_replace('/\s+/u', ' ', $plain) ?? $plain;

        if ($plain === '') {
            return null;
        }

        return Str::limit($plain, 160, '');
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private function syncTaxonomies(Post $post, array $data, bool $onlyProvided = false): void
    {
        if (! $onlyProvided || array_key_exists('category_ids', $data)) {
            $this->taxonomies->syncPostCategories(
                $post,
                array_values((array) ($data['category_ids'] ?? [])),
            );
        }

        if (! $onlyProvided || array_key_exists('tag_ids', $data)) {
            $this->taxonomies->syncPostTags(
                $post,
                array_values((array) ($data['tag_ids'] ?? [])),
            );
        }

        if (! $onlyProvided || array_key_exists('custom_term_ids', $data)) {
            $this->taxonomies->syncPostCustomTerms(
                $post,
                array_values((array) ($data['custom_term_ids'] ?? [])),
            );
        }
    }

    private function nullableString(mixed $value): ?string
    {
        if ($value === null) {
            return null;
        }

        $value = trim((string) $value);

        return $value === '' ? null : $value;
    }

    private function resolveFeaturedImageId(mixed $value): ?int
    {
        if ($value === null || $value === '') {
            return null;
        }

        return \App\Support\Media\MediaImageOptions::assertAssignableImage((int) $value);
    }

    private function nullableId(mixed $value): ?int
    {
        if ($value === null || $value === '') {
            return null;
        }

        return (int) $value;
    }
}
