<?php

namespace App\Filament\Auth;

use Filament\Auth\Pages\PasswordReset\RequestPasswordReset as BaseRequestPasswordReset;

/**
 * Request password reset link from the admin login flow (SRS 15.3 / 20.7).
 */
class RequestPasswordReset extends BaseRequestPasswordReset
{
    // Default Filament behavior is sufficient; class exists for panel wiring + tests.
}
