<?php

namespace App\Filament\Resources\Pages\Schemas;

use App\Enums\ContentStatus;
use App\Enums\Permission;
use App\Enums\UserStatus;
use App\Models\Page;
use App\Models\User;
use App\Services\PageService;
use App\Support\Settings\PermalinkSettings;
use App\Support\SlugGenerator;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;

class PageForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->columns(1)
            ->components([
                Section::make('Content')
                    ->schema([
                        TextInput::make('title')
                            ->label('Title')
                            ->required()
                            ->maxLength(255)
                            ->live(onBlur: true)
                            ->afterStateUpdated(function (?string $state, Set $set, Get $get, ?Page $record): void {
                                if (! app(PermalinkSettings::class)->autoGenerateSlugs()) {
                                    return;
                                }

                                if ($record?->hasBeenPublished()) {
                                    return;
                                }

                                if (filled($get('slug'))) {
                                    return;
                                }

                                if (blank($state)) {
                                    return;
                                }

                                $set('slug', SlugGenerator::sanitize($state));
                            }),
                        TextInput::make('slug')
                            ->label('Slug')
                            ->maxLength(255)
                            ->helperText('URL-friendly identifier. Auto-generated from title when enabled in Permalinks.')
                            ->dehydrateStateUsing(fn (?string $state): ?string => filled($state)
                                ? SlugGenerator::sanitize($state)
                                : $state),
                        Toggle::make('confirm_slug_change')
                            ->label('Confirm slug change')
                            ->helperText('Required when changing the slug of a page that has been published.')
                            ->visible(fn (?Page $record): bool => (bool) $record?->hasBeenPublished())
                            ->dehydrated(),
                        RichEditor::make('body')
                            ->label('Content')
                            ->columnSpanFull(),
                    ])
                    ->columns(2),
                Section::make('Hierarchy')
                    ->schema([
                        Select::make('parent_id')
                            ->label('Parent')
                            ->options(fn (?Page $record): array => app(PageService::class)->parentOptions($record?->id))
                            ->searchable()
                            ->nullable()
                            ->placeholder('— Top level —')
                            ->helperText('A page cannot be nested under itself or one of its descendants.'),
                    ]),
                Section::make('Presentation')
                    ->schema([
                        Select::make('template')
                            ->label('Template')
                            ->options(fn (): array => \App\Support\PageTemplateRegistry::options())
                            ->default(\App\Support\PageTemplateRegistry::defaultKey())
                            ->nullable()
                            ->helperText('Frontend presentation variant. Default is used when left empty.'),
                        Toggle::make('show_in_navigation')
                            ->label('Show in Navigation')
                            ->helperText('Signals inclusion intent for frontend menus. Does not affect URL accessibility.')
                            ->default(false),
                    ])
                    ->columns(2),
                Section::make('Settings')
                    ->schema([
                        Select::make('status')
                            ->label('Status')
                            ->options(ContentStatus::options())
                            ->required()
                            ->default(ContentStatus::Draft->value),
                        Select::make('author_id')
                            ->label('Author')
                            ->options(fn (): array => User::query()
                                ->where('status', UserStatus::Active)
                                ->orderBy('name')
                                ->pluck('name', 'id')
                                ->all())
                            ->searchable()
                            ->required()
                            ->default(fn (): ?int => auth()->id())
                            ->disabled(fn (): bool => ! (auth()->user()?->can(Permission::PagesEditOthers->value) ?? false))
                            ->dehydrated(),
                        DateTimePicker::make('published_at')
                            ->label('Publish Date')
                            ->seconds(false)
                            ->default(now())
                            ->helperText('Used for published pages and URL date tokens when configured.'),
                    ])
                    ->columns(2),
                ...\App\Filament\Forms\Components\SeoPanel::make('page'),
            ]);
    }
}
