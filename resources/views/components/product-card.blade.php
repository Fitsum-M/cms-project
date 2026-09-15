@props([
    'post',
])

@php
    $url = route('frontend.posts.show', $post->slug);
    $imageUrl = $post->featuredImageUrl('medium') ?? $post->featuredImageUrl();
    $availability = str_replace('_', ' ', (string) ($post->customField('availability') ?: ''));
@endphp

<article {{ $attributes->class('flex flex-col overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm transition hover:border-blue-200 hover:shadow-md') }}>
    <a href="{{ $url }}" class="block aspect-video w-full overflow-hidden bg-slate-100">
        @if ($imageUrl)
            <img
                src="{{ $imageUrl }}"
                alt="{{ $post->title }}"
                class="h-full w-full object-cover transition duration-300 hover:scale-105"
                loading="lazy"
            >
        @else
            <div class="flex h-full items-center justify-center bg-gradient-to-br from-slate-100 to-slate-200 text-sm font-medium text-slate-500">
                {{ $post->customField('sku') ?: 'Product' }}
            </div>
        @endif
    </a>

    <div class="flex flex-1 flex-col p-5">
        <div class="flex flex-wrap items-center gap-2 text-xs font-medium uppercase tracking-wide text-slate-500">
            @if (filled($post->customField('sku')))
                <span>{{ $post->customField('sku') }}</span>
            @endif
            @if ($availability !== '')
                <span>&middot;</span>
                <span>{{ $availability }}</span>
            @endif
        </div>

        <h3 class="mt-2 text-lg font-semibold text-slate-900">
            <a href="{{ $url }}" class="hover:text-blue-600">{{ $post->title }}</a>
        </h3>

        @if (filled($post->customField('price')))
            <p class="mt-2 text-base font-semibold text-slate-800">{{ $post->customField('price') }}</p>
        @endif

        @if ($post->resolvedExcerpt())
            <p class="mt-2 line-clamp-3 text-sm text-slate-600">{{ $post->resolvedExcerpt() }}</p>
        @endif

        <div class="mt-auto flex flex-wrap items-center gap-3 pt-5">
            <a href="{{ $url }}" class="text-sm font-semibold text-blue-600 hover:text-blue-700">View details</a>
            @if (filled($post->customField('brochure_url')))
                <a href="{{ $post->customField('brochure_url') }}" class="text-sm font-medium text-slate-600 hover:text-slate-800" rel="noopener noreferrer" target="_blank">Brochure</a>
            @endif
        </div>
    </div>
</article>
