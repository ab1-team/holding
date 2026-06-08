@extends('layouts.app')

@section('title', "{$tenant->name} — Holding App")

@section('content')
<div class="mb-6 flex flex-col gap-2 sm:flex-row sm:items-start sm:justify-between">
    <div>
        <p class="text-xs font-semibold uppercase tracking-wider text-primary">Tenant</p>
        <h1 class="mt-1 text-3xl font-semibold tracking-tight text-on-surface">{{ $tenant->name }}</h1>
        <p class="mt-1 text-sm text-on-surface-variant"><code class="rounded bg-surface-container px-2 py-0.5 text-xs">{{ $tenant->slug }}</code></p>
    </div>
    <div class="flex flex-wrap gap-2">
        <x-ui.button :href="route('admin.tenants.edit', $tenant)" variant="outlined" icon="pencil">Edit</x-ui.button>
        <x-ui.confirm id="delete-tenant-{{ $tenant->id }}" title="Hapus Tenant?" message="Tenant {{ $tenant->name }} dan semua lisensi aplikasi terkait akan dihapus. Tindakan ini tidak dapat dibatalkan." confirm-label="Hapus" :action="route('admin.tenants.destroy', $tenant)" method="DELETE">
            <x-ui.button variant="danger-outlined" icon="trash">Hapus</x-ui.button>
        </x-ui.confirm>
    </div>
</div>

@if(session('status'))
    <div class="mb-4"><x-ui.alert variant="success">{{ session('status') }}</x-ui.alert></div>
@endif
@if(session('new_api_secret'))
    <div class="mb-4">
        <x-ui.alert variant="warning" title="API Secret baru telah dibuat.">
            Salin nilai di bawah — hanya ditampilkan sekali dan tidak dapat dilihat kembali.
            <div class="mt-2 flex max-w-xl gap-2">
                <input type="text" id="newSecretInput" value="{{ session('new_api_secret') }}" readonly class="block w-full rounded-lg border border-tertiary-container bg-surface-container-lowest px-3 py-2 font-mono text-xs">
                <button type="button" x-data x-on:click="navigator.clipboard.writeText(document.getElementById('newSecretInput').value)" class="shrink-0 rounded-full bg-tertiary px-4 py-2 text-xs font-semibold text-on-tertiary hover:opacity-90 transition">Salin</button>
            </div>
        </x-ui.alert>
    </div>
@endif

<div class="grid gap-4 lg:grid-cols-3">
    <x-ui.card title="Detail Tenant" overline="Informasi" class="lg:col-span-2">
        <dl class="grid grid-cols-1 gap-4 sm:grid-cols-3">
            <div>
                <dt class="text-xs font-semibold uppercase tracking-wider text-on-surface-variant">Email</dt>
                <dd class="mt-1 text-sm"><a href="mailto:{{ $tenant->email }}" class="font-medium text-primary hover:underline">{{ $tenant->email }}</a></dd>
            </div>
            <div>
                <dt class="text-xs font-semibold uppercase tracking-wider text-on-surface-variant">Telepon</dt>
                <dd class="mt-1 text-sm text-on-surface">{{ $tenant->phone }}</dd>
            </div>
            <div>
                <dt class="text-xs font-semibold uppercase tracking-wider text-on-surface-variant">Status</dt>
                <dd class="mt-1">
                    <span class="inline-flex items-center gap-1 rounded-full px-2.5 py-0.5 text-xs font-semibold {{ $tenant->is_active ? 'bg-secondary-container text-on-secondary-container' : 'bg-surface-container text-on-surface-variant' }}">
                        <span class="h-1.5 w-1.5 rounded-full {{ $tenant->is_active ? 'bg-secondary' : 'bg-on-surface-variant' }}"></span>
                        {{ $tenant->is_active ? 'Aktif' : 'Nonaktif' }}
                    </span>
                </dd>
            </div>
            <div class="sm:col-span-3">
                <dt class="text-xs font-semibold uppercase tracking-wider text-on-surface-variant">Alamat</dt>
                <dd class="mt-1 text-sm text-on-surface">{{ $tenant->address ?: '—' }}</dd>
            </div>
        </dl>
    </x-ui.card>

    <x-ui.card title="Pengguna" overline="Tim">
        <div class="-mx-5 -my-5 sm:-mx-6 sm:-my-6 divide-y divide-outline-variant">
            @forelse($tenant->users as $u)
            <div class="flex items-center justify-between px-5 py-3 sm:px-6">
                <div class="flex min-w-0 items-center gap-3">
                    <div class="flex h-9 w-9 shrink-0 items-center justify-center rounded-full bg-secondary-container text-xs font-semibold text-on-secondary-container">{{ strtoupper(mb_substr($u->name, 0, 1)) }}</div>
                    <div class="min-w-0">
                        <div class="truncate text-sm font-semibold text-on-surface">{{ $u->name }}</div>
                        <div class="truncate text-xs text-on-surface-variant">{{ $u->email }}</div>
                    </div>
                </div>
                <span class="ml-2 inline-flex items-center rounded-full bg-surface-container px-2 py-0.5 text-[10px] font-semibold text-on-surface-variant">
                    {{ $u->role === 'tenant_owner' ? 'Pemilik' : 'Staff' }}
                </span>
            </div>
            @empty
            <div class="px-5 py-8 text-center text-sm text-on-surface-variant sm:px-6">Belum ada pengguna terdaftar untuk tenant ini.</div>
            @endforelse
        </div>
    </x-ui.card>
