@extends('layouts.app')

@section('content')
<main class="flex min-h-[calc(100dvh-73px)] items-center justify-center px-4 py-10 sm:px-6 sm:py-12">
    <form method="POST" action="/login" class="w-full max-w-md rounded-3xl bg-white p-6 shadow-xl shadow-violet-950/5 ring-1 ring-[#171323]/10 dark:bg-[#1b1728] dark:text-white dark:shadow-black/20 dark:ring-white/10 sm:p-8">
        @csrf
        <p class="text-sm font-semibold uppercase tracking-[0.2em] text-violet-700 dark:text-violet-300">Appointly</p>
        <h1 class="mt-3 text-3xl font-semibold text-[#171323] dark:text-white">Welcome back</h1>
        <p class="mt-2 text-[#171323]/60 dark:text-white/60">Sign in to manage your appointments.</p>
        <div class="mt-8 space-y-4">
            <label class="sr-only" for="login-email">Email address</label>
            <input id="login-email" name="email" type="email" value="{{ old('email') }}" placeholder="Email address" autocomplete="email" required autofocus class="auth-input w-full rounded-xl border border-[#171323]/15 bg-[#fbfaf9] px-4 py-3 text-sm text-[#171323] shadow-sm outline-none transition placeholder:text-[#171323]/45 focus:border-violet-500 focus:ring-4 focus:ring-violet-500/10 dark:border-white/15 dark:bg-[#100d1b] dark:text-white dark:placeholder:text-white/45 dark:focus:border-violet-300">
            <label class="sr-only" for="login-password">Password</label>
            <input id="login-password" name="password" type="password" placeholder="Password" autocomplete="current-password" required class="auth-input w-full rounded-xl border border-[#171323]/15 bg-[#fbfaf9] px-4 py-3 text-sm text-[#171323] shadow-sm outline-none transition placeholder:text-[#171323]/45 focus:border-violet-500 focus:ring-4 focus:ring-violet-500/10 dark:border-white/15 dark:bg-[#100d1b] dark:text-white dark:placeholder:text-white/45 dark:focus:border-violet-300">
            @error('email')<p class="rounded-xl border border-rose-200 bg-rose-50 px-3 py-2 text-sm text-rose-700 dark:border-rose-300/20 dark:bg-rose-400/10 dark:text-rose-200">{{ $message }}</p>@enderror
            <button class="w-full rounded-xl bg-violet-700 px-4 py-3 font-semibold text-white transition hover:bg-violet-800 focus-visible:outline-3 focus-visible:outline-offset-2 focus-visible:outline-violet-400 dark:bg-violet-300 dark:text-[#171323] dark:hover:bg-violet-200">Sign in</button>
            <div class="flex items-center gap-3 text-xs font-semibold uppercase tracking-[0.18em] text-[#171323]/40 dark:text-white/40"><span class="h-px flex-1 bg-[#171323]/10 dark:bg-white/10"></span>or<span class="h-px flex-1 bg-[#171323]/10 dark:bg-white/10"></span></div>
            <a href="{{ route('auth.google.redirect') }}" class="flex w-full items-center justify-center gap-3 rounded-xl border border-[#171323]/15 bg-white px-4 py-3.5 font-semibold text-[#171323]/80 shadow-sm transition hover:border-violet-400 hover:bg-violet-50 dark:border-white/15 dark:bg-white/5 dark:text-white/85 dark:hover:border-violet-300/50 dark:hover:bg-white/10"><span class="grid size-6 place-items-center rounded-full bg-white text-base font-black text-blue-600 ring-1 ring-slate-200">G</span> Sign in with Google</a>
            <p class="text-center text-sm text-[#171323]/60 dark:text-white/60">New to Appointly? <a href="/register" class="font-semibold text-violet-700 hover:text-violet-900 dark:text-violet-300 dark:hover:text-violet-200">Create a workspace</a></p>
        </div>
    </form>
</main>
@endsection
