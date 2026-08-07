<?php

namespace App\Services;

use App\Mail\SettingsTestMail;
use App\Support\Settings\EmailSettings;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Throwable;

class EmailSettingsTester
{
    /**
     * @param  array<string, mixed>  $formData
     * @return array{ok: bool, message: string}
     */
    public function sendTest(array $formData): array
    {
        $recipient = trim((string) ($formData[EmailSettings::TEST_RECIPIENT] ?? ''));

        if ($recipient === '' || ! filter_var($recipient, FILTER_VALIDATE_EMAIL)) {
            return [
                'ok' => false,
                'message' => 'Enter a valid test email recipient before sending.',
            ];
        }

        $host = trim((string) ($formData[EmailSettings::SMTP_HOST] ?? ''));
        if ($host === '') {
            return [
                'ok' => false,
                'message' => 'SMTP host is required to send a test email.',
            ];
        }

        $settings = app(EmailSettings::class);
        $settings->applyFromFormData($formData);

        $serverIdentity = gethostname() ?: (string) parse_url((string) config('app.url'), PHP_URL_HOST) ?: 'cms';
        $sentAt = now();

        try {
            Mail::to($recipient)->send(new SettingsTestMail($sentAt, $serverIdentity));
        } catch (Throwable $exception) {
            return [
                'ok' => false,
                'message' => 'Test email failed: '.Str::limit($exception->getMessage(), 300),
            ];
        } finally {
            // Restore persisted configuration after the attempt.
            $settings->applyRuntimeConfiguration();
        }

        return [
            'ok' => true,
            'message' => "Test email sent to {$recipient}.",
        ];
    }
}
