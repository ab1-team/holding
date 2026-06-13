@extends('layouts.app')

@section('title', "Log #{$log->id} — Holding App")

@section('content')
<x-ui.breadcrumb :items="[
    ['label' => 'Log Aktivitas', 'href' => route('admin.activity-logs.index')],
    ['label' => '#' . $log->id],
]" class="mb-4" />

<x-ui.page-header
    overline="Master"
    title="Detail Log"
    subtitle="{{ $log->created_at->translatedFormat('l, d F Y, H:i:s') }}" />

<x-ui.card>
    <dl class="grid grid-cols-1 gap-4 sm:grid-cols-2">
        <div>
            <dt class="text-xs font-semibold uppercase tracking-wider text-on-surface-variant">Aksi</dt>
            <dd class="mt-1 text-sm font-mono text-on-surface">{{ $log->action }}</dd>
        </div>
        <div>
            <dt class="text-xs font-semibold uppercase tracking-wider text-on-surface-variant">Subjek</dt>
            <dd class="mt-1 text-sm font-mono text-on-surface">{{ $log->subject_type ?? '—' }}{{ $log->subject_id ? '#'.$log->subject_id : '' }}</dd>
        </div>
        <div>
            <dt class="text-xs font-semibold uppercase tracking-wider text-on-surface-variant">Pengguna</dt>
            <dd class="mt-1 text-sm">
                @if($log->user)
                <a href="{{ route('admin.users.show', $log->user) }}" class="font-medium text-primary hover:underline">{{ $log->user->name }}</a>
                <span class="text-on-surface-variant">— {{ $log->user->email }}</span>
                @else
                <span class="text-on-surface-variant">—</span>
                @endif
            </dd>
        </div>
        <div>
            <dt class="text-xs font-semibold uppercase tracking-wider text-on-surface-variant">Tenant</dt>
            <dd class="mt-1 text-sm">
                @if($log->tenant)
                <a href="{{ route('admin.tenants.show', $log->tenant) }}" class="font-medium text-primary hover:underline">{{ $log->tenant->name }}</a>
                @else
                <span class="text-on-surface-variant">—</span>
                @endif
            </dd>
        </div>
        <div>
            <dt class="text-xs font-semibold uppercase tracking-wider text-on-surface-variant">IP Address</dt>
            <dd class="mt-1 text-sm font-mono text-on-surface">{{ $log->ip_address ?? '—' }}</dd>
        </div>
        <div>
            <dt class="text-xs font-semibold uppercase tracking-wider text-on-surface-variant">User Agent</dt>
            <dd class="mt-1 text-sm text-on-surface truncate" title="{{ $log->user_agent }}">{{ $log->user_agent ?? '—' }}</dd>
        </div>
    </dl>
</x-ui.card>

@if($log->metadata && count($log->metadata) > 0)
<x-ui.card title="Metadata" overline="Detail" class="mt-4">
    <pre class="overflow-x-auto rounded-lg bg-surface-container p-4 text-xs text-on-surface font-mono">{{ json_encode($log->metadata, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) }}</pre>
</x-ui.card>
@endif
@endsection
