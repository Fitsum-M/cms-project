<?php

namespace App\Filament\Resources\CustomTaxonomies\RelationManagers;

use App\Models\CustomTaxonomy;
use App\Models\CustomTaxonomyTerm;
use App\Services\CustomTaxonomyTermService;
use App\Support\Settings\PermalinkSettings;
use App\Support\SlugGenerator;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Validation\ValidationException;

class TermsRelationManager extends RelationManager
{
    protected static string $relationship = 'terms';

    protected static ?string $title = 'Terms';

    public function form(Schema $schema): Schema
    {
        /** @var CustomTaxonomy $taxonomy */
        $taxonomy = $this->getOwnerRecord();

        return $schema
            ->components([
                TextInput::make('name')
                    ->label('Name')
                    ->required()
                    ->maxLength(255)
                    ->live(onBlur: true)
                    ->afterStateUpdated(function (?string $state, Set $set, Get $get): void {
                        if (! app(PermalinkSettings::class)->autoGenerateSlugs()) {
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
                    ->required(fn (): bool => ! app(PermalinkSettings::class)->autoGenerateSlugs())
                    ->maxLength(255)
                    ->dehydrateStateUsing(fn (?string $state): ?string => filled($state) ? SlugGenerator::sanitize($state) : $state),
                Select::make('parent_id')
                    ->label('Parent Term')
                    ->options(function (?CustomTaxonomyTerm $record) use ($taxonomy): array {
                        return app(CustomTaxonomyTermService::class)->parentOptions(
                            $taxonomy,
                            $record?->id,
                        );
                    })
                    ->searchable()
                    ->nullable()
                    ->placeholder('— Root level —')
                    ->visible(fn (): bool => $taxonomy->isHierarchical())
                    ->helperText('Only available for hierarchical taxonomies.'),
                Textarea::make('description')
                    ->label('Description')
                    ->rows(3)
                    ->maxLength(2000)
                    ->columnSpanFull(),
            ]);
    }

    public function table(Table $table): Table
    {
        /** @var CustomTaxonomy $taxonomy */
        $taxonomy = $this->getOwnerRecord();

        return $table
            ->recordTitleAttribute('name')
            ->columns([
                TextColumn::make('name')
                    ->label('Name')
                    ->searchable()
                    ->sortable()
                    ->description(fn (CustomTaxonomyTerm $record): ?string => $record->parent
                        ? 'Parent: '.$record->parent->name
                        : null),
                TextColumn::make('slug')
                    ->label('Slug')
                    ->searchable()
                    ->copyable(),
                TextColumn::make('parent.name')
                    ->label('Parent')
                    ->placeholder('— Root —')
                    ->visible($taxonomy->isHierarchical()),
                TextColumn::make('updated_at')
                    ->label('Updated')
                    ->dateTime()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('name')
            ->headerActions([
                CreateAction::make()
                    ->using(function (array $data, string $model): Model {
                        /** @var CustomTaxonomy $taxonomy */
                        $taxonomy = $this->getOwnerRecord();

                        return app(CustomTaxonomyTermService::class)->create($taxonomy, [
                            'name' => $data['name'],
                            'slug' => $data['slug'] ?? null,
                            'parent_id' => $data['parent_id'] ?? null,
                            'description' => $data['description'] ?? null,
                        ]);
                    }),
            ])
            ->recordActions([
                EditAction::make()
                    ->using(function (Model $record, array $data): Model {
                        /** @var CustomTaxonomyTerm $record */
                        return app(CustomTaxonomyTermService::class)->update($record, [
                            'name' => $data['name'],
                            'slug' => $data['slug'] ?? null,
                            'parent_id' => $data['parent_id'] ?? null,
                            'description' => $data['description'] ?? null,
                        ]);
                    }),
                DeleteAction::make()
                    ->using(function (Model $record): void {
                        /** @var CustomTaxonomyTerm $record */
                        try {
                            app(CustomTaxonomyTermService::class)->delete($record);
                        } catch (ValidationException $exception) {
                            Notification::make()
                                ->danger()
                                ->title('Cannot delete term')
                                ->body(collect($exception->errors())->flatten()->first() ?? 'Delete blocked.')
                                ->send();

                            throw $exception;
                        }
                    }),
            ])
            ->toolbarActions([
                DeleteBulkAction::make()
                    ->using(function ($records): void {
                        $service = app(CustomTaxonomyTermService::class);
                        foreach ($records as $record) {
                            try {
                                $service->delete($record);
                            } catch (ValidationException $exception) {
                                Notification::make()
                                    ->danger()
                                    ->title("Cannot delete {$record->name}")
                                    ->body(collect($exception->errors())->flatten()->first() ?? 'Delete blocked.')
                                    ->send();
                            }
                        }
                    }),
            ]);
    }
}
