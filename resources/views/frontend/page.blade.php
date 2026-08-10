@extends('frontend.layout')

@section('title', $page->title.' — '.$siteTitle)

@section('content')
    <article>
        <a href="{{ route('frontend.home') }}" class="text-sm font-medium text-blue-600 hover:text-blue-700">&larr; Back to blog</a>

        <header class="mt-4 border-b border-slate-200 pb-6">
            @if ($page->parent)
                <p class="text-sm text-slate-500">{{ $page->parent->title }}</p>
            @endif
            <h1 class="mt-2 text-3xl font-bold tracking-tight text-slate-900 sm:text-4xl">{{ $page->title }}</h1>
            <p class="mt-2 text-sm text-slate-500">
                @if ($page->author)
                    By {{ $page->author->name }}
                @endif
                @if ($page->published_at)
                    &middot; {{ $page->published_at->format('F j, Y') }}
                @endif
            </p>
        </header>

        <div class="mt-8 max-w-none space-y-4 text-base leading-7 text-slate-700 [&_a]:text-blue-600 [&_a]:underline [&_h2]:mt-8 [&_h2]:text-2xl [&_h2]:font-semibold [&_p]:text-slate-700">
            {!! $page->body ?: '<p>No content yet.</p>' !!}
        </div>
    </article>
@endsection
