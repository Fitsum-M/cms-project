@php
    $emptyMessage = $emptyMessage ?? 'No items.';
@endphp

@if ($items->isEmpty())
    <p class="text-sm text-gray-500 dark:text-gray-400">
        {{ $emptyMessage }}
    </p>
@else
    <div class="overflow-x-auto">
        <table class="w-full table-auto divide-y divide-gray-200 text-sm dark:divide-white/10">
            <thead>
                <tr class="text-start text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">
                    <th class="px-2 py-2 text-start">{{ __('cms.dashboard.content_table.title') }}</th>
                    <th class="px-2 py-2 text-start">{{ __('cms.dashboard.content_table.type') }}</th>
                    <th class="px-2 py-2 text-start">{{ __('cms.dashboard.content_table.status') }}</th>
                    <th class="px-2 py-2 text-start">{{ __('cms.dashboard.content_table.author') }}</th>
                    <th class="px-2 py-2 text-start">{{ __('cms.dashboard.content_table.updated') }}</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-200 dark:divide-white/10">
                @foreach ($items as $item)
                    <tr wire:key="{{ $item->key() }}-{{ $item->status->value }}">
                        <td class="px-2 py-2.5">
                            <a
                                href="{{ $item->editUrl }}"
                                class="font-medium text-primary-600 hover:underline dark:text-primary-400"
                            >
                                {{ $item->title }}
                            </a>
                        </td>
                        <td class="px-2 py-2.5 text-gray-700 dark:text-gray-200">
                            {{ $item->typeLabel() }}
                        </td>
                        <td class="px-2 py-2.5">
                            <x-filament::badge :color="$item->status->color()">
                                {{ $item->status->label() }}
                            </x-filament::badge>
                        </td>
                        <td class="px-2 py-2.5 text-gray-700 dark:text-gray-200">
                            {{ $item->authorName ?? '—' }}
                        </td>
                        <td class="px-2 py-2.5 text-gray-700 dark:text-gray-200" title="{{ $item->updatedAt->diffForHumans() }}">
                            {{ $item->formattedUpdatedAt() }}
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
@endif
