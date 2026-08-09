<x-filament-panels::page>
    <div class="space-y-6">
        <p class="text-sm text-gray-600 dark:text-gray-400">
            {{ $this->getRoleDescription() }}
        </p>

        @foreach ($this->getGroupedCapabilities() as $section)
            <div class="fi-section rounded-xl bg-white shadow-sm ring-1 ring-gray-950/5 dark:bg-white/5 dark:ring-white/10">
                <div class="border-b border-gray-100 px-4 py-3 dark:border-white/10">
                    <h3 class="text-sm font-semibold text-gray-950 dark:text-white">{{ $section['group'] }}</h3>
                </div>
                <ul class="divide-y divide-gray-100 dark:divide-white/5">
                    @foreach ($section['capabilities'] as $capability)
                        <li class="flex items-center justify-between gap-4 px-4 py-2.5 text-sm">
                            <span class="text-gray-900 dark:text-gray-100">{{ $capability['label'] }}</span>
                            @if ($capability['granted'])
                                <span class="inline-flex rounded-md bg-success-50 px-2 py-0.5 text-xs font-medium text-success-700 dark:bg-success-400/10 dark:text-success-400">Yes</span>
                            @else
                                <span class="inline-flex rounded-md bg-gray-50 px-2 py-0.5 text-xs font-medium text-gray-500 dark:bg-white/5 dark:text-gray-400">No</span>
                            @endif
                        </li>
                    @endforeach
                </ul>
            </div>
        @endforeach
    </div>
</x-filament-panels::page>
