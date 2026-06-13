@extends('layouts.app')

@section('title', "Tambah Lisensi untuk {$tenant->name} — Holding App")

@section('content')
<x-ui.breadcrumb :items="[
    ['label' => 'Tenant', 'href' => route('admin.tenants.index')],
    ['label' => $tenant->name, 'href' => route('admin.tenants.show', $tenant)],
    ['label' => 'Tambah Lisensi'],
]" class="mb-4" />

<x-ui.page-header
    overline="Lisensi"
    title="Tambah Lisensi"
    subtitle="Untuk tenant <strong class='font-semibold text-on-surface'>{{ $tenant->name }}</strong>.">
    <x-slot:actions>
        <x-ui.button :href="route('admin.tenants.show', $tenant)" variant="outlined" icon="arrow-left">Kembali</x-ui.button>
    </x-slot:actions>
</x-ui.page-header>

@if($errors->any())
    <div class="mb-4"><x-ui.alert variant="error" icon="exclamation" title="Gagal menyimpan.">{{ $errors->first() }}</x-ui.alert></div>
@endif

<x-ui.card overline="Formulir" title="Tambah Lisensi Baru">
    @include('admin.tenant_applications._form')
</x-ui.card>
@endsection
