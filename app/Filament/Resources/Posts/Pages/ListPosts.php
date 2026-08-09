<?php

namespace App\Filament\Resources\Posts\Pages;

use App\Filament\Resources\Posts\PostResource;
use App\Support\PostTypeRegistry;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListPosts extends ListRecords
{
    protected static string $resource = PostResource::class;

    public function mount(): void
    {
        parent::mount();

        $postType = request()->query('post_type');

        if (! is_string($postType) || $postType === '' || ! PostTypeRegistry::isRegistered($postType)) {
            return;
        }

        $this->tableFilters = [
            ...(is_array($this->tableFilters) ? $this->tableFilters : []),
            'post_type' => ['value' => $postType],
        ];
    }

    public function getTitle(): string|\Illuminate\Contracts\Support\Htmlable
    {
        $postType = request()->query('post_type');

        if (is_string($postType) && PostTypeRegistry::isCustom($postType)) {
            return PostTypeRegistry::label($postType);
        }

        return parent::getTitle();
    }

    protected function getHeaderActions(): array
    {
        $postType = request()->query('post_type');
        $label = 'Add New Post';

        if (is_string($postType) && PostTypeRegistry::isCustom($postType)) {
            $label = 'Add New '.PostTypeRegistry::singularLabel($postType);
        }

        return [
            CreateAction::make()
                ->label($label)
                ->url(fn (): string => PostResource::getUrl('create', array_filter([
                    'post_type' => is_string($postType) && PostTypeRegistry::isCustom($postType) ? $postType : null,
                ]))),
        ];
    }
}
