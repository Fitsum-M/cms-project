<?php

namespace Database\Factories;

use App\Models\PostType;
use App\Support\SlugGenerator;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<PostType>
 */
class PostTypeFactory extends Factory
{
    protected $model = PostType::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $singular = fake()->unique()->words(2, true);
        $plural = $singular.'s';

        return [
            'plural_name' => ucwords($plural),
            'singular_name' => ucwords($singular),
            'slug' => SlugGenerator::sanitize($plural).'-'.fake()->unique()->numerify('###'),
            'icon' => 'heroicon-o-document-text',
            'supports_categories' => true,
            'supports_tags' => true,
            'sort_order' => 0,
        ];
    }
}
