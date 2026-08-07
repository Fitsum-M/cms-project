<?php

namespace Database\Factories;

use App\Enums\TaxonomyStructure;
use App\Models\CustomTaxonomy;
use App\Support\SlugGenerator;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<CustomTaxonomy>
 */
class CustomTaxonomyFactory extends Factory
{
    protected $model = CustomTaxonomy::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $name = fake()->unique()->words(2, true);

        return [
            'name' => ucwords($name),
            'slug' => SlugGenerator::sanitize($name).'-'.fake()->unique()->numerify('###'),
            'structure_type' => TaxonomyStructure::Flat,
        ];
    }

    public function hierarchical(): static
    {
        return $this->state(fn (): array => [
            'structure_type' => TaxonomyStructure::Hierarchical,
        ]);
    }

    public function flat(): static
    {
        return $this->state(fn (): array => [
            'structure_type' => TaxonomyStructure::Flat,
        ]);
    }
}
