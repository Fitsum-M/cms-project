<?php

namespace App\Filament\Widgets;

use App\Enums\Permission;
use App\Filament\Pages\Iam\AllUsers;
use App\Filament\Resources\MediaAssets\MediaAssetResource;
use App\Filament\Resources\Pages\PageResource;
use App\Filament\Resources\Posts\PostResource;
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

    protected ?string $heading = 'Overview';

    protected ?string $description = 'Current content and account totals across the CMS.';

    /**
     * @var int | array<string, ?int> | null
     */
    protected int | array | null $columns = 4;

    public static function canView(): bool
    {
        return auth()->user()?->can(Permission::DashboardView->value) ?? false;
    }

    /**
     * @return array<Stat>
     */
    protected function getStats(): array
    {
        $counts = app(DashboardOverviewService::class)->counts();

        return [
            Stat::make('Posts', number_format($counts['posts']))
                ->description('Published, draft, and pending posts')
                ->icon(Heroicon::OutlinedDocumentText)
                ->color('primary')
                ->url(PostResource::getUrl('index')),
            Stat::make('Pages', number_format($counts['pages']))
                ->description('All non-trashed pages')
                ->icon(Heroicon::OutlinedDocumentDuplicate)
                ->color('success')
                ->url(PageResource::getUrl('index')),
            Stat::make('Media', number_format($counts['media']))
                ->description('Items in the media library')
                ->icon(Heroicon::OutlinedPhoto)
                ->color('warning')
                ->url(MediaAssetResource::getUrl('index')),
            Stat::make('Users', number_format($counts['users']))
                ->description('Active, pending, and suspended accounts')
                ->icon(Heroicon::OutlinedUsers)
                ->color('gray')
                ->url(AllUsers::getUrl()),
        ];
    }
}
