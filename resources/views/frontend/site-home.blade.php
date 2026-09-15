@extends('frontend.layout')

@section('title', $siteTitle.($tagline ? ' — '.$tagline : ''))
@section('meta_description', $tagline ?: 'Organization website powered by CMS custom post types and structured fields.')

@section('content')
    <section class="relative overflow-hidden rounded-3xl border border-slate-200 bg-gradient-to-br from-slate-900 via-slate-800 to-blue-900 px-6 py-14 text-white sm:px-10 sm:py-16">
        <div class="relative max-w-2xl">
            <p class="text-sm font-semibold uppercase tracking-wider text-blue-200">CMS demo website</p>
            <h1 class="mt-4 text-4xl font-bold tracking-tight sm:text-5xl">{{ $siteTitle }}</h1>
            <p class="mt-5 text-lg leading-relaxed text-slate-200">
                {{ $tagline ?: 'A complete multi-page site driven by Team, Services, Products, and Testimonials custom post types — not a blog-only CMS.' }}
            </p>
            <div class="mt-10 flex flex-wrap gap-3">
                <a href="{{ route('frontend.types.index', 'services') }}" class="inline-flex rounded-lg bg-white px-4 py-2.5 text-sm font-semibold text-slate-900 hover:bg-slate-100">
                    Explore services
                </a>
                <a href="{{ route('frontend.types.index', 'team-members') }}" class="inline-flex rounded-lg border border-white/30 px-4 py-2.5 text-sm font-semibold text-white hover:bg-white/10">
                    Meet the team
                </a>
            </div>
        </div>
    </section>

    <section class="mt-24">
        <div class="mb-12 flex flex-wrap items-end justify-between gap-6">
            <div class="max-w-2xl space-y-4">
                <p class="text-sm font-semibold uppercase tracking-wider text-blue-600">Services</p>
                <h2 class="text-2xl font-bold tracking-tight text-slate-900 sm:text-3xl">What we deliver</h2>
                <p class="text-base leading-relaxed text-slate-600">Each service is a CPT entry with benefits, features, eligibility, and CTA fields from the admin.</p>
            </div>
            <a href="{{ route('frontend.types.index', 'services') }}" class="text-sm font-semibold text-blue-600 hover:text-blue-700">All services</a>
        </div>
        @if ($services->isEmpty())
            <p class="rounded-2xl border border-dashed border-slate-300 bg-white p-8 text-center text-slate-600">
                No services yet. Run <code class="rounded bg-slate-100 px-1.5 py-0.5 text-sm">php artisan db:seed --class=DemoWebsiteSeeder</code>.
            </p>
        @else
            <div class="grid gap-8 sm:grid-cols-2">
                @foreach ($services as $service)
                    <x-service-card :post="$service" />
                @endforeach
            </div>
        @endif
    </section>

    <section class="mt-24">
        <div class="mb-12 flex flex-wrap items-end justify-between gap-6">
            <div class="max-w-2xl space-y-4">
                <p class="text-sm font-semibold uppercase tracking-wider text-blue-600">Team</p>
                <h2 class="text-2xl font-bold tracking-tight text-slate-900 sm:text-3xl">Leadership &amp; people</h2>
                <p class="text-base leading-relaxed text-slate-600">Profiles use job title, department, bio, social links, and headshot custom fields.</p>
            </div>
            <a href="{{ route('frontend.types.index', 'team-members') }}" class="text-sm font-semibold text-blue-600 hover:text-blue-700">Full team</a>
        </div>
        @if ($team->isEmpty())
            <p class="rounded-2xl border border-dashed border-slate-300 bg-white p-8 text-center text-slate-600">No team members published yet.</p>
        @else
            <div class="grid gap-8 sm:grid-cols-2 lg:grid-cols-3">
                @foreach ($team as $member)
                    <x-team-card :post="$member" />
                @endforeach
            </div>
        @endif
    </section>

    <section class="mt-24">
        <div class="mb-12 flex flex-wrap items-end justify-between gap-6">
            <div class="max-w-2xl space-y-4">
                <p class="text-sm font-semibold uppercase tracking-wider text-blue-600">Products</p>
                <h2 class="text-2xl font-bold tracking-tight text-slate-900 sm:text-3xl">Packaged offerings</h2>
                <p class="text-base leading-relaxed text-slate-600">SKU, pricing, specs, gallery, brochure URL, and availability — all from structured product fields.</p>
            </div>
            <a href="{{ route('frontend.types.index', 'products') }}" class="text-sm font-semibold text-blue-600 hover:text-blue-700">All products</a>
        </div>
        @if ($products->isEmpty())
            <p class="rounded-2xl border border-dashed border-slate-300 bg-white p-8 text-center text-slate-600">No products published yet.</p>
        @else
            <div class="grid gap-8 sm:grid-cols-2">
                @foreach ($products as $product)
                    <x-product-card :post="$product" />
                @endforeach
            </div>
        @endif
    </section>

    <section class="mt-24">
        <div class="mb-12 flex flex-wrap items-end justify-between gap-6">
            <div class="max-w-2xl space-y-4">
                <p class="text-sm font-semibold uppercase tracking-wider text-blue-600">Testimonials</p>
                <h2 class="text-2xl font-bold tracking-tight text-slate-900 sm:text-3xl">What clients say</h2>
                <p class="text-base leading-relaxed text-slate-600">Quotes, roles, companies, and ratings stored as testimonial CPT metadata.</p>
            </div>
            <a href="{{ route('frontend.types.index', 'testimonials') }}" class="text-sm font-semibold text-blue-600 hover:text-blue-700">All testimonials</a>
        </div>
        @if ($testimonials->isEmpty())
            <p class="rounded-2xl border border-dashed border-slate-300 bg-white p-8 text-center text-slate-600">No testimonials published yet.</p>
        @else
            <div class="grid gap-8 sm:grid-cols-2">
                @foreach ($testimonials as $testimonial)
                    <x-testimonial-card :post="$testimonial" />
                @endforeach
            </div>
        @endif
    </section>

    <section class="mt-24">
        <div class="mb-12 flex flex-wrap items-end justify-between gap-6">
            <div class="max-w-2xl space-y-4">
                <p class="text-sm font-semibold uppercase tracking-wider text-blue-600">News</p>
                <h2 class="text-2xl font-bold tracking-tight text-slate-900 sm:text-3xl">Latest from the blog</h2>
                <p class="text-base leading-relaxed text-slate-600">Standard posts still power news — separate from structured CPT content.</p>
            </div>
            <a href="{{ route('frontend.blog') }}" class="text-sm font-semibold text-blue-600 hover:text-blue-700">Visit blog</a>
        </div>
        @if ($news->isEmpty())
            <p class="rounded-2xl border border-dashed border-slate-300 bg-white p-8 text-center text-slate-600">
                No blog posts yet. Seed demo data or publish a post in admin.
            </p>
        @else
            <x-post-grid :posts="$news" :columns="3" />
        @endif
    </section>

    <section class="mt-24 rounded-3xl border border-slate-200 bg-white px-6 py-12 text-center sm:px-10 sm:py-14">
        <h2 class="text-2xl font-bold tracking-tight text-slate-900">Ready to edit this site from the CMS?</h2>
        <p class="mx-auto mt-5 max-w-xl text-base leading-relaxed text-slate-600">
            Every section above is powered by Filament admin content. Change a service CTA or team bio and refresh — no hard-coded marketing copy in these grids.
        </p>
        <div class="mt-8 flex flex-wrap justify-center gap-3">
            <a href="{{ url('/admin') }}" class="inline-flex rounded-lg bg-blue-600 px-4 py-2.5 text-sm font-semibold text-white hover:bg-blue-700">Open admin</a>
            <a href="{{ route('frontend.pages.show', 'contact') }}" class="inline-flex rounded-lg border border-slate-300 px-4 py-2.5 text-sm font-semibold text-slate-700 hover:bg-slate-50">Contact page</a>
        </div>
    </section>
@endsection
