<?php

namespace App\Filament\Resources\Folders\Schemas;

use App\Models\Folder;
use App\Services\FolderService;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class FolderForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Folder')
                    ->description('Name and optional parent. Moving under a descendant is blocked.')
                    ->schema([
                        TextInput::make('name')
                            ->label('Name')
                            ->required()
                            ->maxLength(255)
                            ->autocomplete(false),
                        Select::make('parent_id')
                            ->label('Parent folder')
                            ->options(fn (?Folder $record): array => app(FolderService::class)
                                ->parentOptions($record?->getKey()))
                            ->searchable()
                            ->nullable()
                            ->placeholder('— Root level —')
                            ->helperText('Leave empty for a root-level folder.'),
                    ]),
            ]);
    }
}
