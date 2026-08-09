<?php

namespace App\Support\Settings;

use App\Enums\SettingGroup;
use App\Services\SettingsStore;
use Carbon\CarbonInterface;
use DateTimeInterface;
use DateTimeZone;
use Illuminate\Support\Carbon;

class GeneralSettings
{
    public const SITE_TITLE = 'site_title';

    public const TAGLINE = 'tagline';

    public const TIMEZONE = 'timezone';

    public const DATE_FORMAT = 'date_format';

    public const TIME_FORMAT = 'time_format';

    public function __construct(
        private readonly SettingsStore $store,
    ) {}

    public function siteTitle(): string
    {
        return (string) $this->store->get(SettingGroup::General, self::SITE_TITLE, self::defaults()[self::SITE_TITLE]);
    }

    public function tagline(): string
    {
        return (string) $this->store->get(SettingGroup::General, self::TAGLINE, self::defaults()[self::TAGLINE]);
    }

    public function timezone(): string
    {
        return (string) $this->store->get(SettingGroup::General, self::TIMEZONE, self::defaults()[self::TIMEZONE]);
    }

    public function dateFormat(): string
    {
        return (string) $this->store->get(SettingGroup::General, self::DATE_FORMAT, self::defaults()[self::DATE_FORMAT]);
    }

    public function timeFormat(): string
    {
        return (string) $this->store->get(SettingGroup::General, self::TIME_FORMAT, self::defaults()[self::TIME_FORMAT]);
    }

    public function dateTimeFormat(): string
    {
        return $this->dateFormat().' '.$this->timeFormat();
    }

    /**
     * @return array{
     *     site_title: string,
     *     tagline: string,
     *     timezone: string,
     *     date_format: string,
     *     time_format: string
     * }
     */
    public function all(): array
    {
        return [
            self::SITE_TITLE => $this->siteTitle(),
            self::TAGLINE => $this->tagline(),
            self::TIMEZONE => $this->timezone(),
            self::DATE_FORMAT => $this->dateFormat(),
            self::TIME_FORMAT => $this->timeFormat(),
        ];
    }

    /**
     * @param  array{
     *     site_title?: string,
     *     tagline?: string,
     *     timezone?: string,
     *     date_format?: string,
     *     time_format?: string
     * }  $data
     */
    public function save(array $data): void
    {
        $merged = [
            ...$this->all(),
            ...$data,
        ];

        $this->store->putMany(SettingGroup::General, [
            self::SITE_TITLE => ['value' => $merged[self::SITE_TITLE], 'type' => 'string'],
            self::TAGLINE => ['value' => $merged[self::TAGLINE], 'type' => 'string'],
            self::TIMEZONE => ['value' => $merged[self::TIMEZONE], 'type' => 'string'],
            self::DATE_FORMAT => ['value' => $merged[self::DATE_FORMAT], 'type' => 'string'],
            self::TIME_FORMAT => ['value' => $merged[self::TIME_FORMAT], 'type' => 'string'],
        ]);
    }

    public function formatDate(DateTimeInterface|string|null $value): ?string
    {
        $carbon = $this->toCarbon($value);

        return $carbon?->timezone($this->timezone())->format($this->dateFormat());
    }

    public function formatTime(DateTimeInterface|string|null $value): ?string
    {
        $carbon = $this->toCarbon($value);

        return $carbon?->timezone($this->timezone())->format($this->timeFormat());
    }

    public function formatDateTime(DateTimeInterface|string|null $value): ?string
    {
        $carbon = $this->toCarbon($value);

        if ($carbon === null) {
            return null;
        }

        $carbon = $carbon->timezone($this->timezone());

        return $carbon->format($this->dateTimeFormat());
    }

    /**
     * @return array{
     *     site_title: string,
     *     tagline: string,
     *     timezone: string,
     *     date_format: string,
     *     time_format: string
     * }
     */
    public static function defaults(): array
    {
        return [
            self::SITE_TITLE => 'CMS System',
            self::TAGLINE => '',
            self::TIMEZONE => config('app.timezone', 'UTC'),
            self::DATE_FORMAT => 'F j, Y',
            self::TIME_FORMAT => 'g:i a',
        ];
    }

    /**
     * @return array<string, string>
     */
    public static function dateFormatOptions(?CarbonInterface $reference = null): array
    {
        $reference ??= Carbon::now();

        $formats = [
            'F j, Y',
            'Y-m-d',
            'm/d/Y',
            'd/m/Y',
            'j F Y',
            'D, M j, Y',
        ];

        return collect($formats)
            ->mapWithKeys(fn (string $format): array => [
                $format => $reference->format($format).' ('.$format.')',
            ])
            ->all();
    }

    /**
     * @return array<string, string>
     */
    public static function timeFormatOptions(?CarbonInterface $reference = null): array
    {
        $reference ??= Carbon::now();

        return [
            'g:i a' => $reference->format('g:i a').' (12-hour)',
            'H:i' => $reference->format('H:i').' (24-hour)',
        ];
    }

    /**
     * @return array<string, string>
     */
    public static function timezoneOptions(): array
    {
        return collect(DateTimeZone::listIdentifiers())
            ->mapWithKeys(fn (string $timezone): array => [$timezone => $timezone])
            ->all();
    }

    public function applyRuntimeConfiguration(): void
    {
        $timezone = $this->timezone();

        if (! in_array($timezone, DateTimeZone::listIdentifiers(), true)) {
            return;
        }

        config(['app.timezone' => $timezone]);
        date_default_timezone_set($timezone);
    }

    private function toCarbon(DateTimeInterface|string|null $value): ?Carbon
    {
        if ($value === null || $value === '') {
            return null;
        }

        return Carbon::parse($value);
    }
}
