<?php

namespace App\Support\Settings;

use App\Enums\SettingGroup;
use App\Services\SettingsStore;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class ReadingSettings
{
    public const HOMEPAGE_PAGE_ID = 'homepage_page_id';

    public const POSTS_PAGE_ID = 'posts_page_id';

    public const POSTS_PER_PAGE = 'posts_per_page';

    public function __construct(
        private readonly SettingsStore $store,
    ) {}

    public function homepagePageId(): ?int
    {
        $value = $this->store->get(SettingGroup::Reading, self::HOMEPAGE_PAGE_ID, self::defaults()[self::HOMEPAGE_PAGE_ID]);

        return $value === null || $value === '' ? null : (int) $value;
    }

    public function postsPageId(): ?int
    {
        $value = $this->store->get(SettingGroup::Reading, self::POSTS_PAGE_ID, self::defaults()[self::POSTS_PAGE_ID]);

        return $value === null || $value === '' ? null : (int) $value;
    }

    public function postsPerPage(): int
    {
        return (int) $this->store->get(
            SettingGroup::Reading,
            self::POSTS_PER_PAGE,
            self::defaults()[self::POSTS_PER_PAGE],
        );
    }

    /**
     * @return array{
     *     homepage_page_id: int|null,
     *     posts_page_id: int|null,
     *     posts_per_page: int
     * }
     */
    public function all(): array
    {
        return [
            self::HOMEPAGE_PAGE_ID => $this->homepagePageId(),
            self::POSTS_PAGE_ID => $this->postsPageId(),
            self::POSTS_PER_PAGE => $this->postsPerPage(),
        ];
    }

    /**
     * @param  array{
     *     homepage_page_id?: int|string|null,
     *     posts_page_id?: int|string|null,
     *     posts_per_page?: int|string
     * }  $data
     */
    public function save(array $data): void
    {
        $merged = [
            ...$this->all(),
            ...$data,
        ];

        $homepageId = $this->normalizeNullableId($merged[self::HOMEPAGE_PAGE_ID] ?? null);
        $postsPageId = $this->normalizeNullableId($merged[self::POSTS_PAGE_ID] ?? null);
        $postsPerPage = max(1, min(100, (int) ($merged[self::POSTS_PER_PAGE] ?? self::defaults()[self::POSTS_PER_PAGE])));

        $this->store->putMany(SettingGroup::Reading, [
            self::HOMEPAGE_PAGE_ID => ['value' => $homepageId, 'type' => 'integer'],
            self::POSTS_PAGE_ID => ['value' => $postsPageId, 'type' => 'integer'],
            self::POSTS_PER_PAGE => ['value' => $postsPerPage, 'type' => 'integer'],
        ]);
    }

    /**
     * @return array{
     *     homepage_page_id: null,
     *     posts_page_id: null,
     *     posts_per_page: int
     * }
     */
    public static function defaults(): array
    {
        return [
            self::HOMEPAGE_PAGE_ID => null,
            self::POSTS_PAGE_ID => null,
            self::POSTS_PER_PAGE => 10,
        ];
    }

    /**
     * Options for page-reference selects. Empty until the pages table exists (Phase 5).
     *
     * @return array<int, string>
     */
    public static function pageOptions(): array
    {
        if (! self::pagesTableReady()) {
            return [];
        }

        return DB::table('pages')
            ->orderBy('title')
            ->get(['id', 'title'])
            ->mapWithKeys(fn (object $page): array => [(int) $page->id => (string) $page->title])
            ->all();
    }

    public static function pagesTableReady(): bool
    {
        try {
            return Schema::hasTable('pages');
        } catch (\Throwable) {
            return false;
        }
    }

    public static function pageExists(int $pageId): bool
    {
        if (! self::pagesTableReady()) {
            return false;
        }

        return DB::table('pages')->where('id', $pageId)->exists();
    }

    private function normalizeNullableId(mixed $value): ?int
    {
        if ($value === null || $value === '') {
            return null;
        }

        return (int) $value;
    }
}
