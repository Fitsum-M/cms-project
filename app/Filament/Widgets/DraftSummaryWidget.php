<?php

namespace App\Filament\Widgets;

use App\Enums\Permission;
use App\Services\DashboardDraftSummaryService;
use App\Support\Dashboard\RecentContentItem;
use Filament\Widgets\Widget;
use Illuminate\Support\Collection;

/**
 * Dashboard Draft Summary — own drafts + pending review for Editor/Admin (SRS 10.3, D.03).
 */
class DraftSummaryWidget extends Widget
{
    protected static ?int $sort = 3;

    protected int | string | array $columnSpan = 'full';

    /**
     * @var view-string
     */
    protected string $view = 'filament.widgets.draft-summary';

    public static function canView(): bool
    {
        return auth()->user()?->can(Permission::DashboardView->value) ?? false;
    }

    /**
     * @return array{
     *     ownDrafts: Collection<int, RecentContentItem>,
     *     pendingReview: Collection<int, RecentContentItem>,
     *     showPendingReview: bool,
     * }
     */
    protected function getViewData(): array
    {
        $user = auth()->user();

        if (! $user) {
            return [
                'ownDrafts' => collect(),
                'pendingReview' => collect(),
                'showPendingReview' => false,
            ];
        }

        $summary = app(DashboardDraftSummaryService::class)->forUser($user);

        return [
            'ownDrafts' => $summary['own_drafts'],
            'pendingReview' => $summary['pending_review'],
            'showPendingReview' => $user->can(Permission::DashboardViewAllDrafts->value),
        ];
    }
}
