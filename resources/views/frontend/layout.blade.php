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
</head>
<body class="min-h-screen bg-slate-50 text-slate-900 antialiased">
    <header class="border-b border-slate-200 bg-white/90 backdrop-blur">
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
                    <a href="{{ route('frontend.pages.show', $navPage->slug) }}" class="text-slate-600 hover:text-blue-600">
                        {{ $navPage->title }}
                    </a>
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
