@foreach ($nodes as $node)
    <li
        class="rounded-lg border border-transparent px-2 py-1.5 hover:border-gray-200 hover:bg-gray-50 dark:hover:border-white/10 dark:hover:bg-white/5"
        style="margin-left: {{ $depth * 1.25 }}rem"
        role="treeitem"
        draggable="{{ $this->canManageFolders() ? 'true' : 'false' }}"
        @dragstart="onDragStart($event, {{ $node['id'] }})"
        @dragover="onDragOver($event)"
        @drop="onDrop($event, {{ $node['id'] }})"
    >
        <div class="flex items-center gap-2">
            <span class="inline-flex size-8 items-center justify-center rounded-md bg-primary-50 text-primary-600 dark:bg-primary-400/10 dark:text-primary-400" aria-hidden="true">
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

            <div class="flex items-center gap-1">
                @if ($this->canManageFolders())
                    {{ ($this->renameFolderAction)(['folder' => $node['id']]) }}
                @endif

                @if (auth()->user()?->can(\App\Enums\Permission::MediaDelete->value))
                    {{ ($this->deleteFolderAction)(['folder' => $node['id']]) }}
                @endif
            </div>
        </div>
    </li>

    @if (! empty($node['children']))
        @include('filament.pages.dam.partials.folder-nodes', ['nodes' => $node['children'], 'depth' => $depth + 1])
    @endif
@endforeach
