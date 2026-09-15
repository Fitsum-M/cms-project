@props([
    'post',
])

@php
    $url = route('frontend.posts.show', $post->slug);
    $headshot = $post->customFieldMedia('headshot_id');
    $imageUrl = $headshot
        ? ($headshot->previewUrl() ?? $headshot->originalUrl())
        : ($post->featuredImageUrl('medium') ?? $post->featuredImageUrl());
@endphp

<article {{ $attributes->class('flex flex-col overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm transition hover:border-blue-200 hover:shadow-md') }}>
    <a href="{{ $url }}" class="block aspect-square w-full overflow-hidden bg-slate-100">
        @if ($imageUrl)
            <img
                src="{{ $imageUrl }}"
                alt="{{ $post->title }}"
                class="h-full w-full object-cover transition duration-300 hover:scale-105"
                loading="lazy"
            >
        @else
            <div class="flex h-full items-center justify-center text-4xl font-semibold text-slate-300">
                {{ \Illuminate\Support\Str::substr($post->title, 0, 1) }}
            </div>
        @endif
    </a>

    <div class="flex flex-1 flex-col p-5">
        @if (filled($post->customField('department')))
            <p class="text-xs font-semibold uppercase tracking-wide text-blue-600">{{ $post->customField('department') }}</p>
        @endif
        <h3 class="mt-1 text-lg font-semibold text-slate-900">
            <a href="{{ $url }}" class="hover:text-blue-600">{{ $post->title }}</a>
        </h3>
        @if (filled($post->customField('job_title')))
            <p class="mt-1 text-sm text-slate-600">{{ $post->customField('job_title') }}</p>
        @endif
        @if (filled($post->customField('bio')))
            <p class="mt-3 line-clamp-3 text-sm text-slate-600">{{ $post->customField('bio') }}</p>
        @endif
        <div class="mt-auto pt-4">
            <a href="{{ $url }}" class="text-sm font-semibold text-blue-600 hover:text-blue-700">View profile</a>
        </div>
    </div>
</article>
