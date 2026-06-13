@extends('layouts.app')

@section('title', 'Aplikasi — Holding App')

@section('content')
<x-ui.page-header
    overline="Master"
    title="Aplikasi"
    subtitle="Daftar aplikasi subsidiary yang ditawarkan vendor." />

<livewire:data-table.smart-table
    :model="\App\Models\Application::class"
    :columns="[
        ['label' => 'Aplikasi', 'view' => 'admin.applications._cell_name'],
        ['label' => 'Base URL', 'field' => 'base_url', 'view' => 'admin.applications._cell_url'],
        ['label' => 'Laporan', 'view' => 'admin.applications._cell_has_report'],
        ['label' => 'Status', 'view' => 'admin.applications._cell_status'],
        ['label' => 'Aksi', 'view' => 'admin.applications._cell_actions', 'align' => 'right'],
    ]"
    :create-url="route('admin.applications.create')"
    create-label="Tambah Aplikasi"
    create-icon="plus"
    search-placeholder="Cari nama atau slug..."
    empty="Belum ada aplikasi. Tambahkan aplikasi subsidiary pertama." />
@endsection
