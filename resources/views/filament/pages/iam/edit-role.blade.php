<x-filament-panels::page>
    <form wire:submit.prevent="save" class="space-y-6">
        <x-filament::section>
            <x-slot name="heading">Role Details</x-slot>
            <x-slot name="description">Modify the role name. User accounts are managed under All Users.</x-slot>

            <div>
                <label for="roleName" class="block text-sm font-semibold text-gray-700 dark:text-gray-300">Role Name</label>
                <input
                    type="text"
                    id="roleName"
                    wire:model.defer="roleName"
                    class="mt-1 block w-full max-w-md rounded-lg border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-800 text-gray-900 dark:text-white shadow-sm focus:border-primary-500 focus:ring focus:ring-primary-200 focus:ring-opacity-50"
                    @disabled($roleName === \App\Enums\UserRole::Administrator->value)
                >
                @error('roleName')
                    <span class="text-xs text-danger-600 mt-1 block">{{ $message }}</span>
                @enderror
                @if ($roleName === \App\Enums\UserRole::Administrator->value)
                    <span class="text-xs text-gray-500 mt-1 block">Administrator name is system-protected and cannot be edited.</span>
                @endif
            </div>
        </x-filament::section>

        <x-filament::section>
            <x-slot name="heading">Role Capabilities Matrix</x-slot>
            <x-slot name="description">Select capabilities permitted for this role. Click any module to expand/collapse its options.</x-slot>

            <div class="space-y-4" x-data="{ activeGroup: 'Dashboard' }">
                @foreach ($this->getPermissionsGrouped() as $groupName => $permissions)
                    @php
                        $groupSlug = \Illuminate\Support\Str::slug($groupName);
                    @endphp
                    <div class="border border-gray-200 dark:border-gray-850 rounded-xl overflow-hidden bg-white dark:bg-gray-900/50 transition">
                        <button
                            type="button"
                            x-on:click="activeGroup = (activeGroup === '{{ $groupSlug }}' ? null : '{{ $groupSlug }}')"
                            style="display: flex; align-items: center; justify-content: space-between; width: 100%; border: none; outline: none; cursor: pointer;"
                            class="px-5 py-4 text-left font-semibold text-sm text-gray-950 dark:text-white bg-gray-50 dark:bg-white/5 hover:bg-gray-100 dark:hover:bg-white/10 transition duration-200"
                        >
                            <div style="display: flex; align-items: center; gap: 12px;">
                                <x-filament::icon icon="heroicon-o-folder" style="width: 20px; height: 20px; display: inline-block;" class="text-gray-400 dark:text-gray-500" />
                                <span>{{ $groupName }}</span>
                                <span style="font-weight: normal; font-size: 0.75rem;" class="text-gray-500 dark:text-gray-400">
                                    ({{ count($permissions) }} capabilities)
                                </span>
                            </div>
                            <div>
                                <svg
                                    style="width: 20px; height: 20px; transition: transform 0.2s;"
                                    :style="activeGroup === '{{ $groupSlug }}' ? 'transform: rotate(180deg);' : ''"
                                    class="text-gray-400 dark:text-gray-500"
                                    fill="none"
                                    viewBox="0 0 24 24"
                                    stroke="currentColor"
                                >
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                                </svg>
                            </div>
                        </button>

                        <div
                            x-show="activeGroup === '{{ $groupSlug }}'"
                            x-collapse
                            class="p-5 border-t border-gray-200 dark:border-gray-800 space-y-3"
                        >
                            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-3">
                                @foreach ($permissions as $permission)
                                    <label class="flex items-start gap-3 p-3 rounded-lg border border-gray-200 dark:border-gray-850 bg-gray-50/30 dark:bg-white/5 cursor-pointer hover:bg-gray-100 dark:hover:bg-white/10 transition">
                                        <input
                                            type="checkbox"
                                            wire:model="rolePermissions"
                                            value="{{ $permission->value }}"
                                            class="rounded border-gray-300 dark:border-gray-700 text-primary-600 shadow-sm focus:border-primary-300 focus:ring focus:ring-primary-200 focus:ring-opacity-50 mt-0.5"
                                        >
                                        <div class="flex flex-col leading-tight">
                                            <span class="text-sm font-semibold text-gray-900 dark:text-white">
                                                {{ $permission->label() }}
                                            </span>
                                            <span class="text-[10px] text-gray-500 dark:text-gray-400 mt-0.5">
                                                {{ $permission->value }}
                                            </span>
                                        </div>
                                    </label>
                                @endforeach
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
        </x-filament::section>

        <div class="flex items-center gap-3 justify-end">
            <x-filament::button color="gray" type="button" href="{{ \App\Filament\Pages\Iam\RolesAndPermissions::getUrl() }}" tag="a">
                Cancel
            </x-filament::button>
            <x-filament::button type="submit">
                Save Changes
            </x-filament::button>
        </div>
    </form>
</x-filament-panels::page>
