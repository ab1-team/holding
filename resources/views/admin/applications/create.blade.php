@extends('layouts.app')

@section('title', 'Tambah Aplikasi — Holding App')

@section('content')
<x-ui.page-header
    overline="Master"
    title="Tambah Aplikasi"
    subtitle="Daftarkan aplikasi subsidiary baru ke vendor." />

<x-ui.card>
    @include('admin.applications._form')
</x-ui.card>
@endsection
