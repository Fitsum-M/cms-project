<x-filament-panels::page>
    <div class="space-y-6">
        <div class="rounded-xl border border-primary-200/70 bg-primary-50/40 px-5 py-4 dark:border-primary-500/20 dark:bg-primary-400/5">
            <div class="flex items-start gap-3">
                <span class="inline-flex size-9 shrink-0 items-center justify-center rounded-lg bg-primary-100 text-primary-600 dark:bg-primary-400/15 dark:text-primary-400">
                    <x-filament::icon icon="heroicon-o-information-circle" class="size-5" />
                </span>
                <div>
                    <h2 class="text-sm font-semibold text-gray-950 dark:text-white">
                        {{ __('cms.iam.roles.matrix_heading') }}
                    </h2>
                    <p class="mt-1 text-sm leading-relaxed text-gray-600 dark:text-gray-300">
                        {{ __('cms.iam.roles.matrix_description') }}
                    </p>
                </div>
            </div>
        </div>

        <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
            @foreach ($this->getRoleCards() as $card)
                @php
                    $role = $card['role'];
                @endphp

                <a
                    href="{{ $card['url'] }}"
                    class="group relative overflow-hidden rounded-xl border border-gray-200 bg-white p-5 shadow-sm transition hover:border-primary-300 hover:shadow-md dark:border-white/10 dark:bg-white/5 dark:hover:border-primary-500/40"
                >
                    <div class="flex items-start justify-between gap-3">
                        <span @class([
                            'inline-flex size-11 shrink-0 items-center justify-center rounded-xl',
                            match ($role->color()) {
                                'danger' => 'bg-danger-50 text-danger-600 dark:bg-danger-400/10 dark:text-danger-400',
                                'warning' => 'bg-warning-50 text-warning-600 dark:bg-warning-400/10 dark:text-warning-400',
                                'info' => 'bg-info-50 text-info-600 dark:bg-info-400/10 dark:text-info-400',
                                default => 'bg-gray-100 text-gray-600 dark:bg-white/10 dark:text-gray-300',
                            },
                        ])>
                            <x-filament::icon :icon="$role->icon()" class="size-5" />
                        </span>

                        <x-filament::badge :color="$role->color()">
                            {{ $card['coverage_percent'] }}%
                        </x-filament::badge>
                    </div>

                    <h3 class="mt-4 text-base font-semibold text-gray-950 group-hover:text-primary-600 dark:text-white dark:group-hover:text-primary-400">
                        {{ $role->value }}
                    </h3>

                    <p class="mt-1 line-clamp-3 text-sm leading-relaxed text-gray-500 dark:text-gray-400">
                        {{ $role->description() }}
                    </p>

                    <div class="mt-4 flex items-center justify-between gap-3">
                        <p class="text-xs font-medium text-gray-500 dark:text-gray-400">
                            {{ __('cms.iam.roles.permissions_count', [
                                'granted' => $card['granted_count'],
                                'total' => $card['total_count'],
                            ]) }}
                        </p>

                        <span class="inline-flex items-center gap-1 text-xs font-semibold text-primary-600 dark:text-primary-400">
                            {{ __('cms.iam.roles.view_details') }}
                            <x-filament::icon icon="heroicon-m-arrow-right" class="size-3.5 transition group-hover:translate-x-0.5" />
                        </span>
                    </div>

                    <div class="mt-3 h-1.5 overflow-hidden rounded-full bg-gray-100 dark:bg-white/10">
                        <div
                            @class([
                                'h-full rounded-full',
                                match ($role->color()) {
                                    'danger' => 'bg-danger-500',
                                    'warning' => 'bg-warning-500',
                                    'info' => 'bg-info-500',
                                    default => 'bg-gray-400',
                                },
                            ])
                            style="width: {{ $card['coverage_percent'] }}%"
                        ></div>
                    </div>
                </a>
            @endforeach
        </div>

        <x-filament::section :heading="__('cms.iam.roles.comparison_heading')">
            <div class="overflow-hidden rounded-xl ring-1 ring-gray-950/5 dark:ring-white/10">
                <div class="overflow-x-auto">
                    <table class="w-full min-w-[56rem] text-sm">
                        <thead>
                            <tr class="border-b border-gray-200 bg-gray-50 dark:border-white/10 dark:bg-white/5">
                                <th class="sticky left-0 z-10 bg-gray-50 px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500 dark:bg-gray-950 dark:text-gray-400">
                                    {{ __('cms.iam.roles.capability') }}
                                </th>
                                @foreach ($this->getRoleNames() as $roleName)
                                    <th class="px-4 py-3 text-center text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">
                                        {{ $roleName }}
                                    </th>
                                @endforeach
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100 dark:divide-white/5">
                            @php $lastGroup = null; @endphp
                            @foreach ($this->getMatrixRows() as $row)
                                @if ($lastGroup !== $row['group'])
                                    @php $lastGroup = $row['group']; @endphp
                                    <tr class="bg-gray-50/80 dark:bg-white/5">
                                        <td
                                            colspan="{{ count($this->getRoleNames()) + 1 }}"
                                            class="px-4 py-2.5 text-xs font-bold uppercase tracking-wider text-gray-500 dark:text-gray-400"
                                        >
                                            {{ $row['group'] }}
                                        </td>
                                    </tr>
                                @endif
                                <tr class="transition hover:bg-gray-50/60 dark:hover:bg-white/5">
                                    <td class="sticky left-0 z-10 bg-white px-4 py-2.5 font-medium text-gray-900 dark:bg-gray-950 dark:text-gray-100">
                                        {{ $row['capability'] }}
                                    </td>
                                    @foreach ($this->getRoleNames() as $roleName)
                                        <td class="px-4 py-2.5 text-center">
                                            @include('filament.pages.iam.partials.permission-status', [
                                                'granted' => $row['roles'][$roleName] ?? false,
                                                'compact' => true,
                                            ])
                                        </td>
                                    @endforeach
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </x-filament::section>
    </div>
</x-filament-panels::page>
