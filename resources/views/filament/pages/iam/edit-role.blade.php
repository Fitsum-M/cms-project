<x-filament-panels::page>
    <form wire:submit.prevent="save" class="space-y-6">
        <x-filament::section>
            <!-- Form Header & Role Name -->
            <div class="space-y-4">
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
            </div>
        </x-filament::section>

        <!-- Permissions Section -->
        <x-filament::section>
            <x-slot name="heading">Role Capabilities Matrix</x-slot>
            <x-slot name="description">Select permitted capabilities for this role.</x-slot>

            <div class="space-y-6">
                @foreach ($this->getPermissionsGrouped() as $groupName => $permissions)
                    <div class="space-y-2">
                        <h5 class="text-xs font-bold uppercase tracking-wider text-primary-600 dark:text-primary-400 border-b border-primary-100 dark:border-primary-950/20 pb-1">
                            {{ $groupName }}
                        </h5>
                        
                        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-3">
                            @foreach ($permissions as $permission)
                                <label class="flex items-start gap-3 p-3 rounded-lg border border-gray-200 dark:border-gray-800 bg-gray-50/30 dark:bg-white/5 cursor-pointer hover:bg-gray-100 dark:hover:bg-white/10 transition">
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
                @endforeach
            </div>
        </x-filament::section>

        <!-- Action Buttons -->
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
