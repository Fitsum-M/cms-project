<?php

namespace App\Filament\Pages\System;

use App\Enums\Permission;
use App\Support\Settings\GeneralSettings;
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
use Throwable;
use UnitEnum;

/**
 * @property-read Schema $form
 */
class GeneralSettingsPage extends Page
{
    use CanUseDatabaseTransactions;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedCog6Tooth;

    protected static string|UnitEnum|null $navigationGroup = 'System Configuration';

    protected static ?string $navigationLabel = 'General';

    protected static ?int $navigationSort = 1;

    protected static ?string $title = 'General Settings';

    protected static ?string $slug = 'system/general';

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
        return $schema
            ->components([
                Section::make('Site identity')
                    ->description('Primary site name and short description used across the CMS.')
                    ->schema([
                        TextInput::make(GeneralSettings::SITE_TITLE)
                            ->label('Site Title')
                            ->required()
                            ->maxLength(255)
                            ->autocomplete(false),
                        TextInput::make(GeneralSettings::TAGLINE)
                            ->label('Tagline')
                            ->maxLength(255)
                            ->autocomplete(false),
                    ])
                    ->columns(1),
                Section::make('Date & time')
                    ->description('System-wide timezone and display formats for dates and times.')
                    ->schema([
                        Select::make(GeneralSettings::TIMEZONE)
                            ->label('Timezone')
                            ->options(GeneralSettings::timezoneOptions())
                            ->searchable()
                            ->required(),
                        Select::make(GeneralSettings::DATE_FORMAT)
                            ->label('Date Format')
                            ->options(GeneralSettings::dateFormatOptions())
                            ->required(),
                        Select::make(GeneralSettings::TIME_FORMAT)
                            ->label('Time Format')
                            ->options(GeneralSettings::timeFormatOptions())
                            ->required(),
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

            app(GeneralSettings::class)->save($data);
            app(GeneralSettings::class)->applyRuntimeConfiguration();

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
            ->title('General settings saved')
            ->send();
    }

    protected function fillForm(): void
    {
        $this->form->fill(app(GeneralSettings::class)->all());
    }

    protected function canEdit(): bool
    {
        return auth()->user()?->can(Permission::SettingsEdit->value) ?? false;
    }
}
