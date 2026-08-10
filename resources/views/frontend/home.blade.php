@extends('frontend.layout')

@section('title', $siteTitle.' — Blog')

@section('content')
    <section class="mb-10">
        <p class="mb-2 text-sm font-semibold uppercase tracking-wider text-blue-600">Live from CMS</p>
        <h1 class="text-3xl font-bold tracking-tight text-slate-900 sm:text-4xl">Published posts</h1>
        <p class="mt-3 max-w-2xl text-base text-slate-600">
            These posts are stored in the database and rendered by the sample frontend.
            Create or edit content in the admin panel to see changes here.
        </p>
    </section>

    @if ($posts->isEmpty())
        <div class="rounded-2xl border border-dashed border-slate-300 bg-white p-10 text-center">
            <h2 class="text-lg font-semibold text-slate-800">No published posts yet</h2>
            <p class="mt-2 text-slate-600">Run <code class="rounded bg-slate-100 px-1.5 py-0.5 text-sm">php artisan db:seed --class=DemoDataSeeder</code> or publish a post in the admin.</p>
            <a href="{{ url('/admin') }}" class="mt-6 inline-flex rounded-lg bg-blue-600 px-4 py-2 text-sm font-medium text-white hover:bg-blue-700">
                Open admin panel
            </a>
        </div>
    @else
        <div class="grid gap-6">
            @foreach ($posts as $post)
                <article class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm transition hover:border-blue-200 hover:shadow-md">
                    <div class="flex flex-wrap items-center gap-2 text-xs font-medium uppercase tracking-wide text-slate-500">
                        <time datetime="{{ $post->published_at?->toIso8601String() }}">
                            {{ $post->published_at?->format('F j, Y') ?? 'Unscheduled' }}
                        </time>
                        @if ($post->author)
                            <span>&middot;</span>
                            <span>{{ $post->author->name }}</span>
                        @endif
                    </div>

                    <h2 class="mt-3 text-2xl font-semibold text-slate-900">
                        <a href="{{ route('frontend.posts.show', $post->slug) }}" class="hover:text-blue-600">
                            {{ $post->title }}
                        </a>
                    </h2>

                    @if ($post->resolvedExcerpt())
                        <p class="mt-3 text-slate-600">{{ $post->resolvedExcerpt() }}</p>
                    @endif

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

                    <a href="{{ route('frontend.posts.show', $post->slug) }}" class="mt-5 inline-flex items-center text-sm font-semibold text-blue-600 hover:text-blue-700">
                        Read more
                        <svg class="ml-1 h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                        </svg>
                    </a>
                </article>
            @endforeach
        </div>

        @if ($posts->hasPages())
            <div class="mt-8">
                {{ $posts->links() }}
            </div>
        @endif
    @endif
@endsection
