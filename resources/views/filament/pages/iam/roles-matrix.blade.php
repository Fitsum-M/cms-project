<x-filament-panels::page>
    <div class="space-y-6">
        <p class="text-sm text-gray-600 dark:text-gray-400">
            Permissions are defined at the role level. Users inherit all capabilities of their single assigned role.
            Per-user overrides are not supported (SRS 11.2 / 15.6).
        </p>

        <div class="fi-section overflow-hidden rounded-xl bg-white shadow-sm ring-1 ring-gray-950/5 dark:bg-white/5 dark:ring-white/10">
            <div class="overflow-x-auto">
                <table class="w-full min-w-[48rem] divide-y divide-gray-200 text-sm dark:divide-white/10">
                    <thead class="bg-gray-50 dark:bg-white/5">
                        <tr>
                            <th class="px-4 py-3 text-left font-medium text-gray-700 dark:text-gray-200">Capability</th>
                            @foreach ($this->getRoleNames() as $roleName)
                                <th class="px-4 py-3 text-center font-medium text-gray-700 dark:text-gray-200">{{ $roleName }}</th>
                            @endforeach
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 dark:divide-white/5">
                        @php $lastGroup = null; @endphp
                        @foreach ($this->getMatrixRows() as $row)
                            @if ($lastGroup !== $row['group'])
                                @php $lastGroup = $row['group']; @endphp
                                <tr class="bg-gray-50/80 dark:bg-white/5">
                                    <td colspan="{{ count($this->getRoleNames()) + 1 }}" class="px-4 py-2 text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">
                                        {{ $row['group'] }}
                                    </td>
                                </tr>
                            @endif
                            <tr>
                                <td class="px-4 py-2.5 text-gray-900 dark:text-gray-100">{{ $row['capability'] }}</td>
                                @foreach ($this->getRoleNames() as $roleName)
                                    <td class="px-4 py-2.5 text-center">
                                        @if ($row['roles'][$roleName] ?? false)
                                            <span class="inline-flex rounded-md bg-success-50 px-2 py-0.5 text-xs font-medium text-success-700 dark:bg-success-400/10 dark:text-success-400">Yes</span>
                                        @else
                                            <span class="inline-flex rounded-md bg-gray-50 px-2 py-0.5 text-xs font-medium text-gray-500 dark:bg-white/5 dark:text-gray-400">No</span>
                                        @endif
                                    </td>
                                @endforeach
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</x-filament-panels::page>
