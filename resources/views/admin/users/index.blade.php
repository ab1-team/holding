@extends('layouts.app')

@section('title', 'Pengguna — Holding App')

@section('content')
<x-ui.page-header
    overline="Master"
    title="Pengguna"
    subtitle="Semua pengguna yang terdaftar di sistem." />

<livewire:data-table.smart-table
    :model="\App\Models\User::class"
    :columns="[
        ['label' => 'Pengguna', 'view' => 'admin.users._cell_name'],
        ['label' => 'Role', 'view' => 'admin.users._cell_role'],
        ['label' => 'Tenant', 'view' => 'admin.users._cell_tenant'],
        ['label' => 'Status', 'view' => 'admin.users._cell_status'],
        ['label' => 'Aksi', 'view' => 'admin.users._cell_actions', 'align' => 'right'],
    ]"
    :with-relations="['tenant']"
    :searchable-columns="['name', 'email']"
    :create-url="route('admin.users.create')"
    create-label="Tambah Pengguna"
    create-icon="plus"
    search-placeholder="Cari nama atau email..."
    empty="Belum ada pengguna. Tambahkan pengguna pertama untuk sistem." />
@endsection
