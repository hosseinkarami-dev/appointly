<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="theme-color" content="#171323">
    <script>
        if (localStorage.getItem('appointly-theme') === 'dark' || (! localStorage.getItem('appointly-theme') && window.matchMedia('(prefers-color-scheme: dark)').matches)) {
            document.documentElement.classList.add('dark');
        }
    </script>
    <link rel="manifest" href="/manifest.webmanifest">
    <link rel="icon" href="/icons/app-icon.svg" type="image/svg+xml">
    <title>{{ $title ?? 'Appointly — calm scheduling for modern teams' }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @livewireStyles
</head>
<body class="min-h-screen bg-[#f8f7f4] text-[#171323] antialiased selection:bg-violet-200 selection:text-violet-950">
    <a href="#main-content" class="sr-only z-50 rounded-full bg-[#171323] px-4 py-2 text-sm font-semibold text-white focus:not-sr-only focus:fixed focus:left-4 focus:top-4">Skip to content</a>
    <header data-landing-nav class="sticky top-0 z-40 border-b border-[#171323]/10 bg-[#f8f7f4] shadow-sm backdrop-blur-xl transition duration-300 dark:border-white/10 dark:bg-[#100d1b] dark:shadow-black/20">
        <div class="mx-auto flex max-w-7xl items-center justify-between px-6 py-4 lg:px-8">
            <a href="{{ route('home') }}" class="flex items-center gap-3" aria-label="Appointly home"><span class="grid size-10 place-items-center rounded-2xl bg-[#171323] text-lg font-bold text-white shadow-lg shadow-violet-950/15">a.</span><span class="text-lg font-semibold tracking-tight dark:text-white">appointly</span></a>
            <nav class="hidden items-center gap-8 text-sm font-medium text-[#171323]/70 dark:text-white/70 md:flex">@if (! request()->routeIs('workspace.*'))<a href="{{ route('home') }}#why-appointly" class="transition hover:text-[#171323] dark:hover:text-white">Why Appointly</a><a href="{{ route('home') }}#how-it-works" class="transition hover:text-[#171323] dark:hover:text-white">How it works</a><a href="{{ route('home') }}#download" class="transition hover:text-[#171323] dark:hover:text-white">Mobile apps</a>@endif</nav>
            <div class="flex items-center gap-2"><span data-connection-status class="hidden text-xs font-medium text-emerald-700 dark:text-emerald-400 sm:block">Online</span><button data-theme-toggle aria-pressed="false" class="inline-flex items-center gap-2 rounded-full border border-[#171323]/15 bg-white px-3 py-2 text-xs font-semibold text-[#171323] shadow-sm transition hover:border-violet-300 hover:text-violet-700 dark:border-white/10 dark:bg-white/8 dark:text-white" title="Toggle dark mode"><i data-theme-icon data-feather="sun" class="size-3.5"></i><span data-theme-label class="hidden sm:inline">Dark</span></button><button data-install-app hidden class="rounded-full border border-[#171323]/15 bg-white px-3 py-2 text-xs font-semibold text-[#171323] shadow-sm transition hover:border-violet-300 hover:text-violet-700 dark:border-white/10 dark:bg-white/8 dark:text-white"><i data-feather="download" class="mr-1 inline size-3.5"></i>Install app</button>@auth<a href="{{ route('workspace.dashboard') }}" class="rounded-full bg-[#171323] px-4 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-violet-900">Workspace</a>@else<a href="{{ route('login') }}" class="hidden text-sm font-semibold text-[#171323]/75 transition hover:text-[#171323] dark:text-white/75 dark:hover:text-white sm:block">Sign in</a><a href="{{ route('register') }}" class="rounded-full bg-[#171323] px-4 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-violet-900">Get started</a>@endauth</div>
        </div>
    </header>
    <div id="main-content" tabindex="-1">@yield('content')</div>
    @if (session('welcome'))
        <span class="hidden" data-alertify-success="{{ session('welcome') }}"></span>
    @endif
    @if ($errors->any())
        <span class="hidden" data-alertify-error="{{ $errors->first() }}"></span>
    @endif
    @livewireScripts
</body>
</html>