</div>

<x-ui.card title="Lisensi Aplikasi" overline="Lisensi" class="mt-4">
    <x-slot:header>
        <x-ui.button :href="route('admin.tenants.licenses.create', $tenant)" icon="plus" size="sm">Tambah Lisensi</x-ui.button>
    </x-slot:header>
    <div class="overflow-x-auto -mx-5 sm:-mx-6">
        <table class="min-w-full divide-y divide-outline-variant">
            <thead class="bg-surface-container">
                <tr>
                    <th class="px-5 py-3 text-left text-[11px] font-semibold uppercase tracking-wider text-on-surface-variant sm:px-6">Aplikasi</th>
                    <th class="px-5 py-3 text-left text-[11px] font-semibold uppercase tracking-wider text-on-surface-variant">Instance URL</th>
                    <th class="px-5 py-3 text-left text-[11px] font-semibold uppercase tracking-wider text-on-surface-variant">Status</th>
                    <th class="px-5 py-3 text-left text-[11px] font-semibold uppercase tracking-wider text-on-surface-variant">Berlaku</th>
                    <th class="px-5 py-3 text-right text-[11px] font-semibold uppercase tracking-wider text-on-surface-variant sm:px-6">Aksi</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-outline-variant">
                @forelse($tenant->tenantApplications as $ta)
                <tr class="hover:bg-surface-container transition">
                    <td class="px-5 py-3.5 sm:px-6">
                        <div class="text-sm font-semibold text-on-surface">{{ $ta->application->name }}</div>
                        @if($ta->label)<div class="text-xs text-on-surface-variant">{{ $ta->label }}</div>@endif
                    </td>
                    <td class="whitespace-nowrap px-5 py-3.5 text-sm"><a href="{{ $ta->instance_url }}" target="_blank" rel="noopener" class="font-medium text-primary hover:underline">{{ parse_url($ta->instance_url, PHP_URL_HOST) }}</a></td>
                    <td class="whitespace-nowrap px-5 py-3.5 text-sm">
                        <span class="inline-flex items-center gap-1 rounded-full px-2.5 py-0.5 text-xs font-semibold {{ $ta->is_active ? 'bg-secondary-container text-on-secondary-container' : 'bg-surface-container text-on-surface-variant' }}">
                            <span class="h-1.5 w-1.5 rounded-full {{ $ta->is_active ? 'bg-secondary' : 'bg-on-surface-variant' }}"></span>
                            {{ $ta->is_active ? 'Aktif' : 'Nonaktif' }}
                        </span>
                        @if($ta->isExpired())<div class="mt-1 text-xs font-medium text-error">Kedaluwarsa</div>@endif
                    </td>
                    <td class="whitespace-nowrap px-5 py-3.5 text-xs text-on-surface-variant">{{ $ta->expired_at?->translatedFormat('d F Y') ?? 'Selamanya' }}</td>
                    <td class="whitespace-nowrap px-5 py-3.5 text-right text-sm font-medium sm:px-6">
                        <a href="{{ route('admin.licenses.edit', $ta) }}" class="text-on-surface-variant hover:text-on-surface">Edit</a>
                        <x-ui.confirm id="revoke-license-{{ $ta->id }}" title="Cabut Lisensi?" message="Lisensi {{ $ta->application->name }} akan dicabut. Pengguna tidak akan bisa mengaksesnya lagi." confirm-label="Cabut" :action="route('admin.licenses.destroy', $ta)" method="DELETE">
                            <button type="submit" class="ml-3 text-error hover:underline">Cabut</button>
                        </x-ui.confirm>
                    </td>
                </tr>
                @empty
                <tr><td colspan="5" class="px-5 py-12 text-center text-sm text-on-surface-variant sm:px-6">Belum ada lisensi aplikasi. <a href="{{ route('admin.tenants.licenses.create', $tenant) }}" class="font-semibold text-primary hover:underline">Tambah lisensi pertama</a>.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</x-ui.card>
@endsection