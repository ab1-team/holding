@extends('layouts.app')

@section('title', 'Beranda — Holding App')

@section('content')
<div class="mb-6">
    <p class="text-xs font-semibold uppercase tracking-wider text-secondary">Beranda</p>
    <h1 class="mt-1 text-3xl font-semibold tracking-tight text-on-surface">Selamat datang, {{ $user->name }}</h1>
    <p class="mt-1 text-sm text-on-surface-variant">
        @if($tenant)
            Tenant: <strong class="font-semibold text-on-surface">{{ $tenant->name }}</strong>
        @else
            Akun Anda belum terikat ke tenant manapun. Hubungi administrator.
        @endif
    </p>
</div>

@if($tenant)
<div class="overflow-hidden rounded-2xl bg-surface-container-lowest shadow-elevated">
    <div class="flex items-center justify-between border-b border-outline-variant px-5 py-4 sm:px-6">
        <div>
            <p class="text-[11px] font-semibold uppercase tracking-wider text-on-surface-variant">Akses Cepat</p>
            <h2 class="mt-0.5 text-base font-semibold text-on-surface">Aplikasi Anda</h2>
        </div>
        <span class="rounded-full bg-primary-container px-3 py-1 text-xs font-semibold text-on-primary-container">{{ $applications->count() }} aplikasi</span>
    </div>
    <div class="divide-y divide-outline-variant">
        @forelse($applications as $ta)
        <div class="flex flex-col gap-3 px-5 py-4 transition hover:bg-surface-container sm:flex-row sm:items-center sm:justify-between sm:px-6">
            <div class="flex min-w-0 items-center gap-3">
                <div class="flex h-12 w-12 shrink-0 items-center justify-center rounded-xl bg-secondary-container text-on-secondary-container">
                    <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="m21 7.5-9-5.25L3 7.5m18 0-9 5.25m9-5.25v9l-9 5.25M3 7.5l9 5.25M3 7.5v9l9 5.25m0-9v9"/>
                    </svg>
                </div>
                <div class="min-w-0">
                    <div class="text-sm font-semibold text-on-surface">{{ $ta->application->name }}</div>
                    <div class="mt-0.5 truncate text-xs text-on-surface-variant">{{ $ta->instance_url }}</div>
                </div>
            </div>
            <a href="{{ route('tenant.access', $ta) }}" class="inline-flex items-center justify-center gap-2 rounded-full bg-primary px-5 py-2 text-sm font-semibold text-on-primary shadow-elevated hover:bg-indigo-700 transition">
                Buka Aplikasi
                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M13.5 6H5.25A2.25 2.25 0 0 0 3 8.25v10.5A2.25 2.25 0 0 0 5.25 21h10.5A2.25 2.25 0 0 0 18 18.75V10.5m-10.5 6L21 3m0 0h-5.25M21 3v5.25"/></svg>
            </a>
        </div>
        @empty
        <div class="px-5 py-12 text-center sm:px-6">
            <div class="mx-auto mb-3 flex h-12 w-12 items-center justify-center rounded-full bg-surface-container text-on-surface-variant">
                <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="m21 7.5-9-5.25L3 7.5m18 0-9 5.25m9-5.25v9l-9 5.25M3 7.5l9 5.25M3 7.5v9l9 5.25m0-9v9"/></svg>
            </div>
            <p class="text-sm font-medium text-on-surface">Belum ada aplikasi</p>
            <p class="mt-1 text-xs text-on-surface-variant">Hubungi vendor untuk aktivasi lisensi.</p>
        </div>
        @endforelse
    </div>
</div>
@endif
@endsection
