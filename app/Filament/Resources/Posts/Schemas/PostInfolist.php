<?php

namespace App\Filament\Resources\Posts\Schemas;

use App\Enums\ContentStatus;
use App\Enums\PostVisibility;
use App\Models\Post;
use App\Support\PostTypeRegistry;
use Filament\Infolists\Components\ImageEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class PostInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Content')
                    ->schema([
                        TextEntry::make('title'),
                        TextEntry::make('slug')
                            ->copyable(),
                        TextEntry::make('excerpt')
                            ->label('Excerpt')
                            ->state(fn (Post $record): string => $record->resolvedExcerpt() ?: '—')
                            ->columnSpanFull(),
                        TextEntry::make('body')
                            ->label('Content')
                            ->html()
                            ->placeholder('—')
                            ->columnSpanFull(),
                        ImageEntry::make('featured_image')
                            ->label('Featured Image')
                            ->visible(fn (Post $record): bool => $record->hasFeaturedImage())
                            ->getStateUsing(fn (Post $record): ?string => $record->featuredImageUrl()),
                        TextEntry::make('featured_image_broken')
                            ->label('Featured Image')
                            ->visible(fn (Post $record): bool => $record->hasBrokenFeaturedImage())
                            ->state('Broken reference — reassign from the media library.'),
                    ])
                    ->columns(2),
                Section::make('Taxonomies')
                    ->schema([
                        TextEntry::make('categories.name')
                            ->label('Categories')
                            ->badge()
                            ->placeholder('—'),
                        TextEntry::make('tags.name')
                            ->label('Tags')
                            ->badge()
                            ->placeholder('—'),
                        TextEntry::make('customTaxonomyTerms')
                            ->label('Custom terms')
                            ->badge()
                            ->state(fn (Post $record): array => $record->customTaxonomyTerms
                                ->map(fn ($term): string => ($term->taxonomy?->name ?? 'Taxonomy').': '.$term->name)
                                ->all())
                            ->placeholder('—'),
                    ]),
                Section::make('Settings')
                    ->schema([
                        TextEntry::make('post_type')
                            ->label('Post Type')
                            ->formatStateUsing(fn (string $state): string => PostTypeRegistry::options()[$state] ?? $state),
                        TextEntry::make('status')
                            ->badge()
                            ->formatStateUsing(fn (ContentStatus $state): string => $state->label()),
                        TextEntry::make('visibility')
                            ->badge()
                            ->formatStateUsing(fn (PostVisibility $state): string => $state->label()),
                        TextEntry::make('author.name')
                            ->label('Author'),
                        TextEntry::make('published_at')
                            ->label('Publish Date')
                            ->dateTime(),
                        TextEntry::make('publicly_accessible')
                            ->label('Publicly accessible now')
                            ->state(fn (Post $record): string => $record->isPubliclyAccessible() ? 'Yes' : 'No'),
                        TextEntry::make('created_at')
                            ->dateTime(),
                        TextEntry::make('updated_at')
                            ->dateTime(),
                    ])
                    ->columns(2),
            ]);
    }
}
