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
    <title>{{ $title ?? 'Appointly workspace' }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @livewireStyles
</head>
<body class="min-h-screen bg-[#f6f5f2] text-[#171323] antialiased dark:bg-[#100d1b] dark:text-white">
    <div class="min-h-screen items-stretch bg-[#f6f5f2] dark:bg-[#171323] lg:flex">
        @persist('workspace-sidebar')
        <aside class="sticky top-0 hidden h-screen w-72 shrink-0 border-r border-[#171323]/8 bg-white px-5 py-6 text-[#171323] lg:flex lg:flex-col dark:border-white/10 dark:bg-[#171323] dark:text-white">
            <a wire:navigate href="{{ route('workspace.dashboard') }}" class="flex items-center gap-3 px-3" aria-label="Appointly workspace home">
                <span class="grid size-10 place-items-center rounded-2xl bg-violet-400 text-lg font-bold text-[#171323]">a.</span>
                <span><span class="block text-lg font-semibold tracking-tight">appointly</span><span class="block text-xs text-[#171323]/45 dark:text-white/45">Workspace</span></span>
            </a>
            <nav class="mt-10 space-y-1" aria-label="Workspace navigation">
                @foreach ([
                    ['workspace.dashboard', 'Overview', 'grid'],
                    ['workspace.calendar', 'Calendar', 'calendar'],
                    ['workspace.appointments', 'Appointments', 'clipboard'],
                    ['workspace.customers', 'Customers', 'users'],
                    ['workspace.services', 'Services', 'briefcase'],
                    ['workspace.team', 'Team', 'users'],
                    ['workspace.reports', 'Reports', 'bar-chart-2'],
                ] as [$route, $label, $icon])
                    <a wire:navigate href="{{ route($route) }}" class="flex items-center gap-3 rounded-2xl px-3 py-3 text-sm font-medium transition {{ request()->routeIs($route) ? 'bg-violet-100 text-violet-900 dark:bg-violet-400/20 dark:text-violet-200' : 'text-[#171323]/55 hover:bg-[#171323]/5 hover:text-[#171323] dark:text-white/55 dark:hover:bg-white/8 dark:hover:text-white' }}">
                        <span class="grid size-8 place-items-center rounded-xl bg-[#171323]/5 dark:bg-white/8"><i data-feather="{{ $icon }}" class="size-4"></i></span>{{ $label }}
                    </a>
                @endforeach
            </nav>
            <div class="mt-auto space-y-2">
                <a wire:navigate href="{{ route('workspace.settings') }}" class="flex items-center gap-3 rounded-2xl px-3 py-3 text-sm font-medium transition {{ request()->routeIs('workspace.settings') ? 'bg-violet-100 text-violet-900 dark:bg-violet-400/20 dark:text-violet-200' : 'text-[#171323]/55 hover:bg-[#171323]/5 hover:text-[#171323] dark:text-white/55 dark:hover:bg-white/8 dark:hover:text-white' }}"><span class="grid size-8 place-items-center rounded-xl bg-[#171323]/5 dark:bg-white/8"><i data-feather="settings" class="size-4"></i></span>Settings</a>
            </div>
        </aside>
        @endpersist
        <div class="min-w-0 flex-1 bg-[#f6f5f2] dark:bg-[#100d1b]">
            <header class="sticky top-0 z-20 flex items-center justify-between border-b border-[#171323]/8 bg-[#f6f5f2]/85 px-5 py-4 backdrop-blur-xl dark:border-white/10 dark:bg-[#100d1b]/85 sm:px-8">
                <div class="flex items-center gap-3">
                    <a wire:navigate href="{{ route('workspace.dashboard') }}" class="grid size-10 place-items-center rounded-2xl bg-[#171323] text-lg font-bold text-white lg:hidden">a.</a>
                    <div><p class="text-sm font-semibold">{{ auth()->user()->name }}</p><p class="text-xs text-[#171323]/45 dark:text-white/45">Your calm command centre</p></div>
                </div>
                <div class="flex items-center gap-2"><button data-theme-toggle aria-pressed="false" class="grid size-10 place-items-center rounded-xl border border-[#171323]/10 bg-white text-sm transition hover:border-violet-300 dark:border-white/10 dark:bg-white/8" title="Toggle dark mode"><i data-theme-icon data-feather="sun" class="size-4"></i><span data-theme-label class="sr-only">Dark</span></button><form method="POST" action="{{ route('logout') }}">@csrf<button class="hidden items-center gap-2 rounded-xl border border-[#171323]/10 bg-white px-3 py-2 text-xs font-semibold sm:flex dark:border-white/10 dark:bg-white/8"><i data-feather="log-out" class="size-3.5"></i>Sign out</button></form></div>
            </header>
            <main id="main-content" class="mx-auto max-w-[1500px] px-5 py-7 sm:px-8 sm:py-10">@yield('content')</main>
        </div>
    </div>
    @livewireScripts
</body>
</html>
