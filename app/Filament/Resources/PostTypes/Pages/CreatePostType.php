<?php

namespace App\Filament\Resources\PostTypes\Pages;

use App\Filament\Resources\PostTypes\PostTypeResource;
use App\Services\PostTypeService;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;

class CreatePostType extends CreateRecord
{
    protected static string $resource = PostTypeResource::class;

    /**
     * @param  array<string, mixed>  $data
     */
    protected function handleRecordCreation(array $data): Model
    {
        return app(PostTypeService::class)->create($data);
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('edit', ['record' => $this->getRecord()]);
    }
}
