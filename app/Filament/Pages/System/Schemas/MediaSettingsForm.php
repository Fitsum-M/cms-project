<?php

namespace App\Filament\Pages\System\Schemas;

use App\Support\Settings\MediaSettings;
use Filament\Forms\Components\CheckboxList;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Illuminate\Validation\Rule;

class MediaSettingsForm
{
    /**
     * @return array<int, Section>
     */
    public static function components(): array
    {
        $foldersReady = MediaSettings::foldersTableReady();
        $folderHelper = $foldersReady
            ? 'Folder used as the default destination for new uploads.'
            : 'Create folders under Digital Asset Management → Folders to enable this setting.';

        return [
            Section::make('Image sizes')
                ->description('Max dimensions for generated thumbnail, medium, and large conversions. Originals are always preserved.')
                ->schema([
                    TextInput::make(MediaSettings::THUMBNAIL_WIDTH)
                        ->label('Thumbnail Width')
                        ->numeric()
                        ->integer()
                        ->required()
                        ->minValue(1)
                        ->maxValue(10000)
                        ->suffix('px'),
                    TextInput::make(MediaSettings::THUMBNAIL_HEIGHT)
                        ->label('Thumbnail Height')
                        ->numeric()
                        ->integer()
                        ->required()
                        ->minValue(1)
                        ->maxValue(10000)
                        ->suffix('px'),
                    TextInput::make(MediaSettings::MEDIUM_WIDTH)
                        ->label('Medium Width')
                        ->numeric()
                        ->integer()
                        ->required()
                        ->minValue(1)
                        ->maxValue(10000)
                        ->suffix('px'),
                    TextInput::make(MediaSettings::MEDIUM_HEIGHT)
                        ->label('Medium Height')
                        ->numeric()
                        ->integer()
                        ->required()
                        ->minValue(1)
                        ->maxValue(10000)
                        ->suffix('px'),
                    TextInput::make(MediaSettings::LARGE_WIDTH)
                        ->label('Large Width')
                        ->numeric()
                        ->integer()
                        ->required()
                        ->minValue(1)
                        ->maxValue(10000)
                        ->suffix('px'),
                    TextInput::make(MediaSettings::LARGE_HEIGHT)
                        ->label('Large Height')
                        ->numeric()
                        ->integer()
                        ->required()
                        ->minValue(1)
                        ->maxValue(10000)
                        ->suffix('px'),
                ])
                ->columns(2),
            Section::make('Uploads')
                ->schema([
                    TextInput::make(MediaSettings::UPLOAD_MAX_FILE_SIZE_MB)
                        ->label('Upload Max File Size')
                        ->numeric()
                        ->integer()
                        ->required()
                        ->minValue(1)
                        ->maxValue(1024)
                        ->suffix('MB'),
                    Select::make(MediaSettings::DEFAULT_UPLOAD_FOLDER_ID)
                        ->label('Default Upload Folder')
                        ->options(fn (): array => MediaSettings::folderOptions())
                        ->searchable()
                        ->nullable()
                        ->placeholder('— Library root —')
                        ->helperText($folderHelper)
                        ->rules(self::folderReferenceRules()),
                    CheckboxList::make(MediaSettings::ALLOWED_FILE_TYPES)
                        ->label('Allowed File Types')
                        ->options(MediaSettings::fileTypeOptions())
                        ->required()
                        ->columns(2)
                        ->bulkToggleable()
                        ->helperText('Permitted extensions from SRS 14.2. At least one type must remain selected.'),
                ])
                ->columns(1),
        ];
    }

    /**
     * @return list<\Illuminate\Contracts\Validation\ValidationRule|string>
     */
    public static function folderReferenceRules(): array
    {
        if (! MediaSettings::foldersTableReady()) {
            return ['nullable'];
        }

        return [
            'nullable',
            'integer',
            Rule::exists('folders', 'id'),
        ];
    }
}
