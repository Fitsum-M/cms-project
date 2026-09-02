<?php

namespace App\Filament\Pages\System;

use App\Enums\Permission;
use App\Filament\Pages\System\Schemas\EmailSettingsForm;
use App\Filament\Pages\System\Schemas\GeneralSettingsForm;
use App\Filament\Pages\System\Schemas\MediaSettingsForm;
use App\Filament\Pages\System\Schemas\PermalinkSettingsForm;
use App\Filament\Pages\System\Schemas\ReadingSettingsForm;
use App\Filament\Pages\System\Schemas\SeoDefaultsForm;
use App\Services\EmailSettingsTester;
use App\Support\Settings\EmailSettings;
use App\Support\Settings\GeneralSettings;
use App\Support\Settings\MediaSettings;
use App\Support\Settings\PermalinkSettings;
use App\Support\Settings\ReadingSettings;
use App\Support\Settings\SeoDefaultsSettings;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Pages\Concerns\CanUseDatabaseTransactions;
use Filament\Pages\Page;
use Filament\Schemas\Components\Actions;
use Filament\Schemas\Components\Component;
use Filament\Schemas\Components\EmbeddedSchema;
use Filament\Schemas\Components\Form;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Schemas\Schema;
use Filament\Support\Exceptions\Halt;
use Filament\Support\Icons\Heroicon;
use Throwable;
use UnitEnum;

/**
 * @property-read Schema $form
 */
class SettingsPage extends Page
{
    use CanUseDatabaseTransactions;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedCog6Tooth;

    protected static string|UnitEnum|null $navigationGroup = 'System Configuration';

    protected static ?string $navigationLabel = 'Settings';

    protected static ?int $navigationSort = 1;

    protected static ?string $title = 'Settings';

    protected static ?string $slug = 'system/settings';

    /**
     * @var array<string, mixed>|null
     */
    public ?array $data = [];

    public static function canAccess(): bool
    {
        $user = auth()->user();

        if ($user === null) {
            return false;
        }

        return $user->can(Permission::SettingsView->value)
            || $user->can(Permission::SeoDefaultsView->value);
    }

    public function mount(): void
    {
        $this->fillForm();
    }

    public function defaultForm(Schema $schema): Schema
    {
        return $schema
            ->operation('edit')
            ->statePath('data');
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Tabs::make('Settings')
                    ->persistTabInQueryString('tab')
                    ->tabs([
                        Tab::make('General')
                            ->key('general', isInheritable: false)
                            ->visible(fn (): bool => $this->canViewSettings())
                            ->disabled(fn (): bool => ! $this->canEditSettings())
                            ->schema(GeneralSettingsForm::components()),
                        Tab::make('Reading')
                            ->key('reading', isInheritable: false)
                            ->visible(fn (): bool => $this->canViewSettings())
                            ->disabled(fn (): bool => ! $this->canEditSettings())
                            ->schema(ReadingSettingsForm::components()),
                        Tab::make('Permalinks')
                            ->key('permalinks', isInheritable: false)
                            ->visible(fn (): bool => $this->canViewSettings())
                            ->disabled(fn (): bool => ! $this->canEditSettings())
                            ->schema(PermalinkSettingsForm::components()),
                        Tab::make('Media')
                            ->key('media', isInheritable: false)
                            ->visible(fn (): bool => $this->canViewSettings())
                            ->disabled(fn (): bool => ! $this->canEditSettings())
                            ->schema(MediaSettingsForm::components()),
                        Tab::make('SEO Defaults')
                            ->key('seo', isInheritable: false)
                            ->visible(fn (): bool => $this->canViewSeo())
                            ->disabled(fn (): bool => ! $this->canEditSeo())
                            ->schema(SeoDefaultsForm::components()),
                        Tab::make('Email')
                            ->key('email', isInheritable: false)
                            ->visible(fn (): bool => $this->canViewSettings())
                            ->disabled(fn (): bool => ! $this->canEditSettings())
                            ->schema(EmailSettingsForm::components()),
                    ]),
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
                ->visible(fn (): bool => $this->canEditAny()),
            Action::make('sendTestEmail')
                ->label('Send Test Email')
                ->color('gray')
                ->action('sendTestEmail')
                ->visible(fn (): bool => $this->canEditSettings()),
        ];
    }

    public function save(): void
    {
        if (! $this->canEditAny()) {
            return;
        }

        try {
            $this->beginDatabaseTransaction();

            $data = $this->form->getState();

            if ($this->canEditSettings()) {
                app(GeneralSettings::class)->save($data);
                app(GeneralSettings::class)->applyRuntimeConfiguration();
                app(ReadingSettings::class)->save($data);
                app(PermalinkSettings::class)->save($data);
                app(MediaSettings::class)->save($data);
                app(EmailSettings::class)->save($data);
            }

            if ($this->canEditSeo()) {
                app(SeoDefaultsSettings::class)->save($data);
            }

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
            ->title('Settings saved')
            ->send();

        $this->fillForm();
    }

    public function sendTestEmail(): void
    {
        if (! $this->canEditSettings()) {
            return;
        }

        $data = $this->form->getState();
        $result = app(EmailSettingsTester::class)->sendTest($data);

        if ($result['ok']) {
            Notification::make()
                ->success()
                ->title('Test email sent')
                ->body($result['message'])
                ->send();

            return;
        }

        Notification::make()
            ->danger()
            ->title('Test email failed')
            ->body($result['message'])
            ->send();
    }

    protected function fillForm(): void
    {
        $state = [];

        if ($this->canViewSettings()) {
            $state = [
                ...$state,
                ...app(GeneralSettings::class)->all(),
                ...app(ReadingSettings::class)->all(),
                ...app(PermalinkSettings::class)->all(),
                ...app(MediaSettings::class)->all(),
                ...app(EmailSettings::class)->all(),
            ];
        }

        if ($this->canViewSeo()) {
            $state = [
                ...$state,
                ...SeoDefaultsForm::prepareFillState(app(SeoDefaultsSettings::class)->all()),
            ];
        }

        $this->form->fill($state);
    }

    protected function canViewSettings(): bool
    {
        return auth()->user()?->can(Permission::SettingsView->value) ?? false;
    }

    protected function canEditSettings(): bool
    {
        return auth()->user()?->can(Permission::SettingsEdit->value) ?? false;
    }

    protected function canViewSeo(): bool
    {
        return auth()->user()?->can(Permission::SeoDefaultsView->value) ?? false;
    }

    protected function canEditSeo(): bool
    {
        return auth()->user()?->can(Permission::SeoDefaultsEdit->value) ?? false;
    }

    protected function canEditAny(): bool
    {
        return $this->canEditSettings() || $this->canEditSeo();
    }
}
