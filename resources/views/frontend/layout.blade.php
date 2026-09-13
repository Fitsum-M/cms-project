<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title', $siteTitle)</title>
    <meta name="description" content="@yield('meta_description', $tagline ?: 'Content powered by the CMS backend.')">
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=instrument-sans:400,500,600,700" rel="stylesheet">
    @if (file_exists(public_path('build/manifest.json')) || file_exists(public_path('hot')))
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    @else
        <script src="https://cdn.tailwindcss.com"></script>
    @endif
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.14.9/dist/cdn.min.js"></script>
    <style>[x-cloak]{display:none!important}</style>
</head>
<body class="min-h-screen bg-slate-50 text-slate-900 antialiased">
    <header class="relative z-40 border-b border-slate-200 bg-white/90 backdrop-blur">
        <div class="mx-auto flex max-w-5xl items-center justify-between gap-4 px-4 py-4 sm:px-6">
            <div>
                <a href="{{ route('frontend.home') }}" class="text-lg font-semibold tracking-tight text-slate-900 hover:text-blue-600">
                    {{ $siteTitle }}
                </a>
                @if (filled($tagline))
                    <p class="text-sm text-slate-500">{{ $tagline }}</p>
                @endif
            </div>
            <nav class="flex flex-wrap items-center justify-end gap-3 text-sm font-medium">
                <a href="{{ route('frontend.home') }}" class="text-slate-600 hover:text-blue-600">Blog</a>
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
                                <a
                                    href="{{ route('frontend.pages.show', $navPage->slug) }}"
                                    class="text-slate-600 hover:text-blue-600"
                                >
                                    {{ $navPage->title }}
                                </a>
                                <button
                                    type="button"
                                    class="inline-flex items-center rounded p-0.5 text-slate-500 hover:bg-slate-100 hover:text-blue-600"
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

                            {{-- top-full + padding keeps hover continuous (no dead gap) --}}
                            <div
                                x-cloak
                                x-show="open"
                                x-transition.opacity
                                class="absolute end-0 top-full z-50 pt-2"
                            >
                                <div class="min-w-[14rem] whitespace-nowrap rounded-lg border border-slate-200 bg-white py-1 text-xs shadow-lg sm:min-w-[16rem] sm:text-sm">
                                    @foreach ($navPage->children as $childPage)
                                        <a
                                            href="{{ route('frontend.pages.show', $childPage->slug) }}"
                                            class="block truncate px-3 py-2 text-slate-700 hover:bg-slate-50 hover:text-blue-600"
                                            title="{{ $childPage->title }}"
                                        >
                                            {{ \Illuminate\Support\Str::limit($childPage->title, 32) }}
                                        </a>
                                    @endforeach
                                </div>
                            </div>
                        </div>
                    @else
                        <a href="{{ route('frontend.pages.show', $navPage->slug) }}" class="text-slate-600 hover:text-blue-600">
                            {{ $navPage->title }}
                        </a>
                    @endif
                @endforeach
                <a href="{{ url('/admin') }}" class="rounded-lg bg-blue-600 px-3 py-1.5 text-white hover:bg-blue-700">
                    Admin
                </a>
            </nav>
        </div>
    </header>

    <main class="mx-auto max-w-5xl px-4 py-10 sm:px-6">
        @yield('content')
    </main>

    <footer class="border-t border-slate-200 bg-white">
        <div class="mx-auto flex max-w-5xl flex-col gap-2 px-4 py-6 text-sm text-slate-500 sm:flex-row sm:items-center sm:justify-between sm:px-6">
            <p>&copy; {{ now()->year }} {{ $siteTitle }}. Content managed via Filament CMS.</p>
            <p class="text-xs uppercase tracking-wide text-slate-400">Demo frontend · backend operational</p>
        </div>
    </footer>
</body>
</html>
