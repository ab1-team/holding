@extends('layouts.app')

@section('title', 'Undang Staff — Holding App')

@section('content')
@php
    $inviteSubtitle = 'Untuk tenant <strong class="font-semibold text-on-surface">' . e($tenant->name) . '</strong>';
@endphp
<x-ui.breadcrumb :items="[
    ['label' => 'Staff', 'href' => route('tenant.staff.index')],
    ['label' => 'Undang Staff'],
]" class="mb-2" />

<x-ui.page-header
    overline="Staff"
    title="Undang Staff"
    :subtitle="$inviteSubtitle"
/>

<x-ui.card>
    <form method="POST" action="{{ route('tenant.staff.store') }}" class="space-y-5">
        @csrf
        <div class="grid gap-4 sm:grid-cols-2">
            <x-ui.input name="name" label="Nama" required />
            <x-ui.input name="email" type="email" label="Email" required />
        </div>
        <x-form.smart-select
            name="role"
            label="Role"
            :options="['tenant_staff' => 'Staff', 'tenant_owner' => 'Pemilik Tenant']"
            :value="old('role', 'tenant_staff')"
            :searchable="false"
            :clearable="false"
            required />
        <x-ui.input
            name="password"
            type="password"
            label="Password"
            hint="Kosongkan untuk generate otomatis. Minimal 8 karakter."
        />
        <div class="rounded-2xl bg-surface-container p-4">
            <x-ui.switcher name="is_active" label="Aktif" :checked="old('is_active', true)" />
        </div>
        <div class="flex justify-end gap-2 border-t border-outline-variant pt-4">
            <x-ui.button :href="route('tenant.staff.index')" variant="outlined">Batal</x-ui.button>
            <x-ui.button type="submit" icon="check">Undang Staff</x-ui.button>
        </div>
    </form>
</x-ui.card>
@endsection
