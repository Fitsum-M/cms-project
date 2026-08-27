<x-filament-panels::page>
    <div
        class="fi-section rounded-xl bg-white shadow-sm ring-1 ring-gray-950/5 dark:bg-white/5 dark:ring-white/10"
        x-data="{
            draggingId: null,
            dropTargetId: null,
            canManage: @js($this->canManageFolders()),
            expanded: {},
            isExpanded(id) {
                return this.expanded[id] !== false
            },
            toggle(id) {
                this.expanded[id] = ! this.isExpanded(id)
            },
            onDragStart(event, id) {
                if (! this.canManage) {
                    event.preventDefault()
                    return
                }
                this.draggingId = id
                event.dataTransfer.effectAllowed = 'move'
                event.dataTransfer.setData('text/plain', String(id))
            },
            onDragEnd() {
                this.draggingId = null
                this.dropTargetId = null
            },
            onDragOver(event, targetId = null) {
                if (! this.canManage || this.draggingId === null) {
                    return
                }
                event.preventDefault()
                event.dataTransfer.dropEffect = 'move'
                this.dropTargetId = targetId
            },
            onDragLeave(event, targetId) {
                if (this.dropTargetId === targetId) {
                    this.dropTargetId = null
                }
            },
            onDrop(event, parentId) {
                event.preventDefault()
                event.stopPropagation()
                if (! this.canManage || this.draggingId === null) {
                    return
                }
                const folderId = this.draggingId
                this.draggingId = null
                this.dropTargetId = null
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
                this.dropTargetId = null
                $wire.moveFolder(folderId, null)
            },
        }"
    >
        <div class="fi-section-content-ctn space-y-4 p-6">
            <div>
                <h2 class="text-base font-semibold text-gray-950 dark:text-white">
                    Folder tree
                </h2>
                <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                    Nest folders without limit. Drag a folder onto another to move it, or onto the drop zone to make it top-level.
                </p>
            </div>

            @if ($this->canManageFolders())
                <div
                    class="rounded-lg border border-dashed border-gray-300 bg-gray-50/80 px-4 py-3 text-sm text-gray-600 transition dark:border-white/10 dark:bg-white/5 dark:text-gray-300"
                    :class="dropTargetId === 'root' ? 'border-primary-400 bg-primary-50/80 text-primary-700 dark:border-primary-400/40 dark:bg-primary-400/10 dark:text-primary-300' : ''"
                    @dragover="onDragOver($event, 'root')"
                    @dragleave="onDragLeave($event, 'root')"
                    @drop="onDropRoot($event)"
                >
                    Drop here to move a folder to the root (Unfiled parent).
                </div>
            @endif

            @if (count($tree) === 0)
                <p class="text-sm text-gray-500 dark:text-gray-400">
                    No folders yet. Create one to organize the media library.
                </p>
            @else
                <ul class="space-y-0.5" role="tree">
                    @include('filament.pages.dam.partials.folder-nodes', ['nodes' => $tree, 'depth' => 0])
                </ul>
            @endif
        </div>
    </div>

    <x-filament-actions::modals />
</x-filament-panels::page>
