@extends('layouts.app')

@section('content')
<main class="flex min-h-[calc(100dvh-73px)] items-center justify-center px-4 py-8 sm:px-6 sm:py-12">
    <div class="grid w-full max-w-5xl overflow-hidden rounded-[2rem] bg-[#171323] shadow-2xl shadow-violet-950/20 lg:grid-cols-[0.85fr_1.15fr]">
        <div class="hidden p-10 text-white lg:block">
            <span class="grid size-12 place-items-center rounded-2xl bg-violet-400 text-xl font-bold text-[#171323]">a.</span>
            <p class="mt-20 text-sm font-semibold uppercase tracking-[0.2em] text-violet-300">Start with a better day</p>
            <h1 class="mt-4 text-4xl font-semibold tracking-tight">Your calmer calendar starts here.</h1>
            <p class="mt-5 leading-7 text-white/65">Set up your business, invite your rhythm, and share a booking page your clients will love.</p>
        </div>
        <form method="POST" action="/register" class="bg-white p-6 text-[#171323] dark:bg-[#1b1728] dark:text-white sm:p-10">
            @csrf
            <p class="text-sm font-semibold uppercase tracking-[0.2em] text-violet-700 dark:text-violet-300">Create workspace</p>
            <h2 class="mt-3 text-3xl font-semibold tracking-tight">Let’s get you set up.</h2>
            <div class="mt-8 grid gap-4 sm:grid-cols-2">
                <input name="name" value="{{ old('name') }}" placeholder="Your name" autocomplete="name" required class="auth-input sm:col-span-2">
                <input name="email" type="email" value="{{ old('email') }}" placeholder="Email address" autocomplete="email" required class="auth-input sm:col-span-2">
                <input name="businessName" value="{{ old('businessName') }}" placeholder="Business name" autocomplete="organization" required class="auth-input sm:col-span-2">
                <input name="password" type="password" placeholder="Password" autocomplete="new-password" required class="auth-input">
                <input name="password_confirmation" type="password" placeholder="Confirm password" autocomplete="new-password" required class="auth-input">
                <label class="sr-only" for="register-timezone">Timezone</label>
                <select id="register-timezone" name="timezone" required class="auth-input sm:col-span-2">
                    <option value="UTC" @selected(old('timezone', 'UTC') === 'UTC')>UTC</option>
                    <option value="Asia/Tehran" @selected(old('timezone') === 'Asia/Tehran')>Asia/Tehran</option>
                    <option value="Europe/London" @selected(old('timezone') === 'Europe/London')>Europe/London</option>
                    <option value="America/New_York" @selected(old('timezone') === 'America/New_York')>America/New_York</option>
                    <option value="America/Los_Angeles" @selected(old('timezone') === 'America/Los_Angeles')>America/Los_Angeles</option>
                </select>
                @if ($errors->any())<div class="sm:col-span-2 rounded-xl border border-rose-200 bg-rose-50 p-3 text-sm text-rose-700 dark:border-rose-300/20 dark:bg-rose-400/10 dark:text-rose-200">{{ $errors->first() }}</div>@endif
            </div>
            <button class="mt-6 w-full rounded-xl bg-violet-700 px-4 py-3.5 font-semibold text-white transition hover:bg-violet-800 focus-visible:outline-3 focus-visible:outline-offset-2 focus-visible:outline-violet-400 dark:bg-violet-300 dark:text-[#171323] dark:hover:bg-violet-200">Create workspace <i data-feather="arrow-right" class="ml-1 inline size-4"></i></button>
            <div class="my-4 flex items-center gap-3 text-xs font-semibold uppercase tracking-[0.18em] text-[#171323]/40 dark:text-white/40"><span class="h-px flex-1 bg-[#171323]/10 dark:bg-white/10"></span>or<span class="h-px flex-1 bg-[#171323]/10 dark:bg-white/10"></span></div>
            <a href="{{ route('auth.google.redirect') }}" class="flex w-full items-center justify-center gap-3 rounded-xl border border-[#171323]/15 bg-white px-4 py-3.5 font-semibold text-[#171323]/80 shadow-sm transition hover:border-violet-400 hover:bg-violet-50 dark:border-white/15 dark:bg-white/5 dark:text-white/85 dark:hover:border-violet-300/50 dark:hover:bg-white/10"><span class="grid size-6 place-items-center rounded-full bg-white text-base font-black text-blue-600 ring-1 ring-slate-200">G</span> Sign up with Google</a>
            <p class="mt-5 text-center text-sm text-[#171323]/60 dark:text-white/60">Already have an account? <a href="/login" class="font-semibold text-violet-700 hover:text-violet-900 dark:text-violet-300 dark:hover:text-violet-200">Sign in</a></p>
        </form>
    </div>
</main>
@endsection
