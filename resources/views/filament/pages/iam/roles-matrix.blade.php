<x-filament-panels::page>
    <style>
        /* Custom styles for Roles Matrix table and layout elements */
        .custom-info-card {
            display: flex;
            align-items: center;
            gap: 12px;
            border-radius: 12px;
            padding: 16px;
            margin-bottom: 24px;
            border: 1px solid #e0e7ff;
            background-color: #f5f3ff;
        }
        .dark .custom-info-card {
            border-color: rgba(99, 102, 241, 0.2);
            background-color: rgba(99, 102, 241, 0.05);
        }
        .custom-info-icon-wrapper {
            display: inline-flex;
            width: 36px;
            height: 36px;
            flex-shrink: 0;
            align-items: center;
            justify-content: center;
            border-radius: 8px;
            background-color: #e0e7ff;
            color: #4f46e5;
        }
        .dark .custom-info-icon-wrapper {
            background-color: rgba(99, 102, 241, 0.15);
            color: #818cf8;
        }

        .roles-table-container {
            overflow: hidden;
            border-radius: 12px;
            border: 1px solid #e5e7eb;
            background-color: #ffffff;
            box-shadow: 0 1px 3px 0 rgba(0, 0, 0, 0.05), 0 1px 2px 0 rgba(0, 0, 0, 0.03);
        }
        .dark .roles-table-container {
            border-color: rgba(255, 255, 255, 0.1);
            background-color: #111827;
        }
        .roles-table {
            width: 100%;
            border-collapse: collapse;
        }
        .roles-table th {
            padding: 14px 24px;
            font-size: 0.75rem;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            color: #6b7280;
            border-bottom: 1px solid #e5e7eb;
            background-color: #f9fafb;
        }
        .dark .roles-table th {
            color: #9ca3af;
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
            background-color: rgba(255, 255, 255, 0.03);
        }
        .roles-table tr {
            transition: background-color 0.2s ease;
        }
        .roles-table tr:hover {
            background-color: rgba(243, 244, 246, 0.5);
        }
        .dark .roles-table tr:hover {
            background-color: rgba(255, 255, 255, 0.02);
        }
        .roles-table td {
            padding: 12px 24px;
            vertical-align: middle;
            border-bottom: 1px solid #e5e7eb;
        }
        .dark .roles-table td {
            border-bottom: 1px solid rgba(255, 255, 255, 0.05);
        }
        .role-description {
            font-size: 0.825rem;
            color: #4b5563;
            max-width: 480px;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }
        .dark .role-description {
            color: #d1d5db;
        }
        .permissions-container {
            display: flex;
            align-items: center;
            gap: 12px;
            max-width: 220px;
        }
        .permissions-text {
            font-size: 0.75rem;
            font-weight: 500;
            color: #4b5563;
            white-space: nowrap;
        }
        .dark .permissions-text {
            color: #9ca3af;
        }
        .progress-bar-bg {
            height: 6px;
            flex-grow: 1;
            min-width: 80px;
            background-color: #f3f4f6;
            border-radius: 9999px;
            overflow: hidden;
        }
        .dark .progress-bar-bg {
            background-color: rgba(255, 255, 255, 0.1);
        }
        .progress-bar-fill {
            height: 100%;
            border-radius: 9999px;
            transition: width 0.3s ease;
        }
        .action-buttons-group {
            display: flex;
            align-items: center;
            justify-content: flex-end;
            gap: 8px;
        }
        .action-btn {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 6px 12px;
            border-radius: 6px;
            font-size: 0.75rem;
            font-weight: 600;
            text-decoration: none;
            transition: all 0.2s ease;
            border: 1px solid transparent;
            cursor: pointer;
        }
        .action-btn-edit {
            color: #4f46e5;
            background-color: #f5f3ff;
            border-color: #e0e7ff;
        }
        .action-btn-edit:hover {
            color: #4338ca;
            background-color: #e0e7ff;
            border-color: #c7d2fe;
        }
        .dark .action-btn-edit {
            color: #818cf8;
            background-color: rgba(79, 70, 229, 0.1);
            border-color: rgba(79, 70, 229, 0.2);
        }
        .dark .action-btn-edit:hover {
            color: #a5b4fc;
            background-color: rgba(79, 70, 229, 0.2);
            border-color: rgba(79, 70, 229, 0.3);
        }
        .action-btn-delete {
            color: #dc2626;
            background-color: #fef2f2;
            border-color: #fee2e2;
        }
        .action-btn-delete:hover {
            color: #b91c1c;
            background-color: #fee2e2;
            border-color: #fecaca;
        }
        .dark .action-btn-delete {
            color: #f87171;
            background-color: rgba(220, 38, 38, 0.1);
            border-color: rgba(220, 38, 38, 0.2);
        }
        .dark .action-btn-delete:hover {
            color: #fca5a5;
            background-color: rgba(220, 38, 38, 0.2);
            border-color: rgba(220, 38, 38, 0.3);
        }
        .action-btn svg {
            width: 16px;
            height: 16px;
            flex-shrink: 0;
        }

        /* Modal styling */
        .modal-overlay {
            position: fixed;
            inset: 0;
            z-index: 50;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 16px;
            background-color: rgba(3, 7, 18, 0.6);
            backdrop-filter: blur(4px);
        }
        .modal-header {
            padding: 16px 24px;
            border-bottom: 1px solid #e5e7eb;
            display: flex;
            justify-content: space-between;
            align-items: center;
            background-color: #f9fafb;
        }
        .dark .modal-header {
            border-color: rgba(255, 255, 255, 0.08);
            background-color: rgba(255, 255, 255, 0.02);
        }
        .modal-footer {
            display: flex;
            justify-content: flex-end;
            gap: 12px;
            padding-top: 8px;
        }
    </style>

    <div class="space-y-6">
        <!-- Header description block with clear margin below -->
        <div class="custom-info-card">
            <span class="custom-info-icon-wrapper">
                <x-filament::icon icon="heroicon-o-information-circle" />
            </span>
            <div>
                <h2 class="text-sm font-semibold text-gray-950 dark:text-white" style="margin: 0;">
                    Manage Roles & Permissions
                </h2>
            </div>
        </div>

        <!-- Roles List Table -->
        <div class="roles-table-container">
            <div class="overflow-x-auto w-full">
                <table class="roles-table text-left">
                    <thead>
                        <tr>
                            <th style="width: 15%; min-width: 130px;">
                                Role
                            </th>
                            <th style="width: 50%;">
                                Description
                            </th>
                            <th style="width: 20%; min-width: 180px;">
                                Permissions
                            </th>
                            <th class="text-right" style="width: 15%; min-width: 160px;">
                                Actions
                            </th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($this->getRoleCards() as $card)
                            <tr>
                                <!-- Role Column -->
                                <td>
                                    <x-filament::badge :color="$card['color'] ?? 'primary'" size="md">
                                        {{ $card['name'] }}
                                    </x-filament::badge>
                                </td>

                                <!-- Description Column (Single line with ellipsis + Tooltip) -->
                                <td title="{{ $card['description'] }}">
                                    <div class="role-description">
                                        {{ $card['description'] }}
                                    </div>
                                </td>

                                <!-- Permissions Status & Progress Bar (Inline) -->
                                <td>
                                    <div class="permissions-container">
                                        <span class="permissions-text">
                                            {{ $card['granted_count'] }}/{{ $card['total_count'] }} ({{ $card['coverage_percent'] }}%)
                                        </span>
                                        <div class="progress-bar-bg">
                                            <div class="progress-bar-fill" style="width: {{ $card['coverage_percent'] }}%; background-color: {{
                                                match ($card['color'] ?? null) {
                                                    'danger' => '#ef4444',
                                                    'warning' => '#f59e0b',
                                                    'info' => '#3b82f6',
                                                    default => '#4f46e5',
                                                }
                                            }};"></div>
                                        </div>
                                    </div>
                                </td>

                                <!-- Action Column (Horizontal Single-Row Button group) -->
                                <td>
                                    <div class="action-buttons-group">
                                        <!-- Edit Action -->
                                        <a
                                            href="{{ \App\Filament\Pages\Iam\EditRole::getUrl(['record' => $card['id']]) }}"
                                            class="action-btn action-btn-edit"
                                        >
                                            <x-filament::icon icon="heroicon-m-pencil-square" />
                                            <span>Edit</span>
                                        </a>

                                        <!-- Delete Action -->
                                        @if ($card['name'] !== \App\Enums\UserRole::Administrator->value)
                                            <button
                                                type="button"
                                                wire:click="confirmDeleteRole({{ $card['id'] }})"
                                                class="action-btn action-btn-delete"
                                            >
                                                <x-filament::icon icon="heroicon-m-trash" />
                                                <span>Delete</span>
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
        <div class="modal-overlay">
            <div class="w-full max-w-md bg-white dark:bg-gray-900 rounded-xl shadow-xl border border-gray-200 dark:border-gray-800 overflow-hidden transform transition-all">
                <div class="modal-header">
                    <h3 class="text-base font-bold text-gray-900 dark:text-white" style="margin: 0;">Add New Role</h3>
                    <button type="button" wire:click="$set('isAddModalOpen', false)" class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-200">
                        <x-filament::icon icon="heroicon-o-x-mark" class="size-5" />
                    </button>
                </div>

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

                    <div class="modal-footer">
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
        <div class="modal-overlay">
            <div class="w-full max-w-md bg-white dark:bg-gray-900 rounded-xl shadow-xl border border-gray-200 dark:border-gray-800 overflow-hidden transform transition-all">
                <div class="modal-header">
                    <h3 class="text-base font-bold text-gray-900 dark:text-white flex items-center gap-2" style="margin: 0; display: flex; align-items: center; gap: 8px;">
                        <x-filament::icon icon="heroicon-o-exclamation-triangle" class="size-5 text-danger-600" style="color: #dc2626;" />
                        Delete Role
                    </h3>
                    <button type="button" wire:click="$set('isDeleteModalOpen', false)" class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-200">
                        <x-filament::icon icon="heroicon-o-x-mark" class="size-5" />
                    </button>
                </div>

                <div class="p-6 space-y-4">
                    <p class="text-sm text-gray-600 dark:text-gray-400 leading-relaxed">
                        Are you sure you want to delete this role? Users assigned to this role will need to be re-assigned. This action cannot be undone.
                    </p>

                    <div class="modal-footer">
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