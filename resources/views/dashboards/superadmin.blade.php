@extends('layouts.app')

@section('title', 'Dashboard Vendor — Holding App')

@section('content')
@php
$cards = [
    ['label' => 'Total Tenant', 'value' => $stats['tenants'], 'sub' => $stats['active_tenants'].' aktif', 'bg' => 'bg-primary-container', 'text' => 'text-on-primary-container'],
    ['label' => 'Master Aplikasi', 'value' => $stats['applications'], 'sub' => 'Terdaftar di vendor', 'bg' => 'bg-secondary-container', 'text' => 'text-on-secondary-container'],
    ['label' => 'Lisensi Aktif', 'value' => $stats['tenant_applications'], 'sub' => 'Tenant ↔ Aplikasi', 'bg' => 'bg-tertiary-container', 'text' => 'text-on-tertiary-container'],
    ['label' => 'Total Pengguna', 'value' => $stats['users'], 'sub' => $stats['tenant_users'].' pengguna tenant', 'bg' => 'bg-surface-container-high', 'text' => 'text-on-surface'],
];
@endphp

<div class="mb-6 flex flex-col gap-1 sm:flex-row sm:items-end sm:justify-between">
    <div>
        <p class="text-xs font-semibold uppercase tracking-wider text-primary">Ringkasan</p>
        <h1 class="mt-1 text-3xl font-semibold tracking-tight text-on-surface">Dashboard Vendor</h1>
        <p class="mt-1 text-sm text-on-surface-variant">Selamat datang kembali. Pantau seluruh tenant dan aplikasi dari satu tempat.</p>
    </div>
    <span class="text-xs font-medium text-on-surface-variant">{{ now()->translatedFormat('l, d F Y') }}</span>
</div>

<div class="mb-6 grid grid-cols-2 gap-3 sm:gap-4 lg:grid-cols-4">
    @foreach($cards as $c)
    <div class="rounded-2xl {{ $c['bg'] }} p-5 shadow-elevated">
        <p class="text-xs font-semibold uppercase tracking-wider {{ $c['text'] }} opacity-80">{{ $c['label'] }}</p>
        <p class="mt-2 text-3xl font-bold tracking-tight {{ $c['text'] }}">{{ $c['value'] }}</p>
        <p class="mt-1 text-xs {{ $c['text'] }} opacity-70">{{ $c['sub'] }}</p>
    </div>
    @endforeach
</div>

<div class="overflow-hidden rounded-2xl bg-surface-container-lowest shadow-elevated">
    <div class="flex items-center justify-between border-b border-outline-variant px-5 py-4 sm:px-6">
        <div>
            <p class="text-[11px] font-semibold uppercase tracking-wider text-on-surface-variant">Aktivitas</p>
            <h2 class="mt-0.5 text-base font-semibold text-on-surface">Tenant Terbaru</h2>
        </div>
        <a href="{{ route('admin.applications.index') }}" class="rounded-full border border-outline bg-surface-container-lowest px-3.5 py-1.5 text-xs font-semibold text-primary hover:bg-primary-container hover:text-on-primary-container transition">Kelola Aplikasi</a>
    </div>
    <div class="overflow-x-auto">
        <table class="min-w-full divide-y divide-outline-variant">
            <thead class="bg-surface-container">
                <tr>
                    <th class="px-5 py-3 text-left text-[11px] font-semibold uppercase tracking-wider text-on-surface-variant sm:px-6">Nama</th>
                    <th class="px-5 py-3 text-left text-[11px] font-semibold uppercase tracking-wider text-on-surface-variant">Slug</th>
                    <th class="px-5 py-3 text-left text-[11px] font-semibold uppercase tracking-wider text-on-surface-variant">Email</th>
                    <th class="px-5 py-3 text-left text-[11px] font-semibold uppercase tracking-wider text-on-surface-variant">Status</th>
                    <th class="px-5 py-3 text-left text-[11px] font-semibold uppercase tracking-wider text-on-surface-variant">Terdaftar</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-outline-variant bg-surface-container-lowest">
                @forelse($recentTenants as $t)
                <tr class="hover:bg-surface-container transition">
                    <td class="whitespace-nowrap px-5 py-3.5 text-sm font-medium text-on-surface sm:px-6">{{ $t->name }}</td>
                    <td class="whitespace-nowrap px-5 py-3.5 text-sm text-on-surface-variant"><code class="rounded bg-surface-container px-1.5 py-0.5 text-xs">{{ $t->slug }}</code></td>
                    <td class="whitespace-nowrap px-5 py-3.5 text-sm text-on-surface-variant">{{ $t->email }}</td>
                    <td class="whitespace-nowrap px-5 py-3.5 text-sm">
                        <span class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-semibold {{ $t->is_active ? 'bg-secondary-container text-on-secondary-container' : 'bg-surface-container text-on-surface-variant' }}">
                            <span class="mr-1 h-1.5 w-1.5 rounded-full {{ $t->is_active ? 'bg-secondary' : 'bg-on-surface-variant' }}"></span>
                            {{ $t->is_active ? 'Aktif' : 'Nonaktif' }}
                        </span>
                    </td>
                    <td class="whitespace-nowrap px-5 py-3.5 text-xs text-on-surface-variant">{{ $t->created_at->diffForHumans() }}</td>
                </tr>
                @empty
                <tr><td colspan="5" class="px-5 py-12 text-center text-sm text-on-surface-variant sm:px-6">Belum ada tenant terdaftar.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection
