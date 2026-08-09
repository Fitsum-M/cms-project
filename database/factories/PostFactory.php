<?php

namespace Database\Factories;

use App\Enums\ContentStatus;
use App\Enums\PostVisibility;
use App\Models\Post;
use App\Models\User;
use App\Support\SlugGenerator;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Post>
 */
class PostFactory extends Factory
{
    protected $model = Post::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $title = fake()->unique()->sentence(3);

        return [
            'title' => rtrim($title, '.'),
            'slug' => SlugGenerator::sanitize($title).'-'.fake()->unique()->numerify('###'),
            'body' => fake()->optional()->paragraphs(2, true),
            'excerpt' => null,
            'author_id' => User::factory(),
            'featured_image_id' => null,
            'post_type' => 'post',
            'status' => ContentStatus::Draft,
            'visibility' => PostVisibility::Public,
            'password' => null,
            'published_at' => now(),
        ];
    }

    public function published(): static
    {
        return $this->state(fn (): array => [
            'status' => ContentStatus::Published,
            'published_at' => now()->subDay(),
        ]);
    }

    public function pending(): static
    {
        return $this->state(fn (): array => [
            'status' => ContentStatus::PendingReview,
        ]);
    }

    public function archived(): static
    {
        return $this->state(fn (): array => [
            'status' => ContentStatus::Archived,
        ]);
    }
}
