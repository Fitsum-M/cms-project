@php
    $roleName = $this->record;
    $roleColor = $this->getRoleColor();
    $roleIcon = $this->getRoleIcon();
    $grantedCount = $this->getGrantedCount();
    $totalCount = $this->getTotalCount();
    $coveragePercent = $this->getCoveragePercent();
@endphp

<x-filament-panels::page>
    <div class="space-y-6">
        <!-- Header Info Card -->
        <div class="overflow-hidden rounded-xl bg-white shadow-sm ring-1 ring-gray-950/5 dark:bg-white/5 dark:ring-white/10">
            <div class="border-b border-gray-100 bg-gradient-to-r from-gray-50 to-white px-6 py-5 dark:border-white/10 dark:from-white/5 dark:to-white/5">
                <div class="flex flex-col gap-5 lg:flex-row lg:items-center lg:justify-between">
                    <div class="flex items-start gap-4">
                        <span @class([
                            'inline-flex size-14 shrink-0 items-center justify-center rounded-2xl',
                            match ($roleColor) {
                                'danger' => 'bg-danger-50 text-danger-600 dark:bg-danger-400/10 dark:text-danger-400',
                                'warning' => 'bg-warning-50 text-warning-600 dark:bg-warning-400/10 dark:text-warning-400',
                                'info' => 'bg-info-50 text-info-600 dark:bg-info-400/10 dark:text-info-400',
                                default => 'bg-gray-100 text-gray-600 dark:bg-white/10 dark:text-gray-300',
                            },
                        ])>
                            <x-filament::icon :icon="$roleIcon" class="size-7" />
                        </span>

                        <div class="min-w-0">
                            <div class="flex flex-wrap items-center gap-2">
                                <h2 class="text-lg font-semibold text-gray-950 dark:text-white">
                                    {{ $roleName }}
                                </h2>
                                <x-filament::badge :color="$roleColor">
                                    {{ in_array($roleName, ['Administrator', 'Editor', 'Author', 'Contributor']) ? __('cms.iam.roles.system_role') : 'Custom Role' }}
                                </x-filament::badge>
                            </div>
                            <p class="mt-1 max-w-3xl text-sm leading-relaxed text-gray-600 dark:text-gray-300">
                                {{ $this->getRoleDescription() }}
                            </p>
                        </div>
                    </div>

                    <div class="shrink-0 rounded-xl border border-gray-200 bg-white px-5 py-4 dark:border-white/10 dark:bg-white/5">
                        <p class="text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">
                            {{ __('cms.iam.roles.coverage') }}
                        </p>
                        <p class="mt-1 text-2xl font-bold tabular-nums text-gray-950 dark:text-white">
                            {{ $grantedCount }}<span class="text-base font-medium text-gray-400">/{{ $totalCount }}</span>
                        </p>
                        <div class="mt-3 h-2 overflow-hidden rounded-full bg-gray-100 dark:bg-white/10">
                            <div
                                @class([
                                    'h-full rounded-full transition-all',
                                    match ($roleColor) {
                                        'danger' => 'bg-danger-500',
                                        'warning' => 'bg-warning-500',
                                        'info' => 'bg-info-500',
                                        default => 'bg-gray-450',
                                    },
                                ])
                                style="width: {{ $coveragePercent }}%"
                            ></div>
                        </div>
                        <p class="mt-2 text-xs text-gray-500 dark:text-gray-400">
                            {{ __('cms.iam.roles.coverage_detail', ['percent' => $coveragePercent]) }}
                        </p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Group Accordion List with Read-Only Checkboxes -->
        <x-filament::section>
            <x-slot name="heading">Role Capabilities Matrix</x-slot>
            <x-slot name="description">View capabilities permitted for this role. Click any module to expand/collapse its options.</x-slot>

            <div class="space-y-4" x-data="{ activeGroup: 'Dashboard' }">
                @foreach ($this->getGroupedCapabilities() as $section)
                    @php
                        $groupSlug = \Illuminate\Support\Str::slug($section['group']);
                    @endphp
                    <div class="border border-gray-200 dark:border-gray-850 rounded-xl overflow-hidden bg-white dark:bg-gray-900/50 transition">
                        <!-- Group Header Button -->
                        <button
                            type="button"
                            x-on:click="activeGroup = (activeGroup === '{{ $groupSlug }}' ? null : '{{ $groupSlug }}')"
                            style="display: flex; align-items: center; justify-content: space-between; width: 100%; border: none; outline: none; cursor: pointer;"
                            class="px-5 py-4 text-left font-semibold text-sm text-gray-950 dark:text-white bg-gray-50 dark:bg-white/5 hover:bg-gray-100 dark:hover:bg-white/10 transition duration-200"
                        >
                            <div style="display: flex; align-items: center; gap: 12px;">
                                <x-filament::icon icon="heroicon-o-folder" style="width: 20px; height: 20px; display: inline-block;" class="text-gray-400 dark:text-gray-500" />
                                <span>{{ $section['group'] }}</span>
                                <span style="font-weight: normal; font-size: 0.75rem;" class="text-gray-500 dark:text-gray-400">
                                    ({{ $section['granted_count'] }}/{{ $section['total_count'] }} enabled)
                                </span>
                            </div>
                            <div>
                                <svg
                                    style="width: 20px; height: 20px; transition: transform 0.2s;"
                                    :style="activeGroup === '{{ $groupSlug }}' ? 'transform: rotate(180deg);' : ''"
                                    class="text-gray-400 dark:text-gray-500"
                                    fill="none"
                                    viewBox="0 0 24 24"
                                    stroke="currentColor"
                                >
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                                </svg>
                            </div>
                        </button>

                        <!-- Group Capabilities List -->
                        <div
                            x-show="activeGroup === '{{ $groupSlug }}'"
                            x-collapse
                            class="p-5 border-t border-gray-200 dark:border-gray-800 space-y-3"
                        >
                            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-3">
                                @foreach ($section['capabilities'] as $capability)
                                    <div @class([
                                        'flex items-start gap-3 p-3 rounded-lg border transition',
                                        $capability['granted']
                                            ? 'border-success-200 bg-success-50/10 dark:border-success-500/20 dark:bg-success-500/5'
                                            : 'border-gray-150 bg-gray-50/10 dark:border-gray-800 dark:bg-white/5',
                                    ])>
                                        <input
                                            type="checkbox"
                                            @checked($capability['granted'])
                                            disabled
                                            class="rounded border-gray-300 dark:border-gray-700 text-success-600 dark:text-success-500 shadow-sm focus:ring-0 opacity-70 mt-0.5"
                                        >
                                        <div class="flex flex-col leading-tight">
                                            <span @class([
                                                'text-sm font-semibold',
                                                $capability['granted']
                                                    ? 'text-success-900 dark:text-success-400'
                                                    : 'text-gray-500 dark:text-gray-400',
                                            ])>
                                                {{ $capability['label'] }}
                                            </span>
                                            <span class="text-[10px] text-gray-450 dark:text-gray-500 mt-0.5">
                                                {{ $capability['value'] }}
                                            </span>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
        </x-filament::section>
    </div>
</x-filament-panels::page>
