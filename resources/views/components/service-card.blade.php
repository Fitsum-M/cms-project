@props([
    'post',
])

@php
    $url = route('frontend.posts.show', $post->slug);
    $icon = $post->customFieldMedia('icon_id');
    $imageUrl = $icon
        ? ($icon->previewUrl() ?? $icon->originalUrl())
        : ($post->featuredImageUrl('medium') ?? $post->featuredImageUrl());
    $benefits = $post->customField('key_benefits', []);
@endphp

<article {{ $attributes->class('flex flex-col overflow-hidden rounded-2xl border border-slate-200 bg-white p-6 shadow-sm transition hover:border-blue-200 hover:shadow-md') }}>
    @if ($imageUrl)
        <img src="{{ $imageUrl }}" alt="" class="mb-4 h-12 w-12 rounded-lg object-cover">
    @else
        <div class="mb-4 flex h-12 w-12 items-center justify-center rounded-lg bg-blue-50 text-blue-600">
            <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M11.42 15.17l-5.2 1.74a1 1 0 01-1.27-1.27l1.74-5.2a2 2 0 01.51-.8l7.9-7.9a2.12 2.12 0 013 3l-7.9 7.9a2 2 0 01-.78.53z" />
            </svg>
        </div>
    @endif

    <h3 class="text-lg font-semibold text-slate-900">
        <a href="{{ $url }}" class="hover:text-blue-600">{{ $post->title }}</a>
    </h3>

    @if ($post->resolvedExcerpt())
        <p class="mt-2 line-clamp-3 text-sm text-slate-600">{{ $post->resolvedExcerpt() }}</p>
    @endif

    @if (is_array($benefits) && $benefits !== [])
        <ul class="mt-4 space-y-1 text-sm text-slate-600">
            @foreach (array_slice($benefits, 0, 3) as $benefit)
                <li class="flex gap-2">
                    <span class="text-blue-600" aria-hidden="true">•</span>
                    <span>{{ $benefit }}</span>
                </li>
            @endforeach
        </ul>
    @endif

    <div class="mt-auto pt-5">
        @if (filled($post->customField('cta_text')) && filled($post->customField('cta_url')))
            <a href="{{ $post->customField('cta_url') }}" class="inline-flex text-sm font-semibold text-blue-600 hover:text-blue-700">
                {{ $post->customField('cta_text') }}
            </a>
        @else
            <a href="{{ $url }}" class="inline-flex text-sm font-semibold text-blue-600 hover:text-blue-700">Learn more</a>
        @endif
    </div>
</article>
