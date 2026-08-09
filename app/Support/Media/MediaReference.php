<?php

namespace App\Support\Media;

final readonly class MediaReference
{
    public function __construct(
        public string $type,
        public string $label,
        public string $detail,
        public ?string $url = null,
    ) {}
}
