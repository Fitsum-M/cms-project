<?php

namespace App\Support;

use Illuminate\Support\HtmlString;
use Stevebauman\Purify\Facades\Purify;

/**
 * Sanitize rich-text HTML for safe frontend output (Review Comment #3 §7.2).
 */
final class ContentHtml
{
    public static function clean(?string $html): string
    {
        return trim(Purify::clean((string) ($html ?? '')));
    }

    /**
     * Purified body HTML for Blade `{!! !!}` output, with empty fallback.
     */
    public static function body(?string $html): HtmlString
    {
        $cleaned = self::clean($html);

        if ($cleaned === '') {
            return new HtmlString('<p>No content yet.</p>');
        }

        return new HtmlString($cleaned);
    }
}
