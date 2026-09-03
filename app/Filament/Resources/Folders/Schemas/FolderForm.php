<?php

namespace App\Filament\Resources\Folders\Schemas;

use App\Models\Folder;
use App\Services\FolderService;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Illuminate\Support\HtmlString;

class FolderForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->columns(1)
            ->components([
                Section::make('Folder details')
                    ->description('Folders organize the media library. Names must be unique among siblings under the same parent.')
                    ->schema([
                        TextInput::make('name')
                            ->label('Name')
                            ->required()
                            ->maxLength(255)
                            ->autocomplete(false)
                            ->live(onBlur: true)
                            ->helperText('Displayed in the folder tree and media library filters.'),
                        Select::make('parent_id')
                            ->label('Parent folder')
                            ->options(function (?Folder $record): array {
                                return app(FolderService::class)->parentOptions(
                                    $record?->exists ? $record->getKey() : null,
                                );
                            })
                            ->searchable()
                            ->native(false)
                            ->nullable()
                            ->live()
                            ->placeholder('— Root level —')
                            ->helperText('Leave empty for a top-level folder. A folder cannot be nested under itself or its descendants.'),
                        Placeholder::make('path_preview')
                            ->label('Path preview')
                            ->content(function (Get $get, ?Folder $record): HtmlString {
                                $name = trim((string) ($get('name') ?: $record?->name ?: 'Untitled'));
                                $parentId = $get('parent_id');
                                $parentId = filled($parentId) ? (int) $parentId : null;

                                $path = self::previewPath($name, $parentId);

                                return new HtmlString(
                                    '<span class="text-sm text-gray-600 dark:text-gray-300">'.$path.'</span>'
                                );
                            }),
                    ])
                    ->columns(1),
            ]);
    }

    private static function previewPath(string $name, ?int $parentId): string
    {
        if ($parentId === null) {
            return e($name);
        }

        $parent = Folder::query()->find($parentId);

        if ($parent === null) {
            return e($name);
        }

        $label = app(FolderService::class)->hierarchicalLabel($parent);

        return e($label.' / '.$name);
    }
}
