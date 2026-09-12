<?php

namespace App\Filament\Resources\Pages\Tables;

use App\Enums\ContentStatus;
use App\Models\Page;
use App\Models\User;
use App\Services\ContentLifecycleService;
use App\Support\Content\ContentSearch;
use Illuminate\Database\Eloquent\Builder;
use App\Services\FrontendContentService;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ForceDeleteAction;
use Filament\Actions\ForceDeleteBulkAction;
use Filament\Actions\ViewAction;
use Filament\Notifications\Notification;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Validation\ValidationException;

class PagesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                ImageColumn::make('featured_image_preview')
                    ->label('')
                    ->getStateUsing(fn (Page $record): ?string => $record->featuredImageUrl())
                    ->square()
                    ->extraImgAttributes(['alt' => ''])
                    ->toggleable(),
                TextColumn::make('title')
                    ->label(__('cms.tables.title'))
                    ->searchable(query: fn (Builder $query, string $search): Builder => ContentSearch::applyPagesSearch($query, $search))
                    ->sortable()
                    ->description(function (Page $record): string {
                        $hierarchy = $record->hierarchicalLabel() !== $record->title
                            ? $record->hierarchicalLabel().' · '
                            : '';

                        return $hierarchy.'/pages/'.$record->slug;
                    }),
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
                    ->sortable(query: fn (Builder $query, string $direction): Builder => $query->orderByRaw(
                        'COALESCE(pages.published_at, pages.created_at) ' . (strtolower($direction) === 'asc' ? 'asc' : 'desc')
                    ))
                    ->toggleable(),
                TextColumn::make('created_at')
                    ->label('Created')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('updated_at')
                    ->label('Updated')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('published_at', 'desc')
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
                ActionGroup::make([
                    Action::make('openOnSite')
                        ->label('Open')
                        ->icon('heroicon-o-arrow-top-right-on-square')
                        ->url(fn (Page $record): string => $record->publicUrl())
                        ->openUrlInNewTab()
                        ->visible(fn (Page $record): bool => ! $record->trashed()
                            && app(FrontendContentService::class)->isPublicPage($record)),
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
                    ->tooltip('Actions')
                    ->icon('heroicon-m-ellipsis-vertical'),
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
                return $query->with(['author', 'parent', 'featuredImage']);
            });
    }
}
