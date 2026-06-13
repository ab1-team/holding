@extends('layouts.app')

@section('title', "Edit {$application->name} — Holding App")

@section('content')
<x-ui.page-header
    overline="Master"
    title="Edit Aplikasi"
    subtitle="Perbarui detail {{ $application->name }}." />

<x-ui.card>
    @include('admin.applications._form')
</x-ui.card>
@endsection
