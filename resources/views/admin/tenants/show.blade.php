@extends('layouts.app')

@section('title', "{$tenant->name} — Holding App")

@section('content')
<x-ui.page-header
    overline="Tenant"
    title="{{ $tenant->name }}"
    subtitle="<code class='rounded bg-surface-container px-2 py-0.5 text-xs'>{{ $tenant->slug }}</code>">
    <x-slot:actions>
        <x-ui.button :href="route('admin.tenants.edit', $tenant)" variant="outlined" icon="pencil">Edit</x-ui.button>
        <x-ui.confirm id="delete-tenant-{{ $tenant->id }}" title="Hapus Tenant?" message="Tenant {{ $tenant->name }} dan semua lisensi aplikasi terkait akan dihapus. Tindakan ini tidak dapat dibatalkan." confirm-label="Hapus" :action="route('admin.tenants.destroy', $tenant)" method="DELETE">
            <x-ui.button variant="danger-outlined" icon="trash">Hapus</x-ui.button>
        </x-ui.confirm>
    </x-slot:actions>
</x-ui.page-header>

@if(session('status'))
    {{-- status handled by layout --}}
@endif
@if(session('new_api_secret'))
    <div class="mb-4" x-data="{ copied: false }">
        <x-ui.alert variant="warning" title="API Secret baru telah dibuat.">
            Salin nilai di bawah — hanya ditampilkan sekali dan tidak dapat dilihat kembali.
            <div class="mt-3 flex max-w-xl flex-col gap-2 sm:flex-row sm:items-center">
                <input type="text" id="newSecretInput" value="{{ session('new_api_secret') }}" readonly
                       class="block w-full rounded-lg border border-tertiary-container bg-surface-container-lowest px-3 py-2 font-mono text-xs text-on-surface focus:outline-none focus:ring-2 focus:ring-tertiary/30"
                       @focus="$el.select()">
                <button type="button"
                        @click="navigator.clipboard.writeText(document.getElementById('newSecretInput').value); copied = true; setTimeout(() => copied = false, 2000)"
                        :class="copied ? 'bg-secondary text-on-secondary' : 'bg-tertiary text-on-tertiary'"
                        class="inline-flex shrink-0 items-center justify-center gap-1.5 rounded-full px-4 py-2 text-xs font-semibold transition hover:opacity-90">
                    <x-ui.icon :name="copied ? 'check' : 'copy'" class="h-3.5 w-3.5" />
                    <span x-text="copied ? 'Tersalin!' : 'Salin'">Salin</span>
                </button>
            </div>
            <p class="mt-2 text-[11px] text-on-surface-variant">Tempel di konfigurasi subsidiary sebagai <code class="rounded bg-surface-container px-1 py-0.5 font-mono">X-Holding-Token</code>.</p>
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
                    <x-ui.status-badge :status="$tenant->is_active">
                        {{ $tenant->is_active ? 'Aktif' : 'Nonaktif' }}
                    </x-ui.status-badge>
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
                    <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full bg-secondary-container text-sm font-semibold text-on-secondary-container">{{ strtoupper(mb_substr($u->name, 0, 1)) }}</div>
                    <div class="min-w-0">
                        <div class="truncate text-sm font-semibold text-on-surface">{{ $u->name }}</div>
                        <div class="truncate text-xs text-on-surface-variant">{{ $u->email }}</div>
                    </div>
                </div>
                <x-ui.badge variant="neutral" size="sm" class="ml-2">
                    {{ $u->role === 'tenant_owner' ? 'Pemilik' : 'Staff' }}
                </x-ui.badge>
            </div>
            @empty
            <x-ui.empty-state
                icon="users"
                title="Belum ada pengguna"
                description="Tambahkan pengguna untuk tenant ini." />
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
                        <x-ui.status-badge :status="$ta->is_active" :expired="$ta->isExpired()">
                            {{ $ta->is_active ? ($ta->isExpired() ? 'Kedaluwarsa' : 'Aktif') : 'Nonaktif' }}
                        </x-ui.status-badge>
                    </td>
                    <td class="whitespace-nowrap px-5 py-3.5 text-xs text-on-surface-variant">{{ $ta->expired_at?->translatedFormat('d F Y') ?? 'Selamanya' }}</td>
                    <td class="whitespace-nowrap px-5 py-3.5 text-right sm:px-6">
                        <div class="flex items-center justify-end gap-1">
                            <x-ui.button
                                :href="route('admin.tenants.licenses.edit', [$tenant, $ta])"
                                variant="text"
                                size="sm"
                                icon="pencil"
                                title="Edit lisensi" />
                            <x-ui.confirm
                                id="regen-secret-{{ $ta->id }}"
                                title="Regenerate API Secret?"
                                message="Secret lama untuk {{ $ta->application->name }} akan langsung <strong class='font-semibold text-error'>tidak valid</strong>. Subsidiary yang masih menggunakan secret lama akan ditolak. Anda harus memberi tahu tim subsidiary untuk update konfigurasi mereka."
                                confirm-label="Regenerate"
                                :action="route('admin.tenants.licenses.regenerate-secret', [$tenant, $ta])"
                                method="POST">
                                <x-ui.button variant="text" size="sm" icon="key" title="Regenerate API Secret" />
                            </x-ui.confirm>
                            <x-ui.confirm
                                id="revoke-license-{{ $ta->id }}"
                                title="Cabut Lisensi?"
                                message="Lisensi {{ $ta->application->name }} akan dicabut. Pengguna tidak akan bisa mengaksesnya lagi."
                                confirm-label="Cabut"
                                :action="route('admin.tenants.licenses.destroy', [$tenant, $ta])"
                                method="DELETE">
                                <x-ui.button variant="text" size="sm" icon="trash" title="Cabut lisensi" />
                            </x-ui.confirm>
                        </div>
                    </td>
                </tr>
                @empty
                <tr><td colspan="5">
                    <x-ui.empty-state
                        icon="key"
                        title="Belum ada lisensi"
                        description="Tambahkan lisensi aplikasi pertama untuk tenant ini.">
                        <x-slot:action>
                            <x-ui.button :href="route('admin.tenants.licenses.create', $tenant)" icon="plus" size="sm">Tambah Lisensi</x-ui.button>
                        </x-slot:action>
                    </x-ui.empty-state>
                </td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</x-ui.card>
@endsection
