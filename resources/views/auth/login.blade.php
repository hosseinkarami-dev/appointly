@extends('layouts.app')

@section('content')
<main class="flex min-h-screen items-center justify-center px-6 py-12">
    <form method="POST" action="/login" class="w-full max-w-md rounded-3xl bg-white p-8 shadow-sm ring-1 ring-slate-200">
        @csrf
        <p class="text-sm font-semibold uppercase tracking-[0.2em] text-indigo-600">Appointly</p>
        <h1 class="mt-3 text-3xl font-semibold">Welcome back</h1>
        <p class="mt-2 text-slate-500">Sign in to manage your appointments.</p>
        <div class="mt-8 space-y-4">
            <input name="email" type="email" value="{{ old('email') }}" placeholder="Email address" required autofocus class="w-full rounded-xl border-slate-200 px-4 py-3">
            <input name="password" type="password" placeholder="Password" required class="w-full rounded-xl border-slate-200 px-4 py-3">
            @error('email')<p class="text-sm text-rose-600">{{ $message }}</p>@enderror
            <button class="w-full rounded-xl bg-indigo-600 px-4 py-3 font-semibold text-white hover:bg-indigo-500">Sign in</button>
            <div class="flex items-center gap-3 text-xs font-semibold uppercase tracking-[0.18em] text-slate-400"><span class="h-px flex-1 bg-slate-200"></span>or<span class="h-px flex-1 bg-slate-200"></span></div>
            <a href="{{ route('auth.google.redirect') }}" class="flex w-full items-center justify-center gap-3 rounded-xl border-2 border-slate-200 bg-white px-4 py-3.5 font-semibold text-slate-700 shadow-sm transition hover:border-violet-400 hover:bg-violet-50"><span class="grid size-6 place-items-center rounded-full bg-white text-base font-black text-blue-600 ring-1 ring-slate-200">G</span> Sign in with Google</a>
            <p class="text-center text-sm text-slate-500">New to Appointly? <a href="/register" class="font-semibold text-indigo-600">Create a workspace</a></p>
        </div>
    </form>
</main>
@endsection
