<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title', $siteTitle)</title>
    <meta name="description" content="@yield('meta_description', $tagline ?: 'Content powered by the CMS backend.')">
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=dm-sans:400,500,600,700|fraunces:600,700" rel="stylesheet">
    @if (file_exists(public_path('build/manifest.json')) || file_exists(public_path('hot')))
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    @else
        <script src="https://cdn.tailwindcss.com"></script>
    @endif
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.14.9/dist/cdn.min.js"></script>
    <style>
        [x-cloak]{display:none!important}
        :root {
            --ag-forest: #0f3d2e;
            --ag-leaf: #1f6b4a;
            --ag-gold: #c4a35a;
            --ag-sand: #f3f6f4;
        }
        body { font-family: 'DM Sans', ui-sans-serif, system-ui, sans-serif; }
        .font-display { font-family: 'Fraunces', Georgia, serif; }
    </style>
</head>
<body class="min-h-screen bg-[var(--ag-sand)] text-slate-900 antialiased">
    <header class="relative z-40 border-b border-emerald-900/10 bg-white/95 backdrop-blur">
        <div class="mx-auto flex max-w-6xl items-center justify-between gap-4 px-4 py-4 sm:px-6">
            <a href="{{ route('frontend.home') }}" class="min-w-0">
                <span class="block truncate text-lg font-semibold tracking-tight text-[var(--ag-forest)] sm:text-xl">
                    {{ \Illuminate\Support\Str::before($siteTitle, ' Financial') ?: $siteTitle }}
                </span>
                @if (filled($tagline))
                    <span class="mt-0.5 block truncate text-xs text-slate-500 sm:text-sm">{{ \Illuminate\Support\Str::limit($tagline, 64) }}</span>
                @endif
            </a>

            <nav class="flex flex-wrap items-center justify-end gap-x-3 gap-y-2 text-sm font-medium">
                <a href="{{ route('frontend.home') }}" class="text-[var(--ag-forest)] hover:text-[var(--ag-leaf)]">Home</a>
                @foreach ($navPages as $navPage)
                    @if ($navPage->children->isNotEmpty())
                        <div
                            class="relative"
                            x-data="{ open: false }"
                            @mouseenter="open = true"
                            @mouseleave="open = false"
                            @keydown.escape.window="open = false"
                        >
                            <div class="inline-flex items-center gap-1">
                                <a href="{{ route('frontend.pages.show', $navPage->slug) }}" class="text-slate-700 hover:text-[var(--ag-leaf)]">
                                    {{ $navPage->title }}
                                </a>
                                <button
                                    type="button"
                                    class="inline-flex items-center rounded p-0.5 text-slate-500 hover:bg-emerald-50 hover:text-[var(--ag-leaf)]"
                                    @click.prevent="open = !open"
                                    :aria-expanded="open.toString()"
                                    aria-haspopup="true"
                                    aria-label="{{ $navPage->title }} submenu"
                                >
                                    <svg class="h-3.5 w-3.5" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                                        <path fill-rule="evenodd" d="M5.23 7.21a.75.75 0 0 1 1.06.02L10 10.94l3.71-3.71a.75.75 0 1 1 1.06 1.06l-4.24 4.25a.75.75 0 0 1-1.06 0L5.21 8.29a.75.75 0 0 1 .02-1.08Z" clip-rule="evenodd" />
                                    </svg>
                                </button>
                            </div>
                            <div x-cloak x-show="open" x-transition.opacity class="absolute end-0 top-full z-50 pt-2">
                                <div class="min-w-[14rem] rounded-lg border border-emerald-900/10 bg-white py-1 text-xs shadow-lg sm:min-w-[16rem] sm:text-sm">
                                    @foreach ($navPage->children as $childPage)
                                        <a
                                            href="{{ route('frontend.pages.show', $childPage->slug) }}"
                                            class="block truncate px-3 py-2 text-slate-700 hover:bg-emerald-50 hover:text-[var(--ag-leaf)]"
                                            title="{{ $childPage->title }}"
                                        >
                                            {{ \Illuminate\Support\Str::limit($childPage->title, 32) }}
                                        </a>
                                    @endforeach
                                </div>
                            </div>
                        </div>
                    @else
                        <a href="{{ route('frontend.pages.show', $navPage->slug) }}" class="text-slate-700 hover:text-[var(--ag-leaf)]">
                            {{ $navPage->title }}
                        </a>
                    @endif
                @endforeach
                <a href="{{ url('/admin') }}" class="rounded-lg bg-[var(--ag-forest)] px-3 py-1.5 text-white hover:bg-[var(--ag-leaf)]">Admin</a>
            </nav>
        </div>
    </header>

    <main class="mx-auto max-w-6xl px-4 py-10 sm:px-6">
        @yield('content')
    </main>

    <footer class="border-t border-emerald-900/10 bg-[var(--ag-forest)] text-emerald-50">
        <div class="mx-auto grid max-w-6xl gap-8 px-4 py-10 sm:grid-cols-2 sm:px-6 lg:grid-cols-3">
            <div>
                <p class="font-display text-xl font-semibold text-white">{{ \Illuminate\Support\Str::before($siteTitle, ' Ltd') }}</p>
                <p class="mt-3 text-sm leading-relaxed text-emerald-100/90">{{ $tagline }}</p>
            </div>
            <div>
                <p class="text-sm font-semibold uppercase tracking-wider text-[var(--ag-gold)]">Explore</p>
                <div class="mt-3 flex flex-col gap-2 text-sm">
                    @foreach ($navPages->take(6) as $footerPage)
                        <a href="{{ route('frontend.pages.show', $footerPage->slug) }}" class="text-emerald-100 hover:text-white">{{ $footerPage->title }}</a>
                    @endforeach
                    <a href="{{ route('frontend.blog') }}" class="text-emerald-100 hover:text-white">News blog</a>
                </div>
            </div>
            <div>
                <p class="text-sm font-semibold uppercase tracking-wider text-[var(--ag-gold)]">Contact</p>
                @php
                    $footerContact = $contactPage ?? app(\App\Services\FrontendContentService::class)->findPublicPage('contact');
                @endphp
                @if ($footerContact)
                    <div class="mt-3 space-y-2 text-sm text-emerald-100 [&_a]:text-white [&_a]:underline">
                        {!! \Illuminate\Support\Str::of(strip_tags($footerContact->body ?? '', '<a><br><strong><p>'))->limit(280) !!}
                    </div>
                @else
                    <p class="mt-3 text-sm text-emerald-100">Update the Contact page in the CMS to show details here.</p>
                @endif
            </div>
        </div>
        <div class="border-t border-white/10">
            <p class="mx-auto max-w-6xl px-4 py-4 text-xs text-emerald-200/80 sm:px-6">
                &copy; {{ now()->year }} {{ $siteTitle }}. Content managed via Filament CMS — no hard-coded marketing grids.
            </p>
        </div>
    </footer>
</body>
</html>
