<?php

namespace App\Filament\Resources\CustomTaxonomies\Pages;

use App\Filament\Resources\CustomTaxonomies\CustomTaxonomyResource;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;

class ViewCustomTaxonomy extends ViewRecord
{
    protected static string $resource = CustomTaxonomyResource::class;

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make(),
        ];
    }
}
