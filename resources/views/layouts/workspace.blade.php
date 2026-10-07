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
<body class="min-h-dvh bg-[#f6f5f2] text-[#171323] antialiased dark:bg-[#100d1b] dark:text-white">
    <div x-data="{ mobileNavOpen: false }" @keydown.escape.window="mobileNavOpen = false" class="min-h-dvh items-stretch bg-[#f6f5f2] dark:bg-[#171323] lg:flex">
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
        <div class="min-h-dvh min-w-0 flex-1 bg-[#f6f5f2] dark:bg-[#100d1b]">
            <header class="sticky top-0 z-20 flex items-center justify-between border-b border-[#171323]/8 bg-[#f6f5f2]/90 px-5 py-4 backdrop-blur-xl dark:border-violet-200/10 dark:bg-[#1b1728]/95 sm:px-8">
                <div class="flex min-w-0 items-center gap-3">
                    <a wire:navigate href="{{ route('workspace.dashboard') }}" class="grid size-10 shrink-0 place-items-center rounded-2xl bg-[#a683ff] text-lg font-bold text-[#171323] lg:hidden" aria-label="Workspace overview">a.</a>
                    <div class="min-w-0"><p class="truncate text-sm font-semibold">{{ auth()->user()->name }}</p><p class="text-xs text-[#171323]/45 dark:text-white/45">Your calm command centre</p></div>
                </div>
                <div class="flex shrink-0 items-center gap-2"><button data-theme-toggle aria-pressed="false" class="grid size-10 place-items-center rounded-xl border border-[#171323]/10 bg-white text-sm transition duration-200 hover:border-violet-300 hover:bg-violet-50 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-violet-400/60 dark:border-white/12 dark:bg-white/5 dark:hover:border-violet-300/30 dark:hover:bg-white/10 dark:focus-visible:ring-violet-300/50" title="Toggle theme"><i data-theme-icon data-feather="sun" class="size-4"></i><span data-theme-label class="sr-only">Dark</span></button><button type="button" @click="mobileNavOpen = ! mobileNavOpen" :aria-expanded="mobileNavOpen.toString()" aria-controls="mobile-workspace-navigation" class="inline-flex min-h-10 cursor-pointer items-center gap-2 rounded-xl border border-[#171323]/10 bg-white px-3 text-sm font-semibold transition hover:border-violet-300 hover:text-violet-700 dark:border-white/10 dark:bg-white/8 dark:hover:text-violet-200 lg:hidden"><i data-feather="menu" class="size-4"></i><span>Menu</span></button><form method="POST" action="{{ route('logout') }}">@csrf<button class="hidden h-10 items-center gap-2 rounded-xl border border-[#171323]/10 bg-white px-3 text-sm font-semibold transition hover:border-violet-300 dark:border-white/10 dark:bg-white/8 lg:inline-flex"><i data-feather="log-out" class="size-4"></i>Sign out</button></form></div>
            </header>
            <div id="mobile-workspace-navigation" x-cloak x-show="mobileNavOpen" x-transition.opacity class="fixed inset-0 z-50 lg:hidden" role="dialog" aria-modal="true" aria-label="Workspace navigation">
                <button type="button" @click="mobileNavOpen = false" class="absolute inset-0 size-full cursor-pointer bg-[#100d1b]/55 backdrop-blur-sm" aria-label="Close navigation"></button>
                <aside x-show="mobileNavOpen" x-transition:enter="transition ease-out duration-300" x-transition:enter-start="-translate-x-full" x-transition:enter-end="translate-x-0" x-transition:leave="transition ease-in duration-200" x-transition:leave-start="translate-x-0" x-transition:leave-end="-translate-x-full" class="absolute inset-y-0 left-0 z-10 flex w-[min(21rem,88vw)] flex-col border-r border-[#171323]/8 bg-white px-5 py-6 text-[#171323] shadow-2xl dark:border-white/10 dark:bg-[#171323] dark:text-white" aria-label="Mobile workspace menu">
                    <div class="flex items-center justify-between gap-3 px-2">
                        <a wire:navigate href="{{ route('workspace.dashboard') }}" @click="mobileNavOpen = false" class="flex items-center gap-3" aria-label="Appointly workspace home"><span class="grid size-10 place-items-center rounded-2xl bg-violet-400 text-lg font-bold text-[#171323]">a.</span><span><span class="block text-lg font-semibold tracking-tight">appointly</span><span class="block text-xs text-[#171323]/45 dark:text-white/45">Workspace</span></span></a>
                        <button type="button" @click="mobileNavOpen = false" class="grid size-10 cursor-pointer place-items-center rounded-xl text-[#171323]/55 transition hover:bg-[#171323]/5 hover:text-[#171323] dark:text-white/60 dark:hover:bg-white/8 dark:hover:text-white" aria-label="Close menu"><i data-feather="x" class="size-5"></i></button>
                    </div>
                    <nav data-workspace-nav aria-label="Workspace navigation" class="mt-8 flex-1 space-y-1 overflow-y-auto">
                    @foreach ([
                        ['workspace.dashboard', 'Overview', 'grid'],
                        ['workspace.calendar', 'Calendar', 'calendar'],
                        ['workspace.appointments', 'Appointments', 'clipboard'],
                        ['workspace.customers', 'Customers', 'users'],
                        ['workspace.services', 'Services', 'briefcase'],
                        ['workspace.team', 'Team', 'users'],
                        ['workspace.reports', 'Reports', 'bar-chart-2'],
                        ['workspace.settings', 'Settings', 'settings'],
                    ] as [$route, $label, $icon])
                        <a wire:navigate @click="mobileNavOpen = false" href="{{ route($route) }}" aria-current="{{ request()->routeIs($route) ? 'page' : 'false' }}" class="flex min-h-12 items-center gap-3 rounded-xl px-3 py-3 text-sm font-medium transition {{ request()->routeIs($route) ? 'bg-violet-100 text-violet-900 dark:bg-violet-400/20 dark:text-violet-200' : 'text-[#171323]/55 hover:bg-[#171323]/5 hover:text-[#171323] dark:text-white/55 dark:hover:bg-white/8 dark:hover:text-white' }}"><span class="grid size-8 shrink-0 place-items-center rounded-xl bg-[#171323]/5 dark:bg-white/8"><i data-feather="{{ $icon }}" class="size-4"></i></span>{{ $label }}</a>
                    @endforeach
                    </nav>
                    <form method="POST" action="{{ route('logout') }}" class="mt-4 border-t border-[#171323]/8 pt-4 dark:border-white/10">@csrf<button class="flex w-full cursor-pointer items-center gap-3 rounded-xl px-3 py-3 text-sm font-medium text-[#171323]/55 transition hover:bg-rose-50 hover:text-rose-700 dark:text-white/55 dark:hover:bg-rose-400/10 dark:hover:text-rose-200"><span class="grid size-8 place-items-center rounded-xl bg-[#171323]/5 dark:bg-white/8"><i data-feather="log-out" class="size-4"></i></span>Sign out</button></form>
                </aside>
            </div>
            <main id="main-content" class="mx-auto max-w-[1500px] px-5 py-7 sm:px-8 sm:py-10">@yield('content')</main>
        </div>
    </div>
    @livewireScripts
</body>
</html>
