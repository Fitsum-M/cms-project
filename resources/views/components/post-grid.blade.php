@props([
    'posts',
    'columns' => 3,
])

@php
    $gridClass = match ((int) $columns) {
        2 => 'grid-cols-1 md:grid-cols-2',
        4 => 'grid-cols-1 md:grid-cols-2 lg:grid-cols-4',
        default => 'grid-cols-1 md:grid-cols-2 lg:grid-cols-3',
    };
@endphp

<div {{ $attributes->class(['grid gap-6', $gridClass]) }}>
    @foreach ($posts as $post)
        <x-post-card :post="$post" />
    @endforeach
</div>
