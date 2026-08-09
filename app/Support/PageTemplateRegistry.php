<?php

namespace App\Support;

/**
 * System-level page template registry (SRS 12.3.5).
 * Populated from config/page-templates.php; null page values resolve to Default.
 */
final class PageTemplateRegistry
{
    public static function defaultKey(): string
    {
        $key = (string) config('page-templates.default', 'default');

        return self::isValid($key) ? $key : 'default';
    }

    /**
     * @return array<string, string> key => label
     */
    public static function options(): array
    {
        $options = [];

        foreach (self::definitions() as $key => $definition) {
            $options[$key] = $definition['label'];
        }

        return $options;
    }

    /**
     * @return list<string>
     */
    public static function keys(): array
    {
        return array_keys(self::definitions());
    }

    public static function isValid(?string $key): bool
    {
        if ($key === null || $key === '') {
            return false;
        }

        return array_key_exists($key, self::definitions());
    }

    /**
     * Resolve stored value to a registry key (null/blank → default).
     */
    public static function resolve(?string $key): string
    {
        if (self::isValid($key)) {
            return (string) $key;
        }

        return self::defaultKey();
    }

    public static function label(?string $key): string
    {
        $resolved = self::resolve($key);

        return self::definitions()[$resolved]['label'] ?? 'Default';
    }

    public static function icon(?string $key): string
    {
        $resolved = self::resolve($key);

        return self::definitions()[$resolved]['icon'] ?? 'heroicon-o-document';
    }

    public static function description(?string $key): ?string
    {
        $resolved = self::resolve($key);

        return self::definitions()[$resolved]['description'] ?? null;
    }

    /**
     * @return list<array{key: string, label: string, description: string|null, icon: string}>
     */
    public static function catalog(): array
    {
        $catalog = [];

        foreach (self::definitions() as $key => $definition) {
            $catalog[] = [
                'key' => $key,
                'label' => $definition['label'],
                'description' => $definition['description'] ?? null,
                'icon' => $definition['icon'] ?? 'heroicon-o-document',
            ];
        }

        return $catalog;
    }

    /**
     * @return array<string, array{label: string, description?: string|null, icon?: string}>
     */
    private static function definitions(): array
    {
        /** @var array<string, array{label: string, description?: string|null, icon?: string}> $templates */
        $templates = config('page-templates.templates', []);

        if ($templates === []) {
            return [
                'default' => [
                    'label' => 'Default',
                    'description' => 'Standard content page layout.',
                    'icon' => 'heroicon-o-document',
                ],
            ];
        }

        return $templates;
    }
}
