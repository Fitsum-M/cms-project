<?php

namespace App\Enums;

enum ContentSlugScope: string
{
    case Posts = 'posts';
    case Pages = 'pages';

    public function table(): string
    {
        return $this->value;
    }

    public function label(): string
    {
        return match ($this) {
            self::Posts => 'posts',
            self::Pages => 'pages',
        };
    }
}
