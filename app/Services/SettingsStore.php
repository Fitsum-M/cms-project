<?php

namespace App\Services;

use App\Enums\SettingGroup;
use App\Models\Setting;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Schema;

class SettingsStore
{
    private const CACHE_TTL_SECONDS = 3600;

    public function get(SettingGroup|string $group, string $key, mixed $default = null): mixed
    {
        $groupValue = $this->groupValue($group);
        $values = $this->all($groupValue);

        return array_key_exists($key, $values) ? $values[$key] : $default;
    }

    /**
     * @return array<string, mixed>
     */
    public function all(SettingGroup|string $group): array
    {
        $groupValue = $this->groupValue($group);

        if (! $this->tableReady()) {
            return [];
        }

        return Cache::remember(
            $this->cacheKey($groupValue),
            self::CACHE_TTL_SECONDS,
            function () use ($groupValue): array {
                return Setting::query()
                    ->where('group', $groupValue)
                    ->get()
                    ->mapWithKeys(fn (Setting $setting): array => [
                        $setting->key => $this->castValue($setting->value, $setting->type),
                    ])
                    ->all();
            },
        );
    }

    public function set(SettingGroup|string $group, string $key, mixed $value, string $type = 'string'): void
    {
        $groupValue = $this->groupValue($group);

        Setting::query()->updateOrCreate(
            [
                'group' => $groupValue,
                'key' => $key,
            ],
            [
                'value' => $this->serializeValue($value, $type),
                'type' => $type,
            ],
        );

        $this->forget($groupValue);
    }

    /**
     * @param  array<string, array{value: mixed, type?: string}>  $items
     */
    public function putMany(SettingGroup|string $group, array $items): void
    {
        $groupValue = $this->groupValue($group);

        foreach ($items as $key => $item) {
            $type = $item['type'] ?? 'string';

            Setting::query()->updateOrCreate(
                [
                    'group' => $groupValue,
                    'key' => $key,
                ],
                [
                    'value' => $this->serializeValue($item['value'], $type),
                    'type' => $type,
                ],
            );
        }

        $this->forget($groupValue);
    }

    public function forget(SettingGroup|string $group): void
    {
        Cache::forget($this->cacheKey($this->groupValue($group)));
    }

    private function groupValue(SettingGroup|string $group): string
    {
        return $group instanceof SettingGroup ? $group->value : $group;
    }

    private function cacheKey(string $group): string
    {
        return "settings.group.{$group}";
    }

    private function tableReady(): bool
    {
        try {
            return Schema::hasTable('settings');
        } catch (\Throwable) {
            return false;
        }
    }

    private function castValue(?string $value, string $type): mixed
    {
        if ($value === null) {
            return null;
        }

        return match ($type) {
            'boolean', 'bool' => filter_var($value, FILTER_VALIDATE_BOOLEAN),
            'integer', 'int' => (int) $value,
            'float', 'double' => (float) $value,
            'array', 'json' => json_decode($value, true) ?? [],
            default => $value,
        };
    }

    private function serializeValue(mixed $value, string $type): ?string
    {
        if ($value === null) {
            return null;
        }

        return match ($type) {
            'boolean', 'bool' => $value ? '1' : '0',
            'array', 'json' => json_encode($value, JSON_THROW_ON_ERROR),
            default => (string) $value,
        };
    }
}
