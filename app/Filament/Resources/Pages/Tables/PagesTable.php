<?php

namespace App\Filament\Resources\Pages\Tables;

use App\Enums\ContentStatus;
use App\Models\Page;
use App\Models\User;
use App\Services\ContentLifecycleService;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ForceDeleteAction;
use Filament\Actions\ForceDeleteBulkAction;
use Filament\Actions\ViewAction;
use Filament\Notifications\Notification;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Validation\ValidationException;

class PagesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('title')
                    ->label('Title')
                    ->searchable()
                    ->sortable()
                    ->description(fn (Page $record): string => $record->hierarchicalLabel() !== $record->title
                        ? $record->hierarchicalLabel()
                        : $record->slug),
                TextColumn::make('parent.title')
                    ->label('Parent')
                    ->placeholder('—')
                    ->toggleable(),
                TextColumn::make('author.name')
                    ->label('Author')
                    ->sortable()
                    ->toggleable(),
                TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->formatStateUsing(fn (Page $record): string => $record->lifecycleLabel())
                    ->sortable(),
                TextColumn::make('template')
                    ->label('Template')
                    ->formatStateUsing(fn (Page $record): string => $record->templateLabel())
                    ->toggleable(),
                TextColumn::make('show_in_navigation')
                    ->label('In Nav')
                    ->badge()
                    ->formatStateUsing(fn (Page $record): string => $record->isNavigationReady() ? 'Yes' : 'No')
                    ->toggleable(),
                TextColumn::make('sort_order')
                    ->label('Order')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('published_at')
                    ->label('Publish Date')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(),
                TextColumn::make('updated_at')
                    ->label('Updated')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('sort_order')
            ->filters([
                TrashedFilter::make(),
                SelectFilter::make('status')
                    ->multiple()
                    ->options(ContentStatus::options()),
                SelectFilter::make('template')
                    ->label('Template')
                    ->options(fn (): array => \App\Support\PageTemplateRegistry::options())
                    ->query(function (Builder $query, array $data): Builder {
                        $value = $data['value'] ?? null;

                        if ($value === null || $value === '') {
                            return $query;
                        }

                        if ($value === \App\Support\PageTemplateRegistry::defaultKey()) {
                            return $query->where(function (Builder $inner): void {
                                $inner->whereNull('template')
                                    ->orWhere('template', \App\Support\PageTemplateRegistry::defaultKey());
                            });
                        }

                        return $query->where('template', $value);
                    }),
                SelectFilter::make('show_in_navigation')
                    ->label('In navigation')
                    ->options([
                        '1' => 'Yes',
                        '0' => 'No',
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        $value = $data['value'] ?? null;

                        if ($value === null || $value === '') {
                            return $query;
                        }

                        return $query->where('show_in_navigation', filter_var($value, FILTER_VALIDATE_BOOLEAN));
                    }),
                SelectFilter::make('author_id')
                    ->label('Author')
                    ->options(fn (): array => User::query()->orderBy('name')->pluck('name', 'id')->all())
                    ->searchable(),
                SelectFilter::make('parent_id')
                    ->label('Parent')
                    ->options(fn (): array => Page::query()
                        ->orderBy('title')
                        ->pluck('title', 'id')
                        ->all())
                    ->searchable()
                    ->placeholder('Any')
                    ->query(function (Builder $query, array $data): Builder {
                        $value = $data['value'] ?? null;

                        if ($value === null || $value === '') {
                            return $query;
                        }

                        return $query->where('parent_id', (int) $value);
                    }),
            ])
            ->recordActions([
                ViewAction::make(),
                EditAction::make()
                    ->visible(fn (Page $record): bool => ! $record->trashed()),
                DeleteAction::make()
                    ->visible(fn (Page $record): bool => ! $record->trashed())
                    ->using(function (Page $record): void {
                        try {
                            app(ContentLifecycleService::class)->trash($record);
                        } catch (ValidationException $exception) {
                            Notification::make()
                                ->danger()
                                ->title('Cannot delete page')
                                ->body(collect($exception->errors())->flatten()->first() ?? 'Delete blocked.')
                                ->send();

                            throw $exception;
                        }
                    }),
                ForceDeleteAction::make()
                    ->visible(fn (Page $record): bool => $record->trashed()
                        && (auth()->user()?->can('forceDelete', $record) ?? false))
                    ->using(function (Page $record): void {
                        try {
                            app(ContentLifecycleService::class)->forceDelete($record, auth()->user());
                        } catch (ValidationException $exception) {
                            Notification::make()
                                ->danger()
                                ->title('Cannot permanently delete page')
                                ->body(collect($exception->errors())->flatten()->first() ?? 'Delete blocked.')
                                ->send();

                            throw $exception;
                        }
                    }),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make()
                        ->using(function (Collection $records): void {
                            $lifecycle = app(ContentLifecycleService::class);

                            foreach ($records as $record) {
                                try {
                                    $lifecycle->trash($record);
                                } catch (ValidationException $exception) {
                                    Notification::make()
                                        ->danger()
                                        ->title("Cannot delete {$record->title}")
                                        ->body(collect($exception->errors())->flatten()->first() ?? 'Delete blocked.')
                                        ->send();
                                }
                            }
                        }),
                    ForceDeleteBulkAction::make()
                        ->visible(fn (): bool => auth()->user()?->can(\App\Enums\Permission::PagesForceDelete->value) ?? false)
                        ->using(function (Collection $records): void {
                            $lifecycle = app(ContentLifecycleService::class);

                            foreach ($records as $record) {
                                try {
                                    $lifecycle->forceDelete($record, auth()->user());
                                } catch (ValidationException) {
                                    // skip
                                }
                            }
                        }),
                ]),
            ])
            ->modifyQueryUsing(function (Builder $query): Builder {
                return $query->with(['author', 'parent']);
            });
    }
}
