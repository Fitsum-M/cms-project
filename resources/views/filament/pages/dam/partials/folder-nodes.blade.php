@foreach ($nodes as $node)
    <li
        class="rounded-lg border border-transparent"
        style="margin-left: {{ $depth * 1.25 }}rem"
        role="treeitem"
        aria-level="{{ $depth + 1 }}"
        x-data="{ hasChildren: @js(! empty($node['children'])) }"
        :aria-expanded="hasChildren ? isExpanded({{ $node['id'] }}) : undefined"
    >
        <div
            class="flex items-center gap-2 rounded-lg border border-transparent px-2 py-1.5 transition hover:bg-gray-50 dark:hover:bg-white/5"
            @class([
                'cursor-grab active:cursor-grabbing' => $this->canManageFolders(),
            ])
            :class="{
                'border-primary-400 bg-primary-50/70 dark:border-primary-400/40 dark:bg-primary-400/10': dropTargetId === {{ $node['id'] }},
                'opacity-50': draggingId === {{ $node['id'] }},
            }"
            draggable="{{ $this->canManageFolders() ? 'true' : 'false' }}"
            @dragstart="onDragStart($event, {{ $node['id'] }})"
            @dragend="onDragEnd()"
            @dragover="onDragOver($event, {{ $node['id'] }})"
            @dragleave="onDragLeave($event, {{ $node['id'] }})"
            @drop="onDrop($event, {{ $node['id'] }})"
        >
            <button
                type="button"
                class="inline-flex size-6 shrink-0 items-center justify-center rounded text-gray-400 hover:bg-gray-100 hover:text-gray-700 dark:hover:bg-white/10 dark:hover:text-gray-200"
                x-show="hasChildren"
                x-cloak
                @click.stop="toggle({{ $node['id'] }})"
                :aria-expanded="isExpanded({{ $node['id'] }})"
                aria-label="Toggle subfolders"
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
                aria-hidden="true"
            ></span>

            <span
                class="inline-flex size-8 shrink-0 items-center justify-center rounded-md bg-primary-50 text-primary-600 dark:bg-primary-400/10 dark:text-primary-400"
                aria-hidden="true"
            >
                <x-filament::icon icon="heroicon-o-folder" class="size-5" />
            </span>

            <div class="min-w-0 flex-1">
                <div class="truncate text-sm font-medium text-gray-950 dark:text-white">
                    {{ $node['name'] }}
                </div>
                <div class="text-xs text-gray-500 dark:text-gray-400">
                    {{ $node['media_count'] }} {{ \Illuminate\Support\Str::plural('item', $node['media_count']) }}
                </div>
            </div>

            @if ($this->canManageFolders() || auth()->user()?->can(\App\Enums\Permission::MediaDelete->value))
                <div class="flex shrink-0 items-center gap-0.5" @mousedown.stop @dragstart.stop.prevent>
                    @if ($this->canManageFolders())
                        {{ ($this->renameFolderAction)(['folder' => $node['id']]) }}
                    @endif

                    @if (auth()->user()?->can(\App\Enums\Permission::MediaDelete->value))
                        {{ ($this->deleteFolderAction)(['folder' => $node['id']]) }}
                    @endif
                </div>
            @endif
        </div>

        @if (! empty($node['children']))
            <ul
                class="mt-0.5 space-y-0.5"
                role="group"
                x-show="isExpanded({{ $node['id'] }})"
                x-cloak
            >
                @include('filament.pages.dam.partials.folder-nodes', [
                    'nodes' => $node['children'],
                    'depth' => $depth + 1,
                ])
            </ul>
        @endif
    </li>
@endforeach
