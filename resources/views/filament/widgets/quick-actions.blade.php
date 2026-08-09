<x-filament-widgets::widget class="fi-wi-quick-actions">
    <x-filament::section
        heading="Quick Actions"
        description="Jump into common editorial tasks."
    >
        <div class="grid gap-3 sm:grid-cols-2 lg:grid-cols-3">
            @foreach ($actions as $action)
                <a
                    href="{{ $action['url'] }}"
                    class="fi-btn relative grid-flow-col items-center justify-center gap-1.5 rounded-lg bg-white px-4 py-3 text-sm font-semibold text-gray-950 shadow-sm ring-1 ring-gray-950/10 transition hover:bg-gray-50 dark:bg-white/5 dark:text-white dark:ring-white/20 dark:hover:bg-white/10"
                >
                    <span class="flex flex-col items-start gap-0.5 text-start">
                        <span>{{ $action['label'] }}</span>
                        <span class="text-xs font-normal text-gray-500 dark:text-gray-400">
                            {{ $action['description'] }}
                        </span>
                    </span>
                </a>
            @endforeach
        </div>
    </x-filament::section>
</x-filament-widgets::widget>
