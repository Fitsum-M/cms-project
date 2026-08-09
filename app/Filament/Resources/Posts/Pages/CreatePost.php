<?php

namespace App\Filament\Resources\Posts\Pages;

use App\Filament\Resources\Posts\PostResource;
use App\Services\PostService;
use App\Support\PostTypeRegistry;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;

class CreatePost extends CreateRecord
{
    protected static string $resource = PostResource::class;

    public function mount(): void
    {
        parent::mount();

        $postType = request()->query('post_type');

        if (is_string($postType) && PostTypeRegistry::isRegistered($postType)) {
            $this->form->fill([
                ...$this->form->getState(),
                'post_type' => $postType,
            ]);
        }
    }

    public function getTitle(): string|\Illuminate\Contracts\Support\Htmlable
    {
        $postType = $this->data['post_type'] ?? request()->query('post_type');

        if (is_string($postType) && PostTypeRegistry::isCustom($postType)) {
            return 'Create '.PostTypeRegistry::singularLabel($postType);
        }

        return parent::getTitle();
    }

    /**
     * @param  array<string, mixed>  $data
     */
    protected function handleRecordCreation(array $data): Model
    {
        return app(PostService::class)->create($data, auth()->user());
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('edit', ['record' => $this->getRecord()]);
    }
}
