@extends('layouts.app')

@section('title', "Edit Staff — {$staff->name}")

@section('content')
<x-ui.breadcrumb :items="[
    ['label' => 'Staff', 'href' => route('tenant.staff.index')],
    ['label' => 'Edit ' . $staff->name],
]" class="mb-2" />

<x-ui.page-header
    overline="Staff"
    :title="'Edit ' . $staff->name"
/>

<x-ui.card>
    <form method="POST" action="{{ route('tenant.staff.update', $staff) }}" class="space-y-5">
        @csrf @method('PUT')
        <div class="grid gap-4 sm:grid-cols-2">
            <x-ui.input name="name" label="Nama" :value="$staff->name" required />
            <x-ui.input name="email" type="email" label="Email" :value="$staff->email" required />
        </div>
        <x-form.smart-select
            name="role"
            label="Role"
            :options="['tenant_staff' => 'Staff', 'tenant_owner' => 'Pemilik Tenant']"
            :value="$staff->role"
            :searchable="false"
            :clearable="false"
            required />
        <x-ui.input
            name="password"
            type="password"
            label="Password Baru"
            hint="Kosongkan jika tidak diubah. Minimal 8 karakter."
        />
        <div class="rounded-2xl bg-surface-container p-4">
            <x-ui.switcher name="is_active" label="Aktif" :checked="$staff->is_active" />
        </div>
        <div class="flex justify-end gap-2 border-t border-outline-variant pt-4">
            <x-ui.button :href="route('tenant.staff.index')" variant="outlined">Batal</x-ui.button>
            <x-ui.button type="submit" icon="check">Simpan Perubahan</x-ui.button>
        </div>
    </form>
</x-ui.card>
@endsection
