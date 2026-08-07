<?php

namespace App\Filament\Resources\CustomTaxonomies\Pages;

use App\Filament\Resources\CustomTaxonomies\CustomTaxonomyResource;
use App\Models\CustomTaxonomy;
use App\Services\CustomTaxonomyService;
use Filament\Actions\DeleteAction;
use Filament\Actions\ViewAction;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Database\Eloquent\Model;

class EditCustomTaxonomy extends EditRecord
{
    protected static string $resource = CustomTaxonomyResource::class;

    protected function getHeaderActions(): array
    {
        return [
            ViewAction::make(),
            DeleteAction::make()
                ->using(fn (CustomTaxonomy $record) => app(CustomTaxonomyService::class)->delete($record)),
        ];
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function mutateFormDataBeforeFill(array $data): array
    {
        /** @var CustomTaxonomy $record */
        $record = $this->getRecord();
        $data['post_type_keys'] = $record->postTypeKeys();
        $data['structure_type'] = $record->structure_type->value;

        return $data;
    }

    /**
     * @param  array<string, mixed>  $data
     */
    protected function handleRecordUpdate(Model $record, array $data): Model
    {
        /** @var CustomTaxonomy $record */
        return app(CustomTaxonomyService::class)->update($record, [
            'name' => $data['name'],
            'slug' => $data['slug'] ?? null,
            'post_type_keys' => $data['post_type_keys'] ?? [],
        ]);
    }
}
