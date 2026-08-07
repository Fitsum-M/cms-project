<?php

namespace Database\Factories;

use App\Models\MediaAsset;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<MediaAsset>
 */
class MediaAssetFactory extends Factory
{
    protected $model = MediaAsset::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $fileName = fake()->unique()->slug().'.jpg';

        return [
            'title' => pathinfo($fileName, PATHINFO_FILENAME),
            'alt_text' => null,
            'caption' => null,
            'description' => null,
            'folder_id' => null,
            'uploaded_by' => User::factory(),
            'original_file_name' => $fileName,
            'mime_type' => 'image/jpeg',
            'size' => fake()->numberBetween(1_024, 2_048_000),
            'width' => 800,
            'height' => 600,
        ];
    }
}
