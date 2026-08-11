<x-filament-panels::page>
    <div
        class="fi-section rounded-xl bg-white shadow-sm ring-1 ring-gray-950/5 dark:bg-white/5 dark:ring-white/10"
        x-data="{
            draggingId: null,
            canManage: @js($this->canManageFolders()),
            onDragStart(event, id) {
                if (! this.canManage) {
                    event.preventDefault()
                    return
                }
                this.draggingId = id
                event.dataTransfer.effectAllowed = 'move'
                event.dataTransfer.setData('text/plain', String(id))
            },
            onDragOver(event) {
                if (! this.canManage || this.draggingId === null) {
                    return
                }
                event.preventDefault()
                event.dataTransfer.dropEffect = 'move'
            },
            onDrop(event, parentId) {
                event.preventDefault()
                event.stopPropagation()
                if (! this.canManage || this.draggingId === null) {
                    return
                }
                const folderId = this.draggingId
                this.draggingId = null
                if (folderId === parentId) {
                    return
                }
                $wire.moveFolder(folderId, parentId)
            },
            onDropRoot(event) {
                event.preventDefault()
                if (! this.canManage || this.draggingId === null) {
                    return
                }
                const folderId = this.draggingId
                this.draggingId = null
                $wire.moveFolder(folderId, null)
            },
        }"
    >
        <div class="fi-section-content-ctn space-y-4 p-6">
            <div class="flex flex-col gap-1 sm:flex-row sm:items-end sm:justify-between">
                <div>
                    <h2 class="text-base font-semibold text-gray-950 dark:text-white">
                        Folder tree
                    </h2>
                    <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                        Nest folders without limit. Drag a folder onto another to move it, or onto Unfiled to make it top-level.
                    </p>
                </div>
            </div>

            <div
                class="rounded-lg border border-dashed border-gray-300 bg-gray-50/80 px-4 py-3 text-sm text-gray-600 dark:border-white/10 dark:bg-white/5 dark:text-gray-300"
                @dragover="onDragOver($event)"
                @drop="onDropRoot($event)"
            >
                Drop here to move a folder to the root (Unfiled parent).
            </div>
            @if (count($tree) === 0)
                <p class="text-sm text-gray-500 dark:text-gray-400">
                    No folders yet. Create one to organize the media library.
                </p>
            @else
                <div class="overflow-x-auto rounded-lg border border-gray-200 dark:border-white/10">
                    <table class="w-full text-left" role="tree">
                        <thead class="border-b border-gray-200 bg-gray-50 dark:border-white/10 dark:bg-white/5">
                            <tr>
                                <th class="px-4 py-2.5 text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">Name</th>
                                <th class="px-4 py-2.5 text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">Items</th>
                                <th class="px-4 py-2.5 text-right text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100 dark:divide-white/5">
                            @include('filament.pages.dam.partials.folder-nodes', ['nodes' => $tree, 'depth' => 0])
                        </tbody>
                    </table>
                </div>
            @endif
        </div>
    </div>

    <x-filament-actions::modals />
</x-filament-panels::page>
