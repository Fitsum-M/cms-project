@foreach ($nodes as $node)
    <li
        class="group/node relative bg-white dark:bg-transparent"
        role="treeitem"
        x-data="{ hasChildren: @js(! empty($node['children'])) }"
        :class="{ 'opacity-50': draggingId === {{ $node['id'] }} }"
    >
        @if ($this->canManageTree())
            <div
                class="absolute inset-x-3 top-0 z-10 h-1.5 -translate-y-1/2 rounded-full transition"
                :class="dropTarget === 'before-{{ $node['id'] }}' ? 'bg-primary-500' : 'bg-transparent'"
                @dragover="onDragOver($event, 'before-{{ $node['id'] }}')"
                @dragleave="onDragLeave('before-{{ $node['id'] }}')"
                @drop="onDropBefore($event, {{ $node['id'] }})"
                title="Drop to place before this page"
            ></div>
        @endif

        <div
            class="flex items-center gap-2 px-3 py-2.5 transition"
            style="padding-left: {{ 0.75 + ($depth * 1.5) }}rem"
            @class([
                'cursor-grab active:cursor-grabbing' => $this->canManageTree(),
            ])
            :class="dropTarget === 'nest-{{ $node['id'] }}' ? 'bg-primary-50 dark:bg-primary-400/10' : 'hover:bg-gray-50 dark:hover:bg-white/5'"
            draggable="{{ $this->canManageTree() ? 'true' : 'false' }}"
            @dragstart="onDragStart($event, {{ $node['id'] }})"
            @dragend="onDragEnd()"
            @dragover="onDragOver($event, 'nest-{{ $node['id'] }}')"
            @dragleave="onDragLeave('nest-{{ $node['id'] }}')"
            @drop="onDropNest($event, {{ $node['id'] }})"
        >
            <button
                type="button"
                class="inline-flex size-7 shrink-0 items-center justify-center rounded-md text-gray-400 transition hover:bg-gray-100 hover:text-gray-700 dark:hover:bg-white/10 dark:hover:text-gray-200"
                x-show="hasChildren"
                x-cloak
                @click.stop="toggle({{ $node['id'] }})"
                :aria-expanded="isExpanded({{ $node['id'] }})"
                aria-label="Toggle child pages"
            >
                <x-filament::icon
                    icon="heroicon-m-chevron-right"
                    class="size-4 transition"
                    x-bind:class="isExpanded({{ $node['id'] }}) ? 'rotate-90' : ''"
                />
            </button>
            <span
                class="inline-flex size-7 shrink-0"
                x-show="! hasChildren"
                aria-hidden="true"
            ></span>

            <span
                @class([
                    'inline-flex size-9 shrink-0 items-center justify-center rounded-lg ring-1 ring-inset',
                    match ($node['status_color']) {
                        'success' => 'bg-emerald-50 text-emerald-600 ring-emerald-600/10 dark:bg-emerald-400/10 dark:text-emerald-400 dark:ring-emerald-400/20',
                        'warning' => 'bg-amber-50 text-amber-600 ring-amber-600/10 dark:bg-amber-400/10 dark:text-amber-400 dark:ring-amber-400/20',
                        'slate' => 'bg-slate-100 text-slate-600 ring-slate-500/10 dark:bg-slate-400/10 dark:text-slate-300 dark:ring-slate-400/20',
                        default => 'bg-gray-50 text-gray-500 ring-gray-500/10 dark:bg-white/10 dark:text-gray-300 dark:ring-white/10',
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
                        class="truncate text-sm font-semibold text-gray-950 hover:text-primary-600 dark:text-white dark:hover:text-primary-400"
                        wire:navigate
                        @mousedown.stop
                        @dragstart.stop.prevent
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
                <div class="mt-0.5 truncate text-xs text-gray-500 dark:text-gray-400">
                    <span class="font-mono">/{{ $node['slug'] }}</span>
                    <span class="mx-1 text-gray-300 dark:text-gray-600">·</span>
                    {{ $node['template_label'] ?? 'Default' }}
                </div>
            </div>

            @if ($this->canManageTree())
                <span
                    class="inline-flex size-8 shrink-0 items-center justify-center rounded-md text-gray-300 opacity-0 transition group-hover/node:opacity-100 dark:text-gray-600"
                    aria-hidden="true"
                    title="Drag to reorder"
                >
                    <x-filament::icon icon="heroicon-m-bars-2" class="size-4" />
                </span>
            @endif
        </div>

        @if (! empty($node['children']))
            <ul
                class="divide-y divide-gray-100 border-t border-gray-100 dark:divide-white/5 dark:border-white/5"
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
