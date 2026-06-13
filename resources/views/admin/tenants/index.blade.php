@extends('layouts.app')

@section('title', 'Tenant — Holding App')

@section('content')
<x-ui.page-header
    overline="Master"
    title="Tenant"
    subtitle="Tenant yang terdaftar di vendor." />

<livewire:data-table.smart-table
    :model="\App\Models\Tenant::class"
    :columns="[
        ['label' => 'Nama', 'view' => 'admin.tenants._cell_name'],
        ['label' => 'Slug', 'view' => 'admin.tenants._cell_slug'],
        ['label' => 'Email', 'view' => 'admin.tenants._cell_email'],
        ['label' => 'Pengguna', 'view' => 'admin.tenants._cell_users_count'],
        ['label' => 'Status', 'view' => 'admin.tenants._cell_status'],
        ['label' => 'Aksi', 'view' => 'admin.tenants._cell_actions', 'align' => 'right'],
    ]"
    :with-relations="[]"
    :searchable-columns="['name', 'slug', 'email']"
    :create-url="route('admin.tenants.create')"
    create-label="Tambah Tenant"
    create-icon="plus"
    search-placeholder="Cari nama, slug, atau email..."
    empty="Belum ada tenant. Tambahkan tenant pertama untuk mulai mengelola lisensi aplikasi." />
@endsection
