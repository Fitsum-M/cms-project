@php
    $emptyMessage = $emptyMessage ?? 'No items.';
@endphp

@once
    <style>
        .fi-dashboard-content-table-wrap {
            overflow: hidden;
            border: 1px solid #e5e7eb;
            border-radius: 0.75rem;
            background: #ffffff;
        }

        .dark .fi-dashboard-content-table-wrap {
            border-color: rgba(255, 255, 255, 0.1);
            background: rgba(255, 255, 255, 0.03);
        }

        .fi-dashboard-content-table-scroll {
            overflow-x: auto;
        }

        .fi-dashboard-content-table {
            width: 100%;
            min-width: 760px;
            border-collapse: collapse;
            table-layout: fixed;
            font-size: 0.875rem;
            line-height: 1.45;
        }

        .fi-dashboard-content-table thead {
            background: #f9fafb;
        }

        .dark .fi-dashboard-content-table thead {
            background: rgba(255, 255, 255, 0.05);
        }

        .fi-dashboard-content-table th {
            padding: 0.75rem 1.25rem;
            text-align: left;
            font-size: 0.6875rem;
            font-weight: 600;
            letter-spacing: 0.05em;
            text-transform: uppercase;
            color: #6b7280;
            border-bottom: 1px solid #e5e7eb;
            white-space: nowrap;
        }

        .dark .fi-dashboard-content-table th {
            color: #9ca3af;
            border-bottom-color: rgba(255, 255, 255, 0.1);
        }

        .fi-dashboard-content-table td {
            padding: 0.875rem 1.25rem;
            vertical-align: middle;
            color: #374151;
            border-bottom: 1px solid #e5e7eb;
        }

        .dark .fi-dashboard-content-table td {
            color: #e5e7eb;
            border-bottom-color: rgba(255, 255, 255, 0.1);
        }

        .fi-dashboard-content-table tbody tr:last-child td {
            border-bottom: 0;
        }

        .fi-dashboard-content-table tbody tr:hover td {
            background: #f9fafb;
        }

        .dark .fi-dashboard-content-table tbody tr:hover td {
            background: rgba(255, 255, 255, 0.05);
        }

        .fi-dashboard-content-table .col-title { width: 34%; }
        .fi-dashboard-content-table .col-type { width: 9%; }
        .fi-dashboard-content-table .col-status { width: 14%; }
        .fi-dashboard-content-table .col-author { width: 17%; }
        .fi-dashboard-content-table .col-updated { width: 26%; }

        .fi-dashboard-content-table .cell-title,
        .fi-dashboard-content-table .cell-author {
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
        }

        .fi-dashboard-content-table .cell-title a {
            display: block;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
            font-weight: 600;
            color: #2563eb;
            text-decoration: none;
        }

        .fi-dashboard-content-table .cell-title a:hover {
            text-decoration: underline;
        }

        .dark .fi-dashboard-content-table .cell-title a {
            color: #60a5fa;
        }

        .fi-dashboard-content-table .cell-type,
        .fi-dashboard-content-table .cell-updated,
        .fi-dashboard-content-table .cell-status {
            white-space: nowrap;
        }

        .fi-dashboard-content-empty {
            border: 1px dashed #e5e7eb;
            border-radius: 0.75rem;
            background: #f9fafb;
            padding: 1.5rem 1rem;
            text-align: center;
            color: #6b7280;
            font-size: 0.875rem;
        }

        .dark .fi-dashboard-content-empty {
            border-color: rgba(255, 255, 255, 0.1);
            background: rgba(255, 255, 255, 0.05);
            color: #9ca3af;
        }

        .fi-dashboard-content-subheading {
            margin: 0 0 0.75rem;
            font-size: 0.875rem;
            font-weight: 600;
            color: #030712;
        }

        .dark .fi-dashboard-content-subheading {
            color: #ffffff;
        }
    </style>
@endonce

@if ($items->isEmpty())
    <div class="fi-dashboard-content-empty">
        {{ $emptyMessage }}
    </div>
@else
    <div class="fi-dashboard-content-table-wrap">
        <div class="fi-dashboard-content-table-scroll">
            <table class="fi-dashboard-content-table">
                <thead>
                    <tr>
                        <th scope="col" class="col-title">{{ __('cms.dashboard.content_table.title') }}</th>
                        <th scope="col" class="col-type">{{ __('cms.dashboard.content_table.type') }}</th>
                        <th scope="col" class="col-status">{{ __('cms.dashboard.content_table.status') }}</th>
                        <th scope="col" class="col-author">{{ __('cms.dashboard.content_table.author') }}</th>
                        <th scope="col" class="col-updated">{{ __('cms.dashboard.content_table.updated') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($items as $item)
                        <tr wire:key="{{ $item->key() }}-{{ $item->status->value }}">
                            <td class="cell-title">
                                <a href="{{ $item->editUrl }}" title="{{ $item->title }}">
                                    {{ $item->title }}
                                </a>
                            </td>
                            <td class="cell-type">{{ $item->typeLabel() }}</td>
                            <td class="cell-status">
                                <x-filament::badge :color="$item->status->color()">
                                    {{ $item->status->label() }}
                                </x-filament::badge>
                            </td>
                            <td class="cell-author" title="{{ $item->authorName ?? '—' }}">
                                {{ $item->authorName ?? '—' }}
                            </td>
                            <td class="cell-updated" title="{{ $item->updatedAt->diffForHumans() }}">
                                {{ $item->formattedUpdatedAt() }}
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
@endif
