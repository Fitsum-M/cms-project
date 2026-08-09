<?php

namespace App\Filament\Auth;

use Filament\Auth\Pages\Login as BaseLogin;

/**
 * Admin login with SRS §19.2 rate limit: 5 attempts / 15 minutes / IP.
 *
 * Filament's default Livewire rate limiter uses a 60-second window; we widen
 * the authenticate window to 15 minutes while keeping the 5-attempt cap and IP key.
 */
class Login extends BaseLogin
{
    public const MAX_ATTEMPTS = 5;

    public const DECAY_SECONDS = 15 * 60;

    /**
     * @param  int  $maxAttempts
     * @param  int  $decaySeconds
     * @param  string|null  $method
     * @param  string|null  $component
     */
    protected function rateLimit($maxAttempts, $decaySeconds = 60, $method = null, $component = null): void
    {
        $method ??= debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, limit: 2)[1]['function'];

        if ($method === 'authenticate') {
            $maxAttempts = self::MAX_ATTEMPTS;
            $decaySeconds = self::DECAY_SECONDS;
        }

        parent::rateLimit($maxAttempts, $decaySeconds, $method, $component);
    }
}
