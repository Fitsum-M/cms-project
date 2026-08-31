{{-- Build id => parentId map so Alpine can hide rows when an ancestor is collapsed --}}
@php
    $parentMap = [];
    $buildParentMap = function (array $nodes, ?int $parentId = null) use (&$buildParentMap, &$parentMap): void {
        foreach ($nodes as $node) {
            $parentMap[$node['id']] = $parentId;
            if (! empty($node['children'])) {
                $buildParentMap($node['children'], $node['id']);
            }
        }
    };
    $buildParentMap($tree);
@endphp

<x-filament-panels::page>
    {{-- Alpine state: expand/collapse + drag-and-drop move --}}
    <div
        class="space-y-4"
        x-data="{
            draggingId: null,
            dropTargetId: null,
            canManage: @js($this->canManageFolders()),
            expanded: {},
            parents: @js($parentMap),
            isExpanded(id) {
                return this.expanded[id] !== false
            },
            // Walk parents; hide row if any ancestor is collapsed
            isRowVisible(id) {
                let parentId = this.parents[id]
                while (parentId !== null && parentId !== undefined) {
                    if (! this.isExpanded(parentId)) {
                        return false
                    }
                    parentId = this.parents[parentId]
                }
                return true
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
            // Nest under another folder via Livewire
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
            // Move to top level (no parent)
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
        <div>
            <h2 class="text-base font-semibold leading-6 text-gray-950 dark:text-white">
                Folder tree
            </h2>
            <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">
                Nest folders without limit. Drag a folder onto another to move it, or onto the drop zone to make it top-level.
            </p>
        </div>

        {{-- Drop zone: make folder top-level --}}
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

        {{-- Folder tree table --}}
        <div class="fi-ta-ctn flex-col overflow-hidden">
            <div class="fi-ta-content relative divide-y divide-gray-200 overflow-x-auto dark:divide-white/10">
                @if (count($tree) === 0)
                    <div class="px-6 py-8 text-sm text-gray-500 dark:text-gray-400">
                        No folders yet. Create one to organize the media library.
                    </div>
                @else
                    <table class="fi-ta-table w-full table-fixed">
                        <colgroup>
                            <col style="width: 55%" />
                            <col style="width: 20%" />
                            <col style="width: 25%" />
                        </colgroup>
                        <thead>
                            <tr class="fi-ta-header-row">
                                <th class="fi-ta-header-cell fi-growable" scope="col">
                                    Name
                                </th>
                                <th class="fi-ta-header-cell" scope="col">
                                    Items
                                </th>
                                <th class="fi-ta-header-cell text-end" scope="col">
                                    <span class="sr-only">Actions</span>
                                </th>
                            </tr>
                        </thead>

                        {{-- Recursive rows from folder-nodes partial --}}
                        <tbody>
                            @include('filament.pages.dam.partials.folder-nodes', ['nodes' => $tree, 'depth' => 0])
                        </tbody>
                    </table>
                @endif
            </div>
        </div>
    </div>

    <x-filament-actions::modals />
</x-filament-panels::page>
