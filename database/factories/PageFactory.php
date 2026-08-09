<?php

namespace Database\Factories;

use App\Enums\ContentStatus;
use App\Models\Page;
use App\Models\User;
use App\Support\SlugGenerator;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Page>
 */
class PageFactory extends Factory
{
    protected $model = Page::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $title = fake()->unique()->words(2, true);

        return [
            'title' => ucwords($title),
            'slug' => SlugGenerator::sanitize($title).'-'.fake()->unique()->numerify('###'),
            'body' => fake()->optional()->paragraphs(2, true),
            'author_id' => User::factory(),
            'parent_id' => null,
            'sort_order' => 0,
            'template' => null,
            'show_in_navigation' => false,
            'status' => ContentStatus::Draft,
            'published_at' => null,
        ];
    }

    public function childOf(Page $parent): static
    {
        return $this->state(fn (): array => [
            'parent_id' => $parent->id,
            'sort_order' => (int) Page::query()->where('parent_id', $parent->id)->max('sort_order') + 1,
        ]);
    }

    public function inNavigation(): static
    {
        return $this->state(fn (): array => [
            'show_in_navigation' => true,
        ]);
    }

    public function withTemplate(string $template): static
    {
        return $this->state(fn (): array => [
            'template' => $template,
        ]);
    }

    public function published(): static
    {
        return $this->state(fn (): array => [
            'status' => ContentStatus::Published,
            'published_at' => now()->subDay(),
        ]);
    }

    public function archived(): static
    {
        return $this->state(fn (): array => [
            'status' => ContentStatus::Archived,
        ]);
    }
}
