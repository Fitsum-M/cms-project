@foreach ($nodes as $node)
    <tr
        class="fi-ta-row fi-clickable"
        role="row"
        aria-level="{{ $depth + 1 }}"
        x-data="{ hasChildren: @js(! empty($node['children'])) }"
        x-show="isRowVisible({{ $node['id'] }})"
        x-cloak
        :aria-expanded="hasChildren ? isExpanded({{ $node['id'] }}) : undefined"
        @class([
            'cursor-grab active:cursor-grabbing' => $this->canManageFolders(),
        ])
        :class="{
            'bg-primary-50/70 dark:bg-primary-400/10': dropTargetId === {{ $node['id'] }},
            'opacity-50': draggingId === {{ $node['id'] }},
        }"
        draggable="{{ $this->canManageFolders() ? 'true' : 'false' }}"
        @dragstart="onDragStart($event, {{ $node['id'] }})"
        @dragend="onDragEnd()"
        @dragover="onDragOver($event, {{ $node['id'] }})"
        @dragleave="onDragLeave($event, {{ $node['id'] }})"
        @drop="onDrop($event, {{ $node['id'] }})"
    >
        <td class="fi-ta-cell" role="gridcell">
            <div class="fi-ta-col">
                <div class="fi-ta-text" style="padding-left: {{ 0.75 + ($depth * 1.25) }}rem">
                    <div class="flex min-w-0 items-center gap-2">
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

                        <span class="truncate text-sm leading-6 font-medium text-gray-950 dark:text-white">
                            {{ $node['name'] }}
                        </span>
                    </div>
                </div>
            </div>
        </td>

        <td class="fi-ta-cell" role="gridcell">
            <div class="fi-ta-col">
                <div class="fi-ta-text">
                    <span class="text-sm leading-6 text-gray-500 dark:text-gray-400">
                        {{ $node['media_count'] }} {{ \Illuminate\Support\Str::plural('item', $node['media_count']) }}
                    </span>
                </div>
            </div>
        </td>

        <td class="fi-ta-cell whitespace-nowrap" role="gridcell">
            <div class="fi-ta-actions justify-end gap-4 pe-2 sm:pe-3" @mousedown.stop @dragstart.stop.prevent>
                @if ($this->canManageFolders())
                    {{ ($this->renameFolderAction)(['folder' => $node['id']]) }}
                @endif

                @if (auth()->user()?->can(\App\Enums\Permission::MediaDelete->value))
                    {{ ($this->deleteFolderAction)(['folder' => $node['id']]) }}
                @endif
            </div>
        </td>
    </tr>

    @if (! empty($node['children']))
        @include('filament.pages.dam.partials.folder-nodes', [
            'nodes' => $node['children'],
            'depth' => $depth + 1,
        ])
    @endif
@endforeach
