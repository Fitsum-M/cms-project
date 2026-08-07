<?php

namespace App\Enums;

enum SmtpEncryption: string
{
    case Tls = 'tls';
    case Ssl = 'ssl';
    case None = 'none';

    public function label(): string
    {
        return match ($this) {
            self::Tls => 'TLS (STARTTLS)',
            self::Ssl => 'SSL (SMTPS)',
            self::None => 'None',
        };
    }

    /**
     * Symfony / Laravel mailer DSN scheme.
     */
    public function scheme(): string
    {
        return match ($this) {
            self::Ssl => 'smtps',
            self::Tls, self::None => 'smtp',
        };
    }

    /**
     * @return array<string, string>
     */
    public static function options(): array
    {
        return collect(self::cases())
            ->mapWithKeys(fn (self $case): array => [$case->value => $case->label()])
            ->all();
    }
}
