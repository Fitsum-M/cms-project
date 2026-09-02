<?php

namespace App\Filament\Pages\System\Schemas;

use App\Enums\SmtpEncryption;
use App\Support\Settings\EmailSettings;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Illuminate\Validation\Rule;

class EmailSettingsForm
{
    /**
     * @return array<int, Section>
     */
    public static function components(): array
    {
        $passwordHelper = app(EmailSettings::class)->hasSmtpPassword()
            ? 'Password is set. Leave blank to keep the current password.'
            : 'Optional SMTP authentication password.';

        return [
            Section::make('SMTP')
                ->description('Outbound mail transport used for invitations, notifications, and test delivery.')
                ->schema([
                    TextInput::make(EmailSettings::SMTP_HOST)
                        ->label('SMTP Host')
                        ->maxLength(255)
                        ->autocomplete(false)
                        ->helperText('Leave blank to disable outbound SMTP.'),
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
        ];
    }
}
