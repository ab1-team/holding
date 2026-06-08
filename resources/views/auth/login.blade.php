@extends('layouts.auth')

@section('title', 'Masuk — Holding App')

@section('content')
<div class="w-full max-w-md">
    <div class="overflow-hidden rounded-2xl bg-surface-container-lowest shadow-elevated-lg">
        <div class="flex items-center gap-3 bg-primary px-6 py-5 text-on-primary">
            <div class="flex h-12 w-12 items-center justify-center rounded-xl bg-on-primary/15">
                <svg class="h-7 w-7" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 12 12 2.25 21.75 12M4.5 9.75v10.125c0 .621.504 1.125 1.125 1.125H9.75v-4.875c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125V21h4.125c.621 0 1.125-.504 1.125-1.125V9.75M8.25 21h8.25"/>
                </svg>
            </div>
            <div>
                <div class="text-xl font-bold tracking-tight">Holding App</div>
                <div class="text-xs font-medium uppercase tracking-wider opacity-80">Pusat Kendali Multi-Tenant</div>
            </div>
        </div>

        <div class="p-6 sm:p-8">
            <h1 class="text-2xl font-semibold text-on-surface mb-1">Masuk</h1>
            <p class="text-sm text-on-surface-variant mb-6">Gunakan akun Anda untuk melanjutkan.</p>

            @if ($errors->any())
                <div class="mb-5 rounded-lg border border-error-container bg-error-container/40 px-3 py-2.5 text-sm text-on-error-container">
                    {{ $errors->first() }}
                </div>
            @endif

            <form method="POST" action="{{ route('login') }}" class="space-y-4">
                @csrf
                <div>
                    <label for="email" class="mb-1.5 block text-sm font-medium text-on-surface">Email</label>
                    <input type="email" id="email" name="email" value="{{ old('email') }}" required autofocus autocomplete="email"
                           class="block w-full rounded-lg border border-outline bg-surface-container-lowest px-3.5 py-2.5 text-sm text-on-surface placeholder:text-on-surface-variant focus:border-primary focus:ring-2 focus:ring-primary/30 focus:outline-none transition">
                </div>
                <div>
                    <label for="password" class="mb-1.5 block text-sm font-medium text-on-surface">Kata Sandi</label>
                    <input type="password" id="password" name="password" required autocomplete="current-password"
                           class="block w-full rounded-lg border border-outline bg-surface-container-lowest px-3.5 py-2.5 text-sm text-on-surface placeholder:text-on-surface-variant focus:border-primary focus:ring-2 focus:ring-primary/30 focus:outline-none transition">
                </div>
                <div class="flex items-center pt-1">
                    <input id="remember" name="remember" type="checkbox" class="h-4 w-4 rounded border-outline text-primary focus:ring-2 focus:ring-primary/30">
                    <label for="remember" class="ml-2 text-sm text-on-surface">Ingat saya</label>
                </div>
                <button type="submit" class="mt-2 w-full rounded-full bg-primary px-5 py-2.5 text-sm font-semibold text-on-primary shadow-elevated hover:bg-indigo-700 focus:ring-2 focus:ring-primary/40 focus:outline-none transition">
                    Masuk
                </button>
            </form>
        </div>
    </div>
    <p class="mt-6 text-center text-xs text-on-surface-variant">v0.1.0 &middot; &copy; {{ date('Y') }}</p>
</div>
@endsection
