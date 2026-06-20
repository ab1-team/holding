@extends('layouts.app')

@section('title', 'Beranda — Holding App')

@section('content')
@php
    $subtitle = $tenant
        ? "Tenant: <strong class='font-semibold text-on-surface'>{$tenant->name}</strong>"
        : 'Akun Anda belum terikat ke tenant manapun. Hubungi administrator.';
@endphp
<x-ui.page-header
    overline="Beranda"
    title="Selamat datang, {{ $user->name }}"
    :subtitle="$subtitle" />

@if($tenant)
@if($expiringSoon->isNotEmpty())
@php
    $expiredCount = $expiringSoon->filter(fn ($l) => $l->isExpired())->count();
    $soonCount = $expiringSoon->count() - $expiredCount;
    $variant = $expiredCount > 0 ? 'error' : 'warning';
    $title = $expiredCount > 0
        ? "{$expiredCount} lisensi sudah kedaluwarsa" . ($soonCount > 0 ? ", {$soonCount} akan kedaluwarsa" : '')
        : "{$soonCount} lisensi akan kedaluwarsa dalam 30 hari ke depan";
@endphp
<x-ui.alert :variant="$variant" :title="$title" class="mb-6">
    <ul class="mt-2 space-y-1 text-xs">
        @foreach($expiringSoon as $l)
        <li class="flex items-center justify-between gap-3">
            <span class="font-medium">{{ $l->application->name }}</span>
            <span class="font-mono {{ $l->isExpired() ? 'font-semibold' : '' }}">
                @if($l->isExpired())
                    Kedaluwarsa {{ $l->expired_at->diffForHumans() }}
                @else
                    {{ $l->expired_at->translatedFormat('d F Y') }} &middot; {{ $l->expired_at->diffForHumans() }}
                @endif
            </span>
        </li>
        @endforeach
    </ul>
    <p class="mt-3 text-xs">Hubungi vendor untuk perpanjangan lisensi.</p>
</x-ui.alert>
@endif

<x-ui.card class="mb-6" overline="Akses Cepat" title="Daftar Aplikasi">
    @if($applications->isEmpty())
        <div class="flex flex-col items-center px-5 py-10 text-center sm:px-6">
            <div class="mb-3 flex h-14 w-14 items-center justify-center rounded-full bg-surface-container text-on-surface-variant">
                <x-ui.icon name="cube" class="h-7 w-7" />
            </div>
            <h3 class="text-sm font-semibold text-on-surface">Belum ada aplikasi</h3>
            <p class="mt-1 max-w-sm text-xs text-on-surface-variant">Hubungi vendor untuk aktivasi lisensi. Setelah diaktifkan, aplikasi akan muncul di sini.</p>
        </div>
    @else
        <ul class="divide-y divide-outline-variant">
            @foreach($applications as $ta)
            @php
                $expired = $ta->isExpired();
                $daysToExpire = $ta->expired_at?->diffInDays(now(), false); // negative = future
                $expiringSoon = $ta->expired_at
                    && ! $expired
                    && $ta->expired_at->diffInDays(now()) <= 30;
            @endphp
            <li class="group flex flex-col gap-4 px-5 py-4 transition hover:bg-surface-container sm:flex-row sm:items-center sm:justify-between sm:px-6 sm:py-5">
                <div class="flex min-w-0 items-start gap-4">
                    <div class="flex h-12 w-12 shrink-0 items-center justify-center rounded-xl {{ $expired ? 'bg-error-container text-on-error-container' : 'bg-secondary-container text-on-secondary-container' }}">
                        <x-ui.icon name="cube" class="h-6 w-6" />
                    </div>
                    <div class="min-w-0 flex-1">
                        <div class="flex flex-wrap items-center gap-2">
                            <h3 class="text-sm font-semibold text-on-surface">{{ $ta->label ?: $ta->application->name }}</h3>
                            @if($ta->label)
                                <span class="text-xs text-on-surface-variant">{{ $ta->application->name }}</span>
                            @endif
                            @if($expired)
                                <x-ui.badge variant="error" size="sm" icon="x-circle">Kedaluwarsa</x-ui.badge>
                            @elseif($expiringSoon)
                                <x-ui.badge variant="warning" size="sm" icon="warning">{{ abs($daysToExpire) }} hari lagi</x-ui.badge>
                            @else
                                <x-ui.badge variant="success" size="sm" icon="check-circle">Aktif</x-ui.badge>
                            @endif
                        </div>
                        @if($ta->application->description)
                        <p class="mt-1 text-xs text-on-surface-variant">{{ Str::limit($ta->application->description, 100) }}</p>
                        @endif
                        <div class="mt-2 flex flex-wrap items-center gap-x-4 gap-y-1 text-[11px] text-on-surface-variant">
                            <span class="inline-flex items-center gap-1.5">
                                <x-ui.icon name="link" class="h-3 w-3" />
                                <span class="font-mono">{{ parse_url($ta->instance_url, PHP_URL_HOST) ?: $ta->instance_url }}</span>
                            </span>
                            @if($ta->expired_at)
                            <span class="inline-flex items-center gap-1.5">
                                <x-ui.icon name="calendar" class="h-3 w-3" />
                                <span>Berlaku hingga {{ $ta->expired_at->translatedFormat('d F Y') }}</span>
                            </span>
                            @endif
                        </div>
                    </div>
                </div>
                <div class="flex shrink-0 items-center gap-2 sm:flex-col sm:items-end sm:gap-1.5">
                    @if($expired)
                        <x-ui.button variant="outlined" size="sm" icon="lock-closed" disabled>Akses Terkunci</x-ui.button>
                        <span class="text-[11px] text-on-surface-variant">Lisensi kedaluwarsa</span>
                    @else
                        <x-ui.button
                            href="{{ route('tenant.access', $ta) }}"
                            size="sm"
                            icon="arrow-right"
                            iconPosition="right">
                            Buka Aplikasi
                        </x-ui.button>
                        @if($expiringSoon)
                            <span class="text-[11px] text-tertiary">Segera perpanjang lisensi</span>
                        @endif
                    @endif
                </div>
            </li>
            @endforeach
        </ul>
    @endif
</x-ui.card>

@if($tenant && $tenant->tenantApplications()->where('is_active', true)->whereHas('application', fn ($q) => $q->where('has_financial_report', true))->exists())
<x-ui.card>
    <div class="flex flex-col items-start gap-4 px-1 py-2 sm:flex-row sm:items-center sm:justify-between">
        <div class="flex items-start gap-3">
            <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-tertiary-container text-on-tertiary-container">
                <x-ui.icon name="chart-bar" class="h-5 w-5" />
            </div>
            <div>
                <h2 class="text-base font-semibold text-on-surface">Laporan Keuangan Komparatif</h2>
                <p class="mt-0.5 text-xs text-on-surface-variant">Bandingkan neraca, laba rugi, arus kas, dan lainnya lintas aplikasi.</p>
            </div>
        </div>
        <x-ui.button :href="route('tenant.reports.index')" variant="filled" icon="chart-bar">Lihat Laporan</x-ui.button>
    </div>
</x-ui.card>
@endif
@endif
@endsection
