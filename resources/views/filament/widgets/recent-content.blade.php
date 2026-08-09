<x-filament-widgets::widget class="fi-wi-recent-content">
    <x-filament::section
        :heading="__('cms.dashboard.recent_content.heading')"
        :description="__('cms.dashboard.recent_content.description', ['count' => \App\Services\DashboardRecentContentService::LIMIT])"
    >
        @include('filament.widgets.partials.content-item-table', [
            'items' => $items,
            'emptyMessage' => __('cms.dashboard.recent_content.empty'),
        ])
    </x-filament::section>
</x-filament-widgets::widget>
