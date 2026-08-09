<?php

namespace App\Services;

/**
 * Lightweight readiness probe for Dashboard load budget (SRS 6 / 19.1 / D.05).
 *
 * Keeps overview + feed queries warm/cheap so the Filament Dashboard stays under 2s.
 */
class DashboardPerformanceService
{
    /** Soft ceiling from SRS Section 6 success metrics (milliseconds). */
    public const MAX_LOAD_MS = 2000;

    public function __construct(
        private readonly DashboardOverviewService $overview,
        private readonly DashboardRecentContentService $recent,
        private readonly DashboardDraftSummaryService $drafts,
    ) {}

    /**
     * Run the same aggregate work the dashboard widgets perform for a user.
     *
     * @return array{elapsed_ms: float, within_budget: bool}
     */
    public function measureWarmQueries(\App\Models\User $user): array
    {
        $started = hrtime(true);

        $this->overview->counts();
        $this->recent->forUser($user);
        $this->drafts->forUser($user);

        $elapsedMs = (hrtime(true) - $started) / 1_000_000;

        return [
            'elapsed_ms' => $elapsedMs,
            'within_budget' => $elapsedMs < self::MAX_LOAD_MS,
        ];
    }
}
