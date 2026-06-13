@extends('layouts.app')

@section('title', 'Tambah Tenant — Holding App')

@section('content')
<x-ui.page-header
    overline="Master"
    title="Tambah Tenant" />

<x-ui.card>
    @include('admin.tenants._form')
</x-ui.card>
@endsection
