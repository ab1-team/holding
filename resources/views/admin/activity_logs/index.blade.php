@extends('layouts.app')

@section('title', 'Log Aktivitas — Holding App')

@section('content')
<div class="mb-6">
    <p class="text-xs font-semibold uppercase tracking-wider text-primary">Master</p>
    <h1 class="mt-1 text-3xl font-semibold tracking-tight text-on-surface">Log Aktivitas</h1>
    <p class="mt-1 text-sm text-on-surface-variant">Jejak aktivitas pengguna di seluruh sistem.</p>
</div>

<x-ui.card>
    <x-slot:header>
        <form method="GET" action="{{ route('admin.activity-logs.index') }}" class="flex flex-wrap items-center gap-2">
            <div class="relative flex-1 min-w-[12rem]">
                <x-ui.icon name="search" class="pointer-events-none absolute left-3 top-1/2 h-5 w-5 -translate-y-1/2 text-on-surface-variant" />
                <input type="text" name="search" value="{{ $search ?? '' }}" placeholder="Cari log..." class="block w-full rounded-full border border-outline bg-surface-container-lowest pl-10 pr-4 py-2 text-sm focus:border-primary focus:ring-2 focus:ring-primary/30 focus:outline-none">
            </div>
            <x-form.smart-select
                name="tenant_id"
                :options="$tenants->pluck('name', 'id')->toArray()"
                :value="$tenantId ?? ''"
                :searchable="true"
                :clearable="true"
                placeholder="Semua Tenant"
                search-placeholder="Cari tenant..." />
            <x-form.smart-select
                name="action"
                :options="array_combine($actions->toArray(), $actions->toArray())"
                :value="$action ?? ''"
                :searchable="true"
                :clearable="true"
                placeholder="Semua Aksi"
                search-placeholder="Cari aksi..." />
            <x-ui.button type="submit" variant="outlined" size="sm">Filter</x-ui.button>
        </form>
    </x-slot:header>

    <div class="overflow-x-auto -mx-5 sm:-mx-6">
        <table class="min-w-full divide-y divide-outline-variant">
            <thead class="bg-surface-container">
                <tr>
                    <th class="px-5 py-3.5 text-left text-[11px] font-semibold uppercase tracking-wider text-on-surface-variant sm:px-6">Waktu</th>
                    <th class="px-5 py-3.5 text-left text-[11px] font-semibold uppercase tracking-wider text-on-surface-variant">Aksi</th>
                    <th class="px-5 py-3.5 text-left text-[11px] font-semibold uppercase tracking-wider text-on-surface-variant">Pengguna</th>
                    <th class="px-5 py-3.5 text-left text-[11px] font-semibold uppercase tracking-wider text-on-surface-variant">Tenant</th>
                    <th class="px-5 py-3.5 text-left text-[11px] font-semibold uppercase tracking-wider text-on-surface-variant">Subjek</th>
                    <th class="px-5 py-3.5 text-left text-[11px] font-semibold uppercase tracking-wider text-on-surface-variant">IP</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-outline-variant">
                @forelse($logs as $log)
                <tr class="hover:bg-surface-container transition">
                    <td class="whitespace-nowrap px-5 py-3.5 text-sm text-on-surface sm:px-6"><a href="{{ route('admin.activity-logs.show', $log) }}" class="font-medium text-primary hover:underline">{{ $log->created_at->translatedFormat('d M Y, H:i') }}</a></td>
                    <td class="whitespace-nowrap px-5 py-3.5 text-sm">
                        @php $actionColors = ['login' => 'bg-primary-container text-on-primary-container', 'logout' => 'bg-surface-container text-on-surface-variant', 'access_app' => 'bg-secondary-container text-on-secondary-container', 'create' => 'bg-emerald-100 text-emerald-800', 'update' => 'bg-amber-100 text-amber-800', 'delete' => 'bg-rose-100 text-rose-800']; @endphp
                        <span class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-semibold {{ $actionColors[$log->action] ?? 'bg-surface-container text-on-surface-variant' }}">{{ $log->action }}</span>
                    </td>
                    <td class="whitespace-nowrap px-5 py-3.5 text-sm text-on-surface-variant">{{ $log->user?->name ?? '—' }}</td>
                    <td class="whitespace-nowrap px-5 py-3.5 text-sm text-on-surface-variant">{{ $log->tenant?->name ?? '—' }}</td>
                    <td class="whitespace-nowrap px-5 py-3.5 text-sm text-on-surface-variant"><code class="rounded bg-surface-container px-1.5 py-0.5 text-xs">{{ $log->subject_type ? class_basename($log->subject_type) : '—' }}#{{ $log->subject_id ?? '—' }}</code></td>
                    <td class="whitespace-nowrap px-5 py-3.5 text-sm text-on-surface-variant">{{ $log->ip_address ?? '—' }}</td>
                </tr>
                @empty
                <tr><td colspan="6" class="px-5 py-12 text-center text-sm text-on-surface-variant sm:px-6">Belum ada aktivitas tercatat.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @if($logs->hasPages())
    <div class="mt-4 border-t border-outline-variant pt-4">{{ $logs->links() }}</div>
    @endif
</x-ui.card>
@endsection