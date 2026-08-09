@php
    $role = $this::role();
    $grantedCount = $this->getGrantedCount();
    $totalCount = $this->getTotalCount();
    $coveragePercent = $this->getCoveragePercent();
@endphp

<x-filament-panels::page>
    <div class="space-y-6">
        <div class="overflow-hidden rounded-xl bg-white shadow-sm ring-1 ring-gray-950/5 dark:bg-white/5 dark:ring-white/10">
            <div class="border-b border-gray-100 bg-gradient-to-r from-gray-50 to-white px-6 py-5 dark:border-white/10 dark:from-white/5 dark:to-white/5">
                <div class="flex flex-col gap-5 lg:flex-row lg:items-center lg:justify-between">
                    <div class="flex items-start gap-4">
                        <span @class([
                            'inline-flex size-14 shrink-0 items-center justify-center rounded-2xl',
                            match ($role->color()) {
                                'danger' => 'bg-danger-50 text-danger-600 dark:bg-danger-400/10 dark:text-danger-400',
                                'warning' => 'bg-warning-50 text-warning-600 dark:bg-warning-400/10 dark:text-warning-400',
                                'info' => 'bg-info-50 text-info-600 dark:bg-info-400/10 dark:text-info-400',
                                default => 'bg-gray-100 text-gray-600 dark:bg-white/10 dark:text-gray-300',
                            },
                        ])>
                            <x-filament::icon :icon="$role->icon()" class="size-7" />
                        </span>

                        <div class="min-w-0">
                            <div class="flex flex-wrap items-center gap-2">
                                <h2 class="text-lg font-semibold text-gray-950 dark:text-white">
                                    {{ $role->value }}
                                </h2>
                                <x-filament::badge :color="$role->color()">
                                    {{ __('cms.iam.roles.system_role') }}
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
                                    match ($role->color()) {
                                        'danger' => 'bg-danger-500',
                                        'warning' => 'bg-warning-500',
                                        'info' => 'bg-info-500',
                                        default => 'bg-gray-400',
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

        @foreach ($this->getGroupedCapabilities() as $section)
            <x-filament::section
                :heading="$section['group']"
                :description="__('cms.iam.roles.group_coverage', [
                    'granted' => $section['granted_count'],
                    'total' => $section['total_count'],
                ])"
            >
                <div class="grid gap-2 sm:grid-cols-2 xl:grid-cols-3">
                    @foreach ($section['capabilities'] as $capability)
                        <div @class([
                            'flex items-center gap-3 rounded-xl border px-3 py-2.5 transition',
                            $capability['granted']
                                ? 'border-success-200/80 bg-success-50/40 dark:border-success-400/20 dark:bg-success-400/5'
                                : 'border-gray-200 bg-gray-50/50 dark:border-white/10 dark:bg-white/5',
                        ])>
                            <span @class([
                                'inline-flex size-8 shrink-0 items-center justify-center rounded-lg',
                                $capability['granted']
                                    ? 'bg-success-100 text-success-600 dark:bg-success-400/15 dark:text-success-400'
                                    : 'bg-gray-100 text-gray-400 dark:bg-white/5 dark:text-gray-500',
                            ])>
                                <x-filament::icon
                                    :icon="$capability['granted'] ? 'heroicon-m-check' : 'heroicon-m-x-mark'"
                                    class="size-4"
                                />
                            </span>

                            <span @class([
                                'min-w-0 flex-1 text-sm',
                                $capability['granted']
                                    ? 'font-medium text-gray-900 dark:text-gray-100'
                                    : 'text-gray-500 dark:text-gray-400',
                            ])>
                                {{ $capability['label'] }}
                            </span>
                        </div>
                    @endforeach
                </div>
            </x-filament::section>
        @endforeach
    </div>
</x-filament-panels::page>
