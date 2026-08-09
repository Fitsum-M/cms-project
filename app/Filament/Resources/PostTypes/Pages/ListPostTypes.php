<?php

namespace App\Filament\Resources\PostTypes\Pages;

use App\Filament\Resources\PostTypes\PostTypeResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListPostTypes extends ListRecords
{
    protected static string $resource = PostTypeResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()
                ->label('Add Post Type'),
        ];
    }
}
