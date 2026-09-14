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
        <x-post-grid :posts="$posts" :columns="3" />

        @if ($posts->hasPages())
            <div class="mt-8">
                {{ $posts->links() }}
            </div>
        @endif
    @endif
@endsection
