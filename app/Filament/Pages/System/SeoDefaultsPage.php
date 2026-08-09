<?php

namespace App\Filament\Pages\System;

use App\Enums\Permission;
use App\Support\Settings\SeoDefaultsSettings;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Forms\Components\CheckboxList;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Pages\Concerns\CanUseDatabaseTransactions;
use Filament\Pages\Page;
use Filament\Schemas\Components\Actions;
use Filament\Schemas\Components\Component;
use Filament\Schemas\Components\EmbeddedSchema;
use Filament\Schemas\Components\Form;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Filament\Support\Exceptions\Halt;
use Filament\Support\Icons\Heroicon;
use Illuminate\Validation\Rule;
use Throwable;
use UnitEnum;

/**
 * @property-read Schema $form
 */
class SeoDefaultsPage extends Page
{
    use CanUseDatabaseTransactions;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedMagnifyingGlass;

    protected static string|UnitEnum|null $navigationGroup = 'System Configuration';

    protected static ?string $navigationLabel = 'SEO Defaults';

    protected static ?int $navigationSort = 5;

    protected static ?string $title = 'SEO Defaults';

    protected static ?string $slug = 'system/seo-defaults';

    /**
     * @var array<string, mixed>|null
     */
    public ?array $data = [];

    public static function canAccess(): bool
    {
        return auth()->user()?->can(Permission::SeoDefaultsView->value) ?? false;
    }

    public function mount(): void
    {
        $this->fillForm();
    }

    public function defaultForm(Schema $schema): Schema
    {
        return $schema
            ->operation('edit')
            ->statePath('data')
            ->disabled(fn (): bool => ! $this->canEdit());
    }

    public function form(Schema $schema): Schema
    {
        $ogHelper = SeoDefaultsSettings::ogImageOptions() === []
            ? 'No images in the media library yet. Upload images via DAM (Phase 4) to set a fallback OG image.'
            : 'Fallback og:image when content has no OG image or featured image.';

        return $schema
            ->components([
                Section::make('Meta defaults')
                    ->description('Fallback values when content-level SEO fields are empty (SRS 12.5.3 inheritance).')
                    ->schema([
                        TextInput::make(SeoDefaultsSettings::META_TITLE_PATTERN)
                            ->label('Default Meta Title Pattern')
                            ->required()
                            ->maxLength(255)
                            ->helperText('Tokens: {title}, {site_title}. Example: {title} | {site_title}')
                            ->autocomplete(false),
                        Textarea::make(SeoDefaultsSettings::META_DESCRIPTION)
                            ->label('Default Meta Description')
                            ->rows(3)
                            ->maxLength(500)
                            ->helperText('Used when a content item has no meta description. Max 500 characters (160 recommended).'),
                    ])
                    ->columns(1),
                Section::make('Open Graph & schema')
                    ->schema([
                        Select::make(SeoDefaultsSettings::OG_IMAGE_ID)
                            ->label('Default OG Image')
                            ->options(fn (): array => SeoDefaultsSettings::ogImageOptions())
                            ->searchable()
                            ->nullable()
                            ->placeholder('— Not set —')
                            ->helperText($ogHelper)
                            ->rules($this->ogImageRules()),
                        Select::make(SeoDefaultsSettings::SCHEMA_TYPE)
                            ->label('Default Schema Type')
                            ->options(SeoDefaultsSettings::schemaTypeOptions())
                            ->required()
                            ->live()
                            ->helperText('WebPage is recommended for general pages.'),
                        TextInput::make('custom_schema_type')
                            ->label('Custom Schema Type')
                            ->maxLength(100)
                            ->visible(fn (Get $get): bool => $get(SeoDefaultsSettings::SCHEMA_TYPE) === 'Custom')
                            ->required(fn (Get $get): bool => $get(SeoDefaultsSettings::SCHEMA_TYPE) === 'Custom')
                            ->helperText('Schema.org type name for advanced use.'),
                    ])
                    ->columns(1),
                Section::make('Robots')
                    ->schema([
                        CheckboxList::make(SeoDefaultsSettings::ROBOTS)
                            ->label('Default Robots')
                            ->options(SeoDefaultsSettings::robotsOptions())
                            ->required()
                            ->columns(2)
                            ->helperText('Default robots directives when content does not override them (e.g. index, follow).'),
                    ])
                    ->columns(1),
            ]);
    }

    public function content(Schema $schema): Schema
    {
        return $schema
            ->components([
                $this->getFormContentComponent(),
            ]);
    }

    public function getFormContentComponent(): Component
    {
        return Form::make([EmbeddedSchema::make('form')])
            ->id('form')
            ->livewireSubmitHandler('save')
            ->footer([
                Actions::make($this->getFormActions())
                    ->alignment($this->getFormActionsAlignment())
                    ->sticky($this->areFormActionsSticky())
                    ->key('form-actions'),
            ]);
    }

    /**
     * @return array<Action>
     */
    protected function getFormActions(): array
    {
        return [
            Action::make('save')
                ->label('Save changes')
                ->submit('save')
                ->keyBindings(['mod+s'])
                ->visible(fn (): bool => $this->canEdit()),
        ];
    }

    public function save(): void
    {
        if (! $this->canEdit()) {
            return;
        }

        try {
            $this->beginDatabaseTransaction();

            $data = $this->form->getState();

            app(SeoDefaultsSettings::class)->save($data);

            $this->commitDatabaseTransaction();
        } catch (Halt $exception) {
            $exception->shouldRollbackDatabaseTransaction()
                ? $this->rollBackDatabaseTransaction()
                : $this->commitDatabaseTransaction();

            return;
        } catch (Throwable $exception) {
            $this->rollBackDatabaseTransaction();

            throw $exception;
        }

        Notification::make()
            ->success()
            ->title('SEO Defaults saved')
            ->send();

        $this->fillForm();
    }

    protected function fillForm(): void
    {
        $settings = app(SeoDefaultsSettings::class)->all();
        $known = array_keys(SeoDefaultsSettings::schemaTypeOptions());

        if (! in_array($settings[SeoDefaultsSettings::SCHEMA_TYPE], $known, true)) {
            $settings['custom_schema_type'] = $settings[SeoDefaultsSettings::SCHEMA_TYPE];
            $settings[SeoDefaultsSettings::SCHEMA_TYPE] = 'Custom';
        } else {
            $settings['custom_schema_type'] = null;
        }

        $this->form->fill($settings);
    }

    protected function canEdit(): bool
    {
        return auth()->user()?->can(Permission::SeoDefaultsEdit->value) ?? false;
    }

    /**
     * @return list<\Illuminate\Contracts\Validation\ValidationRule|string>
     */
    private function ogImageRules(): array
    {
        if (! SeoDefaultsSettings::mediaAssetsTableReady()) {
            return ['nullable'];
        }

        return [
            'nullable',
            'integer',
            Rule::exists('media_assets', 'id'),
        ];
    }
}
