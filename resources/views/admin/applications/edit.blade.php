@extends('layouts.app')

@section('title', "Edit {$application->name} — Holding App")

@section('content')

    <div class="rounded-lg border border-slate-200 bg-white shadow-sm" style="max-width: 56rem;">
        <div class="border-b border-slate-200 px-6 py-4">
            <h1 class="text-xl font-bold text-slate-900">Edit Aplikasi</h1>
            <p class="mt-1 text-sm text-slate-500">Perbarui detail {{ $application->name }}.</p>
        </div>
        <div class="px-6 py-5">
            @include('admin.applications._form')
        </div>
    </div>
@endsection
