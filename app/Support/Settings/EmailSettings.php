<?php

namespace App\Support\Settings;

use App\Enums\SettingGroup;
use App\Enums\SmtpEncryption;
use App\Services\SettingsStore;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Mail;
use Throwable;

class EmailSettings
{
    public const SMTP_HOST = 'smtp_host';

    public const SMTP_PORT = 'smtp_port';

    public const SMTP_ENCRYPTION = 'smtp_encryption';

    public const SMTP_USERNAME = 'smtp_username';

    public const SMTP_PASSWORD = 'smtp_password';

    public const SENDER_NAME = 'sender_name';

    public const SENDER_ADDRESS = 'sender_address';

    public const TEST_RECIPIENT = 'test_email_recipient';

    public function __construct(
        private readonly SettingsStore $store,
    ) {}

    public function smtpHost(): string
    {
        return (string) $this->store->get(SettingGroup::Email, self::SMTP_HOST, self::defaults()[self::SMTP_HOST]);
    }

    public function smtpPort(): int
    {
        return (int) $this->store->get(SettingGroup::Email, self::SMTP_PORT, self::defaults()[self::SMTP_PORT]);
    }

    public function smtpEncryption(): SmtpEncryption
    {
        $value = $this->store->get(
            SettingGroup::Email,
            self::SMTP_ENCRYPTION,
            self::defaults()[self::SMTP_ENCRYPTION],
        );

        return SmtpEncryption::tryFrom((string) $value) ?? SmtpEncryption::Tls;
    }

    public function smtpUsername(): string
    {
        return (string) $this->store->get(SettingGroup::Email, self::SMTP_USERNAME, self::defaults()[self::SMTP_USERNAME]);
    }

    public function smtpPassword(): string
    {
        $encrypted = $this->store->get(SettingGroup::Email, self::SMTP_PASSWORD, null);

        if (! is_string($encrypted) || $encrypted === '') {
            return '';
        }

        try {
            return Crypt::decryptString($encrypted);
        } catch (Throwable) {
            return '';
        }
    }

    public function hasSmtpPassword(): bool
    {
        $encrypted = $this->store->get(SettingGroup::Email, self::SMTP_PASSWORD, null);

        return is_string($encrypted) && $encrypted !== '';
    }

    public function senderName(): string
    {
        return (string) $this->store->get(SettingGroup::Email, self::SENDER_NAME, self::defaults()[self::SENDER_NAME]);
    }

    public function senderAddress(): string
    {
        return (string) $this->store->get(SettingGroup::Email, self::SENDER_ADDRESS, self::defaults()[self::SENDER_ADDRESS]);
    }

    public function testRecipient(): string
    {
        return (string) $this->store->get(SettingGroup::Email, self::TEST_RECIPIENT, self::defaults()[self::TEST_RECIPIENT]);
    }

