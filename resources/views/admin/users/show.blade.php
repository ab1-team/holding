@extends('layouts.app')

@section('title', "{$user->name} — Holding App")

@section('content')
<x-ui.page-header
    overline="Pengguna"
    title="{{ $user->name }}"
    subtitle="{{ $user->email }}">
    <x-slot:actions>
        <x-ui.button :href="route('admin.users.edit', $user)" variant="outlined" icon="pencil">Edit</x-ui.button>
    </x-slot:actions>
</x-ui.page-header>

@if(session('status'))
    {{-- status handled by layout --}}
@endif
@if(session('new_password'))
    <div class="mb-4" x-data="{ copied: false }">
        <x-ui.alert variant="warning" title="Password baru telah di-generate.">
            Salin nilai di bawah — hanya ditampilkan sekali dan tidak dapat dilihat kembali.
            <div class="mt-3 flex max-w-md flex-col gap-2 sm:flex-row sm:items-center">
                <input type="text" id="newPwdInput" value="{{ session('new_password') }}" readonly
                       class="block w-full rounded-lg border border-tertiary-container bg-surface-container-lowest px-3 py-2 font-mono text-xs text-on-surface focus:outline-none focus:ring-2 focus:ring-tertiary/30"
                       @focus="$el.select()">
                <button type="button"
                        @click="navigator.clipboard.writeText(document.getElementById('newPwdInput').value); copied = true; setTimeout(() => copied = false, 2000)"
                        :class="copied ? 'bg-secondary text-on-secondary' : 'bg-tertiary text-on-tertiary'"
                        class="inline-flex shrink-0 items-center justify-center gap-1.5 rounded-full px-4 py-2 text-xs font-semibold transition hover:opacity-90">
                    <x-ui.icon :name="copied ? 'check' : 'copy'" class="h-3.5 w-3.5" />
                    <span x-text="copied ? 'Tersalin!' : 'Salin'">Salin</span>
                </button>
            </div>
            <p class="mt-2 text-[11px] text-on-surface-variant">Bagikan ke user melalui kanal aman (bukan email). User harus ganti password saat login pertama.</p>
        </x-ui.alert>
    </div>
@endif

<x-ui.card title="Detail Pengguna" overline="Informasi">
    @php
        $roleVariants = ['superadmin' => 'info', 'tenant_owner' => 'success', 'tenant_staff' => 'warning'];
        $roleLabels = ['superadmin' => 'Vendor Admin', 'tenant_owner' => 'Pemilik Tenant', 'tenant_staff' => 'Staff Tenant'];
    @endphp
    <dl class="grid grid-cols-1 gap-4 sm:grid-cols-2">
        <div>
            <dt class="text-xs font-semibold uppercase tracking-wider text-on-surface-variant">Role</dt>
            <dd class="mt-1">
                <x-ui.badge :variant="$roleVariants[$user->role] ?? 'neutral'">
                    {{ $roleLabels[$user->role] ?? $user->role }}
                </x-ui.badge>
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
                <x-ui.badge :variant="$user->is_active ? 'success' : 'neutral'" :icon="$user->is_active ? 'check-circle' : 'x-circle'">
                    {{ $user->is_active ? 'Aktif' : 'Nonaktif' }}
                </x-ui.badge>
            </dd>
        </div>
        <div>
            <dt class="text-xs font-semibold uppercase tracking-wider text-on-surface-variant">Login Terakhir</dt>
            <dd class="mt-1 text-sm text-on-surface">{{ $user->last_login_at?->translatedFormat('d F Y, H:i') ?? 'Belum pernah' }}</dd>
        </div>
    </dl>
</x-ui.card>
@endsection
