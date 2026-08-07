<?php

namespace Database\Factories;

use App\Models\CustomTaxonomy;
use App\Models\CustomTaxonomyTerm;
use App\Support\SlugGenerator;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<CustomTaxonomyTerm>
 */
class CustomTaxonomyTermFactory extends Factory
{
    protected $model = CustomTaxonomyTerm::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $name = fake()->unique()->words(2, true);

        return [
            'custom_taxonomy_id' => CustomTaxonomy::factory(),
            'name' => ucwords($name),
            'slug' => SlugGenerator::sanitize($name).'-'.fake()->unique()->numerify('###'),
            'parent_id' => null,
            'description' => fake()->optional()->sentence(),
        ];
    }

    public function forTaxonomy(CustomTaxonomy $taxonomy): static
    {
        return $this->state(fn (): array => [
            'custom_taxonomy_id' => $taxonomy->id,
        ]);
    }
}
