@extends('layouts.app')

@section('title', "Staff — {$tenant->name}")

@section('content')
<div class="mb-6 flex flex-col gap-3 sm:flex-row sm:items-end sm:justify-between">
    <div>
        <p class="text-xs font-semibold uppercase tracking-wider text-secondary">Staff</p>
        <h1 class="mt-1 text-3xl font-semibold tracking-tight text-on-surface">Manajemen Staff</h1>
        <p class="mt-1 text-sm text-on-surface-variant">Staff di tenant <strong class="font-semibold text-on-surface">{{ $tenant->name }}</strong></p>
    </div>
    <x-ui.button :href="route('tenant.staff.create')" icon="plus">Undang Staff</x-ui.button>
</div>

@if(session('status'))
    <div class="mb-4"><x-ui.alert variant="success">{{ session('status') }}</x-ui.alert></div>
@endif
@if(session('new_password'))
    <div class="mb-4">
        <x-ui.alert variant="warning" title="Password baru telah di-generate.">
            Salin dan bagikan ke staff. Tidak akan ditampilkan kembali.
            <div class="mt-2 flex max-w-md gap-2">
                <input type="text" id="newPwdInput" value="{{ session('new_password') }}" readonly class="block w-full rounded-lg border border-tertiary-container bg-surface-container-lowest px-3 py-2 font-mono text-xs">
                <button type="button" x-data x-on:click="navigator.clipboard.writeText(document.getElementById('newPwdInput').value)" class="shrink-0 rounded-full bg-tertiary px-4 py-2 text-xs font-semibold text-on-tertiary hover:opacity-90 transition">Salin</button>
            </div>
        </x-ui.alert>
    </div>
@endif

<x-ui.card>
    <div class="overflow-x-auto -mx-5 sm:-mx-6">
        <table class="min-w-full divide-y divide-outline-variant">
            <thead class="bg-surface-container">
                <tr>
                    <th class="px-5 py-3.5 text-left text-[11px] font-semibold uppercase tracking-wider text-on-surface-variant sm:px-6">Pengguna</th>
                    <th class="px-5 py-3.5 text-left text-[11px] font-semibold uppercase tracking-wider text-on-surface-variant">Role</th>
                    <th class="px-5 py-3.5 text-left text-[11px] font-semibold uppercase tracking-wider text-on-surface-variant">Status</th>
                    <th class="px-5 py-3.5 text-right text-[11px] font-semibold uppercase tracking-wider text-on-surface-variant sm:px-6">Aksi</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-outline-variant">
                @forelse($staff as $s)
                <tr class="hover:bg-surface-container transition">
                    <td class="px-5 py-4 sm:px-6">
                        <div class="flex items-center gap-3">
                            <div class="flex h-10 w-10 items-center justify-center rounded-full bg-secondary-container text-sm font-semibold text-on-secondary-container">{{ strtoupper(mb_substr($s->name, 0, 1)) }}</div>
                            <div>
                                <div class="text-sm font-semibold text-on-surface">{{ $s->name }}</div>
                                <div class="text-xs text-on-surface-variant">{{ $s->email }}</div>
                            </div>
                        </div>
                    </td>
                    <td class="whitespace-nowrap px-5 py-4 text-sm">
                        <span class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-semibold {{ $s->role === 'tenant_owner' ? 'bg-secondary-container text-on-secondary-container' : 'bg-tertiary-container text-on-tertiary-container' }}">
                            {{ $s->role === 'tenant_owner' ? 'Pemilik' : 'Staff' }}
                        </span>
                    </td>
                    <td class="whitespace-nowrap px-5 py-4 text-sm">
                        <span class="inline-flex items-center gap-1 rounded-full px-2.5 py-0.5 text-xs font-semibold {{ $s->is_active ? 'bg-secondary-container text-on-secondary-container' : 'bg-surface-container text-on-surface-variant' }}">
                            <span class="h-1.5 w-1.5 rounded-full {{ $s->is_active ? 'bg-secondary' : 'bg-on-surface-variant' }}"></span>
                            {{ $s->is_active ? 'Aktif' : 'Nonaktif' }}
                        </span>
                    </td>
                    <td class="whitespace-nowrap px-5 py-4 text-right text-sm font-medium sm:px-6">
                        <a href="{{ route('tenant.staff.edit', $s) }}" class="text-on-surface-variant hover:text-on-surface">Edit</a>
                    </td>
                </tr>
                @empty
                <tr><td colspan="4" class="px-5 py-12 text-center text-sm text-on-surface-variant sm:px-6">Belum ada staff. <a href="{{ route('tenant.staff.create') }}" class="font-semibold text-primary hover:underline">Undang staff pertama</a>.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</x-ui.card>
@endsection