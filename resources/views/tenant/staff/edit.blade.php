@extends('layouts.app')

@section('title', "Edit Staff — {$staff->name}")

@section('content')
<div class="mb-6">
    <p class="text-xs font-semibold uppercase tracking-wider text-secondary">Staff</p>
    <h1 class="mt-1 text-3xl font-semibold tracking-tight text-on-surface">Edit {{ $staff->name }}</h1>
</div>
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
        <div>
            <label for="password" class="mb-1.5 block text-sm font-medium text-on-surface">Password Baru <span class="text-xs font-normal text-on-surface-variant">(kosongkan jika tidak diubah)</span></label>
            <input type="password" name="password" id="password" minlength="8" autocomplete="new-password" class="block w-full rounded-lg border border-outline bg-surface-container-lowest px-3.5 py-2.5 text-sm text-on-surface focus:border-primary focus:ring-2 focus:ring-primary/30 focus:outline-none @error('password') border-error @enderror">
            @error('password') <p class="mt-1.5 text-xs text-error">{{ $message }}</p> @enderror
        </div>
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