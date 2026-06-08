@extends('layouts.app')

@section('title', "Tambah Lisensi untuk {$tenant->name} — Holding App")

@section('content')
<nav class="mb-4 text-sm">
    <ol class="flex items-center gap-2 text-on-surface-variant">
        <li><a href="{{ route('admin.tenants.index') }}" class="hover:text-primary">Tenant</a></li>
        <li>/</li>
        <li><a href="{{ route('admin.tenants.show', $tenant) }}" class="hover:text-primary">{{ $tenant->name }}</a></li>
        <li>/</li>
        <li class="text-on-surface">Tambah Lisensi</li>
    </ol>
</nav>
<div class="mb-6">
    <p class="text-xs font-semibold uppercase tracking-wider text-primary">Lisensi</p>
    <h1 class="mt-1 text-3xl font-semibold tracking-tight text-on-surface">Tambah Lisensi</h1>
    <p class="mt-1 text-sm text-on-surface-variant">Untuk tenant <strong class="font-semibold text-on-surface">{{ $tenant->name }}</strong></p>
</div>
<x-ui.card>
    @include('admin.tenant_applications._form')
</x-ui.card>
@endsection