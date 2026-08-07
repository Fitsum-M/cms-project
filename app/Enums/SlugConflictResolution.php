<?php

namespace App\Enums;

enum SlugConflictResolution: string
{
    case AppendNumber = 'append_number';
    case BlockSave = 'block_save';
    case PromptUser = 'prompt_user';

    public function label(): string
    {
        return match ($this) {
            self::AppendNumber => 'Append number (e.g. my-post-2)',
            self::BlockSave => 'Block save',
            self::PromptUser => 'Prompt user',
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
