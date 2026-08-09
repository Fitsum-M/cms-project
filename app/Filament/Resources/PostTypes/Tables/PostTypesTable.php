<?php

namespace App\Filament\Resources\PostTypes\Tables;

use App\Filament\Resources\Posts\PostResource;
use App\Models\PostType;
use App\Services\PostTypeService;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Notifications\Notification;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Validation\ValidationException;

class PostTypesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                IconColumn::make('icon')
                    ->label('')
                    ->icon(fn (PostType $record): string => $record->resolvedIcon())
                    ->color('gray'),
                TextColumn::make('plural_name')
                    ->label('Plural')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('singular_name')
                    ->label('Singular')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('slug')
                    ->label('Slug')
                    ->searchable()
                    ->sortable()
                    ->copyable(),
                TextColumn::make('supports_categories')
                    ->label('Categories')
                    ->badge()
                    ->formatStateUsing(fn (bool $state): string => $state ? 'Yes' : 'No')
                    ->color(fn (bool $state): string => $state ? 'success' : 'gray'),
                TextColumn::make('supports_tags')
                    ->label('Tags')
                    ->badge()
                    ->formatStateUsing(fn (bool $state): string => $state ? 'Yes' : 'No')
                    ->color(fn (bool $state): string => $state ? 'success' : 'gray'),
                TextColumn::make('posts_count')
                    ->label('Content')
                    ->state(fn (PostType $record): int => $record->postsCount())
                    ->sortable(false),
                TextColumn::make('updated_at')
                    ->label('Updated')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('sort_order')
            ->reorderable('sort_order')
            ->filters([])
            ->recordActions([
                Action::make('viewContent')
                    ->label('View content')
                    ->icon(Heroicon::OutlinedNewspaper)
                    ->url(fn (PostType $record): string => PostResource::getUrl('index', [
                        'post_type' => $record->slug,
                    ])),
                ViewAction::make(),
                EditAction::make(),
                DeleteAction::make()
                    ->using(function (PostType $record): void {
                        try {
                            app(PostTypeService::class)->delete($record);
                        } catch (ValidationException $exception) {
                            Notification::make()
                                ->danger()
                                ->title('Cannot delete post type')
                                ->body(collect($exception->errors())->flatten()->first() ?? 'Delete blocked.')
                                ->send();

                            throw $exception;
                        }
                    }),
            ])
            ->toolbarActions([]);
    }
}
