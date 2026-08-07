<?php

namespace App\Enums;

enum UserStatus: string
{
    case PendingActivation = 'pending_activation';
    case Active = 'active';
    case Suspended = 'suspended';

    public function label(): string
    {
        return match ($this) {
            self::PendingActivation => 'Pending Activation',
            self::Active => 'Active',
            self::Suspended => 'Suspended',
        };
    }

    public function canAuthenticate(): bool
    {
        return $this === self::Active;
    }
}
