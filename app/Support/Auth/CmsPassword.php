<?php

namespace App\Support\Auth;

use Illuminate\Validation\Rules\Password;

/**
 * Shared password complexity rules (SRS 15.3).
 */
final class CmsPassword
{
    public static function rules(): Password
    {
        return Password::min(12)
            ->letters()
            ->mixedCase()
            ->numbers()
            ->symbols();
    }
}
