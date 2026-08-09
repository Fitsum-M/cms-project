<x-filament-widgets::widget class="fi-wi-recent-content">
    <x-filament::section
        heading="Recent Content"
        description="Last {{ \App\Services\DashboardRecentContentService::LIMIT }} edited posts and pages."
    >
        @include('filament.widgets.partials.content-item-table', [
            'items' => $items,
            'emptyMessage' => 'No recently edited content yet.',
        ])
    </x-filament::section>
</x-filament-widgets::widget>
