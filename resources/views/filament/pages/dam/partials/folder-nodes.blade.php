@foreach ($nodes as $node)
    <tr
        class="hover:bg-gray-50 dark:hover:bg-white/5"
        role="treeitem"
        draggable="{{ $this->canManageFolders() ? 'true' : 'false' }}"
        @dragstart="onDragStart($event, {{ $node['id'] }})"
        @dragover="onDragOver($event)"
        @drop="onDrop($event, {{ $node['id'] }})"
    >
        <td class="px-4 py-2.5">
            <div class="flex items-center gap-2" style="padding-left: {{ $depth * 1.5 }}rem">
                <span class="inline-flex size-7 shrink-0 items-center justify-center rounded-md bg-primary-50 text-primary-600 dark:bg-primary-400/10 dark:text-primary-400" aria-hidden="true">
                    <x-filament::icon icon="heroicon-o-folder" class="size-4" />
                </span>
                <span class="truncate text-sm font-medium text-gray-950 dark:text-white">
                    {{ $node['name'] }}
                </span>
            </div>
        </td>

        <td class="px-4 py-2.5 text-sm text-gray-500 dark:text-gray-400">
            {{ $node['media_count'] }} {{ \Illuminate\Support\Str::plural('item', $node['media_count']) }}
        </td>

        <td class="px-4 py-2.5">
            <div class="flex items-center justify-end gap-1">
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
        @include('filament.pages.dam.partials.folder-nodes', ['nodes' => $node['children'], 'depth' => $depth + 1])
    @endif
@endforeach