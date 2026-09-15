@extends('frontend.layout')

@section('title', $typeLabel.' — '.$siteTitle)

@section('content')
    <section class="mb-12 space-y-4">
        <p class="text-sm font-semibold uppercase tracking-[0.18em] text-[var(--ag-leaf)]">
            <a href="{{ route('frontend.home') }}" class="hover:text-[var(--ag-forest)]">Home</a>
            <span class="mx-2 text-slate-300">/</span>
            {{ $typeLabel }}
        </p>
        <h1 class="font-display text-3xl font-semibold tracking-tight text-[var(--ag-forest)] sm:text-4xl">{{ $typeLabel }}</h1>
        <p class="max-w-2xl text-base leading-relaxed text-slate-600">
            @switch($postType)
                @case('team-members') Leadership and staff profiles with structured team fields. @break
                @case('services') Core services with benefits, features, eligibility, and CTAs. @break
                @case('products') Product catalog with SKU, pricing, specs, and brochures. @break
                @case('testimonials') Member and partner testimonials. @break
                @case('stats') Dynamic metrics shown on the organization homepage. @break
                @case('impact-stories') Awards and impact recognition. @break
                @case('resources') Downloadable guides and documents. @break
                @case('faqs') Frequently asked questions. @break
                @case('member-saccos') Primary cooperatives in the Union. @break
                @case('join-steps') Membership application steps. @break
                @default Structured CMS entries for <code class="rounded bg-slate-100 px-1.5 py-0.5 text-sm">{{ $postType }}</code>.
            @endswitch
        </p>
    </section>

    @if ($posts->isEmpty())
        <div class="rounded-2xl border border-dashed border-emerald-900/20 bg-white p-10 text-center">
            <h2 class="text-lg font-semibold text-[var(--ag-forest)]">No published {{ strtolower($typeLabel) }} yet</h2>
            <p class="mt-2 text-slate-600">
                Run <code class="rounded bg-slate-100 px-1.5 py-0.5 text-sm">php artisan db:seed --class=AbdiGudinaSiteSeeder</code>
                or create entries in the admin.
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
            @case('stats')
                <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
                    @foreach ($posts as $post)
                        <div class="rounded-2xl border border-emerald-900/10 bg-white p-6">
                            <p class="font-display text-3xl font-semibold text-[var(--ag-forest)]">{{ $post->customField('value') }}</p>
                            <p class="mt-3 text-sm text-slate-700">{{ $post->customField('label') ?: $post->title }}</p>
                        </div>
                    @endforeach
                </div>
                @break
            @case('impact-stories')
                <div class="grid gap-6 sm:grid-cols-2">
                    @foreach ($posts as $post)
                        <article class="rounded-2xl border border-emerald-900/10 bg-white p-6">
                            <p class="text-xs font-semibold uppercase tracking-wider text-[var(--ag-gold)]">{{ $post->customField('year') }}</p>
                            <h2 class="mt-3 text-lg font-semibold text-[var(--ag-forest)]">
                                <a href="{{ route('frontend.posts.show', $post->slug) }}">{{ $post->title }}</a>
                            </h2>
                            <p class="mt-2 text-sm text-slate-600">{{ $post->customField('issuer') }}</p>
                        </article>
                    @endforeach
                </div>
                @break
            @case('resources')
                <div class="grid gap-4 sm:grid-cols-3">
                    @foreach ($posts as $post)
                        <a href="{{ $post->customField('file_url') ?: route('frontend.posts.show', $post->slug) }}" class="rounded-2xl border border-emerald-900/10 bg-white p-5 hover:border-[var(--ag-leaf)]">
                            <p class="text-xs font-semibold uppercase tracking-wider text-[var(--ag-gold)]">{{ $post->customField('category') }}</p>
                            <p class="mt-3 font-semibold text-[var(--ag-forest)]">{{ $post->title }}</p>
                            <p class="mt-4 text-sm font-semibold text-[var(--ag-leaf)]">{{ $post->customField('cta_text') ?: 'Open' }} →</p>
                        </a>
                    @endforeach
                </div>
                @break
            @case('faqs')
                <div class="space-y-3">
                    @foreach ($posts as $post)
                        <details class="rounded-2xl border border-emerald-900/10 bg-white px-5 py-4">
                            <summary class="cursor-pointer font-medium text-[var(--ag-forest)]">{{ $post->title }}</summary>
                            <p class="mt-3 text-sm leading-relaxed text-slate-600">{{ $post->customField('answer') }}</p>
                        </details>
                    @endforeach
                </div>
                @break
            @case('member-saccos')
                <div class="grid gap-3 sm:grid-cols-2 lg:grid-cols-3">
                    @foreach ($posts as $post)
                        <a href="{{ route('frontend.posts.show', $post->slug) }}" class="rounded-xl border border-emerald-900/10 bg-white px-4 py-3 hover:border-[var(--ag-leaf)]">
                            <p class="font-medium text-[var(--ag-forest)]">{{ $post->title }}</p>
                            <p class="mt-1 text-xs text-slate-500">{{ $post->customField('location') }}</p>
                        </a>
                    @endforeach
                </div>
                @break
            @case('join-steps')
                <div class="grid gap-6 sm:grid-cols-2">
                    @foreach ($posts as $post)
                        <article class="rounded-2xl border border-emerald-900/10 bg-white p-6">
                            <p class="text-xs font-semibold uppercase tracking-wider text-[var(--ag-gold)]">Step {{ $post->customField('step_number') }}</p>
                            <h2 class="mt-3 text-lg font-semibold text-[var(--ag-forest)]">{{ $post->title }}</h2>
                            <p class="mt-3 text-sm text-slate-600">{{ $post->customField('summary') }}</p>
                        </article>
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
