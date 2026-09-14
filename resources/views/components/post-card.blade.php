@props([
    'post',
])

@php
    $url = route('frontend.posts.show', $post->slug);
    $imageUrl = $post->featuredImageUrl('medium') ?? $post->featuredImageUrl();
@endphp

<article {{ $attributes->class('flex flex-col overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm transition hover:border-blue-200 hover:shadow-md') }}>
    @if ($imageUrl)
        <a href="{{ $url }}" class="block aspect-video w-full overflow-hidden bg-slate-100">
            <img
                src="{{ $imageUrl }}"
                alt="{{ $post->featuredImage?->alt_text ?? $post->title }}"
                class="h-full w-full object-cover transition duration-300 hover:scale-105"
                loading="lazy"
            >
        </a>
    @endif

    <div class="flex flex-1 flex-col p-6">
        <div class="flex flex-wrap items-center gap-2 text-xs font-medium uppercase tracking-wide text-slate-500">
            <time datetime="{{ $post->published_at?->toIso8601String() }}">
                {{ $post->published_at?->format('F j, Y') ?? 'Unscheduled' }}
            </time>
            @if ($post->author)
                <span>&middot;</span>
                <span>{{ $post->author->name }}</span>
            @endif
            @if (filled($post->post_type) && $post->post_type !== 'post')
                <span>&middot;</span>
                <span>{{ $post->post_type }}</span>
            @endif
        </div>

        <h2 class="mt-3 text-xl font-semibold text-slate-900 sm:text-2xl">
            <a href="{{ $url }}" class="hover:text-blue-600">
                {{ $post->title }}
            </a>
        </h2>

        @if ($post->resolvedExcerpt())
            <p class="mt-3 line-clamp-3 text-slate-600">{{ $post->resolvedExcerpt() }}</p>
        @endif

        @php
            $categories = $post->relationLoaded('categories') ? $post->categories : collect();
            $tags = $post->relationLoaded('tags') ? $post->tags : collect();
        @endphp

        @if ($categories->isNotEmpty() || $tags->isNotEmpty())
            <div class="mt-4 flex flex-wrap gap-2">
                @foreach ($categories as $category)
                    <span class="rounded-full bg-blue-50 px-2.5 py-1 text-xs font-medium text-blue-700">{{ $category->name }}</span>
                @endforeach
                @foreach ($tags as $tag)
                    <span class="rounded-full bg-slate-100 px-2.5 py-1 text-xs font-medium text-slate-600">{{ $tag->name }}</span>
                @endforeach
            </div>
        @endif

        <div class="mt-auto pt-5">
            <a href="{{ $url }}" class="inline-flex items-center text-sm font-semibold text-blue-600 hover:text-blue-700">
                Read more
                <svg class="ml-1 h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                </svg>
            </a>
        </div>
    </div>
</article>
