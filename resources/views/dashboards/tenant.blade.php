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
<x-ui.card
    :padded="false"
    overline="Akses Cepat"
    title="Aplikasi Anda">
    <x-slot:header>
        <x-ui.badge variant="info" size="md">{{ $applications->count() }} aplikasi</x-ui.badge>
    </x-slot:header>
    <div class="divide-y divide-outline-variant">
        @forelse($applications as $ta)
        <div class="flex flex-col gap-3 px-5 py-4 transition hover:bg-surface-container sm:flex-row sm:items-center sm:justify-between sm:px-6">
            <div class="flex min-w-0 items-center gap-3">
                <div class="flex h-12 w-12 shrink-0 items-center justify-center rounded-xl bg-secondary-container text-on-secondary-container">
                    <x-ui.icon name="cube" class="h-6 w-6" />
                </div>
                <div class="min-w-0">
                    <div class="text-sm font-semibold text-on-surface">{{ $ta->application->name }}</div>
                    <div class="mt-0.5 truncate text-xs text-on-surface-variant">{{ $ta->instance_url }}</div>
                </div>
            </div>
            <x-ui.button href="{{ route('tenant.access', $ta) }}" icon="arrow-right" iconPosition="right">
                Buka Aplikasi
            </x-ui.button>
        </div>
        @empty
        <x-ui.empty-state
            icon="cube"
            title="Belum ada aplikasi"
            description="Hubungi vendor untuk aktivasi lisensi." />
        @endforelse
    </div>
</x-ui.card>
@endif
@endsection
