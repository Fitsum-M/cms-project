@extends('frontend.layout')

@section('title', $typeLabel.' — '.$siteTitle)

@section('content')
    <section class="mb-10">
        <p class="mb-2 text-sm font-semibold uppercase tracking-wider text-blue-600">
            <a href="{{ route('frontend.home') }}" class="hover:text-blue-700">Website</a>
            <span class="mx-1 text-slate-300">/</span>
            Custom post type
        </p>
        <h1 class="text-3xl font-bold tracking-tight text-slate-900 sm:text-4xl">{{ $typeLabel }}</h1>
        <p class="mt-3 max-w-2xl text-base text-slate-600">
            @switch($postType)
                @case('team-members')
                    People profiles with job title, department, bio, social links, and headshot fields.
                    @break
                @case('services')
                    Service offerings with icon, benefits, features, eligibility, and CTA fields.
                    @break
                @case('products')
                    Catalog entries with SKU, pricing, specifications, gallery, brochure, and availability.
                    @break
                @case('testimonials')
                    Client quotes with author, role, company, and rating metadata.
                    @break
                @default
                    Entries of type <code class="rounded bg-slate-100 px-1.5 py-0.5 text-sm">{{ $postType }}</code> with structured custom fields.
            @endswitch
        </p>
    </section>

    @if ($posts->isEmpty())
        <div class="rounded-2xl border border-dashed border-slate-300 bg-white p-10 text-center">
            <h2 class="text-lg font-semibold text-slate-800">No published {{ strtolower($typeLabel) }} yet</h2>
            <p class="mt-2 text-slate-600">
                Run <code class="rounded bg-slate-100 px-1.5 py-0.5 text-sm">php artisan db:seed --class=DemoWebsiteSeeder</code>
                or create entries under Content → Posts in the admin.
            </p>
        </div>
    @else
        @switch($postType)
            @case('team-members')
                <div class="grid gap-6 sm:grid-cols-2 lg:grid-cols-3">
                    @foreach ($posts as $post)
                        <x-team-card :post="$post" />
                    @endforeach
                </div>
                @break
            @case('services')
                <div class="grid gap-6 sm:grid-cols-2">
                    @foreach ($posts as $post)
                        <x-service-card :post="$post" />
                    @endforeach
                </div>
                @break
            @case('products')
                <div class="grid gap-6 sm:grid-cols-2 lg:grid-cols-3">
                    @foreach ($posts as $post)
                        <x-product-card :post="$post" />
                    @endforeach
                </div>
                @break
            @case('testimonials')
                <div class="grid gap-6 sm:grid-cols-2">
                    @foreach ($posts as $post)
                        <x-testimonial-card :post="$post" />
                    @endforeach
                </div>
                @break
            @default
                <x-post-grid :posts="$posts" :columns="3" />
        @endswitch

        @if ($posts->hasPages())
            <div class="mt-8">
                {{ $posts->links() }}
            </div>
        @endif
    @endif
@endsection