    /**
     * Form state — password is never exposed; blank means "keep existing".
     *
     * @return array{
     *     smtp_host: string,
     *     smtp_port: int,
     *     smtp_encryption: string,
     *     smtp_username: string,
     *     smtp_password: null,
     *     sender_name: string,
     *     sender_address: string,
     *     test_email_recipient: string
     * }
     */
    public function all(): array
    {
        return [
            self::SMTP_HOST => $this->smtpHost(),
            self::SMTP_PORT => $this->smtpPort(),
            self::SMTP_ENCRYPTION => $this->smtpEncryption()->value,
            self::SMTP_USERNAME => $this->smtpUsername(),
            self::SMTP_PASSWORD => null,
            self::SENDER_NAME => $this->senderName(),
            self::SENDER_ADDRESS => $this->senderAddress(),
            self::TEST_RECIPIENT => $this->testRecipient(),
        ];
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function save(array $data): void
    {
        $merged = [
            ...$this->all(),
            ...$data,
        ];

        $encryption = SmtpEncryption::tryFrom((string) $merged[self::SMTP_ENCRYPTION])
            ?? SmtpEncryption::Tls;

        $items = [
            self::SMTP_HOST => ['value' => trim((string) $merged[self::SMTP_HOST]), 'type' => 'string'],
            self::SMTP_PORT => ['value' => max(1, min(65535, (int) $merged[self::SMTP_PORT])), 'type' => 'integer'],
            self::SMTP_ENCRYPTION => ['value' => $encryption->value, 'type' => 'string'],
            self::SMTP_USERNAME => ['value' => (string) ($merged[self::SMTP_USERNAME] ?? ''), 'type' => 'string'],
            self::SENDER_NAME => ['value' => trim((string) $merged[self::SENDER_NAME]), 'type' => 'string'],
            self::SENDER_ADDRESS => ['value' => trim((string) $merged[self::SENDER_ADDRESS]), 'type' => 'string'],
            self::TEST_RECIPIENT => ['value' => trim((string) ($merged[self::TEST_RECIPIENT] ?? '')), 'type' => 'string'],
        ];

        $password = $merged[self::SMTP_PASSWORD] ?? null;
        if (is_string($password) && $password !== '') {
            $items[self::SMTP_PASSWORD] = [
                'value' => Crypt::encryptString($password),
                'type' => 'string',
            ];
        }

        $this->store->putMany(SettingGroup::Email, $items);
        $this->applyRuntimeConfiguration();
    }

    /**
     * @return array{
     *     smtp_host: string,
     *     smtp_port: int,
     *     smtp_encryption: string,
     *     smtp_username: string,
     *     smtp_password: null,
     *     sender_name: string,
     *     sender_address: string,
     *     test_email_recipient: string
     * }
     */
    public static function defaults(): array
    {
        return [
            self::SMTP_HOST => '',
            self::SMTP_PORT => 587,
            self::SMTP_ENCRYPTION => SmtpEncryption::Tls->value,
            self::SMTP_USERNAME => '',
            self::SMTP_PASSWORD => null,
            self::SENDER_NAME => (string) config('app.name', 'CMS System'),
            self::SENDER_ADDRESS => (string) config('mail.from.address', 'noreply@example.com'),
            self::TEST_RECIPIENT => '',
        ];
    }

    public function applyRuntimeConfiguration(): void
    {
        if ($this->smtpHost() === '') {
            return;
        }

        $encryption = $this->smtpEncryption();

        config([
            'mail.default' => 'smtp',
            'mail.mailers.smtp.transport' => 'smtp',
            'mail.mailers.smtp.host' => $this->smtpHost(),
            'mail.mailers.smtp.port' => $this->smtpPort(),
            'mail.mailers.smtp.scheme' => $encryption->scheme(),
            'mail.mailers.smtp.username' => $this->smtpUsername() !== '' ? $this->smtpUsername() : null,
            'mail.mailers.smtp.password' => $this->smtpPassword() !== '' ? $this->smtpPassword() : null,
            'mail.from.address' => $this->senderAddress() !== '' ? $this->senderAddress() : config('mail.from.address'),
            'mail.from.name' => $this->senderName() !== '' ? $this->senderName() : config('mail.from.name'),
        ]);

        try {
            Mail::purge('smtp');
        } catch (Throwable) {
            // Mail manager may not be resolved yet during early boot.
        }
    }

    /**
     * Apply form values temporarily (e.g. before sending a test without saving password blanks).
     *
     * @param  array<string, mixed>  $data
     */
    public function applyFromFormData(array $data): void
    {
        $encryption = SmtpEncryption::tryFrom((string) ($data[self::SMTP_ENCRYPTION] ?? SmtpEncryption::Tls->value))
            ?? SmtpEncryption::Tls;

        $password = $data[self::SMTP_PASSWORD] ?? null;
        if (! is_string($password) || $password === '') {
            $password = $this->smtpPassword();
        }

        $host = trim((string) ($data[self::SMTP_HOST] ?? $this->smtpHost()));
        $username = (string) ($data[self::SMTP_USERNAME] ?? $this->smtpUsername());
        $senderAddress = trim((string) ($data[self::SENDER_ADDRESS] ?? $this->senderAddress()));
        $senderName = trim((string) ($data[self::SENDER_NAME] ?? $this->senderName()));

        config([
            'mail.default' => 'smtp',
            'mail.mailers.smtp.transport' => 'smtp',
            'mail.mailers.smtp.host' => $host !== '' ? $host : config('mail.mailers.smtp.host'),
            'mail.mailers.smtp.port' => max(1, min(65535, (int) ($data[self::SMTP_PORT] ?? $this->smtpPort()))),
            'mail.mailers.smtp.scheme' => $encryption->scheme(),
            'mail.mailers.smtp.username' => $username !== '' ? $username : null,
            'mail.mailers.smtp.password' => $password !== '' ? $password : null,
            'mail.from.address' => $senderAddress !== '' ? $senderAddress : config('mail.from.address'),
            'mail.from.name' => $senderName !== '' ? $senderName : config('mail.from.name'),
        ]);

        Mail::purge('smtp');
    }
}
