<?php

namespace App\Services;

use App\Enums\ContentSlugScope;
use App\Enums\SlugConflictResolution;
use App\Support\Settings\PermalinkSettings;
use App\Support\SlugGenerator;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Shared slug resolution for Posts & Pages (SRS 12.5.1).
 */
class ContentSlugService
{
    public function __construct(
        private readonly PermalinkSettings $permalinks,
    ) {}

    /**
     * Resolve a unique slug for content.
     *
     * @param  array{
     *     title: string,
     *     slug?: string|null,
     *     scope: ContentSlugScope,
     *     ignore_id?: int|null,
     *     current_slug?: string|null,
     *     has_been_published?: bool,
     *     confirm_slug_change?: bool,
     *     accept_conflict_resolution?: bool
     * }  $input
     */
    public function resolve(array $input): string
    {
        /** @var ContentSlugScope $scope */
        $scope = $input['scope'];
        $title = trim((string) ($input['title'] ?? ''));
        $slugInput = array_key_exists('slug', $input) ? $input['slug'] : null;
        $slugInput = is_string($slugInput) ? trim($slugInput) : null;
        if ($slugInput === '') {
            $slugInput = null;
        }

        $ignoreId = isset($input['ignore_id']) ? (int) $input['ignore_id'] : null;
        $currentSlug = isset($input['current_slug']) && is_string($input['current_slug'])
            ? $input['current_slug']
            : null;
        $hasBeenPublished = (bool) ($input['has_been_published'] ?? false);
        $confirmSlugChange = (bool) ($input['confirm_slug_change'] ?? false);
        $acceptConflictResolution = (bool) ($input['accept_conflict_resolution'] ?? false);

        $source = $this->sourceForGeneration($title, $slugInput);
        $desired = SlugGenerator::sanitize($source);

        if ($hasBeenPublished && $currentSlug !== null && $desired !== $currentSlug && ! $confirmSlugChange) {
            throw ValidationException::withMessages([
                'slug' => 'Changing the slug of published content requires confirmation to prevent broken external links.',
                'slug_change_confirmation' => 'required',
            ]);
        }

        return $this->applyConflictResolution($desired, $scope, $ignoreId, $acceptConflictResolution);
    }

    /**
     * Auto-generate from title when enabled and no manual slug was provided.
     */
    public function sourceForGeneration(string $title, ?string $slugInput): string
    {
        if (filled($slugInput)) {
            return (string) $slugInput;
        }

        if ($this->permalinks->autoGenerateSlugs()) {
            if ($title === '') {
                throw ValidationException::withMessages([
                    'title' => 'A title is required to auto-generate a slug.',
                ]);
            }

            return $title;
        }

        throw ValidationException::withMessages([
            'slug' => 'A slug is required when auto-generation is disabled.',
        ]);
    }

    public function isAvailable(string $slug, ContentSlugScope $scope, ?int $ignoreId = null): bool
    {
        return ! $this->exists(SlugGenerator::sanitize($slug), $scope, $ignoreId);
    }

    private function applyConflictResolution(
        string $desired,
        ContentSlugScope $scope,
        ?int $ignoreId,
        bool $acceptConflictResolution,
    ): string {
        if (! $this->exists($desired, $scope, $ignoreId)) {
            return $desired;
        }

        return match ($this->permalinks->conflictResolution()) {
            SlugConflictResolution::AppendNumber => $this->uniqueWithSuffix($desired, $scope, $ignoreId),
            SlugConflictResolution::BlockSave => throw ValidationException::withMessages([
                'slug' => sprintf(
                    'The slug “%s” is already used by another %s item.',
                    $desired,
                    $scope->label(),
                ),
            ]),
            SlugConflictResolution::PromptUser => $this->promptConflict(
                $desired,
                $scope,
                $ignoreId,
                $acceptConflictResolution,
            ),
        };
    }

    private function promptConflict(
        string $desired,
        ContentSlugScope $scope,
        ?int $ignoreId,
        bool $acceptConflictResolution,
    ): string {
        $suggested = $this->uniqueWithSuffix($desired, $scope, $ignoreId);

        if ($acceptConflictResolution) {
            return $suggested;
        }

        throw ValidationException::withMessages([
            'slug' => sprintf(
                'The slug “%s” is already in use. Suggested alternative: “%s”. Confirm to use the suggestion, or enter a different slug.',
                $desired,
                $suggested,
            ),
            'slug_conflict' => 'prompt',
            'slug_suggestion' => $suggested,
        ]);
    }

    private function uniqueWithSuffix(string $desired, ContentSlugScope $scope, ?int $ignoreId): string
    {
        $base = $desired;
        $candidate = $base;
        $suffix = 2;

        while ($this->exists($candidate, $scope, $ignoreId)) {
            $candidate = $base.'-'.$suffix;
            $suffix++;
        }

        return $candidate;
    }

    private function exists(string $slug, ContentSlugScope $scope, ?int $ignoreId): bool
    {
        $query = DB::table($scope->table())
            ->where('slug', $slug)
            ->whereNull('deleted_at');

        if ($ignoreId !== null) {
            $query->where('id', '!=', $ignoreId);
        }

        return $query->exists();
    }
}
