@extends('frontend.layout')

@section('title', ($heroPage?->seo?->title ?: $siteTitle))
@section('meta_description', $heroPage?->seo?->description ?: ($tagline ?: ''))

@section('content')
    {{-- Hero from CMS page home-hero --}}
    <section class="relative overflow-hidden rounded-[2rem] bg-[var(--ag-forest)] px-6 py-16 text-white sm:px-10 sm:py-20">
        <div class="pointer-events-none absolute inset-0 bg-[radial-gradient(circle_at_top_right,rgba(196,163,90,0.25),transparent_55%)]"></div>
        <div class="relative max-w-3xl">
            <p class="text-sm font-semibold uppercase tracking-[0.2em] text-[var(--ag-gold)]">
                {{ $heroPage?->seo?->description ?: 'EST. 1999 E.C · ADAMA, ETHIOPIA' }}
            </p>
            <h1 class="font-display mt-5 text-4xl font-semibold leading-tight tracking-tight sm:text-5xl lg:text-6xl">
                {{ $heroPage?->title ?: $tagline }}
            </h1>
            <div class="mt-6 max-w-2xl space-y-4 text-base leading-relaxed text-emerald-50/90 sm:text-lg [&_a]:underline [&_p]:text-emerald-50/90">
                @if ($heroPage)
                    {!! $heroPage->safeBodyHtml() !!}
                @else
                    <p>{{ $tagline }}</p>
                    <p class="text-sm text-emerald-100/80">Seed the site with <code class="rounded bg-white/10 px-1.5 py-0.5">php artisan db:seed --class=AbdiGudinaSiteSeeder</code>.</p>
                @endif
            </div>
            <div class="mt-10 flex flex-wrap gap-3">
                @if ($services->isNotEmpty())
                    <a href="{{ route('frontend.types.index', 'services') }}" class="inline-flex rounded-lg bg-white px-4 py-2.5 text-sm font-semibold text-[var(--ag-forest)] hover:bg-emerald-50">
                        Explore services
                    </a>
                @endif
                <a href="{{ route('frontend.pages.show', 'membership') }}" class="inline-flex rounded-lg border border-white/30 px-4 py-2.5 text-sm font-semibold text-white hover:bg-white/10">
                    How to join
                </a>
            </div>
        </div>
    </section>

    {{-- Stats CPT --}}
    @if ($stats->isNotEmpty())
        <section class="mt-10 grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
            @foreach ($stats as $stat)
                <div class="rounded-2xl border border-emerald-900/10 bg-white p-6 shadow-sm">
                    <p class="font-display text-3xl font-semibold text-[var(--ag-forest)]">{{ $stat->customField('value') }}</p>
                    <p class="mt-3 text-sm font-medium text-slate-700">{{ $stat->customField('label') ?: $stat->title }}</p>
                    @if (filled($stat->customField('helper_text')))
                        <p class="mt-2 text-xs text-slate-500">{{ $stat->customField('helper_text') }}</p>
                    @endif
                </div>
            @endforeach
        </section>
    @endif

    {{-- Foundation / About from CMS page --}}
    @if ($aboutPage)
        <section class="mt-24 grid gap-10 lg:grid-cols-[1.1fr_0.9fr] lg:items-start">
            <div>
                <p class="text-sm font-semibold uppercase tracking-[0.18em] text-[var(--ag-leaf)]">{{ $aboutPage->title }}</p>
                <h2 class="font-display mt-4 text-3xl font-semibold tracking-tight text-[var(--ag-forest)] sm:text-4xl">Growing together.</h2>
                <div class="mt-6 space-y-4 text-base leading-relaxed text-slate-700 [&_h3]:mt-6 [&_h3]:text-lg [&_h3]:font-semibold [&_h3]:text-[var(--ag-forest)]">
                    {!! $aboutPage->safeBodyHtml() !!}
                </div>
            </div>
            <div class="rounded-3xl border border-emerald-900/10 bg-white p-8 shadow-sm">
                <dl class="space-y-6">
                    <div>
                        <dt class="text-xs font-semibold uppercase tracking-wider text-[var(--ag-gold)]">Founded</dt>
                        <dd class="mt-2 text-lg font-semibold text-[var(--ag-forest)]">1999 E.C</dd>
                    </div>
                    <div>
                        <dt class="text-xs font-semibold uppercase tracking-wider text-[var(--ag-gold)]">Governance</dt>
                        <dd class="mt-2 text-lg font-semibold text-[var(--ag-forest)]">Member-Owned</dd>
                    </div>
                    <div>
                        <dt class="text-xs font-semibold uppercase tracking-wider text-[var(--ag-gold)]">Headquarters</dt>
                        <dd class="mt-2 text-lg font-semibold text-[var(--ag-forest)]">Adama, Ethiopia</dd>
                    </div>
                </dl>
                <a href="{{ route('frontend.pages.show', $aboutPage->slug) }}" class="mt-8 inline-flex text-sm font-semibold text-[var(--ag-leaf)] hover:text-[var(--ag-forest)]">
                    Read full about page →
                </a>
            </div>
        </section>
    @endif

    {{-- Services CPT --}}
    <section class="mt-24">
        <div class="mb-12 max-w-2xl space-y-4">
            <p class="text-sm font-semibold uppercase tracking-[0.18em] text-[var(--ag-leaf)]">Core Services</p>
            <h2 class="font-display text-3xl font-semibold tracking-tight text-[var(--ag-forest)] sm:text-4xl">Sustainable financial solutions</h2>
            <p class="text-base leading-relaxed text-slate-600">Each service is a CMS custom post with benefits, features, eligibility, and CTA fields.</p>
        </div>
        @if ($services->isEmpty())
            <p class="rounded-2xl border border-dashed border-emerald-900/20 bg-white p-8 text-center text-slate-600">No services published yet.</p>
        @else
            <div class="grid gap-6 sm:grid-cols-2">
                @foreach ($services as $index => $service)
                    <article class="rounded-2xl border border-emerald-900/10 bg-white p-6 shadow-sm">
                        <p class="text-sm font-semibold text-[var(--ag-gold)]">{{ str_pad((string) ($service->customField('display_order') ?: ($index + 1)), 2, '0', STR_PAD_LEFT) }}</p>
                        <h3 class="mt-3 text-xl font-semibold text-[var(--ag-forest)]">
                            <a href="{{ route('frontend.posts.show', $service->slug) }}" class="hover:text-[var(--ag-leaf)]">{{ $service->title }}</a>
                        </h3>
                        <p class="mt-3 text-sm leading-relaxed text-slate-600">{{ $service->resolvedExcerpt() }}</p>
                        @php
                            $benefits = $service->customField('key_benefits', []);
                        @endphp
                        @if (is_array($benefits) && $benefits !== [])
                            <ul class="mt-4 space-y-1 text-sm text-slate-600">
                                @foreach (array_slice($benefits, 0, 3) as $benefit)
                                    <li>• {{ $benefit }}</li>
                                @endforeach
                            </ul>
                        @endif
                    </article>
                @endforeach
            </div>
            <div class="mt-8">
                <a href="{{ route('frontend.types.index', 'services') }}" class="text-sm font-semibold text-[var(--ag-leaf)] hover:text-[var(--ag-forest)]">All services →</a>
            </div>
        @endif
    </section>

    {{-- Member SACCOs CPT --}}
    @if ($memberSaccos->isNotEmpty())
        <section class="mt-24">
            <div class="mb-12 max-w-2xl space-y-4">
                <p class="text-sm font-semibold uppercase tracking-[0.18em] text-[var(--ag-leaf)]">Member network</p>
                <h2 class="font-display text-3xl font-semibold tracking-tight text-[var(--ag-forest)] sm:text-4xl">{{ $memberSaccos->count() }} SACCOs. One Union.</h2>
                <p class="text-base leading-relaxed text-slate-600">Primary cooperatives federated into the Union — managed as Member SACCOs in the CMS.</p>
            </div>
            <div class="grid gap-3 sm:grid-cols-2 lg:grid-cols-3">
                @foreach ($memberSaccos as $sacco)
                    <a href="{{ route('frontend.posts.show', $sacco->slug) }}" class="rounded-xl border border-emerald-900/10 bg-white px-4 py-3 transition hover:border-[var(--ag-leaf)] hover:shadow-sm">
                        <p class="font-medium text-[var(--ag-forest)]">{{ $sacco->title }}</p>
                        <p class="mt-1 text-xs text-slate-500">
                            {{ $sacco->customField('location') }}
                            @if ($sacco->customField('membership_label'))
                                · {{ $sacco->customField('membership_label') }}
                            @endif
                        </p>
                    </a>
                @endforeach
            </div>
            <div class="mt-8">
                <a href="{{ route('frontend.types.index', 'member-saccos') }}" class="text-sm font-semibold text-[var(--ag-leaf)] hover:text-[var(--ag-forest)]">Full SACCO directory →</a>
            </div>
        </section>
    @endif

    {{-- Join steps CPT --}}
    @if ($joinSteps->isNotEmpty())
        <section class="mt-24">
            <div class="mb-12 max-w-2xl space-y-4">
                <p class="text-sm font-semibold uppercase tracking-[0.18em] text-[var(--ag-leaf)]">Membership</p>
                <h2 class="font-display text-3xl font-semibold tracking-tight text-[var(--ag-forest)] sm:text-4xl">How to join the Union</h2>
            </div>
            <div class="grid gap-6 sm:grid-cols-2">
                @foreach ($joinSteps as $step)
                    <article class="rounded-2xl border border-emerald-900/10 bg-white p-6 shadow-sm">
                        <p class="text-xs font-semibold uppercase tracking-wider text-[var(--ag-gold)]">
                            Step {{ str_pad((string) ($step->customField('step_number') ?: ''), 2, '0', STR_PAD_LEFT) }}
                        </p>
                        <h3 class="mt-3 text-lg font-semibold text-[var(--ag-forest)]">{{ $step->title }}</h3>
                        <p class="mt-3 text-sm leading-relaxed text-slate-600">{{ $step->customField('summary') }}</p>
                    </article>
                @endforeach
            </div>
        </section>
    @endif

    {{-- Leadership / Team CPT --}}
    @if ($team->isNotEmpty())
        <section class="mt-24 rounded-3xl border border-emerald-900/10 bg-white px-6 py-12 sm:px-10">
            <div class="grid gap-10 lg:grid-cols-[0.9fr_1.1fr] lg:items-center">
                @php
                    $leader = $team->first();
                @endphp
                <div>
                    <p class="text-sm font-semibold uppercase tracking-[0.18em] text-[var(--ag-leaf)]">Leadership</p>
                    <h2 class="font-display mt-4 text-3xl font-semibold text-[var(--ag-forest)]">{{ $leader->title }}</h2>
                    <p class="mt-2 text-sm font-medium text-slate-600">{{ $leader->customField('job_title') }}</p>
                    <p class="mt-6 text-lg leading-relaxed text-slate-700">“{{ $leader->customField('bio') }}”</p>
                    <a href="{{ route('frontend.types.index', 'team-members') }}" class="mt-6 inline-flex text-sm font-semibold text-[var(--ag-leaf)]">Meet the team →</a>
                </div>
                <div class="grid gap-4 sm:grid-cols-2">
                    @foreach ($team->take(4) as $member)
                        <a href="{{ route('frontend.posts.show', $member->slug) }}" class="rounded-2xl border border-emerald-900/10 bg-[var(--ag-sand)] p-5 hover:border-[var(--ag-leaf)]">
                            <p class="font-semibold text-[var(--ag-forest)]">{{ $member->title }}</p>
                            <p class="mt-1 text-sm text-slate-600">{{ $member->customField('job_title') }}</p>
                        </a>
                    @endforeach
                </div>
            </div>
        </section>
    @endif

    {{-- FAQs CPT --}}
    @if ($faqs->isNotEmpty())
        <section class="mt-24" x-data="{ open: null }">
            <div class="mb-12 max-w-2xl space-y-4">
                <p class="text-sm font-semibold uppercase tracking-[0.18em] text-[var(--ag-leaf)]">FAQs</p>
                <h2 class="font-display text-3xl font-semibold tracking-tight text-[var(--ag-forest)]">Questions members ask</h2>
            </div>
            <div class="space-y-3">
                @foreach ($faqs as $faq)
                    <div class="rounded-2xl border border-emerald-900/10 bg-white">
                        <button
                            type="button"
                            class="flex w-full items-center justify-between gap-4 px-5 py-4 text-left"
                            @click="open = open === {{ $faq->id }} ? null : {{ $faq->id }}"
                        >
                            <span class="font-medium text-[var(--ag-forest)]">{{ $faq->title }}</span>
                            <span class="text-[var(--ag-gold)]" x-text="open === {{ $faq->id }} ? '−' : '+'"></span>
                        </button>
                        <div x-cloak x-show="open === {{ $faq->id }}" class="border-t border-emerald-900/5 px-5 py-4 text-sm leading-relaxed text-slate-600">
                            {{ $faq->customField('answer') }}
                        </div>
                    </div>
                @endforeach
            </div>
        </section>
    @endif

    {{-- Impact stories CPT --}}
    @if ($impactStories->isNotEmpty())
        <section class="mt-24">
            <div class="mb-12 max-w-2xl space-y-4">
                <p class="text-sm font-semibold uppercase tracking-[0.18em] text-[var(--ag-leaf)]">Impact</p>
                <h2 class="font-display text-3xl font-semibold tracking-tight text-[var(--ag-forest)]">Recognition &amp; stories</h2>
            </div>
            <div class="grid gap-6 sm:grid-cols-2">
                @foreach ($impactStories as $story)
                    <article class="rounded-2xl border border-emerald-900/10 bg-white p-6 shadow-sm">
                        <p class="text-xs font-semibold uppercase tracking-wider text-[var(--ag-gold)]">
                            {{ $story->customField('year') }}
                        </p>
                        <h3 class="mt-3 text-lg font-semibold text-[var(--ag-forest)]">
                            <a href="{{ route('frontend.posts.show', $story->slug) }}" class="hover:text-[var(--ag-leaf)]">{{ $story->title }}</a>
                        </h3>
                        <p class="mt-2 text-sm text-slate-600">{{ $story->customField('issuer') }}</p>
                        @if (filled($story->customField('summary')))
                            <p class="mt-3 text-sm leading-relaxed text-slate-600">{{ $story->customField('summary') }}</p>
                        @endif
                    </article>
                @endforeach
            </div>
            <div class="mt-8">
                <a href="{{ route('frontend.types.index', 'impact-stories') }}" class="text-sm font-semibold text-[var(--ag-leaf)]">All impact stories →</a>
            </div>
        </section>
    @endif

    {{-- Resources CPT --}}
    @if ($resources->isNotEmpty())
        <section class="mt-24">
            <div class="mb-12 max-w-2xl space-y-4">
                <p class="text-sm font-semibold uppercase tracking-[0.18em] text-[var(--ag-leaf)]">Resources</p>
                <h2 class="font-display text-3xl font-semibold tracking-tight text-[var(--ag-forest)]">Guides &amp; downloads</h2>
            </div>
            <div class="grid gap-4 sm:grid-cols-3">
                @foreach ($resources as $resource)
                    <a href="{{ $resource->customField('file_url') ?: route('frontend.posts.show', $resource->slug) }}" class="rounded-2xl border border-emerald-900/10 bg-white p-5 shadow-sm hover:border-[var(--ag-leaf)]" @if($resource->customField('file_url')) target="_blank" rel="noopener noreferrer" @endif>
                        <p class="text-xs font-semibold uppercase tracking-wider text-[var(--ag-gold)]">{{ $resource->customField('category') ?: $resource->customField('resource_type') }}</p>
                        <p class="mt-3 font-semibold text-[var(--ag-forest)]">{{ $resource->title }}</p>
                        <p class="mt-4 text-sm font-semibold text-[var(--ag-leaf)]">{{ $resource->customField('cta_text') ?: 'Open' }} →</p>
                    </a>
                @endforeach
            </div>
        </section>
    @endif

    {{-- News / standard posts --}}
    <section class="mt-24">
        <div class="mb-12 flex flex-wrap items-end justify-between gap-6">
            <div class="max-w-2xl space-y-4">
                <p class="text-sm font-semibold uppercase tracking-[0.18em] text-[var(--ag-leaf)]">News</p>
                <h2 class="font-display text-3xl font-semibold tracking-tight text-[var(--ag-forest)]">Latest updates</h2>
            </div>
            <a href="{{ route('frontend.blog') }}" class="text-sm font-semibold text-[var(--ag-leaf)]">Visit news blog →</a>
        </div>
        @if ($news->isEmpty())
            <p class="rounded-2xl border border-dashed border-emerald-900/20 bg-white p-8 text-center text-slate-600">No news posts yet.</p>
        @else
            <x-post-grid :posts="$news" :columns="3" />
        @endif
    </section>
@endsection
