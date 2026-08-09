@foreach ($nodes as $node)
    <li
        class="rounded-lg border border-transparent"
        style="margin-left: {{ $depth * 1.25 }}rem"
        role="treeitem"
        x-data="{ hasChildren: @js(! empty($node['children'])) }"
    >
        @if ($this->canManageTree())
            <div
                class="mx-2 h-1 rounded bg-transparent transition hover:bg-primary-400/40"
                @dragover="onDragOver($event)"
                @drop="onDropBefore($event, {{ $node['id'] }})"
                title="Drop to place before this page"
            ></div>
        @endif

        <div
            class="flex items-center gap-2 rounded-lg px-2 py-1.5 hover:border-gray-200 hover:bg-gray-50 dark:hover:bg-white/5"
            @class([
                'cursor-grab' => $this->canManageTree(),
            ])
            draggable="{{ $this->canManageTree() ? 'true' : 'false' }}"
            @dragstart="onDragStart($event, {{ $node['id'] }})"
            @dragover="onDragOver($event)"
            @drop="onDropNest($event, {{ $node['id'] }})"
        >
            <button
                type="button"
                class="inline-flex size-6 shrink-0 items-center justify-center rounded text-gray-400 hover:bg-gray-100 hover:text-gray-700 dark:hover:bg-white/10 dark:hover:text-gray-200"
                x-show="hasChildren"
                x-cloak
                @click.stop="toggle({{ $node['id'] }})"
                :aria-expanded="isExpanded({{ $node['id'] }})"
            >
                <x-filament::icon
                    icon="heroicon-m-chevron-right"
                    class="size-4 transition"
                    x-bind:class="isExpanded({{ $node['id'] }}) ? 'rotate-90' : ''"
                />
            </button>
            <span
                class="inline-flex size-6 shrink-0"
                x-show="! hasChildren"
            ></span>

            <span
                @class([
                    'inline-flex size-8 shrink-0 items-center justify-center rounded-md',
                    match ($node['status_color']) {
                        'success' => 'bg-emerald-50 text-emerald-600 dark:bg-emerald-400/10 dark:text-emerald-400',
                        'warning' => 'bg-amber-50 text-amber-600 dark:bg-amber-400/10 dark:text-amber-400',
                        'slate' => 'bg-slate-100 text-slate-600 dark:bg-slate-400/10 dark:text-slate-300',
                        default => 'bg-gray-100 text-gray-600 dark:bg-white/10 dark:text-gray-300',
                    },
                ])
                aria-hidden="true"
                title="{{ $node['template_label'] ?? 'Default' }}"
            >
                <x-filament::icon :icon="$node['template_icon']" class="size-5" />
            </span>

            <div class="min-w-0 flex-1">
                <div class="flex flex-wrap items-center gap-2">
                    <a
                        href="{{ $node['edit_url'] }}"
                        class="truncate text-sm font-medium text-primary-600 hover:underline dark:text-primary-400"
                        wire:navigate
                    >
                        {{ $node['title'] }}
                    </a>

                    <span
                        @class([
                            'inline-flex items-center rounded-md px-1.5 py-0.5 text-[11px] font-medium ring-1 ring-inset',
                            match ($node['status_color']) {
                                'success' => 'bg-emerald-50 text-emerald-700 ring-emerald-600/20 dark:bg-emerald-400/10 dark:text-emerald-300 dark:ring-emerald-400/20',
                                'warning' => 'bg-amber-50 text-amber-800 ring-amber-600/20 dark:bg-amber-400/10 dark:text-amber-300 dark:ring-amber-400/20',
                                'slate' => 'bg-slate-50 text-slate-700 ring-slate-500/20 dark:bg-slate-400/10 dark:text-slate-300 dark:ring-slate-400/20',
                                default => 'bg-gray-50 text-gray-600 ring-gray-500/10 dark:bg-white/5 dark:text-gray-300 dark:ring-white/10',
                            },
                        ])
                    >
                        <span
                            @class([
                                'mr-1 size-1.5 rounded-full',
                                match ($node['status_color']) {
                                    'success' => 'bg-emerald-500',
                                    'warning' => 'bg-amber-400',
                                    'slate' => 'bg-slate-500',
                                    default => 'bg-gray-400',
                                },
                            ])
                        ></span>
                        {{ $node['status_label'] }}
                    </span>

                    @if ($node['show_in_navigation'] ?? false)
                        <span class="inline-flex items-center gap-1 rounded-md bg-sky-50 px-1.5 py-0.5 text-[11px] font-medium text-sky-700 ring-1 ring-inset ring-sky-600/20 dark:bg-sky-400/10 dark:text-sky-300 dark:ring-sky-400/20">
                            <x-filament::icon icon="heroicon-m-bars-3" class="size-3" />
                            Nav
                        </span>
                    @endif
                </div>
                <div class="truncate text-xs text-gray-500 dark:text-gray-400">
                    /{{ $node['slug'] }}
                    · {{ $node['template_label'] ?? 'Default' }}
                </div>
            </div>
        </div>

        @if (! empty($node['children']))
            <ul
                class="space-y-0.5"
                role="group"
                x-show="isExpanded({{ $node['id'] }})"
                x-cloak
            >
                @include('filament.pages.content.partials.page-nodes', [
                    'nodes' => $node['children'],
                    'depth' => $depth + 1,
                ])
            </ul>
        @endif
    </li>
@endforeach
