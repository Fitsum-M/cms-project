<?php

namespace App\Filament\Pages\System;

use App\Enums\Permission;
use App\Support\Settings\ReadingSettings;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Pages\Concerns\CanUseDatabaseTransactions;
use Filament\Pages\Page;
use Filament\Schemas\Components\Actions;
use Filament\Schemas\Components\Component;
use Filament\Schemas\Components\EmbeddedSchema;
use Filament\Schemas\Components\Form;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Exceptions\Halt;
use Filament\Support\Icons\Heroicon;
use Illuminate\Validation\Rule;
use Throwable;
use UnitEnum;

/**
 * @property-read Schema $form
 */
class ReadingSettingsPage extends Page
{
    use CanUseDatabaseTransactions;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedBookOpen;

    protected static string|UnitEnum|null $navigationGroup = 'System Configuration';

    protected static ?string $navigationLabel = 'Reading';

    protected static ?int $navigationSort = 2;

    protected static ?string $title = 'Reading Settings';

    protected static ?string $slug = 'system/reading';

    /**
     * @var array<string, mixed>|null
     */
    public ?array $data = [];

    public static function canAccess(): bool
    {
        return auth()->user()?->can(Permission::SettingsView->value) ?? false;
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
        $pagesReady = ReadingSettings::pagesTableReady();
        $pageHelper = $pagesReady
            ? 'Select a CMS page.'
            : 'No pages available yet — page references unlock after Pages are implemented (Phase 5).';

        return $schema
            ->components([
                Section::make('Front page display')
                    ->description('Choose which pages serve as the site homepage and posts listing.')
                    ->schema([
                        Select::make(ReadingSettings::HOMEPAGE_PAGE_ID)
                            ->label('Homepage')
                            ->options(fn (): array => ReadingSettings::pageOptions())
                            ->searchable()
                            ->nullable()
                            ->placeholder('— Not set —')
                            ->helperText($pageHelper)
                            ->rules($this->pageReferenceRules()),
                        Select::make(ReadingSettings::POSTS_PAGE_ID)
                            ->label('Posts Page')
                            ->options(fn (): array => ReadingSettings::pageOptions())
                            ->searchable()
                            ->nullable()
                            ->placeholder('— Not set —')
                            ->helperText($pageHelper)
                            ->rules($this->pageReferenceRules()),
                    ])
                    ->columns(1),
                Section::make('Pagination')
                    ->schema([
                        TextInput::make(ReadingSettings::POSTS_PER_PAGE)
                            ->label('Posts Per Page')
                            ->numeric()
                            ->integer()
                            ->required()
                            ->minValue(1)
                            ->maxValue(100)
                            ->helperText('Default number of posts per listing page (1–100).'),
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

            app(ReadingSettings::class)->save($data);

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
            ->title('Reading settings saved')
            ->send();
    }

    protected function fillForm(): void
    {
        $this->form->fill(app(ReadingSettings::class)->all());
    }

    protected function canEdit(): bool
    {
        return auth()->user()?->can(Permission::SettingsEdit->value) ?? false;
    }

    /**
     * @return list<\Illuminate\Contracts\Validation\ValidationRule|string>
     */
    private function pageReferenceRules(): array
    {
        if (! ReadingSettings::pagesTableReady()) {
            return ['nullable'];
        }

        return [
            'nullable',
            'integer',
            Rule::exists('pages', 'id'),
        ];
    }
}
