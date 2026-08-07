<?php

namespace App\Filament\Resources\Categories\Tables;

use App\Models\Category;
use App\Services\CategoryService;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Notifications\Notification;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Validation\ValidationException;

class CategoriesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->label('Name')
                    ->searchable()
                    ->sortable()
                    ->description(fn (Category $record): ?string => $record->parent
                        ? 'Parent: '.$record->parent->name
                        : null),
                TextColumn::make('slug')
                    ->label('Slug')
                    ->searchable()
                    ->sortable()
                    ->copyable(),
                TextColumn::make('parent.name')
                    ->label('Parent')
                    ->placeholder('— Root —')
                    ->sortable()
                    ->toggleable(),
                TextColumn::make('children_count')
                    ->counts('children')
                    ->label('Children')
                    ->sortable(),
                TextColumn::make('updated_at')
                    ->label('Updated')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('name')
            ->filters([
                SelectFilter::make('parent_id')
                    ->label('Parent')
                    ->options(fn (): array => Category::query()
                        ->whereHas('children')
                        ->orderBy('name')
                        ->pluck('name', 'id')
                        ->all())
                    ->placeholder('All'),
            ])
            ->recordActions([
                ViewAction::make(),
                EditAction::make(),
                DeleteAction::make()
                    ->using(function (Category $record): void {
                        try {
                            app(CategoryService::class)->delete($record);
                        } catch (ValidationException $exception) {
                            Notification::make()
                                ->danger()
                                ->title('Cannot delete category')
                                ->body(collect($exception->errors())->flatten()->first() ?? 'Delete blocked.')
                                ->send();

                            throw $exception;
                        }
                    }),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make()
                        ->using(function ($records): void {
                            $service = app(CategoryService::class);

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
                ]),
            ]);
    }
}
