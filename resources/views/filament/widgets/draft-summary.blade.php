<x-filament-widgets::widget class="fi-wi-draft-summary">
    <x-filament::section
        :heading="__('cms.dashboard.draft_summary.heading')"
        :description="$showPendingReview ? __('cms.dashboard.draft_summary.description_with_review') : __('cms.dashboard.draft_summary.description')"
    >
        <div class="space-y-6">
            <div>
                <h3 class="mb-2 text-sm font-semibold text-gray-950 dark:text-white">
                    {{ __('cms.dashboard.draft_summary.my_drafts') }}
                </h3>

                @include('filament.widgets.partials.content-item-table', [
                    'items' => $ownDrafts,
                    'emptyMessage' => __('cms.dashboard.draft_summary.empty_own'),
                ])
            </div>

            @if ($showPendingReview)
                <div>
                    <h3 class="mb-2 text-sm font-semibold text-gray-950 dark:text-white">
                        {{ __('cms.dashboard.draft_summary.awaiting_review') }}
                    </h3>

                    @include('filament.widgets.partials.content-item-table', [
                        'items' => $pendingReview,
                        'emptyMessage' => __('cms.dashboard.draft_summary.empty_review'),
                    ])
                </div>
            @endif
        </div>
    </x-filament::section>
</x-filament-widgets::widget>
