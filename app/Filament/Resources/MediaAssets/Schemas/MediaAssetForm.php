<?php

namespace App\Filament\Resources\MediaAssets\Schemas;

use App\Services\FolderService;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class MediaAssetForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->columns(1)
            ->components([
                Section::make('Metadata')
                    ->description('Editable identification fields. File name, type, size, and dimensions are set at upload and stay read-only.')
                    ->schema([
                        TextInput::make('title')
                            ->label('Title')
                            ->required()
                            ->maxLength(255)
                            ->helperText('Used for internal identification and library search.'),
                        TextInput::make('alt_text')
                            ->label('Alt Text')
                            ->maxLength(255)
                            ->helperText('Required when inserting an image into content (enforced at insertion, not here).'),
                        Select::make('folder_id')
                            ->label('Folder')
                            ->options(fn (): array => app(FolderService::class)->options())
                            ->searchable()
                            ->nullable()
                            ->placeholder('— Unfiled —')
                            ->helperText('Organize this item in a folder. Moving folders does not break media references.'),
                        Textarea::make('caption')
                            ->label('Caption')
                            ->rows(2)
                            ->maxLength(500)
                            ->columnSpanFull(),
                        Textarea::make('description')
                            ->label('Description')
                            ->rows(4)
                            ->maxLength(2000)
                            ->helperText('Internal reference only; not rendered on the frontend by default.')
                            ->columnSpanFull(),
                    ])
                    ->columns(2),
            ]);
    }
}
