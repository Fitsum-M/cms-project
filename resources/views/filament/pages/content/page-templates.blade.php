<x-filament-panels::page>
    <div class="fi-section overflow-hidden rounded-xl bg-white shadow-sm ring-1 ring-gray-950/5 dark:bg-white/5 dark:ring-white/10">
        <div class="fi-section-content-ctn space-y-6 p-6">
            <div class="max-w-2xl">
                <h2 class="text-base font-semibold text-gray-950 dark:text-white">
                    Registered templates
                </h2>
                <p class="mt-1 text-sm leading-6 text-gray-500 dark:text-gray-400">
                    System-level frontend presentation variants. Assign a template when editing a page.
                    If none is selected, <span class="font-medium text-gray-700 dark:text-gray-200">Default</span> is assumed.
                </p>
            </div>

            <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-3">
                @foreach ($templates as $template)
                    @php
                        $isDefault = $template['key'] === \App\Support\PageTemplateRegistry::defaultKey();
                    @endphp
                    <div
                        @class([
                            'group relative flex h-full flex-col rounded-xl border p-5 transition',
                            'border-primary-200 bg-primary-50/40 ring-1 ring-primary-500/10 dark:border-primary-400/30 dark:bg-primary-400/5 dark:ring-primary-400/20' => $isDefault,
                            'border-gray-200 bg-white hover:border-gray-300 hover:bg-gray-50/80 dark:border-white/10 dark:bg-transparent dark:hover:border-white/20 dark:hover:bg-white/5' => ! $isDefault,
                        ])
                    >
                        <div class="flex items-start gap-3">
                            <span
                                @class([
                                    'inline-flex size-11 shrink-0 items-center justify-center rounded-xl ring-1 ring-inset',
                                    'bg-primary-100 text-primary-700 ring-primary-600/10 dark:bg-primary-400/15 dark:text-primary-300 dark:ring-primary-400/20' => $isDefault,
                                    'bg-gray-50 text-gray-600 ring-gray-500/10 group-hover:bg-white dark:bg-white/10 dark:text-gray-300 dark:ring-white/10' => ! $isDefault,
                                ])
                            >
                                <x-filament::icon :icon="$template['icon']" class="size-5" />
                            </span>

                            <div class="min-w-0 flex-1">
                                <div class="flex flex-wrap items-center gap-2">
                                    <h3 class="text-sm font-semibold text-gray-950 dark:text-white">
                                        {{ $template['label'] }}
                                    </h3>
                                    @if ($isDefault)
                                        <span class="rounded-md bg-primary-100 px-1.5 py-0.5 text-[11px] font-medium text-primary-700 ring-1 ring-inset ring-primary-600/15 dark:bg-primary-400/15 dark:text-primary-300 dark:ring-primary-400/25">
                                            Default
                                        </span>
                                    @endif
                                </div>
                                <p class="mt-1 font-mono text-xs text-gray-500 dark:text-gray-400">
                                    {{ $template['key'] }}
                                </p>
                            </div>
                        </div>

                        @if (filled($template['description']))
                            <p class="mt-4 text-sm leading-6 text-gray-600 dark:text-gray-300">
                                {{ $template['description'] }}
                            </p>
                        @endif
                    </div>
                @endforeach
            </div>
        </div>
    </div>
</x-filament-panels::page>
