<?php

namespace App\Filament\Forms\Tables;

use App\Models\MediaAsset;
use App\Services\FolderService;
use Filament\Support\Enums\FontWeight;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\Layout\Stack;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

/**
 * Visual image browser for ModalTableSelect (featured / OG pickers).
 */
final class MediaImagePickerTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->query(
                MediaAsset::query()
                    ->where('mime_type', 'like', 'image/%')
                    ->with(['media', 'folder'])
            )
            ->extraAttributes([
                'class' => 'fi-media-image-picker',
            ])
            ->columns(static::gridColumns())
            ->contentGrid([
                'md' => 2,
                'lg' => 3,
                'xl' => 4,
            ])
            ->defaultSort('created_at', 'desc')
            ->searchable()
            ->filters([
                SelectFilter::make('folder_scope')
                    ->label('Folder')
                    ->options(function (): array {
                        return [
                            'unfiled' => 'Unfiled',
                            ...app(FolderService::class)->options(),
                        ];
                    })
                    ->query(function (Builder $query, array $data): Builder {
                        $value = $data['value'] ?? null;

                        if ($value === null || $value === '') {
                            return $query;
                        }

                        if ($value === 'unfiled') {
                            return $query->whereNull('folder_id');
                        }

                        return $query->where('folder_id', (int) $value);
                    })
                    ->searchable()
                    ->preload(),
            ]);
    }

    /**
     * @return array<int, Stack>
     */
    private static function gridColumns(): array
    {
        $searchQuery = function (Builder $query, string $search): Builder {
            $term = '%'.$search.'%';

            return $query->where(function (Builder $inner) use ($term): void {
                $inner
                    ->where('title', 'like', $term)
                    ->orWhere('original_file_name', 'like', $term)
                    ->orWhere('alt_text', 'like', $term);
            });
        };

        return [
            Stack::make([
                ImageColumn::make('preview')
                    ->label('Preview')
                    ->getStateUsing(fn (MediaAsset $record): ?string => $record->previewUrl())
                    ->imageHeight('9rem')
                    ->imageWidth('100%')
                    ->extraAttributes([
                        'class' => 'w-full min-w-0 overflow-hidden rounded-lg bg-gray-100 dark:bg-white/5',
                    ])
                    ->extraImgAttributes(fn (MediaAsset $record): array => [
                        'class' => 'h-36 w-full rounded-lg object-cover',
                        'alt' => $record->alt_text ?: $record->title,
                    ])
                    ->placeholder('No preview'),
                TextColumn::make('title')
                    ->label('Title')
                    ->weight(FontWeight::SemiBold)
                    ->searchable(query: $searchQuery)
                    ->limit(40)
                    ->tooltip(fn (MediaAsset $record): string => $record->title)
                    ->description(fn (MediaAsset $record): string => $record->folder?->name ?? 'Unfiled')
                    ->wrap()
                    ->extraAttributes([
                        'class' => 'min-w-0',
                    ]),
            ])
                ->space(2)
                ->extraAttributes([
                    'class' => 'w-full min-w-0',
                ]),
        ];
    }
}
