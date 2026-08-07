<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Mail\Mailables\Headers;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Carbon;

class SettingsTestMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public readonly Carbon $sentAt,
        public readonly string $serverIdentity,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'CMS Email Settings Test',
        );
    }

    public function content(): Content
    {
        return new Content(
            htmlString: '<p>This is a test email from the CMS Email Settings screen.</p>'
                .'<p><strong>Sent at:</strong> '.e($this->sentAt->toIso8601String()).'</p>'
                .'<p><strong>Server:</strong> '.e($this->serverIdentity).'</p>',
        );
    }

    public function headers(): Headers
    {
        return new Headers(
            text: [
                'X-CMS-Test-Email' => '1',
                'X-CMS-Server' => $this->serverIdentity,
                'X-CMS-Test-Timestamp' => $this->sentAt->toIso8601String(),
            ],
        );
    }
}
