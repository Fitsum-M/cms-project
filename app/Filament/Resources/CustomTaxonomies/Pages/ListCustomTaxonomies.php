<?php

namespace App\Filament\Resources\CustomTaxonomies\Pages;

use App\Filament\Resources\CustomTaxonomies\CustomTaxonomyResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListCustomTaxonomies extends ListRecords
{
    protected static string $resource = CustomTaxonomyResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
