<?php

namespace App\Enums;

enum PostVisibility: string
{
    case Public = 'public';
    case PasswordProtected = 'password';
    case Private = 'private';

    public function label(): string
    {
        return match ($this) {
            self::Public => 'Public',
            self::PasswordProtected => 'Password Protected',
            self::Private => 'Private',
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
