<?php

namespace App\Models\Concerns;

use App\Support\ContentHtml;
use Illuminate\Support\HtmlString;

trait HasSafeHtmlBody
{
    /**
     * Sanitized rich-text body for public frontend rendering.
     */
    public function safeBodyHtml(): HtmlString
    {
        return ContentHtml::body($this->body ?? null);
    }
}
