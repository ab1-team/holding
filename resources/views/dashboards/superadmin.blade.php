@extends('layouts.app')

@section('title', 'Dashboard Vendor — Holding App')

@section('content')
@php
$cards = [
    ['label' => 'Total Tenant', 'value' => $stats['tenants'], 'icon' => 'building', 'variant' => 'primary'],
    ['label' => 'Master Aplikasi', 'value' => $stats['applications'], 'icon' => 'cube', 'variant' => 'secondary'],
    ['label' => 'Lisensi Aktif', 'value' => $stats['tenant_applications'], 'icon' => 'key', 'variant' => 'tertiary'],
    ['label' => 'Total Pengguna', 'value' => $stats['users'], 'icon' => 'users', 'variant' => 'surface'],
];
@endphp

<x-ui.page-header
    overline="Ringkasan"
    title="Dashboard Vendor"
    subtitle="Selamat datang kembali. Pantau seluruh tenant dan aplikasi dari satu tempat.">
    <x-slot:actions>
        <span class="text-xs font-medium text-on-surface-variant">{{ now()->translatedFormat('l, d F Y') }}</span>
    </x-slot:actions>
</x-ui.page-header>

<div class="mb-6 grid grid-cols-2 gap-3 sm:gap-4 lg:grid-cols-4">
    @foreach($cards as $c)
    <x-ui.stat-card
        :label="$c['label']"
        :value="$c['value']"
        :icon="$c['icon']"
        :variant="$c['variant']" />
    @endforeach
</div>

<x-ui.card :padded="false">
    <div class="flex flex-col gap-3 border-b border-outline-variant px-5 py-5 sm:flex-row sm:items-end sm:justify-between sm:px-6">
        <div class="min-w-0">
            <p class="text-[11px] font-semibold uppercase tracking-wider text-on-surface-variant">Aktivitas</p>
            <h2 class="mt-0.5 text-xl font-semibold tracking-tight text-on-surface">Tenant Terbaru</h2>
        </div>
        <div class="flex shrink-0 items-center gap-2">
            <x-ui.badge variant="neutral" size="md">{{ $recentTenants->count() }} terbaru</x-ui.badge>
            <x-ui.button :href="route('admin.tenants.index')" variant="text" size="sm" icon="arrow-right" iconPosition="right">Lihat semua</x-ui.button>
        </div>
    </div>
    <div class="divide-y divide-outline-variant">
        @forelse($recentTenants as $t)
        <div class="flex flex-col gap-3 px-5 py-4 transition hover:bg-surface-container sm:flex-row sm:items-center sm:justify-between sm:px-6">
            <div class="flex min-w-0 items-center gap-3">
                <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full {{ $t->is_active ? 'bg-primary-container text-on-primary-container' : 'bg-surface-container text-on-surface-variant' }}">
                    <span class="text-sm font-semibold">{{ strtoupper(mb_substr($t->name, 0, 1)) }}</span>
                </div>
                <div class="min-w-0">
                    <div class="flex items-center gap-2">
                        <a href="{{ route('admin.tenants.show', $t) }}" class="truncate text-sm font-semibold text-on-surface hover:text-primary hover:underline">{{ $t->name }}</a>
                        @if(! $t->is_active)
                            <x-ui.badge variant="neutral" size="sm">Nonaktif</x-ui.badge>
                        @endif
                    </div>
                    <div class="mt-0.5 flex flex-wrap items-center gap-x-2 gap-y-0.5 text-xs text-on-surface-variant">
                        <code class="rounded bg-surface-container px-1.5 py-0.5 text-[11px]">{{ $t->slug }}</code>
                        <span class="hidden sm:inline">&middot;</span>
                        <span class="truncate">{{ $t->email }}</span>
                    </div>
                </div>
            </div>
            <div class="flex items-center gap-3 sm:flex-col sm:items-end sm:gap-1">
                <x-ui.status-badge :status="$t->is_active">
                    {{ $t->is_active ? 'Aktif' : 'Nonaktif' }}
                </x-ui.status-badge>
                <span class="text-[11px] text-on-surface-variant">Terdaftar {{ $t->created_at->diffForHumans() }}</span>
            </div>
        </div>
        @empty
        <div class="px-5 py-12 sm:px-6">
            <x-ui.empty-state
                icon="building"
                title="Belum ada tenant terdaftar"
                description="Tambahkan tenant pertama untuk mulai mengelola lisensi aplikasi.">
                <x-slot:action>
                    <x-ui.button :href="route('admin.tenants.create')" icon="plus">Tambah Tenant</x-ui.button>
                </x-slot:action>
            </x-ui.empty-state>
        </div>
        @endforelse
    </div>
</x-ui.card>
@endsection
