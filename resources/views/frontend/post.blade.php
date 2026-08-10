@extends('frontend.layout')

@section('title', $post->title.' — '.$siteTitle)
@section('meta_description', $post->resolvedExcerpt())

@section('content')
    <article>
        <a href="{{ route('frontend.home') }}" class="text-sm font-medium text-blue-600 hover:text-blue-700">&larr; Back to blog</a>

        <header class="mt-4 border-b border-slate-200 pb-6">
            <div class="flex flex-wrap items-center gap-2 text-xs font-medium uppercase tracking-wide text-slate-500">
                <time datetime="{{ $post->published_at?->toIso8601String() }}">
                    {{ $post->published_at?->format('F j, Y') ?? 'Unscheduled' }}
                </time>
                @if ($post->author)
                    <span>&middot;</span>
                    <span>{{ $post->author->name }}</span>
                @endif
            </div>

            <h1 class="mt-3 text-3xl font-bold tracking-tight text-slate-900 sm:text-4xl">{{ $post->title }}</h1>

            @if ($post->categories->isNotEmpty() || $post->tags->isNotEmpty())
                <div class="mt-4 flex flex-wrap gap-2">
                    @foreach ($post->categories as $category)
                        <span class="rounded-full bg-blue-50 px-2.5 py-1 text-xs font-medium text-blue-700">{{ $category->name }}</span>
                    @endforeach
                    @foreach ($post->tags as $tag)
                        <span class="rounded-full bg-slate-100 px-2.5 py-1 text-xs font-medium text-slate-600">{{ $tag->name }}</span>
                    @endforeach
                </div>
            @endif
        </header>

        @if ($post->featuredImageUrl())
            <figure class="my-8 overflow-hidden rounded-2xl border border-slate-200">
                <img src="{{ $post->featuredImageUrl('large') ?? $post->featuredImageUrl() }}" alt="{{ $post->featuredImage?->alt_text ?? $post->title }}" class="h-auto w-full object-cover">
            </figure>
        @endif

        <div class="mt-8 max-w-none space-y-4 text-base leading-7 text-slate-700 [&_a]:text-blue-600 [&_a]:underline [&_h2]:mt-8 [&_h2]:text-2xl [&_h2]:font-semibold [&_h3]:mt-6 [&_h3]:text-xl [&_h3]:font-semibold [&_p]:text-slate-700">
            {!! $post->body ?: '<p>No content yet.</p>' !!}
        </div>
    </article>
@endsection
