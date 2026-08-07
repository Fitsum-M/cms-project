<?php

namespace App\Filament\Resources\Tags\Tables;

use App\Models\Tag;
use App\Services\TagService;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Notifications\Notification;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Validation\ValidationException;

class TagsTable
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
                TextColumn::make('description')
                    ->label('Description')
                    ->limit(40)
                    ->toggleable(),
                TextColumn::make('updated_at')
                    ->label('Updated')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('name')
            ->filters([])
            ->recordActions([
                ViewAction::make(),
                EditAction::make(),
                DeleteAction::make()
                    ->using(function (Tag $record): void {
                        try {
                            app(TagService::class)->delete($record);
                        } catch (ValidationException $exception) {
                            Notification::make()
                                ->danger()
                                ->title('Cannot delete tag')
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
                            $service = app(TagService::class);

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
