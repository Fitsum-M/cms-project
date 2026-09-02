<?php

namespace App\Filament\Widgets;

use App\Enums\Permission;
use App\Filament\Resources\MediaAssets\MediaAssetResource;
use App\Filament\Resources\Pages\PageResource;
use App\Filament\Resources\Posts\PostResource;
use App\Filament\Resources\Users\UserResource;
use App\Services\DashboardOverviewService;
use Filament\Support\Icons\Heroicon;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

/**
 * Dashboard Overview — post / page / media / user counts (SRS 20.1, D.01).
 */
class OverviewStatsWidget extends StatsOverviewWidget
{
    protected static ?int $sort = 1;

    /**
     * @var int | array<string, ?int> | null
     */
    protected int | array | null $columns = 4;

    public static function canView(): bool
    {
        return auth()->user()?->can(Permission::DashboardView->value) ?? false;
    }

    public function getHeading(): ?string
    {
        return __('cms.dashboard.overview.heading');
    }

    public function getDescription(): ?string
    {
        return __('cms.dashboard.overview.description');
    }

    /**
     * @return array<Stat>
     */
    protected function getStats(): array
    {
        $counts = app(DashboardOverviewService::class)->counts();

        return [
            Stat::make(__('cms.dashboard.overview.stats.posts'), number_format($counts['posts']))
                ->description(__('cms.dashboard.overview.stats.posts_description'))
                ->icon(Heroicon::OutlinedDocumentText)
                ->color('primary')
                ->url(PostResource::getUrl('index')),
            Stat::make(__('cms.dashboard.overview.stats.pages'), number_format($counts['pages']))
                ->description(__('cms.dashboard.overview.stats.pages_description'))
                ->icon(Heroicon::OutlinedDocumentDuplicate)
                ->color('success')
                ->url(PageResource::getUrl('index')),
            Stat::make(__('cms.dashboard.overview.stats.media'), number_format($counts['media']))
                ->description(__('cms.dashboard.overview.stats.media_description'))
                ->icon(Heroicon::OutlinedPhoto)
                ->color('warning')
                ->url(MediaAssetResource::getUrl('index')),
            Stat::make(__('cms.dashboard.overview.stats.users'), number_format($counts['users']))
                ->description(__('cms.dashboard.overview.stats.users_description'))
                ->icon(Heroicon::OutlinedUsers)
                ->color('gray')
                ->url(UserResource::getUrl('index')),
        ];
    }
}
