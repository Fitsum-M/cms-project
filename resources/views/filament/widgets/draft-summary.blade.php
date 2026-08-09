<x-filament-widgets::widget class="fi-wi-draft-summary">
    <x-filament::section
        heading="Draft Summary"
        description="Your drafts{{ $showPendingReview ? ' and content awaiting review' : '' }}."
    >
        <div class="space-y-6">
            <div>
                <h3 class="mb-2 text-sm font-semibold text-gray-950 dark:text-white">
                    My drafts
                </h3>

                @include('filament.widgets.partials.content-item-table', [
                    'items' => $ownDrafts,
                    'emptyMessage' => 'You have no drafts.',
                ])
            </div>

            @if ($showPendingReview)
                <div>
                    <h3 class="mb-2 text-sm font-semibold text-gray-950 dark:text-white">
                        Awaiting review
                    </h3>

                    @include('filament.widgets.partials.content-item-table', [
                        'items' => $pendingReview,
                        'emptyMessage' => 'No content is awaiting review.',
                    ])
                </div>
            @endif
        </div>
    </x-filament::section>
</x-filament-widgets::widget>
