<?php

namespace App\Services;

use App\Models\Tag;
use App\Support\Settings\PermalinkSettings;
use App\Support\SlugGenerator;
use Illuminate\Validation\ValidationException;
use RuntimeException;

class TagService
{
    public function __construct(
        private readonly PermalinkSettings $permalinks,
    ) {}

    /**
     * @param  array{name: string, slug?: string|null, description?: string|null}  $data
     */
    public function create(array $data): Tag
    {
        $name = trim($data['name']);

        if ($name === '') {
            throw ValidationException::withMessages([
                'name' => 'Tag name is required.',
            ]);
        }

        if (Tag::findByNameInsensitive($name) !== null) {
            throw ValidationException::withMessages([
                'name' => 'A tag with this name already exists (names are compared case-insensitively).',
            ]);
        }

        $slug = $this->resolveSlug($name, $data['slug'] ?? null);

        return Tag::query()->create([
            'name' => $name,
            'slug' => $slug,
            'description' => $data['description'] ?? null,
        ]);
    }

    /**
     * @param  array{name?: string, slug?: string|null, description?: string|null}  $data
     */
    public function update(Tag $tag, array $data): Tag
    {
        $name = array_key_exists('name', $data) ? trim((string) $data['name']) : $tag->name;

        if ($name === '') {
            throw ValidationException::withMessages([
                'name' => 'Tag name is required.',
            ]);
        }

        $duplicate = Tag::findByNameInsensitive($name);
        if ($duplicate !== null && $duplicate->id !== $tag->id) {
            throw ValidationException::withMessages([
                'name' => 'A tag with this name already exists (names are compared case-insensitively).',
            ]);
        }

        $slugInput = array_key_exists('slug', $data) ? $data['slug'] : $tag->slug;
        $slug = $this->resolveSlug($name, $slugInput, $tag->id);

        $tag->fill([
            'name' => $name,
            'slug' => $slug,
            'description' => array_key_exists('description', $data)
                ? $data['description']
                : $tag->description,
        ])->save();

        return $tag->refresh();
    }

    /**
     * Inline auto-create for post editors (SRS 13.2.2).
     * Returns the existing tag when the name matches case-insensitively.
     */
    public function findOrCreateByName(string $name): Tag
    {
        $name = trim($name);

        if ($name === '') {
            throw ValidationException::withMessages([
                'name' => 'Tag name is required.',
            ]);
        }

        $existing = Tag::findByNameInsensitive($name);
        if ($existing !== null) {
            return $existing;
        }

        return $this->create([
            'name' => $name,
            'slug' => SlugGenerator::sanitize($name),
        ]);
    }

    public function delete(Tag $tag): void
    {
        app(TaxonomyDeletionGuard::class)->assertDeletable($tag);

        if (! $tag->delete()) {
            throw new RuntimeException('Failed to delete tag.');
        }
    }

    private function resolveSlug(string $name, ?string $slugInput, ?int $ignoreId = null): string
    {
        $source = filled($slugInput)
            ? (string) $slugInput
            : ($this->permalinks->autoGenerateSlugs() ? $name : '');

        if ($source === '') {
            throw ValidationException::withMessages([
                'slug' => 'A slug is required when auto-generation is disabled.',
            ]);
        }

        return SlugGenerator::unique($source, 'tags', 'slug', $ignoreId);
    }
}
