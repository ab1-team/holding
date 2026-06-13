@extends('layouts.app')

@section('title', "Edit {$tenant->name} — Holding App")

@section('content')
<x-ui.page-header
    overline="Master"
    title="Edit {{ $tenant->name }}" />

<x-ui.card>
    @include('admin.tenants._form')
</x-ui.card>
@endsection
