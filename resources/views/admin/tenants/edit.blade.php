@extends('layouts.app')

@section('title', "Edit {$tenant->name} — Holding App")

@section('content')
<div class="mb-6">
    <p class="text-xs font-semibold uppercase tracking-wider text-primary">Master</p>
    <h1 class="mt-1 text-3xl font-semibold tracking-tight text-on-surface">Edit {{ $tenant->name }}</h1>
</div>
<x-ui.card>
    @include('admin.tenants._form')
</x-ui.card>
@endsection