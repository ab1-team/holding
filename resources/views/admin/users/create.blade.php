@extends('layouts.app')

@section('title', 'Tambah Pengguna — Holding App')

@section('content')
<x-ui.page-header
    overline="Master"
    title="Tambah Pengguna"
    subtitle="Daftarkan pengguna baru sebagai vendor admin, pemilik tenant, atau staff tenant.">
    <x-slot:actions>
        <x-ui.button :href="route('admin.users.index')" variant="outlined" icon="arrow-left">Kembali</x-ui.button>
    </x-slot:actions>
</x-ui.page-header>

@if($errors->any())
    <div class="mb-4"><x-ui.alert variant="error" icon="exclamation" title="Gagal menyimpan.">{{ $errors->first() }}</x-ui.alert></div>
@endif

<x-ui.card overline="Formulir" title="Data Pengguna">
    @include('admin.users._form')
</x-ui.card>
@endsection
