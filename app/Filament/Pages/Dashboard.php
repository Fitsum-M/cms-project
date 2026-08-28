<?php

namespace App\Filament\Pages;

use App\Enums\Permission;
use App\Filament\Widgets\DraftSummaryWidget;
use App\Filament\Widgets\OverviewStatsWidget;
use App\Filament\Widgets\QuickActionsWidget;
use App\Filament\Widgets\RecentContentWidget;
use Filament\Pages\Dashboard as BaseDashboard;
use Filament\Widgets\AccountWidget;

/**
 * CMS Dashboard (SRS §10.1 / §10.3 / §20.1).
 *
 * GAP.NAV.01 — Accepted mapping: Filament does not use nested sidebar children
 * for Dashboard sections. The §10.1 children map to on-page widgets instead:
 *
 * | §10.1 child      | Implementation              |
 * |------------------|-----------------------------|
 * | Overview         | OverviewStatsWidget         |
 * | Recent Content   | RecentContentWidget         |
 * | Draft Summary    | DraftSummaryWidget          |
 * | Quick Actions    | QuickActionsWidget          |
 *
 * This matches §10.3 and §20.1, which describe these as Dashboard widgets.
 */
class Dashboard extends BaseDashboard
{
    protected static ?string $navigationLabel = null;

    protected static ?string $title = null;

    public static function canAccess(): bool
    {
        return auth()->user()?->can(Permission::DashboardView->value) ?? false;
    }

    public static function getNavigationLabel(): string
    {
        return __('cms.navigation.dashboard');
    }

    public function getTitle(): string|\Illuminate\Contracts\Support\Htmlable
    {
        return __('cms.navigation.dashboard');
    }

    /**
     * @return array<class-string>
     */
    public function getWidgets(): array
    {
        return [
            AccountWidget::class,
            OverviewStatsWidget::class,
            RecentContentWidget::class,
            DraftSummaryWidget::class,
            QuickActionsWidget::class,
        ];
    }
}
