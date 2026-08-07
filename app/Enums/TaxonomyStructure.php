<?php

namespace App\Enums;

enum TaxonomyStructure: string
{
    case Hierarchical = 'hierarchical';
    case Flat = 'flat';

    public function label(): string
    {
        return match ($this) {
            self::Hierarchical => 'Hierarchical (category-like)',
            self::Flat => 'Flat (tag-like)',
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
