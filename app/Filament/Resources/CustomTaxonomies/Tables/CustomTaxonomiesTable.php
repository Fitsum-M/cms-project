<?php

namespace App\Filament\Resources\CustomTaxonomies\Tables;

use App\Enums\TaxonomyStructure;
use App\Models\CustomTaxonomy;
use App\Services\CustomTaxonomyService;
use App\Support\PostTypeRegistry;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class CustomTaxonomiesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->label('Name')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('slug')
                    ->label('Slug')
                    ->searchable()
                    ->sortable()
                    ->copyable(),
                TextColumn::make('structure_type')
                    ->label('Structure')
                    ->badge()
                    ->formatStateUsing(fn (TaxonomyStructure|string $state): string => $state instanceof TaxonomyStructure
                        ? $state->label()
                        : (string) $state)
                    ->sortable(),
                TextColumn::make('post_types')
                    ->label('Post Types')
                    ->state(function (CustomTaxonomy $record): string {
                        $options = PostTypeRegistry::options();

                        return collect($record->postTypeKeys())
                            ->map(fn (string $key): string => $options[$key] ?? $key)
                            ->implode(', ');
                    }),
                TextColumn::make('terms_count')
                    ->counts('terms')
                    ->label('Terms')
                    ->sortable(),
                TextColumn::make('updated_at')
                    ->label('Updated')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('name')
            ->filters([
                SelectFilter::make('structure_type')
                    ->label('Structure')
                    ->options(TaxonomyStructure::options()),
            ])
            ->recordActions([
                ViewAction::make(),
                EditAction::make(),
                DeleteAction::make()
                    ->using(fn (CustomTaxonomy $record) => app(CustomTaxonomyService::class)->delete($record)),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make()
                        ->using(function ($records): void {
                            $service = app(CustomTaxonomyService::class);
                            foreach ($records as $record) {
                                $service->delete($record);
                            }
                        }),
                ]),
            ]);
    }
}
