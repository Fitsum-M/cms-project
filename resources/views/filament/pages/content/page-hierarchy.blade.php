<x-filament-panels::page>
    <div
        class="fi-section rounded-xl bg-white shadow-sm ring-1 ring-gray-950/5 dark:bg-white/5 dark:ring-white/10"
        x-data="{
            draggingId: null,
            canManage: @js($this->canManageTree()),
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
            onDragOver(event) {
                if (! this.canManage || this.draggingId === null) {
                    return
                }
                event.preventDefault()
                event.dataTransfer.dropEffect = 'move'
            },
            onDropBefore(event, targetId) {
                event.preventDefault()
                event.stopPropagation()
                if (! this.canManage || this.draggingId === null || this.draggingId === targetId) {
                    return
                }
                const draggedId = this.draggingId
                this.draggingId = null
                $wire.reorderRelative(draggedId, targetId, 'before')
            },
            onDropNest(event, parentId) {
                event.preventDefault()
                event.stopPropagation()
                if (! this.canManage || this.draggingId === null || this.draggingId === parentId) {
                    return
                }
                const draggedId = this.draggingId
                this.draggingId = null
                $wire.movePage(draggedId, parentId)
            },
            onDropRoot(event) {
                event.preventDefault()
                if (! this.canManage || this.draggingId === null) {
                    return
                }
                const draggedId = this.draggingId
                this.draggingId = null
                $wire.movePage(draggedId, null)
            },
        }"
    >
        <div class="fi-section-content-ctn space-y-4 p-6">
            <div class="flex flex-col gap-1 sm:flex-row sm:items-end sm:justify-between">
                <div>
                    <h2 class="text-base font-semibold text-gray-950 dark:text-white">
                        Page tree
                    </h2>
                    <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                        Drag a page onto another to nest it, onto the drop zone to make it top-level, or onto a drop line to reorder siblings.
                        Click a title to edit.
                    </p>
                </div>

                <div class="flex flex-wrap gap-2 text-xs text-gray-500 dark:text-gray-400">
                    <span class="inline-flex items-center gap-1">
                        <span class="size-2 rounded-full bg-gray-400"></span> Draft
                    </span>
                    <span class="inline-flex items-center gap-1">
                        <span class="size-2 rounded-full bg-amber-400"></span> Pending
                    </span>
                    <span class="inline-flex items-center gap-1">
                        <span class="size-2 rounded-full bg-emerald-500"></span> Published
                    </span>
                    <span class="inline-flex items-center gap-1">
                        <span class="size-2 rounded-full bg-slate-500"></span> Archived
                    </span>
                </div>
            </div>

            @if ($this->canManageTree())
                <div
                    class="rounded-lg border border-dashed border-gray-300 bg-gray-50/80 px-4 py-3 text-sm text-gray-600 dark:border-white/10 dark:bg-white/5 dark:text-gray-300"
                    @dragover="onDragOver($event)"
                    @drop="onDropRoot($event)"
                >
                    Drop here to move a page to the top level.
                </div>
            @endif

            @if (count($tree) === 0)
                <p class="text-sm text-gray-500 dark:text-gray-400">
                    No pages yet. Create a page to build the hierarchy.
                </p>
            @else
                <ul class="space-y-0.5" role="tree">
                    @include('filament.pages.content.partials.page-nodes', ['nodes' => $tree, 'depth' => 0])
                </ul>
            @endif
        </div>
    </div>

    <x-filament-actions::modals />
</x-filament-panels::page>
