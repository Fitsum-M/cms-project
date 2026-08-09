<x-filament-panels::page>
    {{-- Fallback only: hubs redirect in mount() before this should render. --}}
    <div class="fi-section rounded-xl bg-white shadow-sm ring-1 ring-gray-950/5 dark:bg-white/5 dark:ring-white/10">
        <div class="fi-section-content-ctn p-6">
            <h2 class="text-base font-semibold text-gray-950 dark:text-white">
                {{ static::$title ?? static::getNavigationLabel() }}
            </h2>
            <p class="mt-2 text-sm text-gray-500 dark:text-gray-400">
                Opening the related management screen…
            </p>
        </div>
    </div>
</x-filament-panels::page>
