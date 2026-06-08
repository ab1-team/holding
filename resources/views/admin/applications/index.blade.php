@extends('layouts.app')

@section('title', 'Aplikasi — Holding App')

@section('content')
<div class="mb-6 flex flex-col gap-3 sm:flex-row sm:items-end sm:justify-between">
    <div>
        <p class="text-xs font-semibold uppercase tracking-wider text-primary">Master</p>
        <h1 class="mt-1 text-3xl font-semibold tracking-tight text-on-surface">Aplikasi</h1>
        <p class="mt-1 text-sm text-on-surface-variant">Daftar aplikasi subsidiary yang ditawarkan vendor.</p>
    </div>
    <x-ui.button :href="route('admin.applications.create')" icon="plus">Tambah Aplikasi</x-ui.button>
</div>

<livewire:data-table.smart-table
    :model="\App\Models\Application::class"
    :search-placeholder="$searchPlaceholder"
    :empty="$empty" />
@endsection