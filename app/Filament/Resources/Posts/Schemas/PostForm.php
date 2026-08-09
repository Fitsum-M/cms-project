<?php

namespace App\Filament\Resources\Posts\Schemas;

use App\Enums\ContentStatus;
use App\Enums\Permission;
use App\Enums\PostVisibility;
use App\Enums\UserStatus;
use App\Filament\Forms\Components\MediaLibraryImageSelect;
use App\Models\CustomTaxonomy;
use App\Models\CustomTaxonomyTerm;
use App\Models\Post;
use App\Models\Tag;
use App\Models\User;
use App\Services\CategoryService;
use App\Services\CustomTaxonomyTermService;
use App\Services\TagService;
use App\Support\PostTypeRegistry;
use App\Support\Settings\PermalinkSettings;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;

class PostForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Content')
                    ->schema([
                        TextInput::make('title')
                            ->label('Title')
                            ->required()
                            ->maxLength(255)
                            ->live(onBlur: true)
                            ->afterStateUpdated(function (?string $state, Set $set, Get $get, ?Post $record): void {
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

                                $set('slug', \App\Support\SlugGenerator::sanitize($state));
                            }),
                        TextInput::make('slug')
                            ->label('Slug')
                            ->maxLength(255)
                            ->helperText('URL-friendly identifier. Auto-generated from title when enabled in Permalinks.')
                            ->dehydrateStateUsing(fn (?string $state): ?string => filled($state)
                                ? \App\Support\SlugGenerator::sanitize($state)
                                : $state),
                        Toggle::make('confirm_slug_change')
                            ->label('Confirm slug change')
                            ->helperText('Required when changing the slug of a post that has been published.')
                            ->visible(fn (?Post $record): bool => (bool) $record?->hasBeenPublished())
                            ->dehydrated(),
                        RichEditor::make('body')
                            ->label('Content')
                            ->columnSpanFull(),
                        Textarea::make('excerpt')
                            ->label('Excerpt')
                            ->rows(3)
                            ->maxLength(500)
                            ->helperText('Optional. If empty, the first 160 characters of the content body are used.')
                            ->columnSpanFull(),
                        ...MediaLibraryImageSelect::make(
                            name: 'featured_image_id',
                            label: 'Featured Image',
                            helperText: 'Primary image for this post. Selected from the media library. Used as Open Graph image when SEO OG image is empty.',
                        ),
                    ])
                    ->columns(2),
                Section::make('Settings')
                    ->schema([
                        Select::make('post_type')
                            ->label('Post Type')
                            ->options(fn (): array => PostTypeRegistry::options())
                            ->required()
                            ->default('post')
                            ->live()
                            ->afterStateUpdated(function (?string $state, Set $set, Get $get): void {
                                $allowed = array_map('intval', array_keys(self::customTermOptions((string) ($state ?: 'post'))));
                                $selected = array_map('intval', (array) ($get('custom_term_ids') ?? []));
                                $set('custom_term_ids', array_values(array_intersect($selected, $allowed)));
                            }),
                        Select::make('status')
                            ->label('Status')
                            ->options(ContentStatus::options())
                            ->required()
                            ->default(ContentStatus::Draft->value),
                        Select::make('visibility')
                            ->label('Visibility')
                            ->options(PostVisibility::options())
                            ->required()
                            ->default(PostVisibility::Public->value)
                            ->live(),
                        TextInput::make('password')
                            ->label('Password')
                            ->password()
                            ->revealable()
                            ->maxLength(255)
                            ->visible(fn (Get $get): bool => $get('visibility') === PostVisibility::PasswordProtected->value)
                            ->helperText(fn (?Post $record): string => $record?->password
                                ? 'Leave blank to keep the current password.'
                                : 'Required for password-protected posts.')
                            ->dehydrated(fn (?string $state): bool => filled($state)),
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
                            ->disabled(fn (): bool => ! (auth()->user()?->can(Permission::PostsEditOthers->value) ?? false))
                            ->dehydrated(),
                        DateTimePicker::make('published_at')
                            ->label('Publish Date')
                            ->seconds(false)
                            ->default(now())
                            ->helperText('Future-dated published posts stay hidden until this date/time.'),
                    ])
                    ->columns(2),
                Section::make('Taxonomies')
                    ->schema([
                        Select::make('category_ids')
                            ->label('Categories')
                            ->multiple()
                            ->searchable()
                            ->options(fn (): array => app(CategoryService::class)->parentOptions())
                            ->helperText('Assign one or more hierarchical categories.'),
                        Select::make('tag_ids')
                            ->label('Tags')
                            ->multiple()
                            ->searchable()
                            ->options(fn (): array => Tag::query()->orderBy('name')->pluck('name', 'id')->all())
                            ->createOptionForm([
                                TextInput::make('name')
                                    ->label('Tag name')
                                    ->required()
                                    ->maxLength(255),
                            ])
                            ->createOptionUsing(fn (array $data): int => app(TagService::class)
                                ->findOrCreateByName((string) $data['name'])
                                ->getKey())
                            ->helperText('Type to search. Create a new tag when it does not exist.'),
                        Select::make('custom_term_ids')
                            ->label('Custom taxonomy terms')
                            ->multiple()
                            ->searchable()
                            ->options(fn (Get $get): array => self::customTermOptions((string) ($get('post_type') ?: 'post')))
                            ->helperText('Only taxonomies associated with the selected post type are listed.')
                            ->visible(fn (Get $get): bool => self::customTermOptions((string) ($get('post_type') ?: 'post')) !== []),
                    ])
                    ->columns(1),
            ]);
    }

    /**
     * @return array<int, string>
     */
    private static function customTermOptions(string $postType): array
    {
        $taxonomyIds = CustomTaxonomy::query()
            ->whereHas('postTypeAssociations', fn ($query) => $query->where('post_type_key', $postType))
            ->pluck('id');

        if ($taxonomyIds->isEmpty()) {
            return [];
        }

        $termService = app(CustomTaxonomyTermService::class);

        return CustomTaxonomyTerm::query()
            ->with(['taxonomy', 'parent'])
            ->whereIn('custom_taxonomy_id', $taxonomyIds)
            ->orderBy('name')
            ->get()
            ->mapWithKeys(function (CustomTaxonomyTerm $term) use ($termService): array {
                $taxonomyName = $term->taxonomy?->name ?? 'Taxonomy';
                $label = $termService->hierarchicalLabel($term);

                return [$term->id => "{$taxonomyName}: {$label}"];
            })
            ->all();
    }
}
