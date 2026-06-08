@extends('layouts.app')

@section('title', 'Pengguna — Holding App')

@section('content')
<div class="mb-6 flex flex-col gap-3 sm:flex-row sm:items-end sm:justify-between">
    <div>
        <p class="text-xs font-semibold uppercase tracking-wider text-primary">Master</p>
        <h1 class="mt-1 text-3xl font-semibold tracking-tight text-on-surface">Pengguna</h1>
        <p class="mt-1 text-sm text-on-surface-variant">Semua pengguna yang terdaftar di sistem.</p>
    </div>
    <x-ui.button :href="route('admin.users.create')" icon="plus">Tambah Pengguna</x-ui.button>
</div>

@if(session('status'))
    <div class="mb-4"><x-ui.alert variant="success">{{ session('status') }}</x-ui.alert></div>
@endif

<x-ui.card>
    <x-slot:header>
        <form method="GET" action="{{ route('admin.users.index') }}" class="flex flex-wrap items-center gap-2">
            <div class="relative flex-1 min-w-[12rem]">
                <x-ui.icon name="search" class="pointer-events-none absolute left-3 top-1/2 h-5 w-5 -translate-y-1/2 text-on-surface-variant" />
                <input type="text" name="search" value="{{ $search ?? '' }}" placeholder="Cari nama atau email..." class="block w-full rounded-full border border-outline bg-surface-container-lowest pl-10 pr-4 py-2 text-sm focus:border-primary focus:ring-2 focus:ring-primary/30 focus:outline-none">
            </div>
            <x-form.smart-select
                name="role"
                :options="['superadmin' => 'Vendor Admin', 'tenant_owner' => 'Pemilik Tenant', 'tenant_staff' => 'Staff Tenant']"
                :value="$role ?? ''"
                :searchable="false"
                :clearable="true"
                placeholder="Semua Role"
                class="w-48" />
            <x-ui.button type="submit" variant="outlined" size="sm">Cari</x-ui.button>
        </form>
    </x-slot:header>

    <div class="overflow-x-auto -mx-5 sm:-mx-6">
        <table class="min-w-full divide-y divide-outline-variant">
            <thead class="bg-surface-container">
                <tr>
                    <th class="px-5 py-3.5 text-left text-[11px] font-semibold uppercase tracking-wider text-on-surface-variant sm:px-6">Pengguna</th>
                    <th class="px-5 py-3.5 text-left text-[11px] font-semibold uppercase tracking-wider text-on-surface-variant">Role</th>
                    <th class="px-5 py-3.5 text-left text-[11px] font-semibold uppercase tracking-wider text-on-surface-variant">Tenant</th>
                    <th class="px-5 py-3.5 text-left text-[11px] font-semibold uppercase tracking-wider text-on-surface-variant">Status</th>
                    <th class="px-5 py-3.5 text-right text-[11px] font-semibold uppercase tracking-wider text-on-surface-variant sm:px-6">Aksi</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-outline-variant">
                @forelse($users as $u)
                <tr class="hover:bg-surface-container transition">
                    <td class="px-5 py-4 sm:px-6">
                        <div class="flex items-center gap-3">
                            <div class="flex h-10 w-10 items-center justify-center rounded-full bg-primary-container text-sm font-semibold text-on-primary-container">{{ strtoupper(mb_substr($u->name, 0, 1)) }}</div>
                            <div>
                                <div class="text-sm font-semibold text-on-surface">{{ $u->name }}</div>
                                <div class="text-xs text-on-surface-variant">{{ $u->email }}</div>
                            </div>
                        </div>
                    </td>
                    <td class="whitespace-nowrap px-5 py-4 text-sm">
                        @php
                            $roleLabels = ['superadmin' => ['Vendor', 'bg-primary-container text-on-primary-container'], 'tenant_owner' => ['Pemilik', 'bg-secondary-container text-on-secondary-container'], 'tenant_staff' => ['Staff', 'bg-tertiary-container text-on-tertiary-container']];
                            $rl = $roleLabels[$u->role] ?? [$u->role, 'bg-surface-container text-on-surface-variant'];
                        @endphp
                        <span class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-semibold {{ $rl[1] }}">{{ $rl[0] }}</span>
                    </td>
                    <td class="whitespace-nowrap px-5 py-4 text-sm text-on-surface-variant">{{ $u->tenant?->name ?? '—' }}</td>
                    <td class="whitespace-nowrap px-5 py-4 text-sm">
                        <span class="inline-flex items-center gap-1 rounded-full px-2.5 py-0.5 text-xs font-semibold {{ $u->is_active ? 'bg-secondary-container text-on-secondary-container' : 'bg-surface-container text-on-surface-variant' }}">
                            <span class="h-1.5 w-1.5 rounded-full {{ $u->is_active ? 'bg-secondary' : 'bg-on-surface-variant' }}"></span>
                            {{ $u->is_active ? 'Aktif' : 'Nonaktif' }}
                        </span>
                    </td>
                    <td class="whitespace-nowrap px-5 py-4 text-right text-sm font-medium sm:px-6">
                        <a href="{{ route('admin.users.show', $u) }}" class="text-primary hover:underline">Detail</a>
                        <a href="{{ route('admin.users.edit', $u) }}" class="ml-3 text-on-surface-variant hover:text-on-surface">Edit</a>
                    </td>
                </tr>
                @empty
                <tr><td colspan="5" class="px-5 py-12 text-center text-sm text-on-surface-variant sm:px-6">Belum ada pengguna. <a href="{{ route('admin.users.create') }}" class="font-semibold text-primary hover:underline">Tambah pengguna pertama</a>.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @if($users->hasPages())
    <div class="mt-4 border-t border-outline-variant pt-4">{{ $users->links() }}</div>
    @endif
</x-ui.card>
@endsection