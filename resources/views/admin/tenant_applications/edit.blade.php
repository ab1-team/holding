@extends('layouts.app')

@section('title', "Edit Lisensi — Holding App")

@section('content')
<nav class="mb-4 text-sm">
    <ol class="flex items-center gap-2 text-on-surface-variant">
        <li><a href="{{ route('admin.tenants.index') }}" class="hover:text-primary">Tenant</a></li>
        <li>/</li>
        <li><a href="{{ route('admin.tenants.show', $license->tenant) }}" class="hover:text-primary">{{ $license->tenant->name }}</a></li>
        <li>/</li>
        <li class="text-on-surface">Edit Lisensi {{ $license->application->name }}</li>
    </ol>
</nav>
<div class="mb-6">
    <p class="text-xs font-semibold uppercase tracking-wider text-primary">Lisensi</p>
    <h1 class="mt-1 text-3xl font-semibold tracking-tight text-on-surface">Edit Lisensi</h1>
</div>
<x-ui.card>
    @include('admin.tenant_applications._form')
</x-ui.card>
@endsection