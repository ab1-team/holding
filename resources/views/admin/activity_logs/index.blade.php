@extends('layouts.app')

@section('title', 'Log Aktivitas — Holding App')

@section('content')
<x-ui.page-header
    overline="Master"
    title="Log Aktivitas"
    subtitle="Jejak aktivitas pengguna di seluruh sistem." />

<livewire:data-table.smart-table
    :model="\App\Models\ActivityLog::class"
    :columns="[
        ['label' => 'Waktu', 'view' => 'admin.activity_logs._cell_time'],
        ['label' => 'Aksi', 'view' => 'admin.activity_logs._cell_action'],
        ['label' => 'Pengguna', 'view' => 'admin.activity_logs._cell_user'],
        ['label' => 'Tenant', 'view' => 'admin.activity_logs._cell_tenant'],
        ['label' => 'Subjek', 'view' => 'admin.activity_logs._cell_subject'],
        ['label' => 'IP', 'view' => 'admin.activity_logs._cell_ip'],
    ]"
    :with-relations="['user', 'tenant']"
    :searchable-columns="['action']"
    search-placeholder="Cari log..."
    empty="Belum ada aktivitas tercatat. Aktivitas pengguna akan muncul di sini." />
@endsection
