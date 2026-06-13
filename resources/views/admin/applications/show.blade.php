@extends('layouts.app')

@section('title', "{$application->name} — Holding App")

@section('content')

<x-ui.page-header
    overline="Aplikasi"
    title="{{ $application->name }}"
    subtitle="<code class='text-xs'>{{ $application->slug }}</code>">
    <x-slot:actions>
        <x-ui.button :href="route('admin.applications.edit', $application)" variant="outlined" icon="pencil">Edit</x-ui.button>
        <x-ui.confirm id="delete-application-{{ $application->id }}" title="Hapus Aplikasi?" message="Aplikasi {{ $application->name }} akan dihapus. Tenant yang terikat akan kehilangan akses." confirm-label="Hapus" :action="route('admin.applications.destroy', $application)" method="DELETE">
            <x-ui.button variant="danger-outlined" icon="trash">Hapus</x-ui.button>
        </x-ui.confirm>
    </x-slot:actions>
</x-ui.page-header>

@if(session('status'))
    <div class="mb-4"><x-ui.alert variant="success">{{ session('status') }}</x-ui.alert></div>
@endif

<div class="grid gap-4 lg:grid-cols-3">
    <x-ui.card title="Detail Aplikasi" overline="Informasi" class="lg:col-span-2">
        <dl class="grid grid-cols-1 gap-3 sm:grid-cols-3">
            <dt class="text-sm text-on-surface-variant">Deskripsi</dt>
            <dd class="sm:col-span-2 text-sm text-on-surface">{{ $application->description ?: '—' }}</dd>

            <dt class="text-sm text-on-surface-variant">Base URL</dt>
            <dd class="sm:col-span-2 text-sm"><a href="{{ $application->base_url }}" target="_blank" rel="noopener" class="text-primary hover:underline">{{ $application->base_url }}</a></dd>

            <dt class="text-sm text-on-surface-variant">Path Ikon</dt>
            <dd class="sm:col-span-2 text-sm text-on-surface"><code class="text-xs">{{ $application->icon_path ?: '—' }}</code></dd>

            <dt class="text-sm text-on-surface-variant">Laporan Keuangan</dt>
            <dd class="sm:col-span-2 text-sm">
                <x-ui.badge :variant="$application->has_financial_report ? 'info' : 'neutral'">
                    {{ $application->has_financial_report ? 'Didukung' : 'Tidak Didukung' }}
                </x-ui.badge>
            </dd>

            <dt class="text-sm text-on-surface-variant">Status</dt>
            <dd class="sm:col-span-2 text-sm">
                <x-ui.badge :variant="$application->is_active ? 'success' : 'neutral'" :icon="$application->is_active ? 'check-circle' : 'x-circle'">
                    {{ $application->is_active ? 'Aktif' : 'Nonaktif' }}
                </x-ui.badge>
            </dd>
        </dl>
    </x-ui.card>

    <x-ui.card title="API Token Key" overline="Integrasi">
        <p class="mb-2 text-xs text-on-surface-variant">Digunakan subsidiary untuk memvalidasi request dari Holding App.</p>
        <div class="flex gap-2">
            <input type="text" id="tokenInput" value="{{ $application->api_token_key }}" readonly class="block w-full rounded-md border border-outline bg-surface-container px-3 py-2 font-mono text-xs">
            <button type="button" onclick="navigator.clipboard.writeText(document.getElementById('tokenInput').value)" class="shrink-0 rounded-md border border-outline bg-surface-container-lowest px-3 py-2 text-xs font-medium text-on-surface hover:bg-surface-container">Salin</button>
        </div>
    </x-ui.card>
</div>
@endsection
