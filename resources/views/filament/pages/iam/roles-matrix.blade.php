<x-filament-panels::page>
    <div class="space-y-6">
        <!-- Header description block -->
        <div class="rounded-xl border border-primary-200/70 bg-primary-50/40 px-5 py-4 dark:border-primary-500/20 dark:bg-primary-400/5">
            <div class="flex items-start gap-3">
                <span class="inline-flex size-9 shrink-0 items-center justify-center rounded-lg bg-primary-100 text-primary-600 dark:bg-primary-400/15 dark:text-primary-400">
                    <x-filament::icon icon="heroicon-o-information-circle" class="size-5" />
                </span>
                <div>
                    <h2 class="text-sm font-semibold text-gray-950 dark:text-white">
                        Manage Roles & Permissions
                    </h2>
                </div>
            </div>
        </div>

        <!-- Roles List Table -->
        <!-- Roles List Table -->
        <div class="overflow-hidden rounded-xl border border-gray-200 dark:border-white/10 shadow-sm bg-white dark:bg-gray-900">
            <div class="overflow-x-auto">
                <table class="w-full text-left text-sm divide-y divide-gray-200 dark:divide-white/5 table-fixed">
                    <thead>
                        <tr class="bg-gray-50 dark:bg-white/5">
                            <th class="px-6 py-4 text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400 w-[20%] min-w-[160px]">
                                Role
                            </th>
                            <th class="px-6 py-4 text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400 w-[40%]">
                                Description
                            </th>
                            <th class="px-6 py-4 text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400 w-[25%] min-w-[150px]">
                                Permissions
                            </th>
                            <th class="px-6 py-4 text-right text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400 w-[15%] min-w-[150px]">
                                Actions
                            </th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200 dark:divide-white/5 bg-white dark:bg-gray-900">
                        @foreach ($this->getRoleCards() as $card)
                            <tr class="transition hover:bg-gray-50/50 dark:hover:bg-white/5">
                                <!-- Role Column -->
                                <td class="px-6 py-4 whitespace-nowrap w-[20%] min-w-[160px]">
                                    <div class="flex">
                                        <x-filament::badge :color="$card['color']" size="lg">
                                            {{ $card['name'] }}
                                        </x-filament::badge>
                                    </div>
                                </td>

                                <!-- Description Column -->
                                <td class="px-6 py-4 whitespace-normal w-[40%]">
                                    <p class="text-sm text-gray-500 dark:text-gray-400 max-w-xl">
                                        {{ \Illuminate\Support\Str::limit($card['description'], 35) }}
                                    </p>
                                </td>

                                <!-- Permissions Status & Progress -->
                                <td class="px-6 py-4 whitespace-nowrap w-[25%] min-w-[150px]">
                                    <div class="flex flex-col gap-1 max-w-[200px]">
                                        <span class="text-xs font-semibold text-gray-600 dark:text-gray-400">
                                            {{ $card['granted_count'] }} / {{ $card['total_count'] }} ({{ $card['coverage_percent'] }}%)
                                        </span>
                                        <div class="w-full bg-gray-100 dark:bg-white/10 h-1.5 rounded-full overflow-hidden">
                                            <div @class([
                                                'h-full rounded-full',
                                                match ($card['color']) {
                                                    'danger' => 'bg-danger-500',
                                                    'warning' => 'bg-warning-500',
                                                    'info' => 'bg-info-500',
                                                    default => 'bg-primary-500',
                                                },
                                            ]) style="width: {{ $card['coverage_percent'] }}%"></div>
                                        </div>
                                    </div>
                                </td>

                                <!-- Action Column -->
                                <td class="px-6 py-4 whitespace-nowrap text-right text-sm w-[15%] min-w-[150px]">
                                    <div class="flex items-center justify-end gap-x-6 whitespace-nowrap">
                                        <!-- Edit Action -->
                                         <a
                                             href="{{ \App\Filament\Pages\Iam\EditRole::getUrl(['record' => $card['id']]) }}"
                                             class="inline-flex items-center gap-x-1.5 text-sm font-semibold text-primary-600 hover:text-primary-500 dark:text-primary-400 dark:hover:text-primary-300 whitespace-nowrap"
                                             title="Edit Role"
                                         >
                                             <x-filament::icon icon="heroicon-m-pencil-square" class="size-4 shrink-0" />
                                             Edit
                                         </a>

                                        <!-- Delete Action -->
                                        @if ($card['name'] !== \App\Enums\UserRole::Administrator->value)
                                            <button
                                                type="button"
                                                wire:click="confirmDeleteRole({{ $card['id'] }})"
                                                class="inline-flex items-center gap-x-1.5 text-sm font-semibold text-danger-600 hover:text-danger-500 dark:text-danger-400 dark:hover:text-danger-300 whitespace-nowrap"
                                                title="Delete Role"
                                            >
                                                <x-filament::icon icon="heroicon-m-trash" class="size-4 shrink-0" />
                                                Delete
                                            </button>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- ADD ROLE MODAL -->
    @if ($isAddModalOpen)
        <div class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-gray-950/60 backdrop-blur-sm">
            <div class="w-full max-w-md bg-white dark:bg-gray-900 rounded-xl shadow-xl border border-gray-200 dark:border-gray-800 overflow-hidden transform transition-all">
                <!-- Modal Header -->
                <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-800 flex justify-between items-center bg-gray-50 dark:bg-white/5">
                    <h3 class="text-base font-bold text-gray-900 dark:text-white">Add New Role</h3>
                    <button type="button" wire:click="$set('isAddModalOpen', false)" class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-200">
                        <x-filament::icon icon="heroicon-o-x-mark" class="size-5" />
                    </button>
                </div>
                
                <!-- Modal Form -->
                <form wire:submit.prevent="addRole" class="p-6 space-y-4">
                    <div>
                        <label for="newRoleName" class="block text-sm font-semibold text-gray-700 dark:text-gray-300">Role Name</label>
                        <input
                            type="text"
                            id="newRoleName"
                            wire:model.defer="newRoleName"
                            class="mt-1 block w-full rounded-lg border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-800 text-gray-900 dark:text-white shadow-sm focus:border-primary-500 focus:ring focus:ring-primary-200 focus:ring-opacity-50"
                            placeholder="e.g. Moderator"
                        >
                        @error('newRoleName')
                            <span class="text-xs text-danger-600 mt-1 block">{{ $message }}</span>
                        @enderror
                    </div>

                    <!-- Modal Actions -->
                    <div class="flex justify-end gap-3 pt-2">
                        <x-filament::button color="gray" type="button" wire:click="$set('isAddModalOpen', false)">
                            Cancel
                        </x-filament::button>
                        <x-filament::button type="submit">
                            Create Role
                        </x-filament::button>
                    </div>
                </form>
            </div>
        </div>
    @endif



    <!-- DELETE ROLE MODAL -->
    @if ($isDeleteModalOpen)
        <div class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-gray-950/60 backdrop-blur-sm">
            <div class="w-full max-w-md bg-white dark:bg-gray-900 rounded-xl shadow-xl border border-gray-200 dark:border-gray-800 overflow-hidden transform transition-all">
                <!-- Modal Header -->
                <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-800 flex justify-between items-center bg-gray-50 dark:bg-white/5">
                    <h3 class="text-base font-bold text-gray-900 dark:text-white flex items-center gap-2">
                        <x-filament::icon icon="heroicon-o-exclamation-triangle" class="size-5 text-danger-600" />
                        Delete Role
                    </h3>
                    <button type="button" wire:click="$set('isDeleteModalOpen', false)" class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-200">
                        <x-filament::icon icon="heroicon-o-x-mark" class="size-5" />
                    </button>
                </div>
                
                <!-- Modal Body & Actions -->
                <div class="p-6 space-y-4">
                    <p class="text-sm text-gray-600 dark:text-gray-400 leading-relaxed">
                        Are you sure you want to delete this role? Users assigned to this role will need to be re-assigned. This action cannot be undone.
                    </p>

                    <div class="flex justify-end gap-3 pt-2">
                        <x-filament::button color="gray" type="button" wire:click="$set('isDeleteModalOpen', false)">
                            Cancel
                        </x-filament::button>
                        <x-filament::button color="danger" wire:click="deleteRole">
                            Delete Role
                        </x-filament::button>
                    </div>
                </div>
            </div>
        </div>
    @endif
</x-filament-panels::page>
