<x-filament-panels::page>
    <div class="fi-section rounded-xl bg-white shadow-sm ring-1 ring-gray-950/5 dark:bg-white/5 dark:ring-white/10">
        <div class="fi-section-content-ctn space-y-4 p-6">
            <div>
                <h2 class="text-base font-semibold text-gray-950 dark:text-white">
                    Registered templates
                </h2>
                <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                    System-level frontend presentation variants. Assign a template when editing a page.
                    If none is selected, <strong>Default</strong> is assumed.
                </p>
            </div>

            <div class="grid gap-3 sm:grid-cols-2 lg:grid-cols-3">
                @foreach ($templates as $template)
                    <div class="rounded-xl border border-gray-200 p-4 dark:border-white/10">
                        <div class="flex items-start gap-3">
                            <span class="inline-flex size-10 shrink-0 items-center justify-center rounded-lg bg-primary-50 text-primary-600 dark:bg-primary-400/10 dark:text-primary-400">
                                <x-filament::icon :icon="$template['icon']" class="size-5" />
                            </span>
                            <div class="min-w-0">
                                <div class="flex flex-wrap items-center gap-2">
                                    <h3 class="text-sm font-semibold text-gray-950 dark:text-white">
                                        {{ $template['label'] }}
                                    </h3>
                                    @if ($template['key'] === \App\Support\PageTemplateRegistry::defaultKey())
                                        <span class="rounded-md bg-gray-100 px-1.5 py-0.5 text-[11px] font-medium text-gray-600 dark:bg-white/10 dark:text-gray-300">
                                            Default
                                        </span>
                                    @endif
                                </div>
                                <p class="mt-0.5 font-mono text-xs text-gray-500 dark:text-gray-400">
                                    {{ $template['key'] }}
                                </p>
                                @if (filled($template['description']))
                                    <p class="mt-2 text-sm text-gray-600 dark:text-gray-300">
                                        {{ $template['description'] }}
                                    </p>
                                @endif
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    </div>
</x-filament-panels::page>
