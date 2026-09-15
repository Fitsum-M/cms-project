@extends('frontend.layout')

@php
    $hero = $heroPage;
    $copy = $hero?->customFields() ?? [];
    $heroEyebrow = $hero?->seo?->description;
    $cmsUrl = static function (?string $path): ?string {
        if (! filled($path)) {
            return null;
        }
        if (str_starts_with($path, 'http://') || str_starts_with($path, 'https://')) {
            return $path;
        }

        return url($path);
    };
@endphp

@section('title', ($hero?->seo?->title ?: $siteTitle))
@section('meta_description', $heroEyebrow ?: ($tagline ?: ''))

@section('content')
    <section class="relative overflow-hidden rounded-[2rem] bg-[var(--ag-forest)] px-6 py-16 text-white sm:px-10 sm:py-20">
        <div class="pointer-events-none absolute inset-0 bg-[radial-gradient(circle_at_top_right,rgba(196,163,90,0.25),transparent_55%)]"></div>
        <div class="relative max-w-3xl">
            @if (filled($heroEyebrow))
                <p class="text-sm font-semibold uppercase tracking-[0.2em] text-[var(--ag-gold)]">{{ $heroEyebrow }}</p>
            @endif
            <h1 class="font-display mt-5 text-4xl font-semibold leading-tight tracking-tight sm:text-5xl lg:text-6xl">
                {{ $hero?->title ?: $siteTitle }}
            </h1>
            <div class="mt-6 max-w-2xl space-y-4 text-base leading-relaxed text-emerald-50/90 sm:text-lg [&_a]:underline [&_p]:text-emerald-50/90">
                @if ($hero)
                    {!! $hero->safeBodyHtml() !!}
                @elseif (filled($tagline))
                    <p>{{ $tagline }}</p>
                @endif
            </div>
            @if (filled($copy['primary_cta_label'] ?? null) || filled($copy['secondary_cta_label'] ?? null))
                <div class="mt-10 flex flex-wrap gap-3">
                    @if (filled($copy['primary_cta_label'] ?? null) && filled($copy['primary_cta_url'] ?? null))
                        <a href="{{ $cmsUrl($copy['primary_cta_url']) }}" class="inline-flex rounded-lg bg-white px-4 py-2.5 text-sm font-semibold text-[var(--ag-forest)] hover:bg-emerald-50">
                            {{ $copy['primary_cta_label'] }}
                        </a>
                    @endif
                    @if (filled($copy['secondary_cta_label'] ?? null) && filled($copy['secondary_cta_url'] ?? null))
                        <a href="{{ $cmsUrl($copy['secondary_cta_url']) }}" class="inline-flex rounded-lg border border-white/30 px-4 py-2.5 text-sm font-semibold text-white hover:bg-white/10">
                            {{ $copy['secondary_cta_label'] }}
                        </a>
                    @endif
                </div>
            @endif
        </div>
    </section>

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

    @if ($aboutPage)
        <section class="mt-24 grid gap-10 lg:grid-cols-[1.1fr_0.9fr] lg:items-start">
            <div>
                <p class="text-sm font-semibold uppercase tracking-[0.18em] text-[var(--ag-leaf)]">{{ $aboutPage->title }}</p>
                @if (filled($copy['about_heading'] ?? null))
                    <h2 class="font-display mt-4 text-3xl font-semibold tracking-tight text-[var(--ag-forest)] sm:text-4xl">{{ $copy['about_heading'] }}</h2>
                @endif
                <div class="mt-6 space-y-4 text-base leading-relaxed text-slate-700 [&_h3]:mt-6 [&_h3]:text-lg [&_h3]:font-semibold [&_h3]:text-[var(--ag-forest)]">
                    {!! $aboutPage->safeBodyHtml() !!}
                </div>
            </div>
            @php
                $facts = $copy['foundation_facts'] ?? [];
            @endphp
            @if (is_array($facts) && $facts !== [])
                <div class="rounded-3xl border border-emerald-900/10 bg-white p-8 shadow-sm">
                    <dl class="space-y-6">
                        @foreach ($facts as $fact)
                            <div>
                                <dt class="text-xs font-semibold uppercase tracking-wider text-[var(--ag-gold)]">{{ $fact['label'] ?? '' }}</dt>
                                <dd class="mt-2 text-lg font-semibold text-[var(--ag-forest)]">{{ $fact['value'] ?? '' }}</dd>
                            </div>
                        @endforeach
                    </dl>
                    @if (filled($copy['about_link_label'] ?? null))
                        <a href="{{ route('frontend.pages.show', $aboutPage->slug) }}" class="mt-8 inline-flex text-sm font-semibold text-[var(--ag-leaf)] hover:text-[var(--ag-forest)]">
                            {{ $copy['about_link_label'] }}
                        </a>
                    @endif
                </div>
            @endif
        </section>
    @endif

    @if ($services->isNotEmpty() || filled($copy['services_heading'] ?? null))
        <section class="mt-24">
            <div class="mb-12 max-w-2xl space-y-4">
                @if (filled($copy['services_eyebrow'] ?? null))
                    <p class="text-sm font-semibold uppercase tracking-[0.18em] text-[var(--ag-leaf)]">{{ $copy['services_eyebrow'] }}</p>
                @endif
                @if (filled($copy['services_heading'] ?? null))
                    <h2 class="font-display text-3xl font-semibold tracking-tight text-[var(--ag-forest)] sm:text-4xl">{{ $copy['services_heading'] }}</h2>
                @endif
                @if (filled($copy['services_intro'] ?? null))
                    <p class="text-base leading-relaxed text-slate-600">{{ $copy['services_intro'] }}</p>
                @endif
            </div>
            @if ($services->isNotEmpty())
                <div class="grid gap-6 sm:grid-cols-2">
                    @foreach ($services as $index => $service)
                        <article class="rounded-2xl border border-emerald-900/10 bg-white p-6 shadow-sm">
                            <p class="text-sm font-semibold text-[var(--ag-gold)]">{{ str_pad((string) ($service->customField('display_order') ?: ($index + 1)), 2, '0', STR_PAD_LEFT) }}</p>
                            <h3 class="mt-3 text-xl font-semibold text-[var(--ag-forest)]">
                                <a href="{{ route('frontend.posts.show', $service->slug) }}" class="hover:text-[var(--ag-leaf)]">{{ $service->title }}</a>
                            </h3>
                            <p class="mt-3 text-sm leading-relaxed text-slate-600">{{ $service->resolvedExcerpt() }}</p>
                            @php $benefits = $service->customField('key_benefits', []); @endphp
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
                @if (filled($copy['services_all_label'] ?? null))
                    <div class="mt-8">
                        <a href="{{ route('frontend.types.index', 'services') }}" class="text-sm font-semibold text-[var(--ag-leaf)] hover:text-[var(--ag-forest)]">{{ $copy['services_all_label'] }}</a>
                    </div>
                @endif
            @endif
        </section>
    @endif

    @if ($memberSaccos->isNotEmpty())
        <section class="mt-24">
            <div class="mb-12 max-w-2xl space-y-4">
                @if (filled($copy['saccos_eyebrow'] ?? null))
                    <p class="text-sm font-semibold uppercase tracking-[0.18em] text-[var(--ag-leaf)]">{{ $copy['saccos_eyebrow'] }}</p>
                @endif
                @if (filled($copy['saccos_heading'] ?? null))
                    <h2 class="font-display text-3xl font-semibold tracking-tight text-[var(--ag-forest)] sm:text-4xl">
                        {{ str_replace(':count', (string) $memberSaccos->count(), $copy['saccos_heading']) }}
                    </h2>
                @endif
                @if (filled($copy['saccos_intro'] ?? null))
                    <p class="text-base leading-relaxed text-slate-600">{{ $copy['saccos_intro'] }}</p>
                @endif
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
            @if (filled($copy['saccos_all_label'] ?? null))
                <div class="mt-8">
                    <a href="{{ route('frontend.types.index', 'member-saccos') }}" class="text-sm font-semibold text-[var(--ag-leaf)] hover:text-[var(--ag-forest)]">{{ $copy['saccos_all_label'] }}</a>
                </div>
            @endif
        </section>
    @endif

    @if ($joinSteps->isNotEmpty())
        <section class="mt-24">
            <div class="mb-12 max-w-2xl space-y-4">
                @if (filled($copy['join_eyebrow'] ?? null))
                    <p class="text-sm font-semibold uppercase tracking-[0.18em] text-[var(--ag-leaf)]">{{ $copy['join_eyebrow'] }}</p>
                @endif
                @if (filled($copy['join_heading'] ?? null))
                    <h2 class="font-display text-3xl font-semibold tracking-tight text-[var(--ag-forest)] sm:text-4xl">{{ $copy['join_heading'] }}</h2>
                @endif
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

    @if ($team->isNotEmpty())
        <section class="mt-24 rounded-3xl border border-emerald-900/10 bg-white px-6 py-12 sm:px-10">
            <div class="grid gap-10 lg:grid-cols-[0.9fr_1.1fr] lg:items-center">
                @php $leader = $team->first(); @endphp
                <div>
                    @if (filled($copy['leadership_eyebrow'] ?? null))
                        <p class="text-sm font-semibold uppercase tracking-[0.18em] text-[var(--ag-leaf)]">{{ $copy['leadership_eyebrow'] }}</p>
                    @endif
                    <h2 class="font-display mt-4 text-3xl font-semibold text-[var(--ag-forest)]">{{ $leader->title }}</h2>
                    <p class="mt-2 text-sm font-medium text-slate-600">{{ $leader->customField('job_title') }}</p>
                    <p class="mt-6 text-lg leading-relaxed text-slate-700">“{{ $leader->customField('bio') }}”</p>
                    @if (filled($copy['leadership_link_label'] ?? null))
                        <a href="{{ route('frontend.types.index', 'team-members') }}" class="mt-6 inline-flex text-sm font-semibold text-[var(--ag-leaf)]">{{ $copy['leadership_link_label'] }}</a>
                    @endif
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

    @if ($faqs->isNotEmpty())
        <section class="mt-24" x-data="{ open: null }">
            <div class="mb-12 max-w-2xl space-y-4">
                @if (filled($copy['faqs_eyebrow'] ?? null))
                    <p class="text-sm font-semibold uppercase tracking-[0.18em] text-[var(--ag-leaf)]">{{ $copy['faqs_eyebrow'] }}</p>
                @endif
                @if (filled($copy['faqs_heading'] ?? null))
                    <h2 class="font-display text-3xl font-semibold tracking-tight text-[var(--ag-forest)]">{{ $copy['faqs_heading'] }}</h2>
                @endif
            </div>
            <div class="space-y-3">
                @foreach ($faqs as $faq)
                    <div class="rounded-2xl border border-emerald-900/10 bg-white">
                        <button type="button" class="flex w-full items-center justify-between gap-4 px-5 py-4 text-left" @click="open = open === {{ $faq->id }} ? null : {{ $faq->id }}">
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

    @if ($impactStories->isNotEmpty())
        <section class="mt-24">
            <div class="mb-12 max-w-2xl space-y-4">
                @if (filled($copy['impact_eyebrow'] ?? null))
                    <p class="text-sm font-semibold uppercase tracking-[0.18em] text-[var(--ag-leaf)]">{{ $copy['impact_eyebrow'] }}</p>
                @endif
                @if (filled($copy['impact_heading'] ?? null))
                    <h2 class="font-display text-3xl font-semibold tracking-tight text-[var(--ag-forest)]">{{ $copy['impact_heading'] }}</h2>
                @endif
            </div>
            <div class="grid gap-6 sm:grid-cols-2">
                @foreach ($impactStories as $story)
                    <article class="rounded-2xl border border-emerald-900/10 bg-white p-6 shadow-sm">
                        <p class="text-xs font-semibold uppercase tracking-wider text-[var(--ag-gold)]">{{ $story->customField('year') }}</p>
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
            @if (filled($copy['impact_all_label'] ?? null))
                <div class="mt-8">
                    <a href="{{ route('frontend.types.index', 'impact-stories') }}" class="text-sm font-semibold text-[var(--ag-leaf)]">{{ $copy['impact_all_label'] }}</a>
                </div>
            @endif
        </section>
    @endif

    @if ($resources->isNotEmpty())
        <section class="mt-24">
            <div class="mb-12 max-w-2xl space-y-4">
                @if (filled($copy['resources_eyebrow'] ?? null))
                    <p class="text-sm font-semibold uppercase tracking-[0.18em] text-[var(--ag-leaf)]">{{ $copy['resources_eyebrow'] }}</p>
                @endif
                @if (filled($copy['resources_heading'] ?? null))
                    <h2 class="font-display text-3xl font-semibold tracking-tight text-[var(--ag-forest)]">{{ $copy['resources_heading'] }}</h2>
                @endif
            </div>
            <div class="grid gap-4 sm:grid-cols-3">
                @foreach ($resources as $resource)
                    <a href="{{ $resource->customField('file_url') ?: route('frontend.posts.show', $resource->slug) }}" class="rounded-2xl border border-emerald-900/10 bg-white p-5 shadow-sm hover:border-[var(--ag-leaf)]" @if($resource->customField('file_url')) target="_blank" rel="noopener noreferrer" @endif>
                        <p class="text-xs font-semibold uppercase tracking-wider text-[var(--ag-gold)]">{{ $resource->customField('category') ?: $resource->customField('resource_type') }}</p>
                        <p class="mt-3 font-semibold text-[var(--ag-forest)]">{{ $resource->title }}</p>
                        @if (filled($resource->customField('cta_text')))
                            <p class="mt-4 text-sm font-semibold text-[var(--ag-leaf)]">{{ $resource->customField('cta_text') }} →</p>
                        @endif
                    </a>
                @endforeach
            </div>
        </section>
    @endif

    <section class="mt-24">
        <div class="mb-12 flex flex-wrap items-end justify-between gap-6">
            <div class="max-w-2xl space-y-4">
                @if (filled($copy['news_eyebrow'] ?? null))
                    <p class="text-sm font-semibold uppercase tracking-[0.18em] text-[var(--ag-leaf)]">{{ $copy['news_eyebrow'] }}</p>
                @endif
                @if (filled($copy['news_heading'] ?? null))
                    <h2 class="font-display text-3xl font-semibold tracking-tight text-[var(--ag-forest)]">{{ $copy['news_heading'] }}</h2>
                @endif
            </div>
            @if (filled($copy['news_all_label'] ?? null))
                <a href="{{ route('frontend.blog') }}" class="text-sm font-semibold text-[var(--ag-leaf)]">{{ $copy['news_all_label'] }}</a>
            @endif
        </div>
        @if ($news->isNotEmpty())
            <x-post-grid :posts="$news" :columns="3" />
        @endif
    </section>
@endsection
