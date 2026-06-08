@extends('layouts.app')

@section('title', "{$user->name} — Holding App")

@section('content')
<div class="mb-6 flex flex-col gap-2 sm:flex-row sm:items-start sm:justify-between">
    <div class="flex items-center gap-4">
        <div class="flex h-16 w-16 items-center justify-center rounded-2xl bg-primary-container text-2xl font-semibold text-on-primary-container">{{ strtoupper(mb_substr($user->name, 0, 1)) }}</div>
        <div>
            <p class="text-xs font-semibold uppercase tracking-wider text-primary">Pengguna</p>
            <h1 class="mt-1 text-3xl font-semibold tracking-tight text-on-surface">{{ $user->name }}</h1>
            <p class="mt-0.5 text-sm text-on-surface-variant">{{ $user->email }}</p>
        </div>
    </div>
    <div class="flex flex-wrap gap-2">
        <x-ui.button :href="route('admin.users.edit', $user)" variant="outlined" icon="pencil">Edit</x-ui.button>
    </div>
</div>

@if(session('status'))
    <div class="mb-4"><x-ui.alert variant="success">{{ session('status') }}</x-ui.alert></div>
@endif
@if(session('new_password'))
    <div class="mb-4">
        <x-ui.alert variant="warning" title="Password baru telah di-generate.">
            Salin nilai di bawah — hanya ditampilkan sekali dan tidak dapat dilihat kembali.
            <div class="mt-2 flex max-w-md gap-2">
                <input type="text" id="newPwdInput" value="{{ session('new_password') }}" readonly class="block w-full rounded-lg border border-tertiary-container bg-surface-container-lowest px-3 py-2 font-mono text-xs">
                <button type="button" x-data x-on:click="navigator.clipboard.writeText(document.getElementById('newPwdInput').value)" class="shrink-0 rounded-full bg-tertiary px-4 py-2 text-xs font-semibold text-on-tertiary hover:opacity-90 transition">Salin</button>
            </div>
        </x-ui.alert>
    </div>
@endif

<x-ui.card title="Detail Pengguna" overline="Informasi">
    <dl class="grid grid-cols-1 gap-4 sm:grid-cols-2">
        <div>
            <dt class="text-xs font-semibold uppercase tracking-wider text-on-surface-variant">Role</dt>
            <dd class="mt-1">
                @php
                    $roleLabels = ['superadmin' => ['Vendor Admin', 'bg-primary-container text-on-primary-container'], 'tenant_owner' => ['Pemilik Tenant', 'bg-secondary-container text-on-secondary-container'], 'tenant_staff' => ['Staff Tenant', 'bg-tertiary-container text-on-tertiary-container']];
                    $rl = $roleLabels[$user->role] ?? [$user->role, 'bg-surface-container text-on-surface-variant'];
                @endphp
                <span class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-semibold {{ $rl[1] }}">{{ $rl[0] }}</span>
            </dd>
        </div>
        <div>
            <dt class="text-xs font-semibold uppercase tracking-wider text-on-surface-variant">Tenant</dt>
            <dd class="mt-1 text-sm">
                @if($user->tenant)
                <a href="{{ route('admin.tenants.show', $user->tenant) }}" class="font-medium text-primary hover:underline">{{ $user->tenant->name }}</a>
                @else
                <span class="text-on-surface-variant">—</span>
                @endif
            </dd>
        </div>
        <div>
            <dt class="text-xs font-semibold uppercase tracking-wider text-on-surface-variant">Status</dt>
            <dd class="mt-1">
                <span class="inline-flex items-center gap-1 rounded-full px-2.5 py-0.5 text-xs font-semibold {{ $user->is_active ? 'bg-secondary-container text-on-secondary-container' : 'bg-surface-container text-on-surface-variant' }}">
                    <span class="h-1.5 w-1.5 rounded-full {{ $user->is_active ? 'bg-secondary' : 'bg-on-surface-variant' }}"></span>
                    {{ $user->is_active ? 'Aktif' : 'Nonaktif' }}
                </span>
            </dd>
        </div>
        <div>
            <dt class="text-xs font-semibold uppercase tracking-wider text-on-surface-variant">Login Terakhir</dt>
            <dd class="mt-1 text-sm text-on-surface">{{ $user->last_login_at?->translatedFormat('d F Y, H:i') ?? 'Belum pernah' }}</dd>
        </div>
    </dl>
</x-ui.card>
@endsection