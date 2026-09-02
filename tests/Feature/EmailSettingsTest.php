<?php

namespace Tests\Feature;

use App\Enums\Permission;
use App\Enums\SettingGroup;
use App\Enums\SmtpEncryption;
use App\Enums\UserRole;
use App\Enums\UserStatus;
use App\Filament\Pages\System\SettingsPage;
use App\Mail\SettingsTestMail;
use App\Models\User;
use App\Services\SettingsStore;
use App\Support\Settings\EmailSettings;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Livewire\Livewire;
use Spatie\Permission\Models\Permission as PermissionModel;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class EmailSettingsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RoleSeeder::class);
        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    public function test_administrator_can_save_email_settings(): void
    {
        $admin = $this->makeUser(UserRole::Administrator);

        Livewire::actingAs($admin)
            ->test(SettingsPage::class)
            ->fillForm([
                EmailSettings::SMTP_HOST => 'smtp.example.com',
                EmailSettings::SMTP_PORT => 465,
                EmailSettings::SMTP_ENCRYPTION => SmtpEncryption::Ssl->value,
                EmailSettings::SMTP_USERNAME => 'cms@example.com',
                EmailSettings::SMTP_PASSWORD => 'secret-pass',
                EmailSettings::SENDER_NAME => 'CMS Mailer',
                EmailSettings::SENDER_ADDRESS => 'noreply@example.com',
                EmailSettings::TEST_RECIPIENT => 'admin@example.com',
            ])
            ->call('save')
            ->assertHasNoFormErrors()
            ->assertNotified();

        $settings = app(EmailSettings::class);

        $this->assertSame('smtp.example.com', $settings->smtpHost());
        $this->assertSame(465, $settings->smtpPort());
        $this->assertSame(SmtpEncryption::Ssl, $settings->smtpEncryption());
        $this->assertSame('cms@example.com', $settings->smtpUsername());
        $this->assertSame('secret-pass', $settings->smtpPassword());
        $this->assertSame('CMS Mailer', $settings->senderName());
        $this->assertSame('noreply@example.com', $settings->senderAddress());
        $this->assertSame('admin@example.com', $settings->testRecipient());
        $this->assertSame('smtps', config('mail.mailers.smtp.scheme'));
        $this->assertSame('noreply@example.com', config('mail.from.address'));
    }

    public function test_password_is_preserved_when_left_blank(): void
    {
        app(EmailSettings::class)->save([
            ...EmailSettings::defaults(),
            EmailSettings::SMTP_HOST => 'smtp.example.com',
            EmailSettings::SMTP_PASSWORD => 'original-secret',
            EmailSettings::SENDER_NAME => 'CMS',
            EmailSettings::SENDER_ADDRESS => 'mail@example.com',
        ]);

        $admin = $this->makeUser(UserRole::Administrator);

        Livewire::actingAs($admin)
            ->test(SettingsPage::class)
            ->fillForm([
                EmailSettings::SMTP_HOST => 'smtp.example.com',
                EmailSettings::SMTP_PORT => 587,
                EmailSettings::SMTP_ENCRYPTION => SmtpEncryption::Tls->value,
                EmailSettings::SMTP_USERNAME => 'user',
                EmailSettings::SMTP_PASSWORD => null,
                EmailSettings::SENDER_NAME => 'CMS',
                EmailSettings::SENDER_ADDRESS => 'mail@example.com',
                EmailSettings::TEST_RECIPIENT => '',
            ])
            ->call('save')
            ->assertHasNoFormErrors();

        $this->assertSame('original-secret', app(EmailSettings::class)->smtpPassword());
    }

    public function test_send_test_email_dispatches_mailable(): void
    {
        Mail::fake();

        $admin = $this->makeUser(UserRole::Administrator);

        Livewire::actingAs($admin)
            ->test(SettingsPage::class)
            ->fillForm([
                EmailSettings::SMTP_HOST => 'smtp.example.com',
                EmailSettings::SMTP_PORT => 587,
                EmailSettings::SMTP_ENCRYPTION => SmtpEncryption::Tls->value,
                EmailSettings::SMTP_USERNAME => 'user',
                EmailSettings::SMTP_PASSWORD => 'secret',
                EmailSettings::SENDER_NAME => 'CMS',
                EmailSettings::SENDER_ADDRESS => 'mail@example.com',
                EmailSettings::TEST_RECIPIENT => 'tester@example.com',
            ])
            ->call('sendTestEmail')
            ->assertNotified();

        Mail::assertSent(SettingsTestMail::class, function (SettingsTestMail $mail): bool {
            return $mail->hasTo('tester@example.com')
                && $mail->sentAt !== null
                && $mail->serverIdentity !== '';
        });
    }

    public function test_send_test_email_requires_recipient(): void
    {
        Mail::fake();

        $admin = $this->makeUser(UserRole::Administrator);

        Livewire::actingAs($admin)
            ->test(SettingsPage::class)
            ->fillForm([
                EmailSettings::SMTP_HOST => 'smtp.example.com',
                EmailSettings::SMTP_PORT => 587,
                EmailSettings::SMTP_ENCRYPTION => SmtpEncryption::Tls->value,
                EmailSettings::SENDER_NAME => 'CMS',
                EmailSettings::SENDER_ADDRESS => 'mail@example.com',
                EmailSettings::TEST_RECIPIENT => '',
            ])
            ->call('sendTestEmail')
            ->assertNotified();

        Mail::assertNothingSent();
    }

    public function test_non_administrator_cannot_access_email_settings(): void
    {
        $author = $this->makeUser(UserRole::Author);

        $this->assertFalse($author->can(Permission::SettingsView->value));
        $this->assertFalse($author->can(Permission::SeoDefaultsView->value));

        Livewire::actingAs($author)
            ->test(SettingsPage::class)
            ->assertForbidden();
    }

    public function test_settings_store_encrypts_password(): void
    {
        app(EmailSettings::class)->save([
            ...EmailSettings::defaults(),
            EmailSettings::SMTP_HOST => 'smtp.example.com',
            EmailSettings::SMTP_PASSWORD => 'plain-secret',
            EmailSettings::SENDER_NAME => 'CMS',
            EmailSettings::SENDER_ADDRESS => 'mail@example.com',
        ]);

        $row = app(SettingsStore::class)->all(SettingGroup::Email);

        $this->assertSame('plain-secret', app(EmailSettings::class)->smtpPassword());
        $this->assertDatabaseMissing('settings', [
            'group' => SettingGroup::Email->value,
            'key' => EmailSettings::SMTP_PASSWORD,
            'value' => 'plain-secret',
        ]);
        $this->assertNotSame('plain-secret', $row[EmailSettings::SMTP_PASSWORD] ?? null);
    }

    private function makeUser(UserRole $role): User
    {
        foreach (Permission::cases() as $permission) {
            PermissionModel::findOrCreate($permission->value, 'web');
        }

        $user = User::factory()->create([
            'status' => UserStatus::Active,
            'activated_at' => now(),
        ]);

        $user->assignSingleRole($role);

        return $user->fresh();
    }
}
