<?php

namespace App\Filament\Widgets;

use App\Enums\Permission;
use App\Services\DashboardRecentContentService;
use App\Support\Dashboard\RecentContentItem;
use Filament\Widgets\Widget;
use Illuminate\Support\Collection;

/**
 * Dashboard Recent Content — last 10 edited posts/pages (SRS 10.3, D.02).
 *
 * Users with {@see Permission::DashboardViewRecentAll} see everyone’s edits;
 * Authors/Contributors see only their own (and pages only when they can view pages).
 */
class RecentContentWidget extends Widget
{
    protected static ?int $sort = 2;

    protected int | string | array $columnSpan = 'full';

    /**
     * @var view-string
     */
    protected string $view = 'filament.widgets.recent-content';

    public static function canView(): bool
    {
        return auth()->user()?->can(Permission::DashboardView->value) ?? false;
    }

    /**
     * @return array{items: Collection<int, RecentContentItem>}
     */
    protected function getViewData(): array
    {
        $user = auth()->user();

        return [
            'items' => $user
                ? app(DashboardRecentContentService::class)->forUser($user)
                : collect(),
        ];
    }
}
