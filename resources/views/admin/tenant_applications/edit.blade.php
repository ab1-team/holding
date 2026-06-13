@extends('layouts.app')

@section('title', "Edit Lisensi — Holding App")

@section('content')
<x-ui.breadcrumb :items="[
    ['label' => 'Tenant', 'href' => route('admin.tenants.index')],
    ['label' => $license->tenant->name, 'href' => route('admin.tenants.show', $license->tenant)],
    ['label' => 'Edit Lisensi ' . $license->application->name],
]" class="mb-4" />

<x-ui.page-header
    overline="Lisensi"
    title="Edit Lisensi"
    subtitle="<strong class='font-semibold text-on-surface'>{{ $license->application->name }}</strong> untuk tenant <strong class='font-semibold text-on-surface'>{{ $license->tenant->name }}</strong>.">
    <x-slot:actions>
        <x-ui.button :href="route('admin.tenants.show', $license->tenant)" variant="outlined" icon="arrow-left">Kembali</x-ui.button>
    </x-slot:actions>
</x-ui.page-header>

@if($errors->any())
    <div class="mb-4"><x-ui.alert variant="error" icon="exclamation" title="Gagal menyimpan.">{{ $errors->first() }}</x-ui.alert></div>
@endif

<x-ui.card overline="Formulir" title="Detail Lisensi">
    @include('admin.tenant_applications._form')
</x-ui.card>
@endsection
