<?php

namespace App\Filament\Pages\System;

use App\Enums\Permission;
use App\Enums\SmtpEncryption;
use App\Services\EmailSettingsTester;
use App\Support\Settings\EmailSettings;
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
class EmailSettingsPage extends Page
{
    use CanUseDatabaseTransactions;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedEnvelope;

    protected static string|UnitEnum|null $navigationGroup = 'System Configuration';

    protected static ?string $navigationLabel = 'Email';

    protected static ?int $navigationSort = 6;

    protected static ?string $title = 'Email Settings';

    protected static ?string $slug = 'system/email';

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
        $passwordHelper = app(EmailSettings::class)->hasSmtpPassword()
            ? 'Password is set. Leave blank to keep the current password.'
            : 'Optional SMTP authentication password.';

        return $schema
            ->components([
                Section::make('SMTP')
                    ->description('Outbound mail transport used for invitations, notifications, and test delivery.')
                    ->schema([
                        TextInput::make(EmailSettings::SMTP_HOST)
                            ->label('SMTP Host')
                            ->required()
                            ->maxLength(255)
                            ->autocomplete(false),
                        TextInput::make(EmailSettings::SMTP_PORT)
                            ->label('SMTP Port')
                            ->numeric()
                            ->integer()
                            ->required()
                            ->minValue(1)
                            ->maxValue(65535),
                        Select::make(EmailSettings::SMTP_ENCRYPTION)
                            ->label('SMTP Encryption')
                            ->options(SmtpEncryption::options())
                            ->required()
                            ->rule(Rule::enum(SmtpEncryption::class)),
                        TextInput::make(EmailSettings::SMTP_USERNAME)
                            ->label('SMTP Username')
                            ->maxLength(255)
                            ->autocomplete(false),
                        TextInput::make(EmailSettings::SMTP_PASSWORD)
                            ->label('SMTP Password')
                            ->password()
                            ->revealable()
                            ->maxLength(255)
                            ->autocomplete('new-password')
                            ->helperText($passwordHelper),
                    ])
                    ->columns(2),
                Section::make('Sender')
                    ->schema([
                        TextInput::make(EmailSettings::SENDER_NAME)
                            ->label('Sender Name')
                            ->required()
                            ->maxLength(255),
                        TextInput::make(EmailSettings::SENDER_ADDRESS)
                            ->label('Sender Address')
                            ->email()
                            ->required()
                            ->maxLength(255),
                    ])
                    ->columns(2),
                Section::make('Test delivery')
                    ->description('Send a test message to verify SMTP configuration. Success or failure is reported inline.')
                    ->schema([
                        TextInput::make(EmailSettings::TEST_RECIPIENT)
                            ->label('Test Email Recipient')
                            ->email()
                            ->maxLength(255)
                            ->helperText('Address used by the Send Test Email action.'),
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
            Action::make('sendTestEmail')
                ->label('Send Test Email')
                ->color('gray')
                ->action('sendTestEmail')
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

            app(EmailSettings::class)->save($data);

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
            ->title('Email settings saved')
            ->send();

        $this->fillForm();
    }

    public function sendTestEmail(): void
    {
        if (! $this->canEdit()) {
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
        $this->form->fill(app(EmailSettings::class)->all());
    }

    protected function canEdit(): bool
    {
        return auth()->user()?->can(Permission::SettingsEdit->value) ?? false;
    }
}
