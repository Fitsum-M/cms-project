<x-filament-panels::page>
    <div
        class="fi-section overflow-hidden rounded-xl bg-white shadow-sm ring-1 ring-gray-950/5 dark:bg-white/5 dark:ring-white/10"
        x-data="{
            draggingId: null,
            dropTarget: null,
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
            onDragEnd() {
                this.draggingId = null
                this.dropTarget = null
            },
            onDragOver(event, target = null) {
                if (! this.canManage || this.draggingId === null) {
                    return
                }
                event.preventDefault()
                event.dataTransfer.dropEffect = 'move'
                this.dropTarget = target
            },
            onDragLeave(target) {
                if (this.dropTarget === target) {
                    this.dropTarget = null
                }
            },
            onDropBefore(event, targetId) {
                event.preventDefault()
                event.stopPropagation()
                if (! this.canManage || this.draggingId === null || this.draggingId === targetId) {
                    this.dropTarget = null
                    return
                }
                const draggedId = this.draggingId
                this.draggingId = null
                this.dropTarget = null
                $wire.reorderRelative(draggedId, targetId, 'before')
            },
            onDropNest(event, parentId) {
                event.preventDefault()
                event.stopPropagation()
                if (! this.canManage || this.draggingId === null || this.draggingId === parentId) {
                    this.dropTarget = null
                    return
                }
                const draggedId = this.draggingId
                this.draggingId = null
                this.dropTarget = null
                $wire.movePage(draggedId, parentId)
            },
            onDropRoot(event) {
                event.preventDefault()
                if (! this.canManage || this.draggingId === null) {
                    this.dropTarget = null
                    return
                }
                const draggedId = this.draggingId
                this.draggingId = null
                this.dropTarget = null
                $wire.movePage(draggedId, null)
            },
        }"
    >
        <div class="fi-section-content-ctn space-y-5 p-6">
            <div class="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
                <div class="max-w-2xl">
                    <h2 class="text-base font-semibold text-gray-950 dark:text-white">
                        Page tree
                    </h2>
                    <p class="mt-1 text-sm leading-6 text-gray-500 dark:text-gray-400">
                        Drag a page onto another to nest it, onto the drop zone to make it top-level, or onto a drop line to reorder siblings.
                        Click a title to edit.
                    </p>
                </div>

                <div class="flex flex-wrap gap-1.5">
                    <span class="inline-flex items-center gap-1.5 rounded-full bg-gray-50 px-2.5 py-1 text-xs font-medium text-gray-600 ring-1 ring-inset ring-gray-500/10 dark:bg-white/5 dark:text-gray-300 dark:ring-white/10">
                        <span class="size-1.5 rounded-full bg-gray-400"></span>
                        Draft
                    </span>
                    <span class="inline-flex items-center gap-1.5 rounded-full bg-amber-50 px-2.5 py-1 text-xs font-medium text-amber-800 ring-1 ring-inset ring-amber-600/15 dark:bg-amber-400/10 dark:text-amber-300 dark:ring-amber-400/20">
                        <span class="size-1.5 rounded-full bg-amber-400"></span>
                        Pending
                    </span>
                    <span class="inline-flex items-center gap-1.5 rounded-full bg-emerald-50 px-2.5 py-1 text-xs font-medium text-emerald-700 ring-1 ring-inset ring-emerald-600/15 dark:bg-emerald-400/10 dark:text-emerald-300 dark:ring-emerald-400/20">
                        <span class="size-1.5 rounded-full bg-emerald-500"></span>
                        Published
                    </span>
                    <span class="inline-flex items-center gap-1.5 rounded-full bg-slate-50 px-2.5 py-1 text-xs font-medium text-slate-700 ring-1 ring-inset ring-slate-500/15 dark:bg-slate-400/10 dark:text-slate-300 dark:ring-slate-400/20">
                        <span class="size-1.5 rounded-full bg-slate-500"></span>
                        Archived
                    </span>
                </div>
            </div>

            @if ($this->canManageTree())
                <div
                    class="flex items-center justify-center gap-2 rounded-xl border border-dashed border-gray-300 bg-gray-50/80 px-4 py-4 text-sm text-gray-500 transition dark:border-white/10 dark:bg-white/5 dark:text-gray-400"
                    :class="dropTarget === 'root' ? 'border-primary-400 bg-primary-50 text-primary-700 dark:border-primary-400/40 dark:bg-primary-400/10 dark:text-primary-300' : ''"
                    @dragover="onDragOver($event, 'root')"
                    @dragleave="onDragLeave('root')"
                    @drop="onDropRoot($event)"
                >
                    <x-filament::icon icon="heroicon-m-arrow-up-tray" class="size-4 shrink-0" />
                    Drop here to move a page to the top level
                </div>
            @endif

            @if (count($tree) === 0)
                <div class="rounded-xl border border-dashed border-gray-200 px-6 py-12 text-center dark:border-white/10">
                    <x-filament::icon
                        icon="heroicon-o-document-text"
                        class="mx-auto size-10 text-gray-300 dark:text-gray-600"
                    />
                    <p class="mt-3 text-sm font-medium text-gray-950 dark:text-white">
                        No pages yet
                    </p>
                    <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                        Create a page to start building your hierarchy.
                    </p>
                </div>
            @else
                <div class="overflow-hidden rounded-xl border border-gray-200 dark:border-white/10">
                    <ul class="divide-y divide-gray-100 dark:divide-white/5" role="tree">
                        @include('filament.pages.content.partials.page-nodes', ['nodes' => $tree, 'depth' => 0])
                    </ul>
                </div>
            @endif
        </div>
    </div>

    <x-filament-actions::modals />
</x-filament-panels::page>
