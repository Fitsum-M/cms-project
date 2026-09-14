@extends('frontend.layout')

@section('title', $typeLabel.' — '.$siteTitle)

@section('content')
    <section class="mb-10">
        <p class="mb-2 text-sm font-semibold uppercase tracking-wider text-blue-600">Custom post type</p>
        <h1 class="text-3xl font-bold tracking-tight text-slate-900 sm:text-4xl">{{ $typeLabel }}</h1>
        <p class="mt-3 max-w-2xl text-base text-slate-600">
            Entries below are stored as CMS posts of type <code class="rounded bg-slate-100 px-1.5 py-0.5 text-sm">{{ $postType }}</code>
            with structured custom fields.
        </p>
    </section>

    @if ($posts->isEmpty())
        <div class="rounded-2xl border border-dashed border-slate-300 bg-white p-10 text-center">
            <h2 class="text-lg font-semibold text-slate-800">No published {{ strtolower($typeLabel) }} yet</h2>
            <p class="mt-2 text-slate-600">
                Run <code class="rounded bg-slate-100 px-1.5 py-0.5 text-sm">php artisan db:seed --class=CustomPostTypesDemoSeeder</code>
                or create entries under Content → Posts in the admin.
            </p>
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
