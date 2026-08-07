<?php

namespace App\Filament\Resources\CustomTaxonomies\Pages;

use App\Filament\Resources\CustomTaxonomies\CustomTaxonomyResource;
use App\Services\CustomTaxonomyService;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;

class CreateCustomTaxonomy extends CreateRecord
{
    protected static string $resource = CustomTaxonomyResource::class;

    /**
     * @param  array<string, mixed>  $data
     */
    protected function handleRecordCreation(array $data): Model
    {
        return app(CustomTaxonomyService::class)->create([
            'name' => $data['name'],
            'slug' => $data['slug'] ?? null,
            'structure_type' => $data['structure_type'],
            'post_type_keys' => $data['post_type_keys'] ?? [],
        ]);
    }
}
