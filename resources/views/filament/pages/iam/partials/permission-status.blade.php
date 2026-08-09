@props([
    'granted' => false,
    'compact' => false,
])

@if ($compact)
    @if ($granted)
        <span
            class="inline-flex size-7 items-center justify-center rounded-full bg-success-50 text-success-600 dark:bg-success-400/10 dark:text-success-400"
            title="{{ __('cms.iam.permissions.granted') }}"
        >
            <x-filament::icon icon="heroicon-m-check" class="size-4" />
        </span>
    @else
        <span
            class="inline-flex size-7 items-center justify-center rounded-full bg-gray-100 text-gray-400 dark:bg-white/5 dark:text-gray-500"
            title="{{ __('cms.iam.permissions.denied') }}"
        >
            <x-filament::icon icon="heroicon-m-x-mark" class="size-4" />
        </span>
    @endif
@else
    @if ($granted)
        <x-filament::badge color="success" icon="heroicon-m-check">
            {{ __('cms.iam.permissions.granted') }}
        </x-filament::badge>
    @else
        <x-filament::badge color="gray" icon="heroicon-m-x-mark">
            {{ __('cms.iam.permissions.denied') }}
        </x-filament::badge>
    @endif
@endif
